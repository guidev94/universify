import uuid
import threading
from flask import Blueprint, render_template, request, jsonify
from models import Lead, CrawlJob, Keyword
from crawler.website_crawler import WebsiteCrawler
from crawler.maps_crawler import MapsCrawler
from crawler.validator import validate_lead
from crawler.lead_scorer import calculate_score
from crawler.site_verifier import SiteVerifier
from config import Config

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

    active_jobs[job_uuid] = {"status": "running", "progress": 0, "total": quantity}

    thread = threading.Thread(
        target=run_crawl,
        args=(job_uuid, job_id, source, query, quantity, location),
        daemon=True,
    )
    thread.start()

    return jsonify({"job_id": job_uuid})


def run_crawl(job_uuid, job_id, source, query, quantity, location):
    try:
        CrawlJob.set_running(job_uuid)
        leads = []

        def on_progress(count):
            active_jobs[job_uuid]["progress"] = count
            CrawlJob.update_progress(job_uuid, count)

        if source in ("website", "both"):
            crawler = WebsiteCrawler()
            site_leads = crawler.crawl(query, quantity, on_progress=on_progress)
            leads.extend(site_leads)

        if source in ("maps", "both"):
            maps_crawler = MapsCrawler()
            maps_leads = maps_crawler.crawl(query, location, quantity, on_progress=on_progress)
            leads.extend(maps_leads)

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

            lead_id = Lead.create(validated, crawl_job_id=job_id)
            if lead_id:
                saved_ids.append({"id": lead_id, "website": validated.get("website", "")})
                saved_count += 1

        CrawlJob.complete(job_uuid, saved_count)
        Keyword.track(query, location, saved_count)

        active_jobs[job_uuid]["status"] = "completed"
        active_jobs[job_uuid]["progress"] = saved_count

        if saved_ids:
            verifier = SiteVerifier(max_workers=Config.MAX_CONCURRENT_REQUESTS)
            verifier.verify_leads(saved_ids)

    except Exception as e:
        CrawlJob.fail(job_uuid, str(e))
        active_jobs[job_uuid]["status"] = "error"


@bp.route("/api/crawl/status/<job_uuid>")
def crawl_status(job_uuid):
    if job_uuid in active_jobs:
        info = active_jobs[job_uuid]
        return jsonify({
            "status": info["status"],
            "progress": info["progress"],
            "total": info["total"],
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
