import time
from flask import Blueprint, render_template, jsonify
from models import Lead, CrawlJob
from database import get_connection

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
    conn.execute("DELETE FROM crawl_jobs WHERE id = ?", (job_id,))
    conn.commit()
    conn.close()
    return jsonify({"ok": True})


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
