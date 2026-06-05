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
    data = Lead.get_stats()
    return jsonify(data)


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
    page = request.args.get("page", 1, type=int)
    per_page = 20
    offset = (page - 1) * per_page

    conn = get_connection()
    total = conn.execute("SELECT COUNT(*) as c FROM crawl_jobs").fetchone()["c"]
    jobs = conn.execute(
        "SELECT * FROM crawl_jobs ORDER BY started_at DESC LIMIT ? OFFSET ?",
        (per_page, offset),
    ).fetchall()

    total_leads = conn.execute("SELECT COUNT(*) as c FROM leads").fetchone()["c"]
    today = conn.execute("SELECT COUNT(*) as c FROM leads WHERE date(created_at) = date('now', 'localtime')").fetchone()["c"]
    campaigns_count = conn.execute("SELECT COUNT(*) as c FROM campaigns WHERE status = 'active'").fetchone()["c"]
    conn.close()

    return jsonify({
        "jobs": [dict(r) for r in jobs],
        "total": total,
        "page": page,
        "stats": {
            "total_leads": total_leads,
            "total_jobs": total,
            "today": today,
            "campaigns": campaigns_count,
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
    for c in campaigns:
        conn = get_connection()
        c["leads_count"] = conn.execute("SELECT COUNT(*) as cnt FROM leads WHERE campaign = ?", (c["name"],)).fetchone()["cnt"]
        conn.close()
    return jsonify({"campaigns": campaigns})


@bp.route("/api/campaigns", methods=["POST"])
def create_campaign():
    data = request.get_json() or {}
    name = data.get("name", "").strip()
    if not name:
        return jsonify({"error": "Nome obrigatório"}), 400
    Campaign.create(name, data.get("keyword", ""), data.get("location", ""), int(data.get("goal", 100)))
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


@bp.route("/api/campaigns/<int:camp_id>/extract", methods=["POST"])
def extract_campaign(camp_id):
    import uuid, threading
    from blueprints.crawl import run_crawl
    from models import CrawlJob

    conn = get_connection()
    camp = conn.execute("SELECT * FROM campaigns WHERE id = ?", (camp_id,)).fetchone()
    conn.close()

    if not camp:
        return jsonify({"error": "Campanha não encontrada"}), 404

    camp = dict(camp)
    keyword = camp["keyword"]
    location = camp.get("location", "")

    conn2 = get_connection()
    current_count = conn2.execute(
        "SELECT COUNT(*) as c FROM leads WHERE campaign = ?", (camp["name"],)
    ).fetchone()["c"]

    remaining = camp["goal"] - current_count
    if remaining <= 0:
        conn2.close()
        return jsonify({"error": "Meta já atingida!", "leads_count": current_count, "goal": camp["goal"]}), 400

    search_kw = keyword.split('|')[0].strip() if '|' in keyword else keyword
    search = f"%{search_kw}%"
    unassigned = conn2.execute("""
        SELECT id FROM leads
        WHERE (campaign = '' OR campaign IS NULL)
        AND (category LIKE ? OR name LIKE ? OR website LIKE ?)
        AND (email != '' OR website != '')
        ORDER BY score DESC LIMIT ?
    """, (search, search, search, remaining)).fetchall()

    if len(unassigned) < remaining:
        extra = conn2.execute("""
            SELECT id FROM leads
            WHERE (campaign = '' OR campaign IS NULL)
            AND (email != '' AND website != '')
            AND id NOT IN (SELECT id FROM leads WHERE category LIKE ? OR name LIKE ? OR website LIKE ?)
            ORDER BY score DESC LIMIT ?
        """, (search, search, search, remaining - len(unassigned))).fetchall()
        unassigned = list(unassigned) + list(extra)

    assigned = 0
    for row in unassigned:
        conn2.execute("UPDATE leads SET campaign = ? WHERE id = ?", (camp["name"], row["id"]))
        assigned += 1
    if assigned > 0:
        conn2.commit()
    conn2.close()

    remaining -= assigned
    if remaining <= 0:
        return jsonify({"ok": True, "assigned": assigned, "leads_count": current_count + assigned, "goal": camp["goal"], "message": "Meta atingida com leads existentes!"})

    quantity = min(30, remaining)
    continuous = remaining > 30

    job_uuid = str(uuid.uuid4())
    from blueprints.crawl import active_jobs
    import time as _time
    job_id = CrawlJob.create(job_uuid, keyword, location, "both", quantity)
    active_jobs[job_uuid] = {"status": "running", "progress": 0, "total": remaining, "sources": {}, "started_at": _time.time()}

    def continuous_extract(first_uuid, first_id, camp_name, camp_goal, kw, loc):
        run_crawl(first_uuid, first_id, "both", kw, quantity, loc, campaign_name=camp_name, require_quality=True)

        if not continuous:
            return

        dry_rounds = 0
        max_batches = 15
        for batch in range(max_batches):
            _time.sleep(5)
            conn_check = get_connection()
            current = conn_check.execute("SELECT COUNT(*) as c FROM leads WHERE campaign = ?", (camp_name,)).fetchone()["c"]
            conn_check.close()

            left = camp_goal - current
            if left <= 0:
                active_jobs[first_uuid]["status"] = "completed"
                active_jobs[first_uuid]["progress"] = current
                break

            prev_count = current
            batch_qty = min(30, left)
            batch_uuid = str(uuid.uuid4())
            batch_id = CrawlJob.create(batch_uuid, kw, loc, "both", batch_qty)

            run_crawl(batch_uuid, batch_id, "both", kw, batch_qty, loc, campaign_name=camp_name, require_quality=True)

            conn_after = get_connection()
            after_count = conn_after.execute("SELECT COUNT(*) as c FROM leads WHERE campaign = ?", (camp_name,)).fetchone()["c"]
            conn_after.close()

            active_jobs[first_uuid]["progress"] = after_count

            if after_count <= prev_count:
                dry_rounds += 1
                if dry_rounds >= 2:
                    logger.info(f"[campaign] '{camp_name}' — 2 batches sem novos leads, parando extração contínua")
                    break
            else:
                dry_rounds = 0

            if first_uuid in active_jobs and active_jobs[first_uuid].get("status") == "cancelled":
                break

        active_jobs[first_uuid]["status"] = "completed"

    thread = threading.Thread(
        target=continuous_extract,
        args=(job_uuid, job_id, camp["name"], camp["goal"], keyword, location),
        daemon=True,
    )
    thread.start()

    return jsonify({"job_id": job_uuid, "quantity": quantity, "total_remaining": remaining, "continuous": continuous})


@bp.route("/api/dashboard/activity")
def dashboard_activity():
    conn = get_connection()
    new_leads = conn.execute(
        "SELECT id, name, tier, source, created_at FROM leads ORDER BY created_at DESC LIMIT 10"
    ).fetchall()
    activities = conn.execute(
        "SELECT l.name, a.action, a.details, a.created_at FROM lead_activities a "
        "JOIN leads l ON l.id = a.lead_id ORDER BY a.created_at DESC LIMIT 5"
    ).fetchall()
    running = conn.execute(
        "SELECT keyword, quantity_found, quantity_requested, started_at FROM crawl_jobs "
        "WHERE status = 'running' ORDER BY started_at DESC LIMIT 3"
    ).fetchall()
    conn.close()

    feed = []
    for l in new_leads:
        feed.append({"type": "lead", "id": dict(l)["id"], "name": dict(l)["name"], "tier": dict(l)["tier"], "source": dict(l)["source"], "at": dict(l)["created_at"]})
    for a in activities:
        a = dict(a)
        feed.append({"type": "activity", "name": a["name"], "action": a["action"], "details": a["details"], "at": a["created_at"]})
    for j in running:
        j = dict(j)
        feed.append({"type": "job", "keyword": j["keyword"], "found": j["quantity_found"], "total": j["quantity_requested"], "at": j["started_at"]})

    feed.sort(key=lambda x: x.get("at") or "", reverse=True)
    return jsonify({"feed": feed[:15]})


@bp.route("/api/system/health")
def system_health():
    from scheduler import scheduler
    status = scheduler.get_status()
    return jsonify(status)


@bp.route("/status")
def status_page():
    stats = Lead.get_stats()
    jobs, _ = CrawlJob.get_history(page=1, per_page=1)
    last_crawl = jobs[0]["started_at"] if jobs else "Nunca"

    from flask import render_template_string
    return render_template_string("""
{% extends "base.html" %}
{% set active_page = "status" %}
{% block title %}Sistema — LeadHunter{% endblock %}
{% block page_title %}Sistema{% endblock %}
{% block page_subtitle %}Status do servidor e crawler{% endblock %}
{% block content %}
<div class="max-w-2xl mx-auto space-y-5">
    <div class="glass-card p-6">
        <div class="flex items-center gap-3 mb-5">
            <div class="icon-box bg-emerald-500/10">
                <i data-lucide="server" class="w-5 h-5 text-emerald-400 stroke-[1.5]"></i>
            </div>
            <div>
                <h3 class="text-[14px] font-semibold text-zinc-100">Servidor Online</h3>
                <p class="text-[11px] text-zinc-500">Flask em modo desenvolvimento</p>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div class="p-4 rounded-xl bg-white/[0.02] border border-white/5">
                <p class="text-2xl font-bold text-zinc-100">""" + str(stats['total']) + """</p>
                <p class="text-[10px] text-zinc-500 mt-1">Leads no banco</p>
            </div>
            <div class="p-4 rounded-xl bg-white/[0.02] border border-white/5">
                <p class="text-2xl font-bold text-emerald-400">R$ """ + f"{stats['revenue']:,.0f}".replace(",", ".") + """</p>
                <p class="text-[10px] text-zinc-500 mt-1">Revenue total</p>
            </div>
            <div class="p-4 rounded-xl bg-white/[0.02] border border-white/5">
                <p class="text-sm font-medium text-zinc-300">""" + str(last_crawl) + """</p>
                <p class="text-[10px] text-zinc-500 mt-1">Último crawl</p>
            </div>
            <div class="p-4 rounded-xl bg-white/[0.02] border border-white/5">
                <p class="text-sm font-medium text-blue-400">A cada 3 horas</p>
                <p class="text-[10px] text-zinc-500 mt-1">Intervalo auto-crawl</p>
            </div>
        </div>
    </div>
    <div class="glass-card p-6">
        <h3 class="text-[13px] font-semibold text-zinc-200 mb-3">Fontes Ativas</h3>
        <div class="space-y-2">
            <div class="flex items-center gap-2"><div class="w-2 h-2 rounded-full bg-emerald-400 glow-dot"></div><span class="text-[12px] text-zinc-300">DuckDuckGo Search</span></div>
            <div class="flex items-center gap-2"><div class="w-2 h-2 rounded-full bg-emerald-400 glow-dot"></div><span class="text-[12px] text-zinc-300">Bing Search</span></div>
            <div class="flex items-center gap-2"><div class="w-2 h-2 rounded-full bg-emerald-400 glow-dot"></div><span class="text-[12px] text-zinc-300">Google Maps (Selenium)</span></div>
            <div class="flex items-center gap-2"><div class="w-2 h-2 rounded-full bg-emerald-400 glow-dot"></div><span class="text-[12px] text-zinc-300">Telelistas + GuiaMais</span></div>
            <div class="flex items-center gap-2"><div class="w-2 h-2 rounded-full bg-emerald-400 glow-dot"></div><span class="text-[12px] text-zinc-300">Nicho (Doctoralia, TripAdvisor...)</span></div>
            <div class="flex items-center gap-2"><div class="w-2 h-2 rounded-full bg-blue-400 glow-dot"></div><span class="text-[12px] text-zinc-300">Google Search (fallback)</span></div>
        </div>
    </div>
    <div class="glass-card p-6">
        <h3 class="text-[13px] font-semibold text-zinc-200 mb-3">Scheduler</h3>
        <div class="space-y-2 text-[12px] text-zinc-400" id="schedulerDetails">
            <p>Verificação de sites: <span class="text-zinc-200 font-medium">a cada 1 hora</span></p>
            <p>Auto-crawl (5 keywords × 15 leads): <span class="text-zinc-200 font-medium">a cada 3 horas</span></p>
            <p>Status: <span class="text-emerald-400 font-medium" id="schedulerStatus">Carregando...</span></p>
            <p>Taxa de sucesso: <span class="text-blue-400 font-medium" id="schedulerRate">—</span></p>
        </div>
    </div>
</div>
{% endblock %}
{% block scripts %}
<script>
lucide.createIcons();
async function loadHealth() {
    try {
        const data = await api('/api/system/health');
        document.getElementById('schedulerStatus').textContent = data.running ? 'Ativo' : 'Parado';
        document.getElementById('schedulerStatus').className = data.running ? 'text-emerald-400 font-medium' : 'text-red-400 font-medium';
        document.getElementById('schedulerRate').textContent = data.success_rate + '%';
    } catch(e) {}
}
loadHealth();
</script>
{% endblock %}
    """, stats=stats, last_crawl=last_crawl)
