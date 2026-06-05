import uuid
import time
import logging
import threading
from concurrent.futures import ThreadPoolExecutor, as_completed, TimeoutError
from flask import Blueprint, render_template, request, jsonify
from models import Lead, CrawlJob, Keyword
from crawler.website_crawler import WebsiteCrawler
from crawler.maps_crawler import MapsCrawler
from crawler.bing_crawler import BingCrawler
from crawler.directory_crawler import DirectoryCrawler
from crawler.niche_crawler import NicheCrawler
from crawler.duckduckgo_crawler import DuckDuckGoCrawler
from crawler.yellowpages_crawler import YellowPagesCrawler
from crawler.validator import validate_lead
from crawler.lead_scorer import calculate_score
from crawler.site_verifier import SiteVerifier
from config import Config
from database import get_connection

logger = logging.getLogger("leadhunter.crawl")

bp = Blueprint("crawl", __name__)

active_jobs = {}
MAX_JOB_DURATION = 180


@bp.route("/crawl")
def page():
    return render_template("crawl.html")


@bp.route("/api/crawl", methods=["POST"])
def start_crawl():
    data = request.get_json()
    if not data:
        return jsonify({"error": "Dados inválidos"}), 400

    source = data.get("source", "both")
    query = data.get("query", "").strip()
    quantity = min(int(data.get("quantity", 25)), Config.MAX_LEADS_PER_CRAWL)
    location = data.get("location", "").strip()

    if '|' in query:
        query = query.split('|')[0].strip()
    if '|' in location:
        location = location.split('|')[0].strip()

    if not query:
        return jsonify({"error": "Informe a palavra-chave"}), 400
    if source not in ("website", "maps", "both"):
        return jsonify({"error": "Fonte inválida"}), 400

    conn_dup = get_connection()
    already_running = conn_dup.execute(
        "SELECT COUNT(*) as c FROM crawl_jobs WHERE keyword = ? AND status = 'running'", (query,)
    ).fetchone()["c"]
    conn_dup.close()
    if already_running > 0:
        return jsonify({"error": f"Já existe uma extração rodando para '{query}'"}), 409

    job_uuid = str(uuid.uuid4())
    job_id = CrawlJob.create(job_uuid, query, location, source, quantity)

    active_jobs[job_uuid] = {
        "status": "running",
        "progress": 0,
        "total": quantity,
        "sources": {},
        "started_at": time.time(),
    }

    thread = threading.Thread(
        target=run_crawl,
        args=(job_uuid, job_id, source, query, quantity, location),
        daemon=True,
    )
    thread.start()

    return jsonify({"job_id": job_uuid})


def run_crawl(job_uuid, job_id, source, query, quantity, location, campaign_name=None, require_quality=False):
    saved_ids = []
    try:
        CrawlJob.set_running(job_uuid)
        leads = []
        progress_lock = threading.Lock()

        if '|' in query:
            query = query.split('|')[0].strip()
        if '|' in location:
            location = location.split('|')[0].strip()

        def on_progress(count):
            with progress_lock:
                active_jobs[job_uuid]["progress"] = count
            CrawlJob.update_progress(job_uuid, count)

        crawl_sources = {}
        fetch_qty = quantity * 2

        if source in ("website", "both"):
            crawl_sources["duckduckgo"] = lambda: DuckDuckGoCrawler().crawl(query, fetch_qty)
            crawl_sources["website"] = lambda: WebsiteCrawler().crawl(query, fetch_qty, on_progress=on_progress)

        if source in ("maps", "both"):
            crawl_sources["maps"] = lambda: MapsCrawler().crawl(query, location, fetch_qty, on_progress=on_progress)

        crawl_sources["bing"] = lambda: BingCrawler().crawl(query, fetch_qty, on_progress=on_progress)
        crawl_sources["directory"] = lambda: DirectoryCrawler().crawl(query, location, fetch_qty, on_progress=on_progress)
        crawl_sources["niche"] = lambda: NicheCrawler().crawl(query, location, fetch_qty, on_progress=on_progress)
        crawl_sources["yellowpages"] = lambda: YellowPagesCrawler().crawl(query, location, fetch_qty, on_progress=on_progress)

        job_start = time.time()

        with ThreadPoolExecutor(max_workers=4) as executor:
            futures = {executor.submit(fn): name for name, fn in crawl_sources.items()}

            try:
                for future in as_completed(futures, timeout=90):
                    if time.time() - job_start > MAX_JOB_DURATION:
                        logger.warning(f"[crawl] timeout global atingido para '{query}'")
                        break
                    name = futures[future]
                    try:
                        results = future.result(timeout=45)
                        leads.extend(results)
                        active_jobs[job_uuid]["sources"][name] = len(results)
                        logger.info(f"[crawl] {name}: {len(results)} leads para '{query}'")
                    except Exception as e:
                        active_jobs[job_uuid]["sources"][name] = 0
                        logger.warning(f"[crawl] {name} falhou para '{query}': {e}")
            except TimeoutError:
                logger.warning(f"[crawl] executor timeout para '{query}' — continuando com leads parciais")

            for future in futures:
                future.cancel()

        saved_count = 0
        saved_ids = []
        seen = set()

        for lead in leads:
            if saved_count >= quantity:
                break

            validated = validate_lead(lead)
            if not validated:
                continue

            if require_quality:
                has_email = bool(validated.get("email", "").strip())
                has_website = bool(validated.get("website", "").strip())
                if not has_email and not has_website:
                    continue

            key = (validated.get("email", ""), validated.get("phone", ""))
            if key != ("", "") and key in seen:
                continue
            seen.add(key)

            score, tier = calculate_score(validated)
            validated["score"] = score
            validated["tier"] = tier
            if campaign_name:
                validated["campaign"] = campaign_name

            lead_id = Lead.create(validated, crawl_job_id=job_id)
            if lead_id:
                saved_ids.append({"id": lead_id, "website": validated.get("website", "")})
                if campaign_name:
                    from database import get_connection as gc
                    conn = gc()
                    current_camp = conn.execute("SELECT campaign FROM leads WHERE id = ?", (lead_id,)).fetchone()
                    current_val = current_camp["campaign"] if current_camp else ""
                    if current_val != campaign_name:
                        conn.execute("UPDATE leads SET campaign = ? WHERE id = ?", (campaign_name, lead_id))
                        conn.commit()
                        saved_count += 1
                    conn.close()
                else:
                    saved_count += 1

        CrawlJob.complete(job_uuid, saved_count)
        Keyword.track(query, location, saved_count)

        active_jobs[job_uuid]["status"] = "completed"
        active_jobs[job_uuid]["progress"] = saved_count
        active_jobs[job_uuid]["total_found"] = len(leads)

        if saved_count == 0 and len(leads) > 0:
            logger.info(f"[crawl] '{query}' — {len(leads)} brutos mas 0 novos (todos duplicados)")
        elif saved_count == 0:
            logger.warning(f"[crawl] '{query}' — nenhum lead encontrado nas fontes")
        else:
            logger.info(f"[crawl] '{query}' concluído — {saved_count} leads salvos de {len(leads)} brutos")

    except Exception as e:
        CrawlJob.fail(job_uuid, str(e))
        active_jobs[job_uuid]["status"] = "error"
        logger.error(f"[crawl] erro fatal para '{query}': {e}")

    try:
        if saved_ids:
            verifier = SiteVerifier(max_workers=Config.MAX_CONCURRENT_REQUESTS)
            verifier.verify_leads(saved_ids)
    except Exception as e:
        logger.warning(f"[crawl] verificação de sites falhou para '{query}': {e}")


