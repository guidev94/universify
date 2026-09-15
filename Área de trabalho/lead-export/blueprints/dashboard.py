import time
import logging
from flask import Blueprint, render_template, jsonify, request
from models import Lead, CrawlJob, Campaign
from database import get_connection

logger = logging.getLogger("leadhunter.dashboard")

bp = Blueprint("dashboard", __name__)


@bp.route("/")
@bp.route("/dashboard")
def index():
    return render_template("dashboard.html")


@bp.route("/api/dashboard/stats")
def stats():
    try:
        data = Lead.get_stats()
        
        from scheduler import scheduler
        if scheduler.pipeline:
            data["pipeline"] = scheduler.pipeline.status
        
        with get_connection() as conn:
            row = conn.execute("""
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as success
                FROM (SELECT status FROM crawl_jobs ORDER BY started_at DESC LIMIT 20)
            """).fetchone()
            
            if row and row["total"] > 0:
                data["job_success_rate"] = round((row["success"] / row["total"]) * 100, 1)
            else:
                data["job_success_rate"] = 0
            
        return jsonify(data)
    except Exception as e:
        logger.error(f"Dashboard stats error: {e}")
        return jsonify({"error": "Internal server error"}), 500


@bp.route("/api/dashboard/chart-data")
def chart_data():
    data = Lead.get_chart_data()
    return jsonify(data)


@bp.route("/api/dashboard/recent-jobs")
def recent_jobs():
    jobs, _ = CrawlJob.get_history(page=1, per_page=5)
    return jsonify({"jobs": jobs})


@bp.route("/api/crawl/jobs/<int:job_id>", methods=["DELETE"])
def delete_job(job_id):
    conn = get_connection()
    conn.execute("UPDATE leads SET crawl_job_id = NULL WHERE crawl_job_id = ?", (job_id,))
    conn.execute("DELETE FROM crawl_jobs WHERE id = ?", (job_id,))
    conn.commit()
    conn.close()
    return jsonify({"ok": True})


@bp.route("/logs")
def logs_page():
    return render_template("logs.html")


@bp.route("/api/logs")
def api_logs():
    import json as _json
    page = request.args.get("page", 1, type=int)
    per_page = 20
    offset = (page - 1) * per_page

    with get_connection() as conn:
        total = conn.execute("SELECT COUNT(*) as c FROM crawl_jobs").fetchone()["c"]
        jobs = conn.execute(
            "SELECT * FROM crawl_jobs ORDER BY started_at DESC LIMIT ? OFFSET ?",
            (per_page, offset),
        ).fetchall()

        total_leads = conn.execute("SELECT COUNT(*) as c FROM leads").fetchone()["c"]
        today = conn.execute("SELECT COUNT(*) as c FROM leads WHERE date(created_at) = date('now', 'localtime')").fetchone()["c"]
        campaigns_count = conn.execute("SELECT COUNT(*) as c FROM campaigns WHERE status = 'active'").fetchone()["c"]
        completed_jobs = conn.execute("SELECT COUNT(*) as c FROM crawl_jobs WHERE status = 'completed'").fetchone()["c"]

    enriched_jobs = []
    # ... resto do loop ...
    for job in jobs:
        j = dict(job)
        if j.get("job_state"):
            try:
                state = _json.loads(j["job_state"])
                j["sources_detail"] = state.get("sources", {})
            except (ValueError, TypeError):
                j["sources_detail"] = {}
        else:
            j["sources_detail"] = {}

        if j.get("started_at") and j.get("completed_at"):
            from datetime import datetime
            try:
                start = datetime.strptime(j["started_at"], "%Y-%m-%d %H:%M:%S")
                end = datetime.strptime(j["completed_at"], "%Y-%m-%d %H:%M:%S")
                diff = (end - start).total_seconds()
                j["duration_seconds"] = int(diff)
            except (ValueError, TypeError):
                j["duration_seconds"] = None
        else:
            j["duration_seconds"] = None

        if j["quantity_requested"] and j["quantity_requested"] > 0:
            j["success_pct"] = round((j["quantity_found"] or 0) / j["quantity_requested"] * 100)
        else:
            j["success_pct"] = 0

        enriched_jobs.append(j)

    success_rate = round((completed_jobs / max(1, total)) * 100, 1)

    from scheduler import scheduler
    pipeline_status = scheduler.pipeline.status if scheduler.pipeline else {}

    return jsonify({
        "jobs": enriched_jobs,
        "total": total,
        "page": page,
        "stats": {
            "total_leads": total_leads,
            "total_jobs": total,
            "today": today,
            "campaigns": campaigns_count,
            "success_rate": success_rate,
            "pipeline": pipeline_status,
        }
    })


