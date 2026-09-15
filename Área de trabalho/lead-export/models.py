import hashlib
import json
from datetime import datetime
from database import get_connection
from crawler.lead_scorer import calculate_score


class Lead:
    @staticmethod
    def bulk_create(leads_data, crawl_job_id=None):
        """Cria múltiplos leads em uma única transação ultra-rápida."""
        if not leads_data:
            return 0
            
        conn = get_connection()
        created_count = 0
        try:
            with conn: # Transação única
                for data in leads_data:
                    # Aproveitamos a lógica de deduplicação mas dentro da mesma conexão
                    Lead.create(data, crawl_job_id=crawl_job_id, conn=conn)
                    created_count += 1
            return created_count
        except Exception as e:
            import logging
            logging.getLogger("leadhunter.models").error(f"[bulk_create] error: {e}")
            return created_count
        finally:
            conn.close()

    @staticmethod
    def create(data, crawl_job_id=None, conn=None):
        should_close = False
        if conn is None:
            conn = get_connection()
            should_close = True
            
        dedup_hash = Lead._generate_hash(data)

        try:
            # Query otimizada: busca apenas o necessário para o merge
            existing = conn.execute("SELECT id, email, phone, whatsapp, score, campaign FROM leads WHERE dedup_hash = ?", (dedup_hash,)).fetchone()

            if existing:
                existing = dict(existing)
                new_email = data.get("email", "")
                new_phone = data.get("phone", "")
                new_whatsapp = data.get("whatsapp", "")
                new_score = data.get("score", 0)

                # Merge de emails
                merged_email = existing["email"]
                if new_email and new_email not in (merged_email or ""):
                    emails = set((merged_email or "").split(";")) | set(new_email.split(";"))
                    emails.discard("")
                    merged_email = ";".join(sorted(list(emails))[:5])

                # Merge de telefones
                merged_phone = existing["phone"]
                if new_phone and new_phone not in (merged_phone or ""):
                    phones = set((merged_phone or "").split(";")) | set(new_phone.split(";"))
                    phones.discard("")
                    merged_phone = ";".join(sorted(list(phones))[:3])

                merged_whatsapp = existing["whatsapp"] or new_whatsapp
                best_score = max(new_score, existing["score"] or 0)
                
                # Só recalcula se o score novo for maior ou dados mudaram significativamente
                score, tier, pitch_hook = calculate_score(data)
                if score < best_score:
                    score = best_score
                
                # Atribuição de campanha se estiver vazio
                new_campaign = data.get("campaign", "")
                target_campaign = existing["campaign"] or new_campaign

                conn.execute(
                    """UPDATE leads SET email = ?, phone = ?, whatsapp = ?, score = ?, tier = ?, 
                       pitch_hook = ?, campaign = ?, updated_at = datetime('now', 'localtime') 
                       WHERE id = ?""",
                    (merged_email, merged_phone, merged_whatsapp, score, tier, pitch_hook, target_campaign, existing["id"]),
                )
                if should_close:
                    conn.commit()
                return existing["id"]

            # Novo Lead
            score, tier, pitch_hook = calculate_score(data)
            conn.execute(
                """INSERT INTO leads (name, email, phone, whatsapp, website, category, source,
                   score, tier, site_verified, ssl_valid, crawl_job_id, dedup_hash, campaign, location, pitch_hook)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)""",
                (
                    data["name"], data.get("email", ""), data.get("phone", ""),
                    data.get("whatsapp", ""), data.get("website", ""),
                    data.get("category", ""), data.get("source", ""),
                    score, tier,
                    data.get("site_verified", 0), data.get("ssl_valid", 0),
                    crawl_job_id, dedup_hash, data.get("campaign", ""),
                    data.get("location", ""), pitch_hook
                ),
            )
            if should_close:
                conn.commit()
            
            # Nota: evitamos o SELECT secundário se possível, mas o ID é necessário às vezes
            if should_close:
                row = conn.execute("SELECT last_insert_rowid() as id").fetchone()
                return row["id"] if row else None
            return True # No bulk mode, retornar True basta
        except Exception as e:
            if "UNIQUE" in str(e):
                if should_close: conn.rollback()
                return None
            raise e
        finally:
            if should_close:
                conn.close()

    @staticmethod
    def deduplicate_existing():
        from urllib.parse import urlparse
        conn = get_connection()

        rows = conn.execute("""
            SELECT website, GROUP_CONCAT(id) as ids, COUNT(*) as cnt
            FROM leads WHERE website != ''
            GROUP BY website HAVING cnt > 1
        """).fetchall()

        removed = 0
        for row in rows:
            ids = [int(x) for x in dict(row)["ids"].split(",")]
            leads = conn.execute(
                f"SELECT id, email, phone, whatsapp, score FROM leads WHERE id IN ({','.join('?' * len(ids))})",
                ids,
            ).fetchall()
            leads = [dict(l) for l in leads]

            best = max(leads, key=lambda x: x["score"] or 0)

            all_emails = set()
            all_phones = set()
            for l in leads:
                if l["email"]:
                    for e in l["email"].split(";"):
                        if e.strip():
                            all_emails.add(e.strip())
                if l["phone"]:
                    for p in l["phone"].split(";"):
                        if p.strip():
                            all_phones.add(p.strip())

            merged_email = ";".join(sorted(all_emails)[:5])
            merged_phone = ";".join(sorted(all_phones)[:3])

            conn.execute(
                "UPDATE leads SET email = ?, phone = ? WHERE id = ?",
                (merged_email, merged_phone, best["id"]),
            )

            to_delete = [l["id"] for l in leads if l["id"] != best["id"]]
            if to_delete:
                conn.execute(
                    f"DELETE FROM leads WHERE id IN ({','.join('?' * len(to_delete))})",
                    to_delete,
                )
                removed += len(to_delete)

        if removed > 0:
            conn.execute("UPDATE leads SET dedup_hash = NULL")
            all_leads = conn.execute("SELECT id, email, phone, website, name FROM leads").fetchall()
            seen_hashes = set()
            extra_remove = []
            for lead in all_leads:
                lead = dict(lead)
                new_hash = Lead._generate_hash(lead)
                if new_hash in seen_hashes:
                    extra_remove.append(lead["id"])
                else:
                    seen_hashes.add(new_hash)
                    conn.execute("UPDATE leads SET dedup_hash = ? WHERE id = ?", (new_hash, lead["id"]))
            if extra_remove:
                conn.execute(f"DELETE FROM leads WHERE id IN ({','.join('?' * len(extra_remove))})", extra_remove)
                removed += len(extra_remove)

        conn.commit()
        conn.close()
        return removed

    @staticmethod
    def delete_by_score(min_score):
        conn = get_connection()
        try:
            cursor = conn.execute("DELETE FROM leads WHERE score < ?", (min_score,))
            count = cursor.rowcount
            conn.commit()
            return count
        finally:
            conn.close()

    @staticmethod
    def get_all(page=1, per_page=25, tier=None, status=None, source=None, search=None, sort="score", order="desc", only_verified=False, category=None, score_min=None, score_max=None, has_email=False, has_phone=False, has_website=False, has_whatsapp=False, created_days=None, has_ads=None, has_pixel=None, site_quality=None, is_qualified=None):
        conn = get_connection()
        conditions = []
        params = []

        if tier:
            conditions.append("tier = ?")
            params.append(tier)
        if status:
            statuses = [s.strip() for s in status.split(",")]
            placeholders = ",".join("?" * len(statuses))
            conditions.append(f"status IN ({placeholders})")
            params.extend(statuses)
        if source:
            conditions.append("source = ?")
            params.append(source)
        if is_qualified is not None:
            conditions.append("is_qualified = ?")
            params.append(1 if is_qualified else 0)
        if category:
            conditions.append("category LIKE ?")
            params.append(f"%{category}%")
        if search:
            conditions.append("(name LIKE ? OR email LIKE ? OR category LIKE ? OR phone LIKE ? OR website LIKE ? OR whatsapp LIKE ? OR notes LIKE ? OR cnpj LIKE ? OR partners LIKE ?)")
            params.extend([f"%{search}%"] * 9)
        if only_verified:
            conditions.append("site_verified = 1 AND website != ''")
        if score_min is not None:
            conditions.append("score >= ?")
            params.append(score_min)
        if score_max is not None:
            conditions.append("score <= ?")
            params.append(score_max)
        if has_email:
            conditions.append("email != ''")
        if has_phone:
            conditions.append("phone != ''")
        if has_website:
            conditions.append("website != ''")
        if has_whatsapp:
            conditions.append("whatsapp != ''")
        if has_ads is not None:
            conditions.append("has_ads = ?")
            params.append(1 if has_ads else 0)
        if has_pixel is not None:
            conditions.append("has_pixel = ?")
            params.append(1 if has_pixel else 0)
        if site_quality:
            conditions.append("site_quality = ?")
            params.append(site_quality)
        if created_days is not None:
            conditions.append("created_at >= datetime('now', ? || ' days', 'localtime')")
            params.append(f"-{int(created_days)}")

        where = f"WHERE {' AND '.join(conditions)}" if conditions else ""

        allowed_sorts = {"score", "created_at", "name", "tier", "deal_value"}
        sort_col = sort if sort in allowed_sorts else "score"
        sort_order = "ASC" if order == "asc" else "DESC"

        total = conn.execute(f"SELECT COUNT(*) as cnt FROM leads {where}", params).fetchone()["cnt"]

        offset = (page - 1) * per_page
        
        # OTIMIZAÇÃO: Seleciona apenas colunas essenciais para a lista (reduz tráfego de dados)
        cols = "id, name, email, phone, whatsapp, website, category, source, score, tier, status, created_at, site_quality, has_ads, is_qualified"
        rows = conn.execute(
            f"SELECT {cols} FROM leads {where} ORDER BY {sort_col} {sort_order} LIMIT ? OFFSET ?",
            params + [per_page, offset],
        ).fetchall()

        conn.close()
        return [dict(r) for r in rows], total

    @staticmethod
    def get_by_id(lead_id):
        conn = get_connection()
        row = conn.execute("SELECT * FROM leads WHERE id = ?", (lead_id,)).fetchone()
        conn.close()
        return dict(row) if row else None

    @staticmethod
    def update_status(lead_id, status):
        allowed = {"new", "contacted", "negotiating", "closed_won", "closed_lost"}
        if status not in allowed:
            return False
        conn = get_connection()

        extra = ""
        if status == "contacted":
            extra = ", contacted_at = datetime('now', 'localtime')"
        elif status in ("closed_won", "closed_lost"):
            extra = ", closed_at = datetime('now', 'localtime')"

        conn.execute(
            f"UPDATE leads SET status = ?, updated_at = datetime('now', 'localtime'){extra} WHERE id = ?",
            (status, lead_id),
        )
        conn.commit()
        conn.close()
        return True

    @staticmethod
    def close_deal(lead_id, value, notes=""):
        import time
        for attempt in range(5):
            conn = get_connection()
            try:
                conn.execute(
                    """UPDATE leads SET status = 'closed_won', deal_value = ?, deal_notes = ?,
                       closed_at = datetime('now', 'localtime'), updated_at = datetime('now', 'localtime')
                       WHERE id = ?""",
                    (value, notes, lead_id),
                )
                conn.commit()
                conn.close()
                return True
            except Exception as e:
                conn.close()
                if attempt < 4 and "locked" in str(e).lower():
                    time.sleep(0.3 * (attempt + 1))
                    continue
                return False
        return False

    @staticmethod
    def update_verification(lead_id, site_verified, ssl_valid, new_score, new_tier):
        conn = get_connection()
        conn.execute(
            """UPDATE leads SET site_verified = ?, ssl_valid = ?, score = ?, tier = ?,
               updated_at = datetime('now', 'localtime') WHERE id = ?""",
            (site_verified, ssl_valid, new_score, new_tier, lead_id),
        )
        conn.commit()
        conn.close()

    @staticmethod
    def delete(lead_id):
        conn = get_connection()
        conn.execute("DELETE FROM leads WHERE id = ?", (lead_id,))
        conn.commit()
        conn.close()

    @staticmethod
    def toggle_qualified(lead_id):
        conn = get_connection()
        row = conn.execute("SELECT is_qualified FROM leads WHERE id = ?", (lead_id,)).fetchone()
        if row:
            new_val = 0 if row["is_qualified"] else 1
            conn.execute("UPDATE leads SET is_qualified = ? WHERE id = ?", (new_val, lead_id))
            conn.commit()
            conn.close()
            return True, new_val
        conn.close()
        return False, None

    @staticmethod
    def bulk_delete(ids):
        if not ids:
            return 0
        conn = get_connection()
        placeholders = ",".join("?" * len(ids))
        conn.execute(f"DELETE FROM leads WHERE id IN ({placeholders})", ids)
        conn.commit()
        conn.close()
        return len(ids)

    @staticmethod
    def get_unverified(limit=50):
        conn = get_connection()
        rows = conn.execute(
            "SELECT id, website FROM leads WHERE site_verified = 0 AND website != '' LIMIT ?",
            (limit,),
        ).fetchall()
        conn.close()
        return [dict(r) for r in rows]

    @staticmethod
    def get_stats():
        conn = get_connection()
        try:
            # Query consolidada para evitar múltiplos SELECTs e overhead de locks
            stats_row = conn.execute("""
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN tier = 'hot' THEN 1 ELSE 0 END) as hot,
                    SUM(CASE WHEN tier = 'warm' THEN 1 ELSE 0 END) as warm,
                    SUM(CASE WHEN tier = 'cold' THEN 1 ELSE 0 END) as cold,
                    SUM(CASE WHEN email != '' THEN 1 ELSE 0 END) as with_email,
                    SUM(CASE WHEN phone != '' THEN 1 ELSE 0 END) as with_phone,
                    SUM(CASE WHEN whatsapp != '' THEN 1 ELSE 0 END) as with_whatsapp,
                    SUM(CASE WHEN has_ads = 1 THEN 1 ELSE 0 END) as with_ads,
                    SUM(CASE WHEN site_verified = 1 THEN 1 ELSE 0 END) as verified,
                    AVG(score) as avg_score
                FROM leads
            """).fetchone()
            
            stats = dict(stats_row)
            stats["avg_score"] = round(stats["avg_score"] or 0, 1)

            # Stats de vendas
            sales_row = conn.execute("""
                SELECT 
                    COALESCE(SUM(deal_value), 0) as revenue,
                    SUM(CASE WHEN status = 'closed_won' THEN 1 ELSE 0 END) as deals_closed,
                    SUM(CASE WHEN status = 'negotiating' THEN 1 ELSE 0 END) as negotiating,
                    SUM(CASE WHEN status = 'contacted' THEN 1 ELSE 0 END) as contacted
                FROM leads
                WHERE status IN ('closed_won', 'negotiating', 'contacted')
            """).fetchone()
            
            stats.update(dict(sales_row))
            stats["revenue"] = round(stats["revenue"], 2)

            if stats["total"] > 0:
                stats["conversion_rate"] = round((stats["deals_closed"] / stats["total"]) * 100, 1)
            else:
                stats["conversion_rate"] = 0

            return stats
        finally:
            conn.close()

    @staticmethod
    def get_chart_data():
        from datetime import datetime, timedelta

        conn = get_connection()
        rows = conn.execute(
            """SELECT date(created_at) as day, COUNT(*) as cnt
               FROM leads WHERE created_at >= date('now', '-30 days')
               GROUP BY date(created_at) ORDER BY day""",
        ).fetchall()

        data_map = {r["day"]: r["cnt"] for r in rows}
        today = datetime.now().date()
        days_to_show = max(7, len(data_map))
        labels = []
        values = []
        for i in range(days_to_show - 1, -1, -1):
            day = (today - timedelta(days=i)).isoformat()
            labels.append(day)
            values.append(data_map.get(day, 0))

        chart = {"labels": labels, "values": values}

        tiers = conn.execute(
            "SELECT tier, COUNT(*) as cnt FROM leads WHERE tier IN ('hot','warm','cold') GROUP BY tier",
        ).fetchall()
        chart["tiers"] = {r["tier"]: r["cnt"] for r in tiers}

        sources = conn.execute(
            "SELECT source, COUNT(*) as cnt FROM leads GROUP BY source ORDER BY cnt DESC LIMIT 6",
        ).fetchall()
        chart["sources"] = {r["source"]: r["cnt"] for r in sources}

        conn.close()
        return chart

    @staticmethod
    def get_for_export(tier=None, status=None, quantity=None, keyword=None, campaign=None, site_quality=None, has_ads=None, score_min=None, sort="created_at", order="desc", is_qualified=None):
        conn = get_connection()
        conditions = []
        params = []

        if tier:
            tiers = [t.strip() for t in tier.split(",")]
            placeholders = ",".join("?" * len(tiers))
            conditions.append(f"tier IN ({placeholders})")
            params.extend(tiers)
        if status:
            statuses = [s.strip() for s in status.split(",")]
            placeholders = ",".join("?" * len(statuses))
            conditions.append(f"status IN ({placeholders})")
            params.extend(statuses)
        if is_qualified is not None:
            conditions.append("is_qualified = ?")
            params.append(1 if is_qualified else 0)
        if campaign:
            conditions.append("campaign = ?")
            params.append(campaign)
        if site_quality:
            if site_quality == 'none':
                conditions.append("(website = '' OR website IS NULL) AND site_verified = 0")
            else:
                conditions.append("site_quality = ?")
                params.append(site_quality)
        if has_ads is not None:
            conditions.append("has_ads = ?")
            params.append(1 if has_ads else 0)
        if keyword:
            conditions.append("(category LIKE ? OR name LIKE ?)")
            params.extend([f"%{keyword}%", f"%{keyword}%"])
        if score_min is not None:
            conditions.append("score >= ?")
            params.append(int(score_min))

        where = f"WHERE {' AND '.join(conditions)}" if conditions else ""
        limit = f"LIMIT {int(quantity)}" if quantity and int(quantity) > 0 else ""

        allowed_sorts = {"score", "created_at", "name"}
        sort_col = sort if sort in allowed_sorts else "created_at"
        sort_order = "ASC" if order == "asc" else "DESC"

        rows = conn.execute(
            f"SELECT * FROM leads {where} ORDER BY {sort_col} {sort_order} {limit}", params
        ).fetchall()
        conn.close()
        return [dict(r) for r in rows]

    @staticmethod
    def _generate_hash(data):
        email = (data.get("email") or "").strip().lower()
        phone = (data.get("phone") or "").strip()
        website = (data.get("website") or "").strip().lower()
        name = (data.get("name") or "").strip().lower()

        # If has website, use domain for dedup (Strongest)
        if website:
            from urllib.parse import urlparse
            website_clean = website.lower().strip().split('?')[0].rstrip('/')
            domain = urlparse(website_clean).netloc.lower().replace("www.", "")
            if domain:
                raw = f"site:{domain}"
                return hashlib.sha256(raw.encode()).hexdigest()

        # If has name AND (email OR phone), combine them
        if name and (email or phone):
            raw = f"n:{name}|e:{email}|p:{phone}"
            return hashlib.sha256(raw.encode()).hexdigest()

        # Fallbacks
        if email:
            raw = f"email:{email}"
        elif phone:
            raw = f"phone:{phone}"
        else:
            raw = f"name:{name}"
        return hashlib.sha256(raw.encode()).hexdigest()


