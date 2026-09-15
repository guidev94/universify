import re
import time
import logging
import requests
from bs4 import BeautifulSoup
from fake_useragent import UserAgent
from urllib.parse import urlparse
import os
from crawler.utils import (
    safe_request,
    extract_emails_from_text,
    extract_phones_from_text,
    extract_whatsapp_from_html,
    extract_business_name,
)

logger = logging.getLogger("leadhunter.bing")


class BingCrawler:
    def __init__(self):
        self.ua = UserAgent()
        self.delay = int(os.getenv("REQUEST_DELAY_MS", "1500")) / 1000
        self.session = requests.Session()

    def crawl(self, query, quantity, on_progress=None):
        leads = []
        urls = self._search_bing(query, quantity * 3)

        logger.info(f"[bing] buscando '{query}' — {len(urls)} URLs encontradas")

        from concurrent.futures import ThreadPoolExecutor, as_completed
        from config import Config

        with ThreadPoolExecutor(max_workers=Config.CRAWLER_WORKERS) as executor:
            future_to_url = {executor.submit(self._extract_from_url, url): url for url in urls}

            for future in as_completed(future_to_url, timeout=90):
                if len(leads) >= quantity:
                    break
                try:
                    page_leads = future.result(timeout=Config.CRAWLER_TIMEOUT + 2)
                    if page_leads:
                        leads.extend(page_leads)
                        if on_progress:
                            on_progress(len(leads))
                except Exception:
                    continue

        logger.info(f"[bing] '{query}' — {len(leads)} leads extraídos")
        return leads[:quantity]

    def _search_bing(self, query, num_results):
        urls = []
        
        # Enhanced Dorks for technical obsolescence and high-ticket niches
        dorks = [
            f'"{query}" site:.com.br "© 2017..2022"',
            f'"{query}" site:.com.br "melhor visualizado"',
            f'"{query}" "contato" "11"',
            f'"{query}" site:.com.br "desenvolvido por"',
            f'"{query}" "anuncie aqui"',
            f'"{query}" "whatsapp" "com.br"',
            f'"{query}" "atendimento"',
        ]
        
        # Select 2 random dorks and combine them or rotate faster
        import random
        search_query = random.choice(dorks)
        logger.info(f"[bing] Using dork: {search_query}")
        
        page = 0

        while len(urls) < num_results and page < 5:
            self.session.headers["User-Agent"] = self.ua.random
            params = {"q": search_query, "first": page * 10 + 1, "setlang": "pt-BR"}

            response = safe_request(self.session, "https://www.bing.com/search", params=params, timeout=15)

            if not response:
                page += 1
                continue

            if response.status_code == 429:
                logger.warning("[bing] rate limited (429) — aguardando 30s")
                time.sleep(30)
                page += 1
                continue

            if response.status_code != 200:
                break

            soup = BeautifulSoup(response.text, "lxml")

            found_any = False
            for selector in ["li.b_algo h2 a", "#b_results .b_algo a", "h2 a[href^='http']"]:
                for link in soup.select(selector):
                    href = link.get("href", "")
                    if href.startswith("http") and self._is_valid_url(href):
                        urls.append(href)
                        found_any = True

            if not found_any:
                logger.debug(f"[bing] nenhum resultado na página {page}")

            page += 1
            time.sleep(self.delay * 2)

        return urls

    def _is_valid_url(self, url):
        excluded = {
            "bing.com", "microsoft.com", "google.com", "youtube.com",
            "facebook.com", "instagram.com", "twitter.com", "linkedin.com",
            "wikipedia.org",
        }
        try:
            domain = urlparse(url).netloc.lower()
            return not any(ex in domain for ex in excluded)
        except Exception:
            return False

    def _extract_from_url(self, url):
        leads = []
        self.session.headers["User-Agent"] = self.ua.random

        response = safe_request(self.session, url, timeout=12)
        if not response or response.status_code != 200:
            return leads

        content_type = response.headers.get("Content-Type", "")
        if "text/html" not in content_type:
            return leads

        soup = BeautifulSoup(response.text, "lxml")
        text = soup.get_text(separator=" ")
        domain = urlparse(url).netloc

        emails = extract_emails_from_text(text, response.text)
        phones = extract_phones_from_text(text)
        whatsapp = extract_whatsapp_from_html(response.text)
        name = extract_business_name(soup, domain)

        if emails or phones:
            leads.append({
                "name": name,
                "email": emails[0] if emails else "",
                "phone": phones[0] if phones else "",
                "whatsapp": whatsapp or (phones[0] if phones else ""),
                "website": url,
                "source": "bing",
                "category": "",
            })

        return leads
