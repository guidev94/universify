import re
import time
import logging
import requests
from bs4 import BeautifulSoup
from fake_useragent import UserAgent
from urllib.parse import urlparse, quote
import os
from crawler.utils import (
    safe_request,
    extract_emails_from_text,
    extract_phones_from_text,
    extract_whatsapp_from_html,
    extract_business_name,
    extract_social_from_html,
)

logger = logging.getLogger("leadhunter.yellowpages")


class YellowPagesCrawler:
    """Crawler para Páginas Amarelas, iLocal, e listas empresariais BR."""

    def __init__(self):
        self.ua = UserAgent()
        self.delay = int(os.getenv("REQUEST_DELAY_MS", "1500")) / 1000
        self.session = requests.Session()
        self.session.headers.update({
            "Accept": "text/html,application/xhtml+xml",
            "Accept-Language": "pt-BR,pt;q=0.9",
        })

    def crawl(self, query, location, quantity, on_progress=None):
        leads = []

        sources = [
            self._crawl_hagah,
            self._crawl_cylex,
            self._crawl_hotfrog,
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
                logger.warning(f"[yellowpages] {source_fn.__name__} falhou: {e}")
                continue

        logger.info(f"[yellowpages] '{query}' — {len(leads)} leads")
        return leads[:quantity]

    def _crawl_hagah(self, query, location, limit):
        """Hagah/ClicRBS — diretório do Sul do Brasil."""
        leads = []
        slug = quote(f"{query} {location}".strip())
        url = f"https://www.hagah.com.br/busca?q={slug}"

        self.session.headers["User-Agent"] = self.ua.random
        response = safe_request(self.session, url, timeout=15)
        if not response or response.status_code != 200:
            return leads

        soup = BeautifulSoup(response.text, "lxml")
        items = soup.select("article, .result-item, .business-card, [itemscope]")

        for item in items[:limit]:
            name = ""
            phone = ""
            website = ""

            name_el = item.select_one("h2, h3, .title, [itemprop='name']")
            if name_el:
                name = name_el.get_text(strip=True)

            phone_el = item.select_one("a[href^='tel:'], [itemprop='telephone'], .phone")
            if phone_el:
                phone = re.sub(r"\D", "", phone_el.get_text(strip=True))

            link_el = item.select_one("a[href^='http']:not([href*='hagah'])")
            if link_el:
                website = link_el.get("href", "")

            if name and (phone or website):
                leads.append({
                    "name": name,
                    "email": "",
                    "phone": phone,
                    "whatsapp": phone,
                    "website": website,
                    "source": "hagah",
                    "category": query,
                })

        time.sleep(self.delay)
        return leads

    def _crawl_cylex(self, query, location, limit):
        """Cylex Brasil — diretório empresarial."""
        leads = []
        slug_query = query.lower().replace(" ", "+")
        slug_loc = (location or "brasil").lower().replace(" ", "+")

        url = f"https://www.cylex.com.br/pesquisa/{slug_query}/{slug_loc}"
        self.session.headers["User-Agent"] = self.ua.random

        response = safe_request(self.session, url, timeout=15)
        if not response or response.status_code != 200:
            return leads

        soup = BeautifulSoup(response.text, "lxml")
        items = soup.select(".company-item, .result-item, article, li[itemscope]")

        for item in items[:limit]:
            name = ""
            phone = ""
            website = ""

            name_el = item.select_one("h2, h3, .company-name, [itemprop='name']")
            if name_el:
                name = name_el.get_text(strip=True)

            phone_el = item.select_one("[itemprop='telephone'], .phone, a[href^='tel:']")
            if phone_el:
                raw = phone_el.get_text(strip=True)
                phone = re.sub(r"\D", "", raw)

            link_el = item.select_one("a.website, a[rel='nofollow'][href^='http']")
            if link_el:
                website = link_el.get("href", "")

            if name and (phone or website):
                leads.append({
                    "name": name,
                    "email": "",
                    "phone": phone,
                    "whatsapp": phone,
                    "website": website,
                    "source": "cylex",
                    "category": query,
                })

        time.sleep(self.delay)
        return leads

    def _crawl_hotfrog(self, query, location, limit):
        """HotFrog Brasil — diretório internacional com presença BR."""
        leads = []
        slug = quote(f"{query} {location or 'Brasil'}")

        url = f"https://www.hotfrog.com.br/search/{slug}"
        self.session.headers["User-Agent"] = self.ua.random

        response = safe_request(self.session, url, timeout=15)
        if not response or response.status_code != 200:
            return leads

        soup = BeautifulSoup(response.text, "lxml")
        items = soup.select(".result-item, .business, article, [itemscope]")

        for item in items[:limit]:
            name = ""
            phone = ""
            website = ""

            name_el = item.select_one("h2, h3, .name, [itemprop='name']")
            if name_el:
                name = name_el.get_text(strip=True)

            phone_el = item.select_one("[itemprop='telephone'], .phone, a[href^='tel:']")
            if phone_el:
                phone = re.sub(r"\D", "", phone_el.get_text(strip=True))

            link_el = item.select_one("a.website, a[target='_blank'][href^='http']")
            if link_el:
                href = link_el.get("href", "")
                if "hotfrog" not in href:
                    website = href

            if name and (phone or website):
                leads.append({
                    "name": name,
                    "email": "",
                    "phone": phone,
                    "whatsapp": phone,
                    "website": website,
                    "source": "hotfrog",
                    "category": query,
                })

        time.sleep(self.delay)
        return leads