@bp.route("/pipeline")
def pipeline_page():
    return render_template("pipeline.html")


@bp.route("/campaigns")
def campaigns_page():
    return render_template("campaigns.html")


@bp.route("/api/campaigns")
def list_campaigns():
    campaigns = Campaign.get_all()
    if not campaigns:
        return jsonify({"campaigns": []})

    conn = get_connection()
    try:
        # OTIMIZAÇÃO: Busca estatísticas de todas as campanhas em uma única query
        # Em vez de 1 SELECT por campanha, fazemos 1 para todos
        names = [c["name"] for c in campaigns]
        placeholders = ",".join("?" * len(names))
        
        stats_rows = conn.execute(f"""
            SELECT 
                campaign,
                COUNT(*) as leads_count,
                SUM(CASE WHEN status = 'closed_won' THEN 1 ELSE 0 END) as won_count
            FROM leads 
            WHERE campaign IN ({placeholders})
            GROUP BY campaign
        """, names).fetchall()
        
        stats_map = {row["campaign"]: (row["leads_count"], row["won_count"]) for row in stats_rows}
        
        for c in campaigns:
            # Garante que goal seja sempre um número
            try:
                c["goal"] = int(c["goal"]) if c.get("goal") else 100
            except (ValueError, TypeError):
                c["goal"] = 100
            
            l_count, w_count = stats_map.get(c["name"], (0, 0))
            c["leads_count"] = l_count
            c["won_count"] = w_count
            
        return jsonify({"campaigns": campaigns})
    finally:
        conn.close()



@bp.route("/api/campaigns", methods=["POST"])
def create_campaign():
    data = request.get_json() or {}
    name = data.get("name", "").strip()
    if not name:
        return jsonify({"error": "Nome obrigatório"}), 400
    
    keyword = data.get("keyword", "")
    location = data.get("location", "")
    expand_auto = data.get("expand_auto", False)
    
    Campaign.create(name, keyword, location, int(data.get("goal", 100)), expand_auto=expand_auto)
    return jsonify({"ok": True})


@bp.route("/api/campaigns/<int:camp_id>", methods=["DELETE"])
def delete_campaign(camp_id):
    conn = get_connection()
    camp = conn.execute("SELECT name FROM campaigns WHERE id = ?", (camp_id,)).fetchone()
    if camp:
        conn.execute("UPDATE leads SET campaign = '' WHERE campaign = ?", (camp["name"],))
        conn.execute("DELETE FROM campaigns WHERE id = ?", (camp_id,))
        conn.commit()
    conn.close()
    return jsonify({"ok": True})


@bp.route("/api/campaigns/<int:camp_id>", methods=["PATCH"])
def update_campaign(camp_id):
    data = request.get_json() or {}

    name = data.get("name", "").strip() if "name" in data else None
    keyword = data.get("keyword", "").strip() if "keyword" in data else None
    location = data.get("location", "").strip() if "location" in data else None
    goal = int(data["goal"]) if "goal" in data and data["goal"] else None

    if name is not None and not name:
        return jsonify({"error": "Nome não pode ser vazio"}), 400
    if goal is not None and goal < 1:
        return jsonify({"error": "Meta deve ser pelo menos 1"}), 400

    if name:
        conn = get_connection()
        old = conn.execute("SELECT name FROM campaigns WHERE id = ?", (camp_id,)).fetchone()
        if old and old["name"] != name:
            conn.execute("UPDATE leads SET campaign = ? WHERE campaign = ?", (name, old["name"]))
            conn.commit()
        conn.close()

    success = Campaign.update(camp_id, name=name, keyword=keyword, location=location, goal=goal)
    if not success:
        return jsonify({"error": "Nenhuma alteração"}), 400
    return jsonify({"ok": True})


