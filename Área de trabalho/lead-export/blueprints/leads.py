from flask import Blueprint, render_template, request, jsonify
from urllib.parse import quote
from models import Lead, Activity
from database import get_connection

bp = Blueprint("leads", __name__)


@bp.route("/leads")
def page():
    return render_template("leads.html")


@bp.route("/api/leads")
def list_leads():
    page = request.args.get("page", 1, type=int)
    per_page = request.args.get("per_page", 25, type=int)
    tier = request.args.get("tier", "").strip() or None
    status = request.args.get("status", "").strip() or None
    source = request.args.get("source", "").strip() or None
    search = request.args.get("search", "").strip() or None
    category = request.args.get("category", "").strip() or None
    sort = request.args.get("sort", "score")
    order = request.args.get("order", "desc")
    score_min = request.args.get("score_min", type=int)
    score_max = request.args.get("score_max", type=int)

    per_page = min(per_page, 100)

    only_verified = request.args.get("verified", "").strip()

    leads, total = Lead.get_all(
        page=page, per_page=per_page, tier=tier,
        status=status, source=source, search=search,
        sort=sort, order=order, only_verified=bool(only_verified),
        category=category, score_min=score_min, score_max=score_max,
    )

    return jsonify({
        "leads": leads,
        "total": total,
        "page": page,
        "per_page": per_page,
        "pages": (total + per_page - 1) // per_page,
    })


@bp.route("/api/leads/<int:lead_id>")
def get_lead(lead_id):
    lead = Lead.get_by_id(lead_id)
    if not lead:
        return jsonify({"error": "Lead não encontrado"}), 404
    return jsonify(lead)


@bp.route("/api/leads/<int:lead_id>", methods=["PATCH"])
def update_lead(lead_id):
    data = request.get_json()
    if not data or "status" not in data:
        return jsonify({"error": "Status obrigatório"}), 400

    success = Lead.update_status(lead_id, data["status"])
    if not success:
        return jsonify({"error": "Status inválido"}), 400
    return jsonify({"ok": True})


@bp.route("/api/leads/<int:lead_id>/close", methods=["PATCH"])
def close_lead(lead_id):
    data = request.get_json()
    if not data:
        return jsonify({"error": "Dados obrigatórios"}), 400

    value = float(data.get("deal_value", 0))
    notes = str(data.get("deal_notes", "")).strip()[:500]

    Lead.close_deal(lead_id, value, notes)
    return jsonify({"ok": True})


@bp.route("/api/leads/<int:lead_id>", methods=["DELETE"])
def delete_lead(lead_id):
    Lead.delete(lead_id)
    return jsonify({"ok": True})


@bp.route("/api/leads/bulk-delete", methods=["POST"])
def bulk_delete():
    data = request.get_json()
    if not data or "ids" not in data:
        return jsonify({"error": "IDs obrigatórios"}), 400

    ids = [int(i) for i in data["ids"] if str(i).isdigit()]
    count = Lead.bulk_delete(ids)
    return jsonify({"deleted": count})


@bp.route("/api/leads/bulk-delete-by-score", methods=["POST"])
def bulk_delete_by_score():
    data = request.get_json()
    if not data or "max_score" not in data:
        return jsonify({"error": "max_score obrigatório"}), 400

    max_score = int(data["max_score"])
    if max_score < 0 or max_score > 100:
        return jsonify({"error": "max_score deve ser entre 0 e 100"}), 400

    conn = get_connection()
    count = conn.execute("SELECT COUNT(*) as c FROM leads WHERE score <= ?", (max_score,)).fetchone()["c"]

    if data.get("dry_run"):
        conn.close()
        return jsonify({"count": count, "dry_run": True})

    conn.execute("DELETE FROM leads WHERE score <= ?", (max_score,))
    conn.commit()
    conn.close()
    return jsonify({"deleted": count})


@bp.route("/api/leads/<int:lead_id>/action/whatsapp")
def whatsapp_action(lead_id):
    lead = Lead.get_by_id(lead_id)
    if not lead:
        return jsonify({"error": "Lead não encontrado"}), 404

    phone = (lead.get("whatsapp") or lead.get("phone") or "").replace("+", "")
    if not phone:
        return jsonify({"error": "Lead sem WhatsApp/telefone"}), 400

    name = lead.get("name", "")
    msg = f"Olá {name}! Vi seu negócio online e gostaria de apresentar uma proposta para criar um site profissional para sua empresa. Posso te explicar melhor?"
    url = f"https://wa.me/{phone}?text={quote(msg)}"

    Lead.update_status(lead_id, "contacted")
    return jsonify({"url": url})


