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

        while self.running:
            now = time.time()

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

            time.sleep(60)

    def _verify_unverified_sites(self):
        unverified = Lead.get_unverified(limit=100)
        if not unverified:
            return
        logger.info(f"[scheduler] verificando {len(unverified)} sites")
        verifier = SiteVerifier(max_workers=5)
        verifier.verify_leads(unverified)
        logger.info("[scheduler] verificação concluída")

    def _auto_crawl(self):
        keywords = Keyword.get_for_autocrawl(limit=7)
        if not keywords:
            logger.info("[scheduler] nenhuma keyword para auto-crawl")
            return

        logger.info(f"[scheduler] auto-crawl iniciado — {len(keywords)} keywords")

        for kw in keywords:
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

    def _run_multi_source_crawl(self, keyword, location):
        job_uuid = str(uuid.uuid4())
        quantity = 30
        job_id = CrawlJob.create(job_uuid, keyword, location, "both", quantity)
        CrawlJob.set_running(job_uuid)

        leads = []

        sources = {
            "duckduckgo": lambda: DuckDuckGoCrawler().crawl(keyword, quantity),
            "website": lambda: WebsiteCrawler().crawl(keyword, quantity),
            "bing": lambda: BingCrawler().crawl(keyword, quantity),
            "maps": lambda: MapsCrawler().crawl(keyword, location, quantity),
            "directory": lambda: DirectoryCrawler().crawl(keyword, location, quantity),
            "niche": lambda: NicheCrawler().crawl(keyword, location, quantity),
        }

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

        saved_count = 0
        for lead in leads:
            if saved_count >= quantity:
                break

            validated = validate_lead(lead)
            if not validated:
                continue

            score, tier = calculate_score(validated)
            validated["score"] = score
            validated["tier"] = tier

            lead_id = Lead.create(validated, crawl_job_id=job_id)
            if lead_id:
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


scheduler = Scheduler(verify_interval=3600, crawl_interval=10800)
