import re
import time
import logging
import requests
from bs4 import BeautifulSoup
from fake_useragent import UserAgent
import os
from crawler.search_engine import SearchEngine
from crawler.utils import safe_request

logger = logging.getLogger("leadhunter.directory")


class DirectoryCrawler:
    def __init__(self):
        self.ua = UserAgent()
        self.delay = int(os.getenv("REQUEST_DELAY_MS", "1500")) / 1000
        self.session = requests.Session()
        self.search_engine = SearchEngine()

    def crawl(self, query, location, quantity, on_progress=None):
        leads = []

        sources = [
            self._crawl_telelistas,
            self._crawl_guiamais,
            self._crawl_via_search,
        ]

        for source_fn in sources:
            if len(leads) >= quantity:
                break
            try:
                results = source_fn(query, location, quantity - len(leads))
                leads.extend(results)
                if on_progress:
                    on_progress(len(leads))
            except Exception as e:
                logger.warning(f"[directory] {source_fn.__name__} falhou: {e}")
                continue

        logger.info(f"[directory] '{query}' — {len(leads)} leads extraídos")
        return leads[:quantity]

    def _crawl_telelistas(self, query, location, limit):
        leads = []
        slug_query = query.lower().replace(" ", "-")
        slug_location = location.lower().replace(" ", "-").replace(",", "") if location else "brasil"

        url = f"https://www.telelistas.net/{slug_location}/{slug_query}"
        self.session.headers["User-Agent"] = self.ua.random

        response = safe_request(self.session, url, timeout=15)
        if not response or response.status_code != 200:
            return leads

        soup = BeautifulSoup(response.text, "lxml")
        listings = soup.select(".result-item, .listing-item, article, .company-item, li[itemscope]")

        for item in listings[:limit]:
            name = ""
            phone = ""
            website = ""

            name_el = item.select_one("h2, h3, .company-name, .title, [itemprop='name']")
            if name_el:
                name = name_el.get_text(strip=True)

            phone_el = item.select_one("a[href^='tel:'], .phone, .telefone, [itemprop='telephone']")
            if phone_el:
                phone = re.sub(r"\D", "", phone_el.get_text(strip=True))

            site_el = item.select_one("a[href*='http']:not([href*='telelistas'])")
            if site_el:
                website = site_el.get("href", "")

            if name and (phone or website):
                leads.append({
                    "name": name,
                    "email": "",
                    "phone": phone,
                    "whatsapp": phone,
                    "website": website,
                    "source": "directory_telelistas",
                    "category": query,
                })

        time.sleep(self.delay)
        logger.info(f"[directory] telelistas: {len(leads)} leads")
        return leads

    def _crawl_guiamais(self, query, location, limit):
        leads = []
        slug_location = (location or "brasil").lower().replace(" ", "-").replace(",", "")
        slug_query = query.lower().replace(" ", "+")

        url = f"https://www.guiamais.com.br/busca/{slug_location}/{slug_query}"
        self.session.headers["User-Agent"] = self.ua.random

        response = safe_request(self.session, url, timeout=15)
        if not response or response.status_code != 200:
            return leads

        soup = BeautifulSoup(response.text, "lxml")
        listings = soup.select(".business-card, .result-card, article, .listing, [itemscope]")

        for item in listings[:limit]:
            name = ""
            phone = ""
            website = ""

            name_el = item.select_one("h2, h3, .business-name, .title, [itemprop='name']")
            if name_el:
                name = name_el.get_text(strip=True)

            phone_el = item.select_one("a[href^='tel:'], .phone, [itemprop='telephone']")
            if phone_el:
                raw = phone_el.get("href", "") or phone_el.get_text(strip=True)
                phone = re.sub(r"\D", "", raw)

            site_el = item.select_one("a.website, a[rel='nofollow'][href^='http']")
            if site_el:
                website = site_el.get("href", "")

            if name and (phone or website):
                leads.append({
                    "name": name,
                    "email": "",
                    "phone": phone,
                    "whatsapp": phone,
                    "website": website,
                    "source": "directory_guiamais",
                    "category": query,
                })

        time.sleep(self.delay)
        logger.info(f"[directory] guiamais: {len(leads)} leads")
        return leads

    def _crawl_via_search(self, query, location, limit):
        leads = []
        search_query = f"{query} {location or ''} telefone contato site".strip()

        urls = self.search_engine.search(search_query, limit * 2)

        from concurrent.futures import ThreadPoolExecutor, as_completed
        
        def _process_url(url):
            try:
                temp_session = requests.Session()
                temp_session.headers["User-Agent"] = self.ua.random
                response = safe_request(temp_session, url, timeout=12)
                if not response or response.status_code != 200:
                    return None

                content_type = response.headers.get("Content-Type", "")
                if "text/html" not in content_type:
                    return None

                soup = BeautifulSoup(response.text, "lxml")
                text = soup.get_text(separator=" ")

                from urllib.parse import urlparse
                domain = urlparse(url).netloc

                name = ""
                og = soup.find("meta", property="og:site_name")
                if og and og.get("content"):
                    name = og["content"].strip()
                elif soup.find("title") and soup.find("title").string:
                    name = soup.find("title").string.strip().split(" | ")[0].split(" - ")[0]
                else:
                    name = domain.replace("www.", "").split(".")[0].title()

                phone_match = re.search(r"\(?\d{2}\)?\s*\d{4,5}[\-\s]?\d{4}", text)
                phone = phone_match.group() if phone_match else ""

                if name and (phone or url):
                    return {
                        "name": name,
                        "email": "",
                        "phone": phone,
                        "whatsapp": "",
                        "website": url,
                        "source": "directory_search",
                        "category": query,
                    }
            except Exception:
                pass
            return None

        with ThreadPoolExecutor(max_workers=5) as executor:
            future_to_url = {executor.submit(_process_url, url): url for url in urls[:limit * 2]}
            for future in as_completed(future_to_url):
                if len(leads) >= limit:
                    break
                res = future.result()
                if res:
                    leads.append(res)

        return leads
