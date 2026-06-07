import uuid
import json
import threading
import time
import logging
from logging.handlers import RotatingFileHandler
from concurrent.futures import ThreadPoolExecutor, as_completed
from crawler.site_verifier import SiteVerifier
from crawler.website_crawler import WebsiteCrawler
from crawler.maps_crawler import MapsCrawler
from crawler.bing_crawler import BingCrawler
from crawler.directory_crawler import DirectoryCrawler
from crawler.niche_crawler import NicheCrawler
from crawler.duckduckgo_crawler import DuckDuckGoCrawler
from crawler.yellowpages_crawler import YellowPagesCrawler
from crawler.enrichment_crawler import EnrichmentCrawler
from crawler.validator import validate_lead
from crawler.lead_scorer import calculate_score
from models import Lead, Keyword, CrawlJob
from config import Config

file_handler = RotatingFileHandler(
    Config.LOG_FILE if hasattr(Config, "LOG_FILE") else "./data/crawl.log",
    maxBytes=5 * 1024 * 1024,
    backupCount=3,
    encoding="utf-8",
)

logging.basicConfig(
    level=logging.INFO,
    format="[%(asctime)s] [%(levelname)s] %(name)s — %(message)s",
    datefmt="%Y-%m-%d %H:%M:%S",
    handlers=[logging.StreamHandler(), file_handler],
)
logger = logging.getLogger("leadhunter.scheduler")


class CrawlSemaphore:
    """Limits total concurrent crawl jobs system-wide."""

    def __init__(self, max_concurrent=3):
        self._semaphore = threading.Semaphore(max_concurrent)

    def acquire(self, timeout=None):
        return self._semaphore.acquire(timeout=timeout)

    def release(self):
        self._semaphore.release()


crawl_gate = CrawlSemaphore(max_concurrent=3)


