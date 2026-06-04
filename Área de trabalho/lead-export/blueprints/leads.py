from flask import Blueprint, render_template, request, jsonify
from urllib.parse import quote
from models import Lead

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
    sort = request.args.get("sort", "score")
    order = request.args.get("order", "desc")

    per_page = min(per_page, 100)

    only_verified = request.args.get("verified", "").strip()

    leads, total = Lead.get_all(
        page=page, per_page=per_page, tier=tier,
        status=status, source=source, search=search,
        sort=sort, order=order, only_verified=bool(only_verified),
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