@bp.route("/api/leads/<int:lead_id>/action/email")
def email_action(lead_id):
    lead = Lead.get_by_id(lead_id)
    if not lead:
        return jsonify({"error": "Lead não encontrado"}), 404

    email = lead.get("email", "")
    if not email:
        return jsonify({"error": "Lead sem email"}), 400

    name = lead.get("name", "")
    subject = quote(f"Proposta de site profissional para {name}")
    body = quote(f"Olá {name},\n\nVi seu negócio e gostaria de apresentar uma proposta para criar um site profissional que vai aumentar suas vendas.\n\nPosso agendar uma conversa?\n\nAtenciosamente")
    url = f"mailto:{email}?subject={subject}&body={body}"

    Lead.update_status(lead_id, "contacted")
    return jsonify({"url": url})


@bp.route("/api/leads/<int:lead_id>/notes", methods=["PATCH"])
def update_notes(lead_id):
    data = request.get_json()
    if not data:
        return jsonify({"error": "Dados obrigatórios"}), 400
    notes = str(data.get("notes", ""))[:2000]
    conn = get_connection()
    conn.execute("UPDATE leads SET notes = ?, updated_at = datetime('now', 'localtime') WHERE id = ?", (notes, lead_id))
    conn.commit()
    conn.close()
    Activity.log(lead_id, "note_added", "Nota atualizada")
    return jsonify({"ok": True})


@bp.route("/api/leads/<int:lead_id>/tags", methods=["PATCH"])
def update_tags(lead_id):
    data = request.get_json()
    if not data:
        return jsonify({"error": "Dados obrigatórios"}), 400
    tags = str(data.get("tags", ""))[:500]
    conn = get_connection()
    conn.execute("UPDATE leads SET tags = ?, updated_at = datetime('now', 'localtime') WHERE id = ?", (tags, lead_id))
    conn.commit()
    conn.close()
    return jsonify({"ok": True})


@bp.route("/api/leads/<int:lead_id>/activities")
def lead_activities(lead_id):
    activities = Activity.get_for_lead(lead_id)
    return jsonify({"activities": activities})


@bp.route("/api/leads/bulk-contact", methods=["POST"])
def bulk_contact():
    data = request.get_json() or {}
    tier = data.get("tier", "hot")
    limit = min(int(data.get("limit", 10)), 50)
    conn = get_connection()
    rows = conn.execute(
        "SELECT id, name, whatsapp, phone, email FROM leads WHERE tier = ? AND status = 'new' ORDER BY score DESC LIMIT ?",
        (tier, limit),
    ).fetchall()
    conn.close()
    contacted = []
    for row in rows:
        lead = dict(row)
        phone = lead.get("whatsapp") or lead.get("phone") or ""
        if phone:
            Lead.update_status(lead["id"], "contacted")
            Activity.log(lead["id"], "bulk_contact", "Marcado como contatado em lote")
            contacted.append({"id": lead["id"], "name": lead["name"], "phone": phone})
    return jsonify({"contacted": len(contacted), "leads": contacted})


@bp.route("/api/leads/pipeline")
def pipeline_stats():
    conn = get_connection()
    stages = ["new", "contacted", "negotiating", "closed_won", "closed_lost"]
    pipeline = {}
    for stage in stages:
        pipeline[stage] = conn.execute("SELECT COUNT(*) as c FROM leads WHERE status = ?", (stage,)).fetchone()["c"]
    total = sum(pipeline.values())
    conn.close()
    return jsonify({"pipeline": pipeline, "total": total})


@bp.route("/api/leads/today")
def leads_today():
    conn = get_connection()
    count = conn.execute("SELECT COUNT(*) as c FROM leads WHERE date(created_at) = date('now', 'localtime')").fetchone()["c"]
    conn.close()
    return jsonify({"today": count})


@bp.route("/api/leads/categories")
def lead_categories():
    conn = get_connection()
    rows = conn.execute("SELECT DISTINCT category FROM leads WHERE category != '' ORDER BY category").fetchall()
    conn.close()
    return jsonify({"categories": [dict(r)["category"] for r in rows]})
