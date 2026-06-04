from flask import Blueprint, render_template, jsonify
from models import Lead, CrawlJob

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
