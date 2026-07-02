import queue
import threading
import logging
from concurrent.futures import ThreadPoolExecutor
from crawler.site_verifier import SiteVerifier
from crawler.enrichment_crawler import EnrichmentCrawler
from crawler.cnpj_enricher import CNPJEnricher
from crawler.deep_scraper import DeepContactScraper
from database import get_connection

logger = logging.getLogger("leadhunter.pipeline")


class LeadPipeline:
    """Async lead processing pipeline: Verify → Enrich."""

    def __init__(self, event_bus=None):
        self.event_bus = event_bus
        self.verify_queue = queue.Queue(maxsize=2000)
        self.enrich_queue = queue.Queue(maxsize=1000)
        
        # Aumentando workers para paralelismo real em I/O
        self.deep_executor = ThreadPoolExecutor(max_workers=20, thread_name_prefix="pipeline-deep")
        self.enrich_executor = ThreadPoolExecutor(max_workers=20, thread_name_prefix="pipeline-enrich")
        self.cnpj_executor = ThreadPoolExecutor(max_workers=5, thread_name_prefix="pipeline-cnpj")
        
        # Verifier com mais workers internos
        self.verifier = SiteVerifier(max_workers=15)
        self.enricher = EnrichmentCrawler()
        self.cnpj_enricher = CNPJEnricher()
        self.deep_scraper = DeepContactScraper()
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
        self.deep_executor.shutdown(wait=False)

    def submit_for_verification(self, leads):
        """Submit leads for site verification. leads = list of {id, website}."""
        for lead in leads:
            if lead.get("website"):
                self.verify_queue.put(lead)

    def submit_for_enrichment(self, leads):
        """Submit leads for social enrichment. leads = list of {id, website, ...}."""
        for lead in leads:
            self.enrich_queue.put(lead)

    def feed_unverified(self, limit=200):
        """Pull unverified leads from DB and push to verify queue."""
        conn = get_connection()
        # Only pick leads that haven't been updated in the last 12 hours
        rows = conn.execute(
            """SELECT id, website FROM leads 
               WHERE site_verified = 0 AND website != '' 
               AND (updated_at < datetime('now', '-12 hours', 'localtime') OR updated_at = created_at)
               LIMIT ?""",
            (limit,),
        ).fetchall()
        conn.close()
        count = 0
        for row in rows:
            self.verify_queue.put(dict(row))
            count += 1
        return count

    def feed_unenriched(self, limit=100):
        """Pull verified but unenriched leads and push to enrich queue."""
        conn = get_connection()
        # Filter by updated_at to avoid re-enriching failed attempts too soon
        rows = conn.execute(
            """SELECT id, name, email, phone, whatsapp, website, instagram, facebook
               FROM leads WHERE website != '' AND site_verified = 1
               AND (instagram = '' OR instagram IS NULL)
               AND (updated_at < datetime('now', '-24 hours', 'localtime') OR updated_at IS NULL OR updated_at = created_at)
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
        logger.info(f"[pipeline:verify] processing batch of {len(leads)} leads")
        
        def on_lead_done(lead_id, site_verified):
            if self.event_bus:
                 self.event_bus.publish("lead_verified", {"id": lead_id, "site_verified": site_verified})

        try:
            self.verifier.verify_leads(leads, on_lead_complete=on_lead_done)
        except Exception as e:
            logger.error(f"[pipeline:verify] verifier error: {e}")

        conn = get_connection()
        try:
            # Batch select to avoid N+1
            ids = [l["id"] for l in leads]
            placeholders = ",".join("?" * len(ids))
            rows = conn.execute(
                f"SELECT id, name, website, site_verified FROM leads WHERE id IN ({placeholders})", ids
            ).fetchall()
            
            for row in rows:
                row_dict = dict(row)
                if row_dict["site_verified"]:
                    logger.debug(f"[pipeline:verify] lead {row_dict['id']} verified, feeding to enrich")
                    self.enrich_queue.put(row_dict)
                
                # CNPJ Enrichment Trigger (Background, limited workers)
                try:
                    self.cnpj_executor.submit(self.cnpj_enricher.enrich_lead, row_dict["id"], row_dict["name"])
                except Exception as e:
                    logger.debug(f"[pipeline:cnpj] error triggering enrichment: {e}")

            conn.close()
        except Exception as e:
            logger.error(f"[pipeline:verify] DB loop error: {e}")
            if conn: conn.close()

        if self.event_bus:
            self.event_bus.publish("pipeline_verify", {"count": len(leads)})

        logger.info(f"[pipeline:verify] batch of {len(leads)} done")

    def _enrich_batch(self, leads):
        if not leads:
            return
        logger.info(f"[pipeline:enrich] processing {len(leads)} leads")
        
        def process_single_enrich(lead):
            try:
                data = self.enricher.enrich_lead(lead)
                if not data:
                    return False
                
                lead_id = lead["id"]
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
                
                # Fetch website for deep scraper
                row = conn.execute("SELECT website FROM leads WHERE id = ?", (lead_id,)).fetchone()
                if row and row["website"]:
                    try:
                        self.deep_executor.submit(self.deep_scraper.scrape_additional_info, lead_id, row["website"])
                    except Exception as e:
                        logger.debug(f"[pipeline:deep] error triggering: {e}")
                
                conn.close()
                
                if self.event_bus:
                    self.event_bus.publish("lead_enriched", {"id": lead_id, "success": True})
                return True
            except Exception as e:
                logger.warning(f"[pipeline:enrich] lead {lead.get('id')} failed: {e}")
                return False

        # Parallel enrichment
        futures = [self.enrich_executor.submit(process_single_enrich, l) for l in leads]
        enriched_count = sum(1 for f in futures if f.result())

        if self.event_bus:
            self.event_bus.publish("pipeline_enrich", {"count": enriched_count})

        logger.info(f"[pipeline:enrich] done — {enriched_count} enriched")
