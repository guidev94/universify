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
    sort = request.args.get("sort", "created_at")
    order = request.args.get("order", "desc")
    score_min = request.args.get("score_min", type=int)
    score_max = request.args.get("score_max", type=int)
    has_email = bool(request.args.get("has_email", "").strip())
    has_phone = bool(request.args.get("has_phone", "").strip())
    has_website = bool(request.args.get("has_website", "").strip())
    has_whatsapp = bool(request.args.get("has_whatsapp", "").strip())
    has_ads = request.args.get("has_ads", "").strip()
    has_pixel = request.args.get("has_pixel", "").strip()
    site_quality = request.args.get("site_quality", "").strip() or None
    created_days = request.args.get("created_days", type=int)
    is_qualified = request.args.get("qualified", "").strip()

    per_page = min(per_page, 100)

    only_verified = request.args.get("verified", "").strip()

    leads, total = Lead.get_all(
        page=page, per_page=per_page, tier=tier,
        status=status, source=source, search=search,
        sort=sort, order=order, only_verified=bool(only_verified),
        category=category, score_min=score_min, score_max=score_max,
        has_email=has_email, has_phone=has_phone, has_website=has_website,
        has_whatsapp=has_whatsapp, created_days=created_days,
        has_ads=True if has_ads == '1' else False if has_ads == '0' else None,
        has_pixel=True if has_pixel == '1' else False if has_pixel == '0' else None,
        site_quality=site_quality,
        is_qualified=True if is_qualified == '1' else False if is_qualified == '0' else None,
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


@bp.route("/api/leads/<int:lead_id>/qualify", methods=["PATCH"])
def qualify_lead(lead_id):
    success, new_val = Lead.toggle_qualified(lead_id)
    if not success:
        return jsonify({"error": "Lead não encontrado"}), 404
    
    # Log activity
    Activity.log(lead_id, "lead_qualified" if new_val else "lead_unqualified", 
                 "Lead marcado como qualificado" if new_val else "Lead desmarcado como qualificado")
    
    return jsonify({"ok": True, "is_qualified": bool(new_val)})


@bp.route("/api/leads/<int:lead_id>/close", methods=["PATCH"])
def close_lead(lead_id):
    data = request.get_json()
    if not data:
        return jsonify({"error": "Dados obrigatórios"}), 400

    value = float(data.get("deal_value", 0))
    notes = str(data.get("deal_notes", "")).strip()[:500]

    success = Lead.close_deal(lead_id, value, notes)
    if not success:
        return jsonify({"error": "Erro ao salvar. Tente novamente."}), 500

    Activity.log(lead_id, "deal_closed", f"R$ {value:.2f}")
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


@bp.route("/api/leads/stats-by-score")
def leads_stats_by_score():
    conn = get_connection()
    # Total counts by every 10 points
    rows = conn.execute("""
        SELECT (score / 10) * 10 as range_start, count(*) as count 
        FROM leads 
        GROUP BY range_start 
        ORDER BY range_start ASC
    """).fetchall()
    stats = {row["range_start"]: row["count"] for row in rows}
    
    total = conn.execute("SELECT count(*) as c FROM leads").fetchone()["c"]
    conn.close()
    return jsonify({"stats": stats, "total": total})

@bp.route("/api/leads/bulk-delete-by-score", methods=["POST"])
def bulk_delete_by_score():
    data = request.get_json()
    if not data or "max_score" not in data:
        return jsonify({"error": "max_score obrigatório"}), 400

    max_score = int(data["max_score"])
    if max_score < 0 or max_score > 100:
        return jsonify({"error": "max_score deve ser entre 0 e 100"}), 400

    conn = get_connection()
    # Also fetch tiers for the preview
    rows = conn.execute("SELECT count(*) as count, tier FROM leads WHERE score <= ? GROUP BY tier", (max_score,)).fetchall()
    counts_by_tier = {row["tier"]: row["count"] for row in rows}
    total_count = sum(counts_by_tier.values())

    if data.get("dry_run"):
        conn.close()
        return jsonify({
            "count": total_count, 
            "details": counts_by_tier,
            "dry_run": True
        })

    conn.execute("DELETE FROM leads WHERE score <= ?", (max_score,))
    conn.commit()
    conn.close()
    return jsonify({"deleted": total_count})


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
    revenue = {}
    for stage in stages:
        pipeline[stage] = conn.execute("SELECT COUNT(*) as c FROM leads WHERE status = ?", (stage,)).fetchone()["c"]
        revenue[stage] = round(conn.execute("SELECT COALESCE(SUM(deal_value), 0) as v FROM leads WHERE status = ?", (stage,)).fetchone()["v"], 2)
    total = sum(pipeline.values())
    total_pipeline_value = round(revenue["negotiating"] + revenue["contacted"], 2)
    conn.close()
    return jsonify({"pipeline": pipeline, "revenue": revenue, "total": total, "total_pipeline_value": total_pipeline_value})


@bp.route("/api/leads/today")
def leads_today():
    conn = get_connection()
    count = conn.execute("SELECT COUNT(*) as c FROM leads WHERE date(created_at) = date('now', 'localtime')").fetchone()["c"]
    conn.close()
    return jsonify({"today": count})


@bp.route("/api/leads/<int:lead_id>/ai-copy")
def lead_ai_copy(lead_id):
    lead = Lead.get_by_id(lead_id)
    if not lead:
        return jsonify({"error": "Lead não encontrado"}), 404
    
    from crawler.ai_copywriter import AICopywriter
    writer = AICopywriter()
    message = writer.generate_message(lead)
    subject = writer.generate_email_subject(lead)
    
    return jsonify({
        "message": message,
        "subject": subject
    })

@bp.route("/api/leads/bulk-status-update", methods=["POST"])
def bulk_status_update():
    data = request.get_json()
    if not data or "from_status" not in data or "to_status" not in data:
        return jsonify({"error": "Status de origem e destino obrigatórios"}), 400

    from_status = data["from_status"]
    to_status = data["to_status"]
    
    allowed = {"new", "contacted", "negotiating", "closed_won", "closed_lost"}
    if to_status not in allowed:
        return jsonify({"error": "Status de destino inválido"}), 400

    conn = get_connection()
    # If from_status is 'opportunities', we handle it specially based on the technical logic
    if from_status == 'opportunities':
        # This matches the frontend filter logic
        conn.execute("""
            UPDATE leads SET status = ?, updated_at = datetime('now', 'localtime')
            WHERE (site_quality = 'poor' AND site_verified = 1) 
            OR (website = '' AND site_verified = 0)
        """, (to_status,))
    else:
        conn.execute("""
            UPDATE leads SET status = ?, updated_at = datetime('now', 'localtime')
            WHERE status = ?
        """, (to_status, from_status))
    
    count = conn.execute("SELECT changes()").fetchone()[0]
    conn.commit()
    conn.close()
    
    return jsonify({"updated": count})


@bp.route("/api/leads/bulk-update", methods=["PATCH"])
def bulk_update_leads():
    data = request.get_json()
    if not data or "ids" not in data:
        return jsonify({"error": "IDs obrigatórios"}), 400

    ids = [int(i) for i in data["ids"] if str(i).isdigit()]
    status = data.get("status")
    qualify = data.get("qualify") # True to qualify, False to unqualify, None to skip
    
    if not ids:
        return jsonify({"updated": 0})

    conn = get_connection()
    placeholders = ",".join("?" * len(ids))
    
    updates = []
    params = []
    
    if status:
        allowed = {"new", "contacted", "negotiating", "closed_won", "closed_lost"}
        if status in allowed:
            updates.append("status = ?")
            params.append(status)
            
            if status == "contacted":
                updates.append("contacted_at = datetime('now', 'localtime')")
            elif status in ("closed_won", "closed_lost"):
                updates.append("closed_at = datetime('now', 'localtime')")
    
    if qualify is not None:
        updates.append("is_qualified = ?")
        params.append(1 if qualify else 0)

    if not updates:
        conn.close()
        return jsonify({"error": "Nenhuma ação informada"}), 400

    updates.append("updated_at = datetime('now', 'localtime')")
    
    conn.execute(
        f"UPDATE leads SET {', '.join(updates)} WHERE id IN ({placeholders})",
        params + ids
    )
    
    count = conn.execute("SELECT changes()").fetchone()[0]
    conn.commit()
    conn.close()
    
    return jsonify({"updated": count})


@bp.route("/api/leads/bulk-delete-column", methods=["POST"])
def bulk_delete_column():
    data = request.get_json()
    if not data or "status" not in data:
        return jsonify({"error": "Status obrigatório"}), 400

    status = data["status"]
    conn = get_connection()
    
    if status == 'opportunities':
        conn.execute("""
            DELETE FROM leads 
            WHERE (site_quality = 'poor' AND site_verified = 1) 
            OR (website = '' AND site_verified = 0)
        """)
    else:
        conn.execute("DELETE FROM leads WHERE status = ?", (status,))
        
    count = conn.execute("SELECT changes()").fetchone()[0]
    conn.commit()
    conn.close()
    
    return jsonify({"deleted": count})