@bp.route("/api/campaigns/<int:camp_id>/leads")
def campaign_leads(camp_id):
    conn = get_connection()
    camp = conn.execute("SELECT name FROM campaigns WHERE id = ?", (camp_id,)).fetchone()
    if not camp:
        conn.close()
        return jsonify({"error": "Campanha não encontrada"}), 404

    page = request.args.get("page", 1, type=int)
    per_page = 20
    offset = (page - 1) * per_page

    total = conn.execute("SELECT COUNT(*) as c FROM leads WHERE campaign = ?", (camp["name"],)).fetchone()["c"]
    rows = conn.execute(
        "SELECT * FROM leads WHERE campaign = ? ORDER BY created_at DESC LIMIT ? OFFSET ?",
        (camp["name"], per_page, offset),
    ).fetchall()
    conn.close()

    return jsonify({"leads": [dict(r) for r in rows], "total": total, "page": page, "pages": (total + per_page - 1) // per_page})


# Fila dedicada para campanhas para gerenciar concorrência sem travar
from concurrent.futures import ThreadPoolExecutor
campaign_executor = ThreadPoolExecutor(max_workers=5, thread_name_prefix="campaign-worker")

@bp.route("/api/campaigns/<int:camp_id>/extract", methods=["POST"])
def extract_campaign(camp_id):
    import uuid
    from blueprints.crawl import run_crawl
    from models import CrawlJob, Campaign
    from job_manager import job_manager

    # Responde rápido
    job_uuid = str(uuid.uuid4())
    
    conn = get_connection()
    camp = conn.execute("SELECT * FROM campaigns WHERE id = ?", (camp_id,)).fetchone()
    conn.close()

    if not camp:
        return jsonify({"error": "Campanha não encontrada"}), 404

    camp_dict = dict(camp)
    
    # Se já está rodando, não inicia outra
    if camp_dict.get("extraction_status") == "running":
        return jsonify({"error": "Esta campanha já está sendo processada"}), 400

    # Marca como rodando imediatamente
    Campaign.update_extraction_status(camp_id, "running")
    job_manager.register(job_uuid, camp_dict["goal"])

    def background_extract(job_uuid, camp_id, camp_name, camp_goal, keyword, location):
        try:
            # 1. Atribuição heurística rápida
            conn_bg = get_connection()
            current_count = conn_bg.execute("SELECT COUNT(*) as c FROM leads WHERE campaign = ?", (camp_name,)).fetchone()["c"]
            remaining = camp_goal - current_count
            
            if remaining > 0:
                search_kw = keyword.split('|')[0].strip() if '|' in keyword else keyword
                conn_bg.execute("""
                    UPDATE leads SET campaign = ? 
                    WHERE id IN (
                        SELECT id FROM leads 
                        WHERE (campaign = '' OR campaign IS NULL) 
                        AND (category LIKE ? OR name LIKE ?) 
                        LIMIT ?
                    )
                """, (camp_name, f"%{search_kw}%", f"%{search_kw}%", remaining))
                conn_bg.commit()
            
            # Recalcula após atribuição
            current_count = conn_bg.execute("SELECT COUNT(*) as c FROM leads WHERE campaign = ?", (camp_name,)).fetchone()["c"]
            conn_bg.close()
            
            if current_count >= camp_goal:
                Campaign.update_extraction_status(camp_id, "idle")
                job_manager.complete(job_uuid, current_count)
                return

            # 2. Extração em massa
            from crawler.utils import expand_local_keywords
            queries = expand_local_keywords(keyword, location)
            
            current_total = current_count
            for q in queries:
                # Check status safe
                conn_chk = get_connection()
                status = conn_chk.execute("SELECT extraction_status FROM campaigns WHERE id = ?", (camp_id,)).fetchone()
                conn_chk.close()
                if not status or status["extraction_status"] != "running": break
                
                left = camp_goal - current_total
                if left <= 5: break
                
                batch_uuid = str(uuid.uuid4())
                batch_id = CrawlJob.create(batch_uuid, q, location, "both", min(40, left))
                found = run_crawl(batch_uuid, batch_id, "both", q, min(40, left), location, campaign_name=camp_name, require_quality=True)
                current_total += (found or 0)
                job_manager.update(job_uuid, progress=current_total)

            Campaign.update_extraction_status(camp_id, "idle")
            job_manager.complete(job_uuid, current_total)

        except Exception as e:
            logger.error(f"Background extraction failed: {e}")
            Campaign.update_extraction_status(camp_id, "error")

    # Envia para a fila gerenciada
    campaign_executor.submit(background_extract, job_uuid, camp_id, camp_dict["name"], camp_dict["goal"], camp_dict["keyword"], camp_dict.get("location", ""))

    return jsonify({"ok": True, "job_id": job_uuid})




@bp.route("/api/campaigns/<int:camp_id>/cancel", methods=["POST"])
def cancel_campaign_extraction(camp_id):
    from models import Campaign
    Campaign.update_extraction_status(camp_id, "idle")
    return jsonify({"ok": True})


@bp.route("/api/dashboard/activity")
def dashboard_activity():
    from job_manager import job_manager
    conn = get_connection()
    try:
        # OTIMIZAÇÃO: Union de novas atividades, novos leads e jobs rodando para reduzir requests ao SQLite
        # Usamos subqueries para garantir o LIMIT correto em cada parte
        new_leads = conn.execute("""
            SELECT 'lead' as type, id, name, tier, source, created_at as at, NULL as action, NULL as details, NULL as found, NULL as total, NULL as uuid
            FROM leads ORDER BY created_at DESC LIMIT 10
        """).fetchall()
        
        activities = conn.execute("""
            SELECT 'activity' as type, l.id, l.name, NULL as tier, NULL as source, a.created_at as at, a.action, a.details, NULL as found, NULL as total, NULL as uuid
            FROM lead_activities a 
            JOIN leads l ON l.id = a.lead_id 
            ORDER BY a.created_at DESC LIMIT 5
        """).fetchall()
        
        running_jobs = conn.execute("""
            SELECT 'job' as type, NULL as id, keyword as name, NULL as tier, NULL as source, started_at as at, NULL as action, NULL as details, quantity_found as found, quantity_requested as total, job_uuid as uuid
            FROM crawl_jobs WHERE status = 'running' ORDER BY started_at DESC LIMIT 5
        """).fetchall()
        
        feed = [dict(r) for r in (new_leads + activities + running_jobs)]
        
        # Enriquecimento com dados em memória dos jobs
        for item in feed:
            if item["type"] == "job":
                state = job_manager.get(item["uuid"])
                if state:
                    item["found"] = state.get("progress", 0)

        feed.sort(key=lambda x: x.get("at") or "", reverse=True)
        return jsonify({"feed": feed[:15]})
    finally:
        conn.close()



@bp.route("/api/dashboard/top-qualified")
def top_qualified():
    conn = get_connection()
    # Pega leads qualificados manualmente ou com score muito alto que anunciam e tem site ruim
    rows = conn.execute("""
        SELECT id, name, score, website, category, site_quality, has_ads, tier 
        FROM leads 
        WHERE (is_qualified = 1 OR (score >= 85 AND has_ads = 1))
        AND status = 'new'
        ORDER BY score DESC LIMIT 5
    """).fetchall()
    conn.close()
    return jsonify({"leads": [dict(r) for r in rows]})


@bp.route("/status")
def status_page():
    return render_template("status.html")