class ParallelScheduler:
    """Independent timer-based scheduler — tasks never block each other."""

    def __init__(self):
        self.executor = ThreadPoolExecutor(max_workers=8, thread_name_prefix="sched")
        self.timers = {}
        self.running = False
        self._lock = threading.Lock()
        self._task_locks = {}
        self.last_crawl = 0
        self.last_verify = 0
        self.status = "stopped"
        self.last_error = None
        self.total_runs = 0
        self.total_successes = 0
        self.pipeline = None
        self.event_bus = None
        self._load_state()

    def start(self):
        if self.running:
            return
        self.running = True
        self.status = "running"

        self._schedule_repeating("cleanup", self._cleanup_stuck_jobs, interval=30, initial_delay=5)
        self._schedule_repeating("campaign_fill", self._crawl_campaigns, interval=300, initial_delay=15)
        self._schedule_repeating("verify", self._verify_and_feed_pipeline, interval=Config.SCHEDULER_VERIFY_INTERVAL, initial_delay=20)
        self._schedule_repeating("auto_crawl", self._auto_crawl, interval=Config.SCHEDULER_CRAWL_INTERVAL, initial_delay=30)
        self._schedule_repeating("decay", self._decay_scores, interval=21600, initial_delay=60)
        self._schedule_repeating("enrich", self._feed_enrichment, interval=2700, initial_delay=45)

        logger.info(
            "[scheduler] ParallelScheduler started — 6 tasks registered "
            f"(verify={Config.SCHEDULER_VERIFY_INTERVAL}s, crawl={Config.SCHEDULER_CRAWL_INTERVAL}s)"
        )

    def stop(self):
        self.running = False
        self.status = "stopped"
        with self._lock:
            for timer in self.timers.values():
                timer.cancel()
            self.timers.clear()
        self.executor.shutdown(wait=False)

    def get_status(self):
        now = time.time()
        next_crawl = self.last_crawl + Config.SCHEDULER_CRAWL_INTERVAL - now
        next_verify = self.last_verify + Config.SCHEDULER_VERIFY_INTERVAL - now
        pipeline_status = self.pipeline.status if self.pipeline else {}
        return {
            "running": self.running,
            "status": self.status,
            "last_crawl_at": self.last_crawl,
            "next_crawl_in": max(0, int(next_crawl)),
            "last_verify_at": self.last_verify,
            "next_verify_in": max(0, int(next_verify)),
            "last_error": self.last_error,
            "total_runs": self.total_runs,
            "total_successes": self.total_successes,
            "success_rate": round((self.total_successes / max(1, self.total_runs)) * 100, 1),
            "pipeline": pipeline_status,
        }

    def _schedule_repeating(self, name, func, interval, initial_delay=5):
        self._task_locks[name] = threading.Lock()

        def _run():
            if not self.running:
                return
            self.executor.submit(self._safe_execute, name, func)
            timer = threading.Timer(interval, _run)
            timer.daemon = True
            timer.name = f"timer-{name}"
            with self._lock:
                self.timers[name] = timer
            timer.start()

        timer = threading.Timer(initial_delay, _run)
        timer.daemon = True
        timer.name = f"timer-{name}-init"
        with self._lock:
            self.timers[name] = timer
        timer.start()

    def _safe_execute(self, name, func):
        lock = self._task_locks.get(name)
        if lock and not lock.acquire(blocking=False):
            return
        try:
            func()
        except Exception as e:
            logger.error(f"[scheduler:{name}] error: {e}")
            self.last_error = f"{name}: {e}"
        finally:
            if lock:
                lock.release()

    def _decay_scores(self):
        from database import get_connection
        conn = get_connection()
        affected = conn.execute("""
            UPDATE leads SET
                score = MAX(10, score - 5),
                tier = CASE
                    WHEN MAX(10, score - 5) >= 80 THEN 'hot'
                    WHEN MAX(10, score - 5) >= 50 THEN 'warm'
                    ELSE 'cold' END
            WHERE status = 'new'
            AND created_at < datetime('now', '-30 days', 'localtime')
            AND contacted_at IS NULL
        """)
        if affected.rowcount > 0:
            logger.info(f"[scheduler:decay] {affected.rowcount} leads rebaixados")
        conn.commit()
        conn.close()

    def _cleanup_stuck_jobs(self):
        from database import get_connection
        from job_manager import job_manager

        conn = get_connection()
        stuck = conn.execute(
            "UPDATE crawl_jobs SET status = 'error', completed_at = datetime('now','localtime') "
            "WHERE status = 'running' AND started_at < datetime('now', '-5 minutes', 'localtime')"
        )
        if stuck.rowcount > 0:
            logger.warning(f"[scheduler:cleanup] {stuck.rowcount} stuck jobs marked as error")
        conn.commit()
        conn.close()

        job_manager.cleanup_stale(max_age=300)

    def _verify_and_feed_pipeline(self):
        self.last_verify = time.time()
        if self.pipeline:
            count = self.pipeline.feed_unverified(limit=100)
            if count > 0:
                logger.info(f"[scheduler:verify] fed {count} leads to pipeline verify queue")
        else:
            self._verify_unverified_sites_legacy()

    def _verify_unverified_sites_legacy(self):
        unverified = Lead.get_unverified(limit=100)
        if not unverified:
            return
        logger.info(f"[scheduler:verify] verifying {len(unverified)} sites (legacy)")
        verifier = SiteVerifier(max_workers=5)
        verifier.verify_leads(unverified)

    def _feed_enrichment(self):
        if self.pipeline:
            count = self.pipeline.feed_unenriched(limit=30)
            if count > 0:
                logger.info(f"[scheduler:enrich] fed {count} leads to pipeline enrich queue")
        else:
            self._enrich_leads_legacy()

    def _enrich_leads_legacy(self):
        from database import get_connection
        conn = get_connection()
        rows = conn.execute(
            """SELECT id, name, email, phone, whatsapp, website, instagram, facebook
               FROM leads WHERE website != '' AND (instagram = '' OR instagram IS NULL)
               ORDER BY score DESC LIMIT 30""",
        ).fetchall()
        conn.close()

        if not rows:
            return

        leads = [dict(r) for r in rows]
        logger.info(f"[scheduler:enrich] enriching {len(leads)} leads (legacy)")
        enricher = EnrichmentCrawler()

        for lead_id, data in enricher.enrich_batch(leads, max_count=30):
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
            conn.close()

    def _auto_crawl(self):
        keywords = Keyword.get_for_autocrawl(limit=5)
        if not keywords:
            logger.info("[scheduler:crawl] no keywords for auto-crawl")
            return

        expanded = self._expand_keywords(keywords)[:5]
        logger.info(f"[scheduler:crawl] auto-crawl started — {len(expanded)} keywords")

        for kw in expanded:
            self.total_runs += 1
            retries = 0
            max_retries = 3
            delay = 30

            while retries < max_retries:
                if not crawl_gate.acquire(timeout=60):
                    logger.warning(f"[scheduler:crawl] semaphore timeout for '{kw['keyword']}'")
                    break
                try:
                    count = self._run_multi_source_crawl(kw["keyword"], kw["location"])
                    self.total_successes += 1
                    self.last_crawl = time.time()
                    self._save_state()
                    logger.info(f"[scheduler:crawl] '{kw['keyword']}' — {count} leads saved")
                    break
                except Exception as e:
                    retries += 1
                    self.last_error = f"{kw['keyword']}: {e}"
                    if retries < max_retries:
                        logger.warning(f"[scheduler:crawl] retry {retries}/{max_retries} for '{kw['keyword']}'")
                        time.sleep(delay)
                        delay *= 2
                    else:
                        logger.error(f"[scheduler:crawl] '{kw['keyword']}' failed after {max_retries} retries: {e}")
                finally:
                    crawl_gate.release()

    def _crawl_campaigns(self):
        from database import get_connection
        conn = get_connection()
        campaigns = conn.execute(
            "SELECT * FROM campaigns WHERE status = 'active'"
        ).fetchall()
        conn.close()

        if not campaigns:
            return

        futures = []
        for camp in campaigns:
            camp = dict(camp)
            futures.append(self.executor.submit(self._fill_single_campaign, camp))

        for f in as_completed(futures, timeout=300):
            try:
                f.result()
            except Exception as e:
                logger.warning(f"[scheduler:campaign] error: {e}")

    def _fill_single_campaign(self, camp):
        from database import get_connection
        conn = get_connection()
        current = conn.execute("SELECT COUNT(*) as c FROM leads WHERE campaign = ?", (camp["name"],)).fetchone()["c"]
        conn.close()

        if current >= camp["goal"]:
            return

        remaining = camp["goal"] - current
        keyword = camp.get("keyword", "")
        location = camp.get("location", "")

        if not keyword:
            return

        assigned = self._assign_existing_leads(camp["name"], keyword, location, remaining)
        if assigned >= remaining:
            logger.info(f"[scheduler:campaign] '{camp['name']}': goal reached with existing leads (+{assigned})")
            return

        remaining -= assigned
        quantity = min(30, remaining)

        logger.info(f"[scheduler:campaign] '{camp['name']}': {current + assigned}/{camp['goal']} — fetching {quantity} new")

        if not crawl_gate.acquire(timeout=30):
            logger.warning(f"[scheduler:campaign] '{camp['name']}' — crawl gate busy, skipping")
            return
        try:
            count = self._run_multi_source_crawl(keyword, location, campaign_name=camp["name"], quantity_override=quantity)
            logger.info(f"[scheduler:campaign] '{camp['name']}': +{count} new leads")
            if self.event_bus:
                self.event_bus.publish("campaign_progress", {"name": camp["name"], "added": count, "goal": camp["goal"]})
        except Exception as e:
            logger.warning(f"[scheduler:campaign] '{camp['name']}' failed: {e}")
        finally:
            crawl_gate.release()

    def _expand_keywords(self, keywords):
        bairros = ["centro", "zona norte", "zona sul", "zona leste", "zona oeste"]
        expanded = []
        for kw in keywords:
            expanded.append(kw)
            base = kw["keyword"].split("|")[0].strip()
            loc = kw.get("location", "").split("|")[0].strip()
            if loc:
                bairro = bairros[len(expanded) % len(bairros)]
                expanded.append({"keyword": f"{base} {bairro}", "location": loc})
        return expanded[:10]

    def _assign_existing_leads(self, campaign_name, keyword, location, limit):
        from database import get_connection
        conn = get_connection()
        search = f"%{keyword.split('|')[0].strip()}%"
        rows = conn.execute("""
            SELECT id FROM leads
            WHERE (campaign = '' OR campaign IS NULL)
            AND (category LIKE ? OR name LIKE ?)
            AND (email != '' OR website != '')
            ORDER BY score DESC
            LIMIT ?
        """, (search, search, limit)).fetchall()

        count = 0
        for row in rows:
            conn.execute("UPDATE leads SET campaign = ? WHERE id = ?", (campaign_name, row["id"]))
            count += 1

        if count > 0:
            conn.commit()
        conn.close()
        return count

    def _run_multi_source_crawl(self, keyword, location, campaign_name=None, quantity_override=None):
        if '|' in keyword:
            keyword = keyword.split('|')[0].strip()
        if '|' in (location or ''):
            location = location.split('|')[0].strip()

        if not keyword:
            return 0

        from database import get_connection as _gc
        conn_check = _gc()
        running = conn_check.execute(
            "SELECT COUNT(*) as c FROM crawl_jobs WHERE keyword = ? AND status = 'running'", (keyword,)
        ).fetchone()["c"]
        conn_check.close()
        if running > 0:
            logger.info(f"[scheduler:crawl] '{keyword}' already has a running job — skipping")
            return 0

        job_uuid = str(uuid.uuid4())
        quantity = quantity_override or 30
        job_id = CrawlJob.create(job_uuid, keyword, location, "both", quantity)
        CrawlJob.set_running(job_uuid)

        from job_manager import job_manager
        job_manager.register(job_uuid, quantity)

        leads = []
        fetch_qty = quantity * 2

        sources = {
            "duckduckgo": lambda: DuckDuckGoCrawler().crawl(keyword, fetch_qty),
            "website": lambda: WebsiteCrawler().crawl(keyword, fetch_qty),
            "bing": lambda: BingCrawler().crawl(keyword, fetch_qty),
            "maps": lambda: MapsCrawler().crawl(keyword, location, fetch_qty),
            "directory": lambda: DirectoryCrawler().crawl(keyword, location, fetch_qty),
            "niche": lambda: NicheCrawler().crawl(keyword, location, fetch_qty),
            "yellowpages": lambda: YellowPagesCrawler().crawl(keyword, location, fetch_qty),
        }

        try:
            with ThreadPoolExecutor(max_workers=4) as executor:
                futures = {executor.submit(fn): name for name, fn in sources.items()}

                for future in as_completed(futures, timeout=120):
                    name = futures[future]
                    try:
                        results = future.result(timeout=60)
                        leads.extend(results)
                        job_manager.update(job_uuid, sources={**job_manager.get(job_uuid).get("sources", {}), name: len(results)})
                    except Exception as e:
                        logger.warning(f"[scheduler:crawl] {name} failed: {e}")

                for future in futures:
                    future.cancel()
        except Exception as e:
            logger.warning(f"[scheduler:crawl] executor timeout for '{keyword}': {e}")

        saved_count = 0
        saved_ids = []
        for lead in leads:
            if saved_count >= quantity:
                break

            validated = validate_lead(lead)
            if not validated:
                continue

            if campaign_name:
                has_email = bool(validated.get("email", "").strip())
                has_website = bool(validated.get("website", "").strip())
                if not has_email and not has_website:
                    continue

            score, tier = calculate_score(validated)
            validated["score"] = score
            validated["tier"] = tier
            if campaign_name:
                validated["campaign"] = campaign_name

            lead_id = Lead.create(validated, crawl_job_id=job_id)
            if lead_id:
                saved_ids.append({"id": lead_id, "website": validated.get("website", "")})
                if campaign_name:
                    from database import get_connection as gc
                    c = gc()
                    current_camp = c.execute("SELECT campaign FROM leads WHERE id = ?", (lead_id,)).fetchone()
                    current_val = current_camp["campaign"] if current_camp else ""
                    if current_val != campaign_name:
                        c.execute("UPDATE leads SET campaign = ? WHERE id = ?", (campaign_name, lead_id))
                        c.commit()
                        saved_count += 1
                    c.close()
                else:
                    saved_count += 1

        CrawlJob.complete(job_uuid, saved_count)
        job_manager.complete(job_uuid, saved_count)
        Keyword.track(keyword, location, saved_count)

        if self.pipeline and saved_ids:
            self.pipeline.submit_for_verification(saved_ids)

        if self.event_bus and saved_count > 0:
            self.event_bus.publish("job_completed", {"uuid": job_uuid, "keyword": keyword, "found": saved_count})

        return saved_count

    def _load_state(self):
        try:
            import os
            state_path = os.path.join(os.path.dirname(__file__), "data", "scheduler_state.json")
            if os.path.exists(state_path):
                with open(state_path, "r") as f:
                    state = json.load(f)
                    self.last_crawl = state.get("last_crawl", 0)
                    self.last_verify = state.get("last_verify", 0)
                    self.total_runs = state.get("total_runs", 0)
                    self.total_successes = state.get("total_successes", 0)
                    logger.info("[scheduler] state restored from disk")
        except Exception:
            pass

    def _save_state(self):
        try:
            import os
            state_path = os.path.join(os.path.dirname(__file__), "data", "scheduler_state.json")
            os.makedirs(os.path.dirname(state_path), exist_ok=True)
            with open(state_path, "w") as f:
                json.dump({
                    "last_crawl": self.last_crawl,
                    "last_verify": self.last_verify,
                    "total_runs": self.total_runs,
                    "total_successes": self.total_successes,
                }, f)
        except Exception as e:
            logger.error(f"[scheduler] error saving state: {e}")


scheduler = ParallelScheduler()
