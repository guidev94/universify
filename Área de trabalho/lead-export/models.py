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
            conn.execute(
                """INSERT INTO leads (name, email, phone, whatsapp, website, category, source,
                   score, tier, site_verified, ssl_valid, crawl_job_id, dedup_hash)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                   ON CONFLICT(dedup_hash) DO UPDATE SET updated_at = datetime('now', 'localtime')""",
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
    def get_all(page=1, per_page=25, tier=None, status=None, source=None, search=None, sort="score", order="desc", only_verified=False):
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
        if search:
            conditions.append("(name LIKE ? OR email LIKE ?)")
            params.extend([f"%{search}%", f"%{search}%"])
        if only_verified:
            conditions.append("site_verified = 1 AND website != ''")

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
        conn = get_connection()
        conn.execute(
            """UPDATE leads SET status = 'closed_won', deal_value = ?, deal_notes = ?,
               closed_at = datetime('now', 'localtime'), updated_at = datetime('now', 'localtime')
               WHERE id = ?""",
            (value, notes, lead_id),
        )
        conn.commit()
        conn.close()
        return True

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
        stats["total"] = conn.execute("SELECT COUNT(*) as cnt FROM leads").fetchone()["cnt"]
        stats["hot"] = conn.execute("SELECT COUNT(*) as cnt FROM leads WHERE tier = 'hot'").fetchone()["cnt"]
        stats["warm"] = conn.execute("SELECT COUNT(*) as cnt FROM leads WHERE tier = 'warm'").fetchone()["cnt"]
        stats["cold"] = conn.execute("SELECT COUNT(*) as cnt FROM leads WHERE tier = 'cold'").fetchone()["cnt"]
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
        conn = get_connection()
        rows = conn.execute(
            """SELECT date(created_at) as day, COUNT(*) as cnt
               FROM leads WHERE created_at >= date('now', '-30 days')
               GROUP BY date(created_at) ORDER BY day""",
        ).fetchall()
        chart = {"labels": [r["day"] for r in rows], "values": [r["cnt"] for r in rows]}

        tiers = conn.execute(
            "SELECT tier, COUNT(*) as cnt FROM leads GROUP BY tier",
        ).fetchall()
        chart["tiers"] = {r["tier"]: r["cnt"] for r in tiers}

        conn.close()
        return chart

    @staticmethod
    def get_for_export(tier=None, status=None, quantity=None):
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
        if email or phone:
            raw = f"{email}|{phone}"
        else:
            name = (data.get("name") or "").strip().lower()
            website = (data.get("website") or "").strip().lower()
            raw = f"{name}|{website}"
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
