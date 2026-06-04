import os
import uuid
from flask import Blueprint, render_template, request, jsonify, send_file
from models import Lead
from crawler.exporter import export_csv, export_xlsx
from config import Config

bp = Blueprint("export", __name__)


@bp.route("/export")
def page():
    return render_template("export.html")


@bp.route("/api/export", methods=["POST"])
def export_leads():
    data = request.get_json() or {}
    fmt = data.get("format", "csv")
    tier = data.get("tier", "").strip() or None
    status = data.get("status", "").strip() or None
    quantity = data.get("quantity")

    if fmt not in ("csv", "xlsx"):
        return jsonify({"error": "Formato inválido"}), 400

    leads = Lead.get_for_export(tier=tier, status=status, quantity=quantity)

    if not leads:
        return jsonify({"error": "Nenhum lead encontrado com esses filtros"}), 404

    os.makedirs(Config.EXPORT_DIR, exist_ok=True)
    filename = f"leads_{uuid.uuid4().hex[:8]}.{fmt}"
    filepath = os.path.join(Config.EXPORT_DIR, filename)

    if fmt == "xlsx":
        export_xlsx(leads, filepath)
        mimetype = "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
    else:
        export_csv(leads, filepath)
        mimetype = "text/csv"

    return send_file(filepath, as_attachment=True, download_name=filename, mimetype=mimetype)


@bp.route("/api/export/preview", methods=["POST"])
def export_preview():
    data = request.get_json() or {}
    tier = data.get("tier", "").strip() or None
    status = data.get("status", "").strip() or None

    leads = Lead.get_for_export(tier=tier, status=status)
    return jsonify({"count": len(leads)})
