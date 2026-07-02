import time
import logging
import requests
from bs4 import BeautifulSoup
from fake_useragent import UserAgent
from urllib.parse import urlparse
import os
from crawler.search_engine import SearchEngine
from crawler.utils import (
    safe_request,
    extract_emails_from_text,
    extract_phones_from_text,
    extract_whatsapp_from_html,
    extract_business_name,
)

logger = logging.getLogger("leadhunter.website")


class WebsiteCrawler:
    def __init__(self):
        self.ua = UserAgent()
        self.delay = int(os.getenv("REQUEST_DELAY_MS", "1500")) / 1000
        self.session = requests.Session()
        self.session.headers.update({
            "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
            "Accept-Language": "pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7",
        })
        self.search_engine = SearchEngine()

    def crawl(self, query, quantity, on_progress=None):
        leads = []
        search_query = f"{query} contato email telefone"
        urls = self.search_engine.search(search_query, quantity * 3)

        logger.info(f"[website] buscando '{query}' — {len(urls)} URLs para processar")

        from concurrent.futures import ThreadPoolExecutor, as_completed
        
        with ThreadPoolExecutor(max_workers=5) as executor:
            future_to_url = {executor.submit(self._extract_from_url, url): url for url in urls}
            
            for future in as_completed(future_to_url):
                if len(leads) >= quantity:
                    break
                
                try:
                    page_leads = future.result()
                    leads.extend(page_leads)
                    if on_progress:
                        on_progress(len(leads))
                except Exception as e:
                    logger.debug(f"[website] erro processando futuro: {e}")

        logger.info(f"[website] '{query}' — {len(leads)} leads extraídos")
        return leads[:quantity]

    def _extract_from_url(self, url):
        from crawler.validator import validate_lead
        from crawler.lead_scorer import calculate_score
        from crawler.utils import safe_request, extract_emails_from_text, extract_phones_from_text, extract_whatsapp_from_html, extract_business_name, extract_social_from_html
        
        leads = []
        try:
            response = safe_request(self.session, url, timeout=15)
            if not response or response.status_code != 200:
                return leads

            html = response.text
            soup = BeautifulSoup(html, "lxml")
            domain = urlparse(url).netloc

            # Tenta encontrar link para página de contato
            contact_url = ""
            for a in soup.find_all("a", href=True):
                href = a["href"].lower()
                if "contato" in href or "contact" in href or "fale-conosco" in href:
                    if href.startswith("http"):
                        contact_url = a["href"]
                    else:
                        base = f"{urlparse(url).scheme}://{domain}"
                        contact_url = f"{base}/{a['href'].lstrip('/')}"
                    break
            
            if contact_url:
                logger.debug(f"[website] visitando página de contato: {contact_url}")
                contact_resp = safe_request(self.session, contact_url, timeout=10)
                if contact_resp and contact_resp.status_code == 200:
                    html += " " + contact_resp.text

            emails = extract_emails_from_text(html, html)
            phones = extract_phones_from_text(html)
            whatsapp = extract_whatsapp_from_html(html)
            social = extract_social_from_html(html)
            
            if emails or phones or whatsapp:
                name = extract_business_name(soup, domain)
                
                # Create leads for each unique email/phone combination if possible
                # But typically we want one lead per website
                lead = {
                    "name": name,
                    "email": ";".join(emails),
                    "phone": ";".join(phones),
                    "whatsapp": whatsapp or (phones[0] if phones else ""),
                    "website": url,
                    "instagram": social.get("instagram", ""),
                    "facebook": social.get("facebook", ""),
                    "source": "website_crawler",
                    "category": ""
                }
                leads.append(lead)

        except Exception as e:
            logger.debug(f"[website] erro em {url}: {e}")
            
        return leads
