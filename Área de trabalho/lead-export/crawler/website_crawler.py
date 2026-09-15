import logging
import requests
from bs4 import BeautifulSoup
from urllib.parse import urlparse
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

logger = logging.getLogger("leadhunter.website")


class WebsiteCrawler:
    """Crawler de websites com deep-contact extraction (visita /contato, /sobre).

    Otimizações:
    - Timeouts curtos (Config.CRAWLER_TIMEOUT)
    - Workers paralelos (Config.CRAWLER_WORKERS)
    - Busca apenas query + "contato email telefone" (mais targeted)
    """

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
        search_query = f"{query} contato email telefone"
        urls = self.search_engine.search(search_query, quantity * 3)

        logger.info(f"[website] '{query}' — {len(urls)} URLs para processar")

        with ThreadPoolExecutor(max_workers=Config.CRAWLER_WORKERS) as executor:
            future_to_url = {executor.submit(self._extract_from_url, url): url for url in urls}

            for future in as_completed(future_to_url, timeout=120):
                if len(leads) >= quantity:
                    break
                try:
                    page_leads = future.result(timeout=Config.CRAWLER_TIMEOUT * 2)
                    if page_leads:
                        leads.extend(page_leads)
                        if on_progress:
                            on_progress(len(leads))
                except Exception:
                    continue

        logger.info(f"[website] '{query}' — {len(leads)} leads extraídos")
        return leads[:quantity]

    def _extract_from_url(self, url):
        """Extrai leads de uma URL, visitando também a página de contato."""
        leads = []
        try:
            response = safe_request(self.session, url, timeout=Config.CRAWLER_TIMEOUT)
            if not response or response.status_code != 200:
                return leads

            html = response.text
            soup = BeautifulSoup(html, "lxml")
            domain = urlparse(url).netloc

            # Busca link de contato para visitar
            contact_url = self._find_contact_page(soup, url, domain)
            if contact_url:
                contact_resp = safe_request(self.session, contact_url, timeout=Config.CRAWLER_TIMEOUT)
                if contact_resp and contact_resp.status_code == 200:
                    html += " " + contact_resp.text

            emails = extract_emails_from_text(html, html)
            phones = extract_phones_from_text(html)
            whatsapp = extract_whatsapp_from_html(html)
            social = extract_social_from_html(html)

            if emails or phones or whatsapp:
                name = extract_business_name(soup, domain)
                leads.append({
                    "name": name,
                    "email": ";".join(emails),
                    "phone": ";".join(phones),
                    "whatsapp": whatsapp or (phones[0] if phones else ""),
                    "website": url,
                    "instagram": social.get("instagram", ""),
                    "facebook": social.get("facebook", ""),
                    "source": "website_crawler",
                    "category": "",
                })

        except Exception as e:
            logger.debug(f"[website] erro em {url}: {e}")

        return leads

    def _find_contact_page(self, soup, base_url, domain):
        """Encontra a URL da página de contato."""
        contact_keywords = ("contato", "contact", "fale-conosco", "fale conosco", "atendimento")
        for a in soup.find_all("a", href=True):
            href_lower = a["href"].lower()
            text_lower = a.get_text().lower()
            if any(kw in href_lower or kw in text_lower for kw in contact_keywords):
                href = a["href"]
                if href.startswith("http"):
                    return href
                else:
                    base = f"{urlparse(base_url).scheme}://{domain}"
                    return f"{base}/{href.lstrip('/')}"
        return ""
