import uuid
import json
import threading
import time
import logging
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

logging.basicConfig(
    level=logging.INFO,
    format="[%(asctime)s] [%(levelname)s] %(name)s — %(message)s",
    datefmt="%Y-%m-%d %H:%M:%S",
    handlers=[
        logging.StreamHandler(),
        logging.FileHandler(
            Config.LOG_FILE if hasattr(Config, "LOG_FILE") else "./data/crawl.log",
            encoding="utf-8",
        ),
    ],
)
logger = logging.getLogger("leadhunter.scheduler")


class Scheduler:
    def __init__(self, verify_interval=3600, crawl_interval=10800):
        self.verify_interval = verify_interval
        self.crawl_interval = crawl_interval
        self.running = False
        self.thread = None
        self.last_crawl = 0
        self.last_verify = 0
        self.status = "stopped"
        self.last_error = None
        self.total_runs = 0
        self.total_successes = 0
        self._load_state()

    def start(self):
        if self.running:
            return
        self.running = True
        self.status = "running"
        self.thread = threading.Thread(target=self._run_loop, daemon=True)
        self.thread.start()
        logger.info("[scheduler] iniciado — verificação a cada %ds, crawl a cada %ds", self.verify_interval, self.crawl_interval)

    def stop(self):
        self.running = False
        self.status = "stopped"
        logger.info("[scheduler] parado")

    def get_status(self):
        now = time.time()
        next_crawl = self.last_crawl + self.crawl_interval - now
        next_verify = self.last_verify + self.verify_interval - now
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
        }

    def _run_loop(self):
        time.sleep(5)
        last_decay = time.time()
        last_campaign_fill = 0

        while self.running:
            now = time.time()

            self._cleanup_stuck_jobs()

            if now - last_campaign_fill >= 300:
                try:
                    self._crawl_campaigns()
                    last_campaign_fill = time.time()
                except Exception as e:
                    logger.warning(f"[scheduler] erro no campaign fill: {e}")

            if now - self.last_verify >= self.verify_interval:
                try:
                    self._verify_unverified_sites()
                    self.last_verify = time.time()
                except Exception as e:
                    logger.error(f"[scheduler] erro na verificação de sites: {e}")
                    self.last_error = str(e)

            if now - self.last_crawl >= self.crawl_interval:
                try:
                    self._auto_crawl()
                    self.last_crawl = time.time()
                    self._save_state()
                except Exception as e:
                    logger.error(f"[scheduler] erro no auto-crawl: {e}")
                    self.last_error = str(e)

            if now - last_decay >= 21600:
                try:
                    self._decay_scores()
                    last_decay = time.time()
                except Exception as e:
                    logger.warning(f"[scheduler] erro no score decay: {e}")

            time.sleep(60)

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
            logger.info(f"[scheduler] score decay: {affected.rowcount} leads rebaixados")
        conn.commit()
        conn.close()

    def _cleanup_stuck_jobs(self):
        from database import get_connection
        conn = get_connection()
        stuck = conn.execute(
            "UPDATE crawl_jobs SET status = 'error', completed_at = datetime('now','localtime') WHERE status = 'running' AND started_at < datetime('now', '-2 minutes', 'localtime')"
        )
        if stuck.rowcount > 0:
            logger.warning(f"[scheduler] {stuck.rowcount} jobs stuck marcados como erro")
        conn.commit()
        conn.close()

        from blueprints.crawl import active_jobs
        stale = [k for k, v in active_jobs.items() if v.get("started_at") and time.time() - v["started_at"] > 300]
        for k in stale:
            del active_jobs[k]

    def _verify_unverified_sites(self):
        unverified = Lead.get_unverified(limit=100)
        if not unverified:
            return
        logger.info(f"[scheduler] verificando {len(unverified)} sites")
        verifier = SiteVerifier(max_workers=5)
        verifier.verify_leads(unverified)
        logger.info("[scheduler] verificação concluída")

        self._enrich_leads()

    def _enrich_leads(self):
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
        logger.info(f"[scheduler] enriquecendo {len(leads)} leads")

        enricher = EnrichmentCrawler()
        enriched_count = 0

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
                enriched_count += 1

            conn.close()

        logger.info(f"[scheduler] enriquecimento: {enriched_count} leads atualizados")

    def _auto_crawl(self):
        self._crawl_campaigns()

        keywords = Keyword.get_for_autocrawl(limit=5)
        if not keywords:
            logger.info("[scheduler] nenhuma keyword para auto-crawl")
            return

        expanded = self._expand_keywords(keywords)
        logger.info(f"[scheduler] auto-crawl iniciado — {len(expanded)} keywords (expandidas de {len(keywords)})")

        for kw in expanded:
            self.total_runs += 1
            retries = 0
            max_retries = 3
            delay = 30

            while retries < max_retries:
                try:
                    count = self._run_multi_source_crawl(kw["keyword"], kw["location"])
                    self.total_successes += 1
                    logger.info(f"[scheduler] '{kw['keyword']}' — {count} leads salvos")
                    break
                except Exception as e:
                    retries += 1
                    self.last_error = f"{kw['keyword']}: {e}"
                    if retries < max_retries:
                        logger.warning(f"[scheduler] retry {retries}/{max_retries} para '{kw['keyword']}' — espera {delay}s")
                        time.sleep(delay)
                        delay *= 2
                    else:
                        logger.error(f"[scheduler] '{kw['keyword']}' falhou após {max_retries} tentativas: {e}")

    def _crawl_campaigns(self):
        from database import get_connection
        conn = get_connection()
        campaigns = conn.execute(
            "SELECT * FROM campaigns WHERE status = 'active'"
        ).fetchall()
        conn.close()

        for camp in campaigns:
            camp = dict(camp)
            conn = get_connection()
            current = conn.execute("SELECT COUNT(*) as c FROM leads WHERE campaign = ?", (camp["name"],)).fetchone()["c"]
            conn.close()

            if current >= camp["goal"]:
                continue

            remaining = camp["goal"] - current
            keyword = camp.get("keyword", "")
            location = camp.get("location", "")

            if not keyword:
                continue

            assigned = self._assign_existing_leads(camp["name"], keyword, location, remaining)
            if assigned >= remaining:
                logger.info(f"[scheduler] campanha '{camp['name']}': meta atingida com leads existentes (+{assigned})")
                continue

            remaining -= assigned
            quantity = min(30, remaining)

            logger.info(f"[scheduler] campanha '{camp['name']}': {current + assigned}/{camp['goal']} — buscando {quantity} novos")

            try:
                count = self._run_multi_source_crawl(keyword, location, campaign_name=camp["name"], quantity_override=quantity)
                logger.info(f"[scheduler] campanha '{camp['name']}': +{count} leads novos")
            except Exception as e:
                logger.warning(f"[scheduler] campanha '{camp['name']}' falhou: {e}")

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
            logger.info(f"[scheduler] campanha '{campaign_name}': {count} leads existentes atribuídos")
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
            logger.info(f"[scheduler] '{keyword}' já tem job running — pulando")
            return 0

        job_uuid = str(uuid.uuid4())
        quantity = quantity_override or 30
        job_id = CrawlJob.create(job_uuid, keyword, location, "both", quantity)
        CrawlJob.set_running(job_uuid)

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
                        logger.info(f"[scheduler] {name}: {len(results)} leads brutos")
                    except Exception as e:
                        logger.warning(f"[scheduler] {name} falhou: {e}")

                for future in futures:
                    future.cancel()
        except Exception as e:
            logger.warning(f"[scheduler] executor timeout para '{keyword}': {e}")

        saved_count = 0
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
        Keyword.track(keyword, location, saved_count)

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
                    logger.info("[scheduler] estado restaurado do disco")
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
            logger.error(f"[scheduler] erro salvando estado: {e}")


scheduler = Scheduler(verify_interval=1800, crawl_interval=1800)