class CrawlJob:
    @staticmethod
    def create(job_uuid, keyword, location, source, quantity_requested):
        from database import get_connection
        conn = get_connection()
        conn.execute(
            """INSERT INTO crawl_jobs (job_uuid, keyword, location, source, quantity_requested)
               VALUES (?, ?, ?, ?, ?)""",
            (job_uuid, keyword, location, source, quantity_requested),
        )
        conn.commit()
        row = conn.execute("SELECT id FROM crawl_jobs WHERE job_uuid = ?", (job_uuid,)).fetchone()
        conn.close()
        return row["id"] if row else None

    @staticmethod
    def update_progress(job_uuid, progress, quantity_found=None):
        conn = get_connection()
        if quantity_found is not None:
            conn.execute(
                "UPDATE crawl_jobs SET progress = ?, quantity_found = ? WHERE job_uuid = ?",
                (progress, quantity_found, job_uuid),
            )
        else:
            conn.execute(
                "UPDATE crawl_jobs SET progress = ? WHERE job_uuid = ?",
                (progress, job_uuid),
            )
        conn.commit()
        conn.close()

    @staticmethod
    def complete(job_uuid, quantity_found):
        conn = get_connection()
        conn.execute(
            """UPDATE crawl_jobs SET status = 'completed', quantity_found = ?,
               completed_at = datetime('now', 'localtime') WHERE job_uuid = ?""",
            (quantity_found, job_uuid),
        )
        conn.commit()
        conn.close()

    @staticmethod
    def fail(job_uuid, error_msg):
        conn = get_connection()
        conn.execute(
            """UPDATE crawl_jobs SET status = 'error',
               errors = ?, completed_at = datetime('now', 'localtime') WHERE job_uuid = ?""",
            (json.dumps([error_msg]), job_uuid),
        )
        conn.commit()
        conn.close()

    @staticmethod
    def set_running(job_uuid):
        conn = get_connection()
        conn.execute("UPDATE crawl_jobs SET status = 'running' WHERE job_uuid = ?", (job_uuid,))
        conn.commit()
        conn.close()

    @staticmethod
    def get_by_uuid(job_uuid):
        conn = get_connection()
        row = conn.execute("SELECT * FROM crawl_jobs WHERE job_uuid = ?", (job_uuid,)).fetchone()
        conn.close()
        return dict(row) if row else None

    @staticmethod
    def get_history(page=1, per_page=10):
        conn = get_connection()
        total = conn.execute("SELECT COUNT(*) as cnt FROM crawl_jobs").fetchone()["cnt"]
        offset = (page - 1) * per_page
        rows = conn.execute(
            "SELECT * FROM crawl_jobs ORDER BY started_at DESC LIMIT ? OFFSET ?",
            (per_page, offset),
        ).fetchall()
        conn.close()
        return [dict(r) for r in rows], total


