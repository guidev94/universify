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
from config import Config
from database import get_connection
from job_manager import job_manager

logger = logging.getLogger("leadhunter.crawl")

bp = Blueprint("crawl", __name__)

MAX_JOB_DURATION = 600


def _get_event_bus():
    try:
        from blueprints.events import event_bus
        return event_bus
    except ImportError:
        return None


def _get_pipeline():
    try:
        from flask import current_app
        return current_app.config.get("PIPELINE")
    except RuntimeError:
        return None


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
    expand = data.get("expand", False)
    metropolitan = data.get("metropolitan", False)

    if not query:
        return jsonify({"error": "Informe a palavra-chave"}), 400
    if source not in ("website", "maps", "both"):
        return jsonify({"error": "Fonte inválida"}), 400

    queries_to_run = [query]
    if expand:
        from crawler.utils import expand_local_keywords
        queries_to_run = expand_local_keywords(query, location)
        
    if metropolitan:
        from crawler.utils import expand_metropolitan_search
        metro_queries = expand_metropolitan_search(query, location)
        # Combine unique queries
        queries_to_run = list(dict.fromkeys(queries_to_run + metro_queries))

    # Safety limit for total parallel jobs from a single request
    # Reduced from 15 to 8 to avoid total search engine block
    queries_to_run = queries_to_run[:8] 

    job_uuids = []
    
    # Check if there are already too many jobs running globally
    conn_global = get_connection()
    running_total = conn_global.execute("SELECT COUNT(*) as c FROM crawl_jobs WHERE status = 'running'").fetchone()["c"]
    conn_global.close()
    
    if running_total > 20:
        return jsonify({"error": "Muitas extrações rodando no servidor. Aguarde alguns minutos."}), 429
    
    for q in queries_to_run:
        q_clean = q
        if '|' in q_clean:
            q_clean = q_clean.split('|')[0].strip()
            
        # We don't check for running jobs here to allow massive parallel extraction
        # SQLite WAL mode handles the concurrency
        
        job_uuid = str(uuid.uuid4())
        job_id = CrawlJob.create(job_uuid, q_clean, location, source, quantity)
        job_manager.register(job_uuid, quantity)

        # Threading for non-blocking UI
        thread = threading.Thread(
            target=run_crawl,
            args=(job_uuid, job_id, source, q_clean, quantity, location),
            daemon=True,
        )
        thread.start()
        job_uuids.append(job_uuid)

    return jsonify({"job_id": job_uuids[0], "job_ids": job_uuids, "count": len(job_uuids)})


