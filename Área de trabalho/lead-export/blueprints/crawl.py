import uuid
import logging
import threading
from concurrent.futures import ThreadPoolExecutor, as_completed
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

logger = logging.getLogger("leadhunter.crawl")

bp = Blueprint("crawl", __name__)

active_jobs = {}


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

    if not query:
        return jsonify({"error": "Informe a palavra-chave"}), 400
    if source not in ("website", "maps", "both"):
        return jsonify({"error": "Fonte inválida"}), 400

    job_uuid = str(uuid.uuid4())
    job_id = CrawlJob.create(job_uuid, query, location, source, quantity)

    active_jobs[job_uuid] = {
        "status": "running",
        "progress": 0,
        "total": quantity,
        "sources": {},
    }

    thread = threading.Thread(
        target=run_crawl,
        args=(job_uuid, job_id, source, query, quantity, location),
        daemon=True,
    )
    thread.start()

    return jsonify({"job_id": job_uuid})


def run_crawl(job_uuid, job_id, source, query, quantity, location, campaign_name=None):
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

        with ThreadPoolExecutor(max_workers=4) as executor:
            futures = {executor.submit(fn): name for name, fn in crawl_sources.items()}

            for future in as_completed(futures, timeout=180):
                name = futures[future]
                try:
                    results = future.result(timeout=90)
                    leads.extend(results)
                    active_jobs[job_uuid]["sources"][name] = len(results)
                    logger.info(f"[crawl] {name}: {len(results)} leads para '{query}'")
                except Exception as e:
                    active_jobs[job_uuid]["sources"][name] = 0
                    logger.warning(f"[crawl] {name} falhou para '{query}': {e}")

        saved_count = 0
        saved_ids = []
        seen = set()

        for lead in leads:
            if saved_count >= quantity:
                break

            validated = validate_lead(lead)
            if not validated:
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
                    conn.execute("UPDATE leads SET campaign = ? WHERE id = ? AND (campaign = '' OR campaign IS NULL)", (campaign_name, lead_id))
                    conn.commit()
                    conn.close()
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


@bp.route("/api/keywords/suggestions")
def keyword_suggestions():
    suggestions = Keyword.get_suggestions(limit=10)
    return jsonify({"keywords": suggestions})
