import queue
import threading
import logging
from crawler.site_verifier import SiteVerifier
from crawler.enrichment_crawler import EnrichmentCrawler
from database import get_connection

logger = logging.getLogger("leadhunter.pipeline")


class LeadPipeline:
    """Async lead processing pipeline: Verify → Enrich."""

    def __init__(self, event_bus=None):
        self.event_bus = event_bus
        self.verify_queue = queue.Queue()
        self.enrich_queue = queue.Queue()
        self._running = False

    def start(self):
        if self._running:
            return
        self._running = True
        threading.Thread(target=self._verify_loop, daemon=True, name="pipeline-verify").start()
        threading.Thread(target=self._enrich_loop, daemon=True, name="pipeline-enrich").start()
        logger.info("[pipeline] started — verify + enrich workers active")

    def stop(self):
        self._running = False

    def submit_for_verification(self, leads):
        """Submit leads for site verification. leads = list of {id, website}."""
        for lead in leads:
            if lead.get("website"):
                self.verify_queue.put(lead)

    def submit_for_enrichment(self, leads):
        """Submit leads for social enrichment. leads = list of {id, website, ...}."""
        for lead in leads:
            self.enrich_queue.put(lead)

    def feed_unverified(self, limit=100):
        """Pull unverified leads from DB and push to verify queue."""
        conn = get_connection()
        rows = conn.execute(
            "SELECT id, website FROM leads WHERE site_verified = 0 AND website != '' LIMIT ?",
            (limit,),
        ).fetchall()
        conn.close()
        count = 0
        for row in rows:
            self.verify_queue.put(dict(row))
            count += 1
        return count

    def feed_unenriched(self, limit=30):
        """Pull verified but unenriched leads and push to enrich queue."""
        conn = get_connection()
        rows = conn.execute(
            """SELECT id, name, email, phone, whatsapp, website, instagram, facebook
               FROM leads WHERE website != '' AND site_verified = 1
               AND (instagram = '' OR instagram IS NULL)
               ORDER BY score DESC LIMIT ?""",
            (limit,),
        ).fetchall()
        conn.close()
        count = 0
        for row in rows:
            self.enrich_queue.put(dict(row))
            count += 1
        return count

    @property
    def status(self):
        return {
            "verify_pending": self.verify_queue.qsize(),
            "enrich_pending": self.enrich_queue.qsize(),
            "running": self._running,
        }

    def _verify_loop(self):
        while self._running:
            batch = []
            try:
                item = self.verify_queue.get(timeout=10)
                batch.append(item)
                while len(batch) < 20:
                    try:
                        batch.append(self.verify_queue.get_nowait())
                    except queue.Empty:
                        break
                self._verify_batch(batch)
            except queue.Empty:
                continue
            except Exception as e:
                logger.error(f"[pipeline:verify] error: {e}")

    def _enrich_loop(self):
        while self._running:
            batch = []
            try:
                item = self.enrich_queue.get(timeout=15)
                batch.append(item)
                while len(batch) < 10:
                    try:
                        batch.append(self.enrich_queue.get_nowait())
                    except queue.Empty:
                        break
                self._enrich_batch(batch)
            except queue.Empty:
                continue
            except Exception as e:
                logger.error(f"[pipeline:enrich] error: {e}")

    def _verify_batch(self, leads):
        if not leads:
            return
        logger.info(f"[pipeline:verify] processing {len(leads)} leads")
        verifier = SiteVerifier(max_workers=5)
        verifier.verify_leads(leads)

        verified_ids = [l["id"] for l in leads]
        conn = get_connection()
        for lid in verified_ids:
            row = conn.execute(
                "SELECT id, website, site_verified FROM leads WHERE id = ?", (lid,)
            ).fetchone()
            if row and row["site_verified"]:
                self.enrich_queue.put(dict(row))
        conn.close()

        if self.event_bus:
            self.event_bus.publish("pipeline_verify", {"count": len(leads)})

        logger.info(f"[pipeline:verify] done — {len(leads)} verified")

    def _enrich_batch(self, leads):
        if not leads:
            return
        logger.info(f"[pipeline:enrich] processing {len(leads)} leads")
        enricher = EnrichmentCrawler()
        enriched_count = 0

        for lead_id, data in enricher.enrich_batch(leads, max_count=len(leads)):
            try:
                conn = get_connection()
                updates = []
                params = []

                if data.get("instagram"):
                    updates.append("instagram = ?")
                    params.append(data["instagram"])
                if data.get("facebook"):
                    updates.append("facebook = ?")
                    params.append(data["facebook"])
                if data.get("whatsapp") or data.get("contact_whatsapp"):
                    updates.append("whatsapp = ?")
                    params.append(data.get("whatsapp") or data.get("contact_whatsapp"))

                new_emails = data.get("new_emails", []) + data.get("contact_emails", [])
                if new_emails:
                    existing = conn.execute("SELECT email FROM leads WHERE id = ?", (lead_id,)).fetchone()
                    current = (existing["email"] or "") if existing else ""
                    all_emails = set(current.split(";")) | set(new_emails)
                    all_emails.discard("")
                    merged = ";".join(sorted(all_emails)[:5])
                    updates.append("email = ?")
                    params.append(merged)

                if updates:
                    updates.append("updated_at = datetime('now', 'localtime')")
                    params.append(lead_id)
                    conn.execute(f"UPDATE leads SET {', '.join(updates)} WHERE id = ?", params)
                    conn.commit()
                    enriched_count += 1
                conn.close()
            except Exception as e:
                logger.warning(f"[pipeline:enrich] lead {lead_id} failed: {e}")

        if self.event_bus:
            self.event_bus.publish("pipeline_enrich", {"count": enriched_count})

        logger.info(f"[pipeline:enrich] done — {enriched_count} enriched")
