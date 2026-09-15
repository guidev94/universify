import logging
import requests
from bs4 import BeautifulSoup
from urllib.parse import urlparse, unquote
from concurrent.futures import ThreadPoolExecutor, as_completed
from config import Config
from crawler.search_engine import SearchEngine
from crawler.utils import (
    safe_request,
    extract_emails_from_text,
    extract_phones_from_text,
    extract_whatsapp_from_html,
    extract_business_name,
    extract_social_from_html,
)

logger = logging.getLogger("leadhunter.duckduckgo")


class DuckDuckGoCrawler:
    """Crawler DuckDuckGo otimizado com paralelismo alto e timeouts curtos."""

    def __init__(self):
        self.session = requests.Session()
        self.session.headers.update({
            "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
            "Accept-Language": "pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7",
            "User-Agent": SearchEngine._UA_POOL[0],
        })
        self.search_engine = SearchEngine()

    def crawl(self, query, quantity, on_progress=None):
        leads = []
        # Busca URLs via SearchEngine (já otimizado com paralelo)
        urls = self.search_engine.search(query, quantity * 3)

        if not urls:
            urls = self._search_fallback(query, quantity * 2)

        logger.info(f"[duckduckgo] '{query}' — {len(urls)} URLs para processar")

        # Processamento paralelo com workers da Config
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

        logger.info(f"[duckduckgo] '{query}' — {len(leads)} leads extraídos")
        return leads[:quantity]

    def _search_fallback(self, query, num_results):
        """Fallback local se SearchEngine não retornar nada."""
        urls = []
        try:
            response = self.session.get(
                "https://lite.duckduckgo.com/lite/",
                params={"q": query},
                timeout=Config.CRAWLER_TIMEOUT,
            )
            if response.status_code == 200:
                soup = BeautifulSoup(response.text, "lxml")
                for link in soup.select("a.result-link"):
                    href = link.get("href", "")
                    if href.startswith("http") and self._is_valid_url(href):
                        urls.append(href)

            if not urls:
                response = self.session.post(
                    "https://html.duckduckgo.com/html/",
                    data={"q": query, "kl": "br-pt"},
                    timeout=Config.CRAWLER_TIMEOUT,
                )
                if response.status_code == 200:
                    soup = BeautifulSoup(response.text, "lxml")
                    for link in soup.select("a.result__a"):
                        href = link.get("href", "")
                        if "uddg=" in href:
                            try:
                                real_url = unquote(href.split("uddg=")[1].split("&")[0])
                                if self._is_valid_url(real_url):
                                    urls.append(real_url)
                            except Exception:
                                continue
                        elif href.startswith("http") and self._is_valid_url(href):
                            urls.append(href)
        except Exception as e:
            logger.debug(f"[duckduckgo] search fallback error: {e}")

        return urls[:num_results]

    def _is_valid_url(self, url):
        excluded = frozenset({
            "duckduckgo.com", "google.com", "youtube.com", "facebook.com",
            "instagram.com", "twitter.com", "linkedin.com", "wikipedia.org",
            "bing.com", "microsoft.com",
        })
        try:
            domain = urlparse(url).netloc.lower()
            return not any(ex in domain for ex in excluded)
        except Exception:
            return False

    def _extract_from_url(self, url):
        """Extrai leads de uma URL com timeout curto."""
        leads = []

        response = safe_request(self.session, url, timeout=Config.CRAWLER_TIMEOUT)
        if not response or response.status_code != 200:
            return leads

        content_type = response.headers.get("Content-Type", "")
        if "text/html" not in content_type:
            return leads

        html = response.text
        soup = BeautifulSoup(html, "lxml")
        text = soup.get_text(separator=" ")
        domain = urlparse(url).netloc

        emails = extract_emails_from_text(text, html)
        phones = extract_phones_from_text(text)
        whatsapp = extract_whatsapp_from_html(html)
        name = extract_business_name(soup, domain)
        social = extract_social_from_html(html)

        if emails or phones or whatsapp:
            leads.append({
                "name": name,
                "email": ";".join(emails) if emails else "",
                "phone": ";".join(phones) if phones else "",
                "whatsapp": whatsapp or (phones[0] if phones else ""),
                "website": url,
                "instagram": social.get("instagram", ""),
                "facebook": social.get("facebook", ""),
                "source": "duckduckgo",
                "category": "",
            })

        return leads
