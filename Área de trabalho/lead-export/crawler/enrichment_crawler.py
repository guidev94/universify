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
    extract_social_from_html,
)

logger = logging.getLogger("leadhunter.enrichment")


class EnrichmentCrawler:
    """Visita sites de leads existentes para enriquecer dados (redes sociais, emails extras)."""

    def __init__(self):
        self.ua = UserAgent()
        self.delay = int(os.getenv("REQUEST_DELAY_MS", "1500")) / 1000
        self.session = requests.Session()
        self.session.headers.update({
            "Accept": "text/html,application/xhtml+xml",
            "Accept-Language": "pt-BR,pt;q=0.9",
        })

    def enrich_lead(self, lead):
        website = lead.get("website", "")
        if not website:
            return {}

        self.session.headers["User-Agent"] = self.ua.random
        # Reduced timeout from 12 to 8
        response = safe_request(self.session, website, timeout=8)
        if not response or response.status_code != 200:
            return {}

        content_type = response.headers.get("Content-Type", "")
        if "text/html" not in content_type:
            return {}

        html = response.text
        soup = BeautifulSoup(html, "lxml")
        text = soup.get_text(separator=" ")
        enriched = {}

        new_emails = extract_emails_from_text(text, html)
        existing = set((lead.get("email") or "").split(";"))
        fresh_emails = [e for e in new_emails if e not in existing and e]
        if fresh_emails:
            enriched["new_emails"] = fresh_emails

        new_phones = extract_phones_from_text(text)
        existing_phones = set((lead.get("phone") or "").split(";"))
        fresh_phones = [p for p in new_phones if p not in existing_phones and p]
        if fresh_phones:
            enriched["new_phones"] = fresh_phones

        whatsapp = extract_whatsapp_from_html(html)
        if whatsapp and whatsapp != lead.get("whatsapp", ""):
            enriched["whatsapp"] = whatsapp

        social = extract_social_from_html(html)
        if social.get("instagram") and not lead.get("instagram"):
            enriched["instagram"] = social["instagram"]
        if social.get("facebook") and not lead.get("facebook"):
            enriched["facebook"] = social["facebook"]

        contact_page = self._find_contact_page(soup, website)
        if contact_page:
            enriched.update(self._scrape_contact_page(contact_page, lead))

        time.sleep(self.delay)
        return enriched

    def _find_contact_page(self, soup, base_url):
        keywords = ["contato", "contact", "fale-conosco", "fale conosco"]
        for link in soup.select("a[href]"):
            href = (link.get("href") or "").lower()
            text = (link.get_text() or "").lower()
            if any(k in href or k in text for k in keywords):
                full_url = href if href.startswith("http") else f"{base_url.rstrip('/')}/{href.lstrip('/')}"
                if urlparse(full_url).netloc == urlparse(base_url).netloc:
                    return full_url
        return None

    def _scrape_contact_page(self, url, lead):
        enriched = {}
        response = safe_request(self.session, url, timeout=10)
        if not response or response.status_code != 200:
            return enriched

        html = response.text
        text = BeautifulSoup(html, "lxml").get_text(separator=" ")

        emails = extract_emails_from_text(text, html)
        existing = set((lead.get("email") or "").split(";"))
        fresh = [e for e in emails if e not in existing and e]
        if fresh:
            enriched["contact_emails"] = fresh

        whatsapp = extract_whatsapp_from_html(html)
        if whatsapp and whatsapp != lead.get("whatsapp", ""):
            enriched["contact_whatsapp"] = whatsapp

        social = extract_social_from_html(html)
        if social.get("instagram"):
            enriched["instagram"] = social["instagram"]
        if social.get("facebook"):
            enriched["facebook"] = social["facebook"]

        return enriched

    def enrich_batch(self, leads, max_count=50):
        enriched_count = 0
        for lead in leads[:max_count]:
            try:
                data = self.enrich_lead(lead)
                if data:
                    enriched_count += 1
                    yield lead["id"], data
            except Exception as e:
                logger.debug(f"[enrichment] erro no lead {lead.get('id')}: {e}")
                continue
        logger.info(f"[enrichment] {enriched_count}/{len(leads[:max_count])} leads enriquecidos")
