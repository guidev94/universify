import re
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

logger = logging.getLogger("leadhunter.niche")


class NicheCrawler:
    def __init__(self):
        self.ua = UserAgent()
        self.delay = int(os.getenv("REQUEST_DELAY_MS", "1500")) / 1000
        self.session = requests.Session()
        self.search_engine = SearchEngine()

    def crawl(self, query, location, quantity, on_progress=None):
        leads = []

        search_queries = self._build_niche_queries(query, location)

        for search_query in search_queries:
            if len(leads) >= quantity:
                break
            try:
                urls = self.search_engine.search(search_query, quantity * 2)
                for url in urls:
                    if len(leads) >= quantity:
                        break
                    lead = self._extract_from_listing(url, query)
                    if lead:
                        leads.append(lead)
                        if on_progress:
                            on_progress(len(leads))
                        time.sleep(self.delay * 0.5)
            except Exception as e:
                logger.warning(f"[niche] erro buscando '{search_query[:40]}': {e}")
                continue

        logger.info(f"[niche] '{query}' — {len(leads)} leads extraídos")
        return leads[:quantity]

    def _build_niche_queries(self, query, location):
        query_lower = query.lower()
        loc = location or "brasil"
        queries = [
            f"{query} {loc} telefone email contato",
            f"{query} {loc} whatsapp contato",
        ]

        if any(w in query_lower for w in ["dentista", "odonto", "dental"]):
            queries.append(f"doctoralia.com.br {query} {loc}")
        elif any(w in query_lower for w in ["advogado", "escritório", "jurídico"]):
            queries.append(f"jusbrasil.com.br {query} {loc}")
        elif any(w in query_lower for w in ["restaurante", "pizzaria", "lanchonete", "comida"]):
            queries.append(f"tripadvisor.com.br {query} {loc}")
        elif any(w in query_lower for w in ["médico", "clínica", "hospital"]):
            queries.append(f"doctoralia.com.br {query} {loc}")
        elif any(w in query_lower for w in ["hotel", "pousada", "hospedagem"]):
            queries.append(f"booking.com {query} {loc}")
        elif any(w in query_lower for w in ["academia", "personal", "fitness"]):
            queries.append(f"{query} {loc} instagram contato")

        return queries

    def _extract_from_listing(self, url, category):
        self.session.headers["User-Agent"] = self.ua.random

        response = safe_request(self.session, url, timeout=10)
        if not response or response.status_code != 200:
            return None

        content_type = response.headers.get("Content-Type", "")
        if "text/html" not in content_type:
            return None

        soup = BeautifulSoup(response.text, "lxml")
        text = soup.get_text(separator=" ")
        domain = urlparse(url).netloc

        emails = extract_emails_from_text(text, response.text)
        phones = extract_phones_from_text(text)
        whatsapp = extract_whatsapp_from_html(response.text)
        name = extract_business_name(soup, domain)

        if not emails and not phones:
            return None

        return {
            "name": name,
            "email": emails[0] if emails else "",
            "phone": phones[0] if phones else "",
            "whatsapp": whatsapp or (phones[0] if phones else ""),
            "website": url,
            "source": "niche_directory",
            "category": category,
        }
