import hashlib
import json
from datetime import datetime
from database import get_connection


class Lead:
    @staticmethod
    def create(data, crawl_job_id=None):
        conn = get_connection()
        dedup_hash = Lead._generate_hash(data)

        try:
            existing = conn.execute("SELECT id, email, phone, whatsapp, score FROM leads WHERE dedup_hash = ?", (dedup_hash,)).fetchone()

            if existing:
                existing = dict(existing)
                new_email = data.get("email", "")
                new_phone = data.get("phone", "")
                new_whatsapp = data.get("whatsapp", "")
                new_score = data.get("score", 0)

                merged_email = existing["email"]
                if new_email and new_email not in (merged_email or ""):
                    merged_email = f"{merged_email};{new_email}" if merged_email else new_email

                merged_phone = existing["phone"]
                if new_phone and new_phone not in (merged_phone or ""):
                    merged_phone = f"{merged_phone};{new_phone}" if merged_phone else new_phone

                merged_whatsapp = existing["whatsapp"]
                if new_whatsapp and new_whatsapp not in (merged_whatsapp or ""):
                    merged_whatsapp = new_whatsapp if not merged_whatsapp else merged_whatsapp

                best_score = max(new_score, existing["score"] or 0)

                conn.execute(
                    """UPDATE leads SET email = ?, phone = ?, whatsapp = ?, score = ?,
                       updated_at = datetime('now', 'localtime') WHERE id = ?""",
                    (merged_email, merged_phone, merged_whatsapp, best_score, existing["id"]),
                )
                conn.commit()
                return existing["id"]

            conn.execute(
                """INSERT INTO leads (name, email, phone, whatsapp, website, category, source,
                   score, tier, site_verified, ssl_valid, crawl_job_id, dedup_hash)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)""",
                (
                    data["name"], data.get("email", ""), data.get("phone", ""),
                    data.get("whatsapp", ""), data.get("website", ""),
                    data.get("category", ""), data.get("source", ""),
                    data.get("score", 0), data.get("tier", "cold"),
                    data.get("site_verified", 0), data.get("ssl_valid", 0),
                    crawl_job_id, dedup_hash,
                ),
            )
            conn.commit()
            lead_id = conn.execute("SELECT id FROM leads WHERE dedup_hash = ?", (dedup_hash,)).fetchone()
            return lead_id["id"] if lead_id else None
        finally:
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
    def get_all(page=1, per_page=25, tier=None, status=None, source=None, search=None, sort="score", order="desc", only_verified=False, category=None, score_min=None, score_max=None, has_email=False, has_phone=False, has_website=False, has_whatsapp=False, created_days=None):
        conn = get_connection()
        conditions = []
        params = []

        if tier:
            conditions.append("tier = ?")
            params.append(tier)
        if status:
            conditions.append("status = ?")
            params.append(status)
        if source:
            conditions.append("source = ?")
            params.append(source)
        if category:
            conditions.append("category LIKE ?")
            params.append(f"%{category}%")
        if search:
            conditions.append("(name LIKE ? OR email LIKE ? OR category LIKE ? OR phone LIKE ? OR website LIKE ? OR whatsapp LIKE ? OR notes LIKE ?)")
            params.extend([f"%{search}%"] * 7)
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
        if created_days is not None:
            conditions.append("created_at >= datetime('now', ? || ' days', 'localtime')")
            params.append(f"-{int(created_days)}")

        where = f"WHERE {' AND '.join(conditions)}" if conditions else ""

        allowed_sorts = {"score", "created_at", "name", "tier"}
        sort_col = sort if sort in allowed_sorts else "score"
        sort_order = "ASC" if order == "asc" else "DESC"

        total = conn.execute(f"SELECT COUNT(*) as cnt FROM leads {where}", params).fetchone()["cnt"]

        offset = (page - 1) * per_page
        rows = conn.execute(
            f"SELECT * FROM leads {where} ORDER BY {sort_col} {sort_order} LIMIT ? OFFSET ?",
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
        stats = {}
        stats["hot"] = conn.execute("SELECT COUNT(*) as cnt FROM leads WHERE tier = 'hot'").fetchone()["cnt"]
        stats["warm"] = conn.execute("SELECT COUNT(*) as cnt FROM leads WHERE tier = 'warm'").fetchone()["cnt"]
        stats["cold"] = conn.execute("SELECT COUNT(*) as cnt FROM leads WHERE tier = 'cold'").fetchone()["cnt"]
        stats["total"] = stats["hot"] + stats["warm"] + stats["cold"]
        stats["with_email"] = conn.execute("SELECT COUNT(*) as cnt FROM leads WHERE email != ''").fetchone()["cnt"]
        stats["with_phone"] = conn.execute("SELECT COUNT(*) as cnt FROM leads WHERE phone != ''").fetchone()["cnt"]
        stats["with_whatsapp"] = conn.execute("SELECT COUNT(*) as cnt FROM leads WHERE whatsapp != ''").fetchone()["cnt"]
        stats["verified"] = conn.execute("SELECT COUNT(*) as cnt FROM leads WHERE site_verified = 1").fetchone()["cnt"]

        avg_row = conn.execute("SELECT AVG(score) as avg_score FROM leads").fetchone()
        stats["avg_score"] = round(avg_row["avg_score"] or 0, 1)

        revenue_row = conn.execute("SELECT COALESCE(SUM(deal_value), 0) as total FROM leads WHERE status = 'closed_won'").fetchone()
        stats["revenue"] = round(revenue_row["total"], 2)
        stats["deals_closed"] = conn.execute("SELECT COUNT(*) as cnt FROM leads WHERE status = 'closed_won'").fetchone()["cnt"]
        stats["negotiating"] = conn.execute("SELECT COUNT(*) as cnt FROM leads WHERE status = 'negotiating'").fetchone()["cnt"]
        stats["contacted"] = conn.execute("SELECT COUNT(*) as cnt FROM leads WHERE status = 'contacted'").fetchone()["cnt"]

        if stats["total"] > 0:
            stats["conversion_rate"] = round((stats["deals_closed"] / stats["total"]) * 100, 1)
        else:
            stats["conversion_rate"] = 0

        conn.close()
        return stats

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
    def get_for_export(tier=None, status=None, quantity=None, keyword=None):
        conn = get_connection()
        conditions = []
        params = []

        if tier:
            tiers = [t.strip() for t in tier.split(",")]
            placeholders = ",".join("?" * len(tiers))
            conditions.append(f"tier IN ({placeholders})")
            params.extend(tiers)
        if status:
            conditions.append("status = ?")
            params.append(status)
        if keyword:
            conditions.append("(category LIKE ? OR name LIKE ?)")
            params.extend([f"%{keyword}%", f"%{keyword}%"])

        where = f"WHERE {' AND '.join(conditions)}" if conditions else ""
        limit = f"LIMIT {int(quantity)}" if quantity else ""

        rows = conn.execute(
            f"SELECT * FROM leads {where} ORDER BY score DESC {limit}", params
        ).fetchall()
        conn.close()
        return [dict(r) for r in rows]

    @staticmethod
    def _generate_hash(data):
        email = (data.get("email") or "").strip().lower()
        phone = (data.get("phone") or "").strip()
        website = (data.get("website") or "").strip().lower()

        if website:
            from urllib.parse import urlparse
            domain = urlparse(website).netloc.lower().replace("www.", "")
            if domain:
                raw = f"site:{domain}"
                return hashlib.sha256(raw.encode()).hexdigest()

        if email:
            raw = f"email:{email}"
        elif phone:
            raw = f"phone:{phone}"
        else:
            name = (data.get("name") or "").strip().lower()
            raw = f"name:{name}"
        return hashlib.sha256(raw.encode()).hexdigest()


class CrawlJob:
    @staticmethod
    def create(job_uuid, keyword, location, source, quantity):
        conn = get_connection()
        conn.execute(
            """INSERT INTO crawl_jobs (job_uuid, keyword, location, source, quantity_requested)
               VALUES (?, ?, ?, ?, ?)""",
            (job_uuid, keyword, location, source, quantity),
        )
        conn.commit()
        row = conn.execute("SELECT id FROM crawl_jobs WHERE job_uuid = ?", (job_uuid,)).fetchone()
        conn.close()
        return row["id"]

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
        rows = conn.execute(
            "SELECT keyword, location, times_used, total_leads_found FROM keywords ORDER BY times_used DESC LIMIT ?",
            (limit,),
        ).fetchall()
        conn.close()
        return [dict(r) for r in rows]

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
    def create(name, keyword="", location="", goal=100):
        conn = get_connection()
        conn.execute(
            "INSERT INTO campaigns (name, keyword, location, goal) VALUES (?, ?, ?, ?)",
            (name, keyword, location, goal),
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
        allowed = {"name", "keyword", "location", "goal", "status", "description"}
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
