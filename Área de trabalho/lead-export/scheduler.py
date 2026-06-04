import uuid
import threading
import time
from crawler.site_verifier import SiteVerifier
from models import Lead, Keyword, CrawlJob
from crawler.website_crawler import WebsiteCrawler
from crawler.maps_crawler import MapsCrawler
from crawler.validator import validate_lead
from crawler.lead_scorer import calculate_score
from config import Config


class Scheduler:
    def __init__(self, verify_interval=3600, crawl_interval=21600):
        self.verify_interval = verify_interval
        self.crawl_interval = crawl_interval
        self.running = False
        self.thread = None

    def start(self):
        if self.running:
            return
        self.running = True
        self.thread = threading.Thread(target=self._run_loop, daemon=True)
        self.thread.start()

    def stop(self):
        self.running = False

    def _run_loop(self):
        cycle = 0
        while self.running:
            try:
                self._verify_unverified_sites()
            except Exception:
                pass

            cycle += 1
            if cycle % 6 == 0:
                try:
                    self._auto_crawl()
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
        keywords = Keyword.get_for_autocrawl(limit=3)
        if not keywords:
            return

        for kw in keywords:
            try:
                self._run_single_crawl(kw["keyword"], kw["location"])
            except Exception:
                continue

    def _run_single_crawl(self, keyword, location):
        job_uuid = str(uuid.uuid4())
        quantity = 10
        job_id = CrawlJob.create(job_uuid, keyword, location, "both", quantity)
        CrawlJob.set_running(job_uuid)

        leads = []

        try:
            crawler = WebsiteCrawler()
            site_leads = crawler.crawl(keyword, quantity)
            leads.extend(site_leads)
        except Exception:
            pass

        try:
            maps_crawler = MapsCrawler()
            maps_leads = maps_crawler.crawl(keyword, location, quantity)
            leads.extend(maps_leads)
        except Exception:
            pass

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


scheduler = Scheduler(verify_interval=3600, crawl_interval=21600)