class Keyword:
    @staticmethod
    def track(keyword, location, leads_found):
        conn = get_connection()
        existing = conn.execute(
            "SELECT id, times_used, total_leads_found FROM keywords WHERE keyword = ? AND location = ?",
            (keyword, location),
        ).fetchone()

        if existing:
            conn.execute(
                """UPDATE keywords SET times_used = times_used + 1,
                   total_leads_found = total_leads_found + ?,
                   last_used_at = datetime('now', 'localtime') WHERE id = ?""",
                (leads_found, existing["id"]),
            )
        else:
            conn.execute(
                "INSERT INTO keywords (keyword, location, total_leads_found) VALUES (?, ?, ?)",
                (keyword, location, leads_found),
            )

        conn.commit()
        conn.close()

    @staticmethod
    def get_suggestions(limit=10):
        conn = get_connection()
        # Otimização: Cache de sugestões via query simples
        rows = conn.execute(
            "SELECT keyword, location, times_used, total_leads_found FROM keywords ORDER BY times_used DESC LIMIT ?",
            (limit,),
        ).fetchall()
        conn.close()
        return [dict(r) for r in rows]

    @staticmethod
    def cleanup_old_data(days=60):
        """Limpa logs e dados temporários antigos para manter o banco leve."""
        conn = get_connection()
        try:
            # Limpa atividades muito antigas
            conn.execute("DELETE FROM lead_activities WHERE created_at < datetime('now', ? || ' days')", (f"-{days}",))
            # Limpa histórico de jobs antigos (mantém os últimos 500)
            conn.execute("""
                DELETE FROM crawl_jobs 
                WHERE id NOT IN (SELECT id FROM crawl_jobs ORDER BY started_at DESC LIMIT 500)
            """)
            conn.commit()
        finally:
            conn.close()

    @staticmethod
    def get_for_autocrawl(limit=3):
        conn = get_connection()
        rows = conn.execute(
            "SELECT keyword, location FROM keywords WHERE times_used > 0 ORDER BY last_used_at ASC LIMIT ?",
            (limit,),
        ).fetchall()
        conn.close()
        return [dict(r) for r in rows]


