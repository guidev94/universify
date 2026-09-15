import queue
import threading
import logging
import time
from concurrent.futures import ThreadPoolExecutor, as_completed
from crawler.site_verifier import SiteVerifier
from crawler.enrichment_crawler import EnrichmentCrawler
from crawler.cnpj_enricher import CNPJEnricher
from crawler.deep_scraper import DeepContactScraper
from database import get_connection
from config import Config

logger = logging.getLogger("leadhunter.pipeline")


class LeadPipeline:
    """Pipeline assíncrono de processamento de leads: Verify → Enrich → Deep Scrape.

    Otimizações:
    - Filas maiores para nunca bloquear producers
    - Batch sizes maiores para throughput
    - Workers ajustáveis via Config
    - Auto-feed interno (não depende apenas do scheduler)
    """

    def __init__(self, event_bus=None):
        self.event_bus = event_bus

        # Filas com capacidade generosa para nunca bloquear
        self.verify_queue = queue.Queue(maxsize=Config.PIPELINE_VERIFY_QUEUE_SIZE)
        self.enrich_queue = queue.Queue(maxsize=Config.PIPELINE_ENRICH_QUEUE_SIZE)

        # Executors com workers otimizados para I/O-bound tasks
        self.deep_executor = ThreadPoolExecutor(
            max_workers=Config.PIPELINE_ENRICH_WORKERS,
            thread_name_prefix="pipeline-deep"
        )
        self.enrich_executor = ThreadPoolExecutor(
            max_workers=Config.PIPELINE_ENRICH_WORKERS,
            thread_name_prefix="pipeline-enrich"
        )
        self.cnpj_executor = ThreadPoolExecutor(
            max_workers=10, thread_name_prefix="pipeline-cnpj"
        )

        # Verifier com workers generosos
        self.verifier = SiteVerifier(max_workers=Config.PIPELINE_VERIFY_WORKERS)
        self.enricher = EnrichmentCrawler()
        self.cnpj_enricher = CNPJEnricher()
        self.deep_scraper = DeepContactScraper()
        self._running = False

        # Métricas internas
        self._stats = {
            "verified_total": 0,
            "enriched_total": 0,
            "deep_scraped_total": 0,
            "verify_errors": 0,
            "enrich_errors": 0,
        }

    def start(self):
        if self._running:
            return
        self._running = True

        # Workers de verificação (aumentado para 4 threads consumidoras)
        for i in range(4):
            threading.Thread(
                target=self._verify_loop,
                daemon=True,
                name=f"pipeline-verify-{i}"
            ).start()

        # Workers de enriquecimento (3 threads consumidoras)
        for i in range(3):
            threading.Thread(
                target=self._enrich_loop,
                daemon=True,
                name=f"pipeline-enrich-{i}"
            ).start()

        # Auto-feeder: alimenta as filas periodicamente sem depender do scheduler
        threading.Thread(
            target=self._auto_feed_loop,
            daemon=True,
            name="pipeline-autofeed"
        ).start()

        logger.info("[pipeline] started — 2 verify + 2 enrich workers + auto-feeder active")

    def stop(self):
        self._running = False
        self.deep_executor.shutdown(wait=False)
        self.enrich_executor.shutdown(wait=False)
        self.cnpj_executor.shutdown(wait=False)

    def submit_for_verification(self, leads):
        """Submit leads for site verification. leads = list of {id, website}."""
        submitted = 0
        for lead in leads:
            if lead.get("website"):
                try:
                    self.verify_queue.put_nowait(lead)
                    submitted += 1
                except queue.Full:
                    # Fila cheia — dropa os mais antigos não é ideal, mas previne deadlock
                    logger.warning("[pipeline] verify queue full, dropping oldest items")
                    try:
                        for _ in range(min(100, len(leads))):
                            self.verify_queue.get_nowait()
                    except queue.Empty:
                        pass
                    self.verify_queue.put_nowait(lead)
                    submitted += 1
        return submitted

    def submit_for_enrichment(self, leads):
        """Submit leads for social enrichment."""
        submitted = 0
        for lead in leads:
            try:
                self.enrich_queue.put_nowait(lead)
                submitted += 1
            except queue.Full:
                break
        return submitted

    def feed_unverified(self, limit=500):
        """Pull unverified leads from DB and push to verify queue."""
        if self.verify_queue.qsize() > Config.PIPELINE_VERIFY_QUEUE_SIZE * 0.7:
            return 0  # Fila já está bem cheia, não precisa alimentar

        conn = get_connection()
        rows = conn.execute(
            """SELECT id, website FROM leads 
               WHERE site_verified = 0 AND website != '' 
               AND (updated_at < datetime('now', '-6 hours', 'localtime') OR updated_at = created_at)
               ORDER BY score DESC
               LIMIT ?""",
            (limit,),
        ).fetchall()
        conn.close()

        count = 0
        for row in rows:
            try:
                self.verify_queue.put_nowait(dict(row))
                count += 1
            except queue.Full:
                break
        return count

    def feed_unenriched(self, limit=300):
        """Pull verified but unenriched leads and push to enrich queue."""
        if self.enrich_queue.qsize() > Config.PIPELINE_ENRICH_QUEUE_SIZE * 0.7:
            return 0

        conn = get_connection()
        rows = conn.execute(
            """SELECT id, name, email, phone, whatsapp, website, instagram, facebook
               FROM leads WHERE website != '' AND site_verified = 1
               AND (instagram = '' OR instagram IS NULL)
               AND (updated_at < datetime('now', '-12 hours', 'localtime') OR updated_at IS NULL OR updated_at = created_at)
               ORDER BY score DESC LIMIT ?""",
            (limit,),
        ).fetchall()
        conn.close()

        count = 0
        for row in rows:
            try:
                self.enrich_queue.put_nowait(dict(row))
                count += 1
            except queue.Full:
                break
        return count

    @property
    def status(self):
        return {
            "verify_pending": self.verify_queue.qsize(),
            "enrich_pending": self.enrich_queue.qsize(),
            "running": self._running,
            "verified_total": self._stats["verified_total"],
            "enriched_total": self._stats["enriched_total"],
        }

    # ─── Auto Feed Loop ──────────────────────────────────────────────────────

    def _auto_feed_loop(self):
        """Alimenta as filas automaticamente a cada 30s se estiverem vazias."""
        while self._running:
            try:
                if self.verify_queue.qsize() < 50:
                    self.feed_unverified(limit=500)
                if self.enrich_queue.qsize() < 30:
                    self.feed_unenriched(limit=300)
            except Exception as e:
                logger.error(f"[pipeline:autofeed] error: {e}")
            time.sleep(30)

    # ─── Verify Loop ─────────────────────────────────────────────────────────

    def _verify_loop(self):
        while self._running:
            batch = []
            try:
                # Espera pelo primeiro item
                item = self.verify_queue.get(timeout=5)
                batch.append(item)
                # Coleta mais itens sem esperar (batch)
                while len(batch) < Config.PIPELINE_VERIFY_BATCH:
                    try:
                        batch.append(self.verify_queue.get_nowait())
                    except queue.Empty:
                        break
                self._verify_batch(batch)
            except queue.Empty:
                continue
            except Exception as e:
                logger.error(f"[pipeline:verify] error: {e}")
                self._stats["verify_errors"] += 1

    # ─── Enrich Loop ─────────────────────────────────────────────────────────

    def _enrich_loop(self):
        while self._running:
            batch = []
            try:
                item = self.enrich_queue.get(timeout=5)
                batch.append(item)
                while len(batch) < Config.PIPELINE_ENRICH_BATCH:
                    try:
                        batch.append(self.enrich_queue.get_nowait())
                    except queue.Empty:
                        break
                self._enrich_batch(batch)
            except queue.Empty:
                continue
            except Exception as e:
                logger.error(f"[pipeline:enrich] error: {e}")
                self._stats["enrich_errors"] += 1

    # ─── Batch Processing ────────────────────────────────────────────────────

    def _verify_batch(self, leads):
        if not leads:
            return
        logger.info(f"[pipeline:verify] processing batch of {len(leads)} leads")

        try:
            results = self.verifier.verify_batch(leads)
        except Exception as e:
            logger.error(f"[pipeline:verify] verifier error: {e}")
            results = []

        verified_ids = []
        if results:
            from crawler.lead_scorer import calculate_score
            conn = get_connection()
            try:
                # Otimização: SELECT inicial em lote para todos os IDs
                placeholders = ",".join("?" * len(results))
                lids = [r["id"] for r in results]
                rows = conn.execute(f"SELECT * FROM leads WHERE id IN ({placeholders})", lids).fetchall()
                leads_map = {row["id"]: dict(row) for row in rows}

                with conn: # Transação única para todo o batch
                    for res in results:
                        lid = res["id"]
                        lead_data = leads_map.get(lid)
                        if not lead_data: continue
                            
                        lead_data.update(res)
                        new_score, new_tier, pitch_hook = calculate_score(lead_data)
                        
                        conn.execute(
                            """UPDATE leads SET
                                site_verified = ?, site_status = ?, ssl_valid = ?, ssl_expired = ?,
                                copyright_year = ?, is_responsive = ?, site_quality = ?,
                                cms = ?, has_pixel = ?, has_analytics = ?, has_ads = ?,
                                ads_platforms = ?, tech_score = ?, score = ?, tier = ?,
                                pitch_hook = ?, updated_at = datetime('now', 'localtime')
                                WHERE id = ?""",
                            (res["site_verified"], res.get("site_status", ""), res["ssl_valid"],
                             res.get("ssl_expired", 0), res.get("copyright_year", 0),
                             res.get("is_responsive", 1), res["site_quality"],
                             res["cms"], res["has_pixel"], res["has_analytics"],
                             res["has_ads"], res["ads_platforms"], res["tech_score"],
                             new_score, new_tier, pitch_hook, lid)
                        )
                        
                        if res["site_verified"]:
                            verified_ids.append(lid)
                            if self.event_bus:
                                self.event_bus.publish("lead_verified", {"id": lid, "score": new_score, "tier": new_tier})
                                
                        # CNPJ Enrichment (background - keep thread limit in mind)
                        try:
                            self.cnpj_executor.submit(self.cnpj_enricher.enrich_lead, lid, lead_data["name"])
                        except Exception: pass
            except Exception as e:
                logger.error(f"[pipeline:verify] DB update error: {e}")
            finally:
                conn.close()


        # Push para enriquecimento
        if verified_ids:
            conn = get_connection()
            try:
                placeholders = ",".join("?" * len(verified_ids))
                rows = conn.execute(
                    f"SELECT id, name, email, phone, whatsapp, website, instagram, facebook FROM leads WHERE id IN ({placeholders})", 
                    verified_ids
                ).fetchall()
                for row in rows:
                    try:
                        self.enrich_queue.put_nowait(dict(row))
                    except queue.Full:
                        break
            finally:
                conn.close()

        self._stats["verified_total"] += len(leads)
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
                    return None
                return {"id": lead["id"], "data": data, "website": lead.get("website")}
            except Exception as e:
                logger.debug(f"[pipeline:enrich] lead {lead.get('id')} failed: {e}")
                return None

        # Processamento paralelo de enriquecimento
        futures = [self.enrich_executor.submit(process_single_enrich, l) for l in leads]
        results = []
        
        try:
            for f in as_completed(futures, timeout=180):
                try:
                    res = f.result()
                    if res:
                        results.append(res)
                except Exception as e:
                    logger.debug(f"[pipeline:enrich] individual lead failed: {e}")
        except TimeoutError:
            unfinished = len([f for f in futures if not f.done()])
            logger.error(f"[pipeline:enrich] timeout: {unfinished} leads unfinished")
        except Exception as e:
            logger.error(f"[pipeline:enrich] batch error: {e}")

        # Atualização em Batch (dentro de uma única transação)
        enriched_count = 0
        if results:
            conn = get_connection()
            try:
                # Otimização: Carrega emails existentes em lote
                placeholders = ",".join("?" * len(results))
                lids = [r["id"] for r in results]
                rows = conn.execute(f"SELECT id, email FROM leads WHERE id IN ({placeholders})", lids).fetchall()
                email_map = {row["id"]: (row["email"] or "") for row in rows}

                with conn: # Inicia transação
                    for res in results:
                        lead_id = res["id"]
                        data = res["data"]
                        website = res["website"]
                        
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
                            current = email_map.get(lead_id, "")
                            all_emails = set(current.split(";")) | set(new_emails)
                            all_emails.discard("")
                            merged = ";".join(sorted(all_emails)[:5])
                            updates.append("email = ?")
                            params.append(merged)

                        if updates:
                            updates.append("updated_at = datetime('now', 'localtime')")
                            params.append(lead_id)
                            conn.execute(f"UPDATE leads SET {', '.join(updates)} WHERE id = ?", params)
                            enriched_count += 1

                        # Deep scraper e Eventos ainda podem ser disparados aqui ou fora
                        if website:
                            def _deep_run(lid, web):
                                try:
                                    self.deep_scraper.scrape_additional_info(lid, web)
                                    self._stats["deep_scraped_total"] += 1
                                    if self.event_bus:
                                        self.event_bus.publish("lead_deep_scraped", {"id": lid})
                                except Exception: pass

                            self.deep_executor.submit(_deep_run, lead_id, website)
                        
                        if self.event_bus:
                            self.event_bus.publish("lead_enriched", {"id": lead_id, "success": True})
            except Exception as e:
                logger.error(f"[pipeline:enrich] DB batch update error: {e}")
            finally:
                conn.close()

        self._stats["enriched_total"] += enriched_count

        if self.event_bus:
            self.event_bus.publish("pipeline_enrich", {"count": enriched_count})

        logger.info(f"[pipeline:enrich] done — {enriched_count}/{len(leads)} enriched")
