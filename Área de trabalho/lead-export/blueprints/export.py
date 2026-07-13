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
    campaign = data.get("campaign", "").strip() or None
    site_quality = data.get("site_quality", "").strip() or None
    is_qualified = data.get("is_qualified")
    if is_qualified is not None:
        is_qualified = bool(is_qualified)
    
    has_ads = data.get("has_ads")
    if has_ads is not None:
        has_ads = bool(has_ads)
    
    quantity = data.get("quantity")
    keyword = data.get("keyword", "").strip() or None
    ids = data.get("ids")
    selected_columns = data.get("columns") 
    score_min = data.get("score_min")
    sort = data.get("sort", "created_at")
    order = data.get("order", "desc")

    if fmt not in ("csv", "xlsx"):
        return jsonify({"error": "Formato inválido"}), 400

    if ids and isinstance(ids, list) and len(ids) > 0:
        from database import get_connection
        conn = get_connection()
        placeholders = ",".join("?" * len(ids))
        rows = conn.execute(f"SELECT * FROM leads WHERE id IN ({placeholders}) ORDER BY created_at DESC", ids).fetchall()
        conn.close()
        leads = [dict(r) for r in rows]
    else:
        leads = Lead.get_for_export(
            tier=tier, status=status, quantity=quantity, 
            keyword=keyword, campaign=campaign, 
            site_quality=site_quality, has_ads=has_ads,
            score_min=score_min, sort=sort, order=order,
            is_qualified=is_qualified
        )

    if not leads:
        return jsonify({"error": "Nenhum lead encontrado com esses filtros"}), 404

    os.makedirs(Config.EXPORT_DIR, exist_ok=True)
    filename = f"leads_{uuid.uuid4().hex[:8]}.{fmt}"
    filepath = os.path.join(Config.EXPORT_DIR, filename)

    if fmt == "xlsx":
        export_xlsx(leads, filepath, selected_columns=selected_columns)
        mimetype = "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
    else:
        export_csv(leads, filepath, selected_columns=selected_columns)
        mimetype = "text/csv"

    return send_file(filepath, as_attachment=True, download_name=filename, mimetype=mimetype)


@bp.route("/api/export/preview", methods=["POST"])
def export_preview():
    data = request.get_json() or {}
    tier = data.get("tier", "").strip() or None
    status = data.get("status", "").strip() or None
    keyword = data.get("keyword", "").strip() or None

    leads = Lead.get_for_export(tier=tier, status=status, keyword=keyword)
    preview = [{"name": l["name"], "email": l["email"], "phone": l["phone"], "score": l["score"], "tier": l["tier"]} for l in leads[:5]]
    return jsonify({"count": len(leads), "preview": preview})
