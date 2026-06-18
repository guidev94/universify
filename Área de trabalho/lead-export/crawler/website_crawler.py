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

        for url in urls:
            if len(leads) >= quantity:
                break

            try:
                page_leads = self._extract_from_url(url)
                leads.extend(page_leads)

                if on_progress:
                    on_progress(len(leads))

                time.sleep(self.delay)
            except Exception as e:
                logger.debug(f"[website] erro extraindo {url[:60]}: {e}")
                continue

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

            emails = extract_emails_from_text(html)
            phones = extract_phones_from_text(html)
            whatsapp = extract_whatsapp_from_html(html)
            social = extract_social_from_html(html)
            
            # If we find contacts, it's a lead!
            if emails or phones or whatsapp:
                raw_lead = {
                    "name": extract_business_name(soup, domain),
                    "email": ";".join(emails),
                    "phone": ";".join(phones),
                    "whatsapp": whatsapp,
                    "website": url,
                    "instagram": social.get("instagram", ""),
                    "facebook": social.get("facebook", ""),
                    "source": "website_crawler"
                }
                
                # Check for old site markers already here for faster scoring
                # (Scoring will happen again in blueprints/crawl.py)
                leads.append(raw_lead)

        except Exception as e:
            logger.debug(f"[website] erro em {url}: {e}")
            
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
            lead = {
                "name": name,
                "email": emails[0] if emails else "",
                "phone": phones[0] if phones else "",
                "whatsapp": whatsapp or (phones[0] if phones else ""),
                "website": url,
                "source": "website",
                "category": "",
            }
            leads.append(lead)

            for i in range(1, max(len(emails), len(phones))):
                extra_lead = {
                    "name": name,
                    "email": emails[i] if i < len(emails) else "",
                    "phone": phones[i] if i < len(phones) else "",
                    "whatsapp": whatsapp or "",
                    "website": url,
                    "source": "website",
                    "category": "",
                }
                if extra_lead["email"] or extra_lead["phone"]:
                    leads.append(extra_lead)

        return leads