def run_crawl(job_uuid, job_id, source, query, quantity, location, campaign_name=None, require_quality=False):
    saved_ids = []
    event_bus = _get_event_bus()

    try:
        CrawlJob.set_running(job_uuid)
        leads = []

        if '|' in query:
            query = query.split('|')[0].strip()
        if '|' in location:
            location = location.split('|')[0].strip()

        def on_progress(count):
            job_manager.update(job_uuid, progress=count)
            CrawlJob.update_progress(job_uuid, count)
            if event_bus:
                state = job_manager.get(job_uuid)
                event_bus.publish("job_progress", {
                    "uuid": job_uuid, "progress": count,
                    "total": quantity, "sources": state.get("sources", {}),
                })

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
                        logger.warning(f"[crawl] global timeout for '{query}'")
                        break
                    name = futures[future]
                    try:
                        results = future.result(timeout=45)
                        leads.extend(results)
                        current_state = job_manager.get(job_uuid) or {}
                        sources = current_state.get("sources", {})
                        sources[name] = len(results)
                        job_manager.update(job_uuid, sources=sources)
                        logger.info(f"[crawl] {name}: {len(results)} leads for '{query}'")
                    except Exception as e:
                        logger.warning(f"[crawl] {name} failed for '{query}': {e}")
            except TimeoutError:
                logger.warning(f"[crawl] executor timeout for '{query}' — continuing with partial leads")

            for future in futures:
                future.cancel()

        # Dedup leads by website/phone before processing
        unique_leads = []
        seen_leads = set()
        for l in leads:
            l_key = (l.get("website", "").strip(), l.get("phone", "").strip())
            if l_key not in seen_leads:
                seen_leads.add(l_key)
                unique_leads.append(l)

        saved_count = 0
        saved_ids = []
        seen_validated = set()
        
        conn = get_connection()
        try:
            for lead in unique_leads:
                if saved_count >= quantity:
                    break

                validated = validate_lead(lead)
                if not validated:
                    continue

                if require_quality:
                    has_email = bool(validated.get("email", "").strip())
                    has_website = bool(validated.get("website", "").strip())
                    has_phone = bool(validated.get("phone", "").strip())
                    # Allow if has email OR website OR phone
                    if not (has_email or has_website or has_phone):
                        continue

                v_email = validated.get("email", "").strip()
                v_phone = validated.get("phone", "").strip()
                v_key = (v_email, v_phone)
                
                if v_key != ("", "") and v_key in seen_validated:
                    continue
                seen_validated.add(v_key)

                score, tier = calculate_score(validated)
                validated["score"] = score
                validated["tier"] = tier
                if campaign_name:
                    validated["campaign"] = campaign_name
                if location:
                    validated["location"] = location

                lead_id = Lead.create(validated, crawl_job_id=job_id, conn=conn)
                if lead_id:
                    saved_ids.append({"id": lead_id, "website": validated.get("website", "")})
                    saved_count += 1

                    if event_bus:
                        event_bus.publish("lead_new", {
                            "id": lead_id, "name": validated.get("name", ""),
                            "tier": tier, "score": score,
                        })
            conn.commit()
        finally:
            conn.close()

        CrawlJob.complete(job_uuid, saved_count)

        job_manager.complete(job_uuid, saved_count)
        Keyword.track(query, location, saved_count)

        if event_bus:
            event_bus.publish("job_completed", {"uuid": job_uuid, "keyword": query, "found": saved_count})
            if saved_count > 0:
                from models import Lead as _L
                stats = _L.get_stats()
                conn_today = get_connection()
                today_count = conn_today.execute("SELECT COUNT(*) as c FROM leads WHERE date(created_at) = date('now', 'localtime')").fetchone()["c"]
                conn_today.close()
                event_bus.publish("stats_updated", {"total": stats["total"], "hot": stats["hot"], "warm": stats["warm"], "cold": stats["cold"], "today": today_count})

        if saved_count == 0 and len(leads) > 0:
            logger.info(f"[crawl] '{query}' — {len(leads)} raw but 0 new (all duplicates)")
        elif saved_count == 0:
            logger.warning(f"[crawl] '{query}' — no leads found")
        else:
            logger.info(f"[crawl] '{query}' complete — {saved_count} leads saved from {len(leads)} raw")

    except Exception as e:
        CrawlJob.fail(job_uuid, str(e))
        job_manager.fail(job_uuid)
        if event_bus:
            event_bus.publish("job_error", {"uuid": job_uuid, "keyword": query, "error": str(e)})
        logger.error(f"[crawl] fatal error for '{query}': {e}")

    try:
        if saved_ids:
            from scheduler import scheduler
            if scheduler.pipeline:
                scheduler.pipeline.submit_for_verification(saved_ids)
            else:
                from crawler.site_verifier import SiteVerifier
                verifier = SiteVerifier(max_workers=Config.MAX_CONCURRENT_REQUESTS)
                verifier.verify_leads(saved_ids)
    except Exception as e:
        logger.warning(f"[crawl] site verification failed for '{query}': {e}")


@bp.route("/api/crawl/status/<job_uuid>")
def crawl_status(job_uuid):
    state = job_manager.get(job_uuid)
    if state:
        return jsonify({
            "status": state.get("status", "running"),
            "progress": state.get("progress", 0),
            "total": state.get("total", 0),
            "sources": state.get("sources", {}),
            "total_found": state.get("total_found", 0),
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
    job_manager.update(job_uuid, status="cancelled")
    return jsonify({"ok": True})


@bp.route("/api/crawl/abort/<job_uuid>", methods=["POST"])
def abort_crawl(job_uuid):
    CrawlJob.fail(job_uuid, "Abortado manualmente")
    job_manager.fail(job_uuid)
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

    job_manager.register(new_uuid, job["quantity_requested"])

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
        "WHERE status = 'running' AND started_at < datetime('now', '-5 minutes', 'localtime')"
    )
    cleaned = conn.execute("SELECT changes() as c").fetchone()["c"]
    conn.commit()
    conn.close()

    stale = job_manager.cleanup_stale(max_age=300)
    return jsonify({"ok": True, "cleaned": cleaned + len(stale)})


@bp.route("/api/crawl/jobs/clear-all", methods=["POST"])
def clear_all_logs():
    import time as _time
    conn = get_connection()
    count = conn.execute("SELECT COUNT(*) as c FROM crawl_jobs WHERE status != 'running'").fetchone()["c"]
    for attempt in range(5):
        try:
            conn.execute("DELETE FROM crawl_jobs WHERE status != 'running'")
            conn.commit()
            break
        except Exception:
            _time.sleep(0.5)
    conn.close()
    return jsonify({"ok": True, "deleted": count})


@bp.route("/api/keywords/suggestions")
def keyword_suggestions():
    suggestions = Keyword.get_suggestions(limit=10)
    return jsonify({"keywords": suggestions})