@bp.route("/api/crawl/status/<job_uuid>")
def crawl_status(job_uuid):
    if job_uuid in active_jobs:
        info = active_jobs[job_uuid]
        if info.get("started_at") and time.time() - info["started_at"] > 600:
            del active_jobs[job_uuid]
        else:
            return jsonify({
                "status": info["status"],
                "progress": info["progress"],
                "total": info["total"],
                "sources": info.get("sources", {}),
                "total_found": info.get("total_found", 0),
            })

    job = CrawlJob.get_by_uuid(job_uuid)
    if not job:
        return jsonify({"error": "Job não encontrado"}), 404

    return jsonify({
        "status": job["status"],
        "progress": job["progress"],
        "total": job["quantity_requested"],
    })


@bp.route("/api/crawl/history")
def crawl_history():
    page = request.args.get("page", 1, type=int)
    jobs, total = CrawlJob.get_history(page=page, per_page=10)
    return jsonify({"jobs": jobs, "total": total})


@bp.route("/api/crawl/cancel/<job_uuid>", methods=["POST"])
def cancel_crawl(job_uuid):
    if job_uuid in active_jobs:
        active_jobs[job_uuid]["status"] = "cancelled"
    return jsonify({"ok": True})


@bp.route("/api/crawl/abort/<job_uuid>", methods=["POST"])
def abort_crawl(job_uuid):
    CrawlJob.fail(job_uuid, "Abortado manualmente")
    if job_uuid in active_jobs:
        del active_jobs[job_uuid]
    return jsonify({"ok": True})


@bp.route("/api/crawl/retry/<int:job_id>", methods=["POST"])
def retry_crawl(job_id):
    conn = get_connection()
    job = conn.execute("SELECT * FROM crawl_jobs WHERE id = ?", (job_id,)).fetchone()
    conn.close()
    if not job:
        return jsonify({"error": "Job não encontrado"}), 404

    job = dict(job)
    new_uuid = str(uuid.uuid4())
    new_id = CrawlJob.create(new_uuid, job["keyword"], job["location"], job["source"], job["quantity_requested"])

    active_jobs[new_uuid] = {
        "status": "running",
        "progress": 0,
        "total": job["quantity_requested"],
        "sources": {},
        "started_at": time.time(),
    }

    thread = threading.Thread(
        target=run_crawl,
        args=(new_uuid, new_id, job["source"], job["keyword"], job["quantity_requested"], job["location"]),
        daemon=True,
    )
    thread.start()

    return jsonify({"job_id": new_uuid, "new_job_id": new_id, "ok": True})


@bp.route("/api/crawl/cleanup", methods=["POST"])
def cleanup_jobs():
    conn = get_connection()
    conn.execute(
        "UPDATE crawl_jobs SET status = 'error', completed_at = datetime('now','localtime') "
        "WHERE status = 'running' AND started_at < datetime('now', '-2 minutes', 'localtime')"
    )
    cleaned = conn.execute("SELECT changes() as c").fetchone()["c"]
    conn.commit()
    conn.close()

    stale = [k for k, v in active_jobs.items() if v.get("started_at") and time.time() - v["started_at"] > 300]
    for k in stale:
        del active_jobs[k]

    return jsonify({"ok": True, "cleaned": cleaned + len(stale)})


@bp.route("/api/keywords/suggestions")
def keyword_suggestions():
    suggestions = Keyword.get_suggestions(limit=10)
    return jsonify({"keywords": suggestions})
