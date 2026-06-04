import uuid
import threading
import time
from crawler.site_verifier import SiteVerifier
from crawler.website_crawler import WebsiteCrawler
from crawler.maps_crawler import MapsCrawler
from crawler.bing_crawler import BingCrawler
from crawler.directory_crawler import DirectoryCrawler
from crawler.niche_crawler import NicheCrawler
from crawler.validator import validate_lead
from crawler.lead_scorer import calculate_score
from models import Lead, Keyword, CrawlJob
from config import Config


class Scheduler:
    def __init__(self, verify_interval=3600, crawl_interval=10800):
        self.verify_interval = verify_interval
        self.crawl_interval = crawl_interval
        self.running = False
        self.thread = None
        self.last_crawl = 0

    def start(self):
        if self.running:
            return
        self.running = True
        self.thread = threading.Thread(target=self._run_loop, daemon=True)
        self.thread.start()

    def stop(self):
        self.running = False

    def _run_loop(self):
        while self.running:
            try:
                self._verify_unverified_sites()
            except Exception:
                pass

            elapsed = time.time() - self.last_crawl
            if elapsed >= self.crawl_interval:
                try:
                    self._auto_crawl()
                    self.last_crawl = time.time()
                except Exception:
                    pass

            time.sleep(self.verify_interval)

    def _verify_unverified_sites(self):
        unverified = Lead.get_unverified(limit=100)
        if not unverified:
            return
        verifier = SiteVerifier(max_workers=5)
        verifier.verify_leads(unverified)

    def _auto_crawl(self):
        keywords = Keyword.get_for_autocrawl(limit=5)
        if not keywords:
            return

        for kw in keywords:
            try:
                self._run_multi_source_crawl(kw["keyword"], kw["location"])
            except Exception:
                continue

    def _run_multi_source_crawl(self, keyword, location):
        job_uuid = str(uuid.uuid4())
        quantity = 15
        job_id = CrawlJob.create(job_uuid, keyword, location, "both", quantity)
        CrawlJob.set_running(job_uuid)

        leads = []

        sources = [
            ("google", lambda: WebsiteCrawler().crawl(keyword, quantity)),
            ("bing", lambda: BingCrawler().crawl(keyword, quantity)),
            ("maps", lambda: MapsCrawler().crawl(keyword, location, quantity)),
            ("directory", lambda: DirectoryCrawler().crawl(keyword, location, quantity)),
            ("niche", lambda: NicheCrawler().crawl(keyword, location, quantity)),
        ]

        for name, fn in sources:
            try:
                results = fn()
                leads.extend(results)
            except Exception:
                continue

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


scheduler = Scheduler(verify_interval=3600, crawl_interval=10800)