class Activity:
    @staticmethod
    def log(lead_id, action, details=""):
        conn = get_connection()
        conn.execute(
            "INSERT INTO lead_activities (lead_id, action, details) VALUES (?, ?, ?)",
            (lead_id, action, details),
        )
        conn.commit()
        conn.close()

    @staticmethod
    def get_for_lead(lead_id, limit=20):
        conn = get_connection()
        rows = conn.execute(
            "SELECT * FROM lead_activities WHERE lead_id = ? ORDER BY created_at DESC LIMIT ?",
            (lead_id, limit),
        ).fetchall()
        conn.close()
        return [dict(r) for r in rows]


class Campaign:
    @staticmethod
    def create(name, keyword="", location="", goal=100, expand_auto=0):
        conn = get_connection()
        conn.execute(
            "INSERT INTO campaigns (name, keyword, location, goal, expand_auto) VALUES (?, ?, ?, ?, ?)",
            (name, keyword, location, goal, 1 if expand_auto else 0),
        )
        conn.commit()
        conn.close()


    @staticmethod
    def get_all():
        conn = get_connection()
        rows = conn.execute("SELECT * FROM campaigns ORDER BY created_at DESC").fetchall()
        conn.close()
        return [dict(r) for r in rows]

    @staticmethod
    def get_stats(campaign_name):
        conn = get_connection()
        total = conn.execute("SELECT COUNT(*) as c FROM leads WHERE campaign = ?", (campaign_name,)).fetchone()["c"]
        contacted = conn.execute("SELECT COUNT(*) as c FROM leads WHERE campaign = ? AND status = 'contacted'", (campaign_name,)).fetchone()["c"]
        won = conn.execute("SELECT COUNT(*) as c FROM leads WHERE campaign = ? AND status = 'closed_won'", (campaign_name,)).fetchone()["c"]
        conn.close()
        return {"total": total, "contacted": contacted, "won": won}

    @staticmethod
    def get_by_id(campaign_id):
        conn = get_connection()
        row = conn.execute("SELECT * FROM campaigns WHERE id = ?", (campaign_id,)).fetchone()
        conn.close()
        return dict(row) if row else None

    @staticmethod
    def update(campaign_id, **kwargs):
        conn = get_connection()
        allowed = {"name", "keyword", "location", "goal", "status", "description", "expand_auto"}
        updates = []
        params = []
        for key, value in kwargs.items():
            if key in allowed and value is not None:
                updates.append(f"{key} = ?")
                params.append(value)
        if not updates:
            conn.close()
            return False
        updates.append("updated_at = datetime('now', 'localtime')")
        params.append(campaign_id)
        conn.execute(f"UPDATE campaigns SET {', '.join(updates)} WHERE id = ?", params)
        conn.commit()
        conn.close()
        return True

    @staticmethod
    def get_remaining(campaign_name, goal):
        conn = get_connection()
        current = conn.execute("SELECT COUNT(*) as c FROM leads WHERE campaign = ?", (campaign_name,)).fetchone()["c"]
        conn.close()
        return max(0, goal - current)

    @staticmethod
    def update_extraction_status(campaign_id, status):
        conn = get_connection()
        conn.execute(
            "UPDATE campaigns SET extraction_status = ?, updated_at = datetime('now', 'localtime') WHERE id = ?",
            (status, campaign_id),
        )
        if status == "running":
            conn.execute(
                "UPDATE campaigns SET last_extracted_at = datetime('now', 'localtime') WHERE id = ?",
                (campaign_id,),
            )
        conn.commit()
        conn.close()


class MessageTemplate:
    @staticmethod
    def create(name, channel, body, subject=""):
        conn = get_connection()
        conn.execute(
            "INSERT INTO message_templates (name, channel, body, subject) VALUES (?, ?, ?, ?)",
            (name, channel, body, subject),
        )
        conn.commit()
        conn.close()

    @staticmethod
    def get_all():
        conn = get_connection()
        rows = conn.execute("SELECT * FROM message_templates ORDER BY created_at DESC").fetchall()
        conn.close()
        return [dict(r) for r in rows]

    @staticmethod
    def get_by_id(template_id):
        conn = get_connection()
        row = conn.execute("SELECT * FROM message_templates WHERE id = ?", (template_id,)).fetchone()
        conn.close()
        return dict(row) if row else None

    @staticmethod
    def render(template_body, lead):
        return template_body.replace(
            "{nome}", lead.get("name", "")
        ).replace(
            "{email}", lead.get("email", "").split(";")[0]
        ).replace(
            "{empresa}", lead.get("name", "")
        ).replace(
            "{segmento}", lead.get("category", "")
        ).replace(
            "{site}", lead.get("website", "")
        )
