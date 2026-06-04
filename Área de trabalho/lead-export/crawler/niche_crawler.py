import re
import time
import requests
from bs4 import BeautifulSoup
from fake_useragent import UserAgent
from urllib.parse import urlparse
import os


class NicheCrawler:
    def __init__(self):
        self.ua = UserAgent()
        self.delay = int(os.getenv("REQUEST_DELAY_MS", "1500")) / 1000
        self.session = requests.Session()

    def crawl(self, query, location, quantity, on_progress=None):
        leads = []

        niche_urls = self._get_niche_search_urls(query, location)

        for search_url in niche_urls:
            if len(leads) >= quantity:
                break
            try:
                results = self._scrape_results(search_url, query, quantity - len(leads))
                leads.extend(results)
                if on_progress:
                    on_progress(len(leads))
                time.sleep(self.delay)
            except Exception:
                continue

        return leads[:quantity]

    def _get_niche_search_urls(self, query, location):
        query_lower = query.lower()
        loc = location or "brasil"
        urls = []

        urls.append(f"https://www.google.com/search?q={query}+{loc}+%22telefone%22+%22email%22+site&num=20&hl=pt-BR")
        urls.append(f"https://www.google.com/search?q={query}+{loc}+contato+whatsapp&num=20&hl=pt-BR")

        if any(w in query_lower for w in ["dentista", "odonto", "dental"]):
            urls.append(f"https://www.google.com/search?q=site:doctoralia.com.br+{query}+{loc}&num=10")
        elif any(w in query_lower for w in ["advogado", "escritório", "jurídico"]):
            urls.append(f"https://www.google.com/search?q=site:jusbrasil.com.br+{query}+{loc}&num=10")
        elif any(w in query_lower for w in ["restaurante", "pizzaria", "lanchonete", "comida"]):
            urls.append(f"https://www.google.com/search?q=site:tripadvisor.com.br+{query}+{loc}&num=10")
        elif any(w in query_lower for w in ["médico", "clínica", "hospital"]):
            urls.append(f"https://www.google.com/search?q=site:doctoralia.com.br+{query}+{loc}&num=10")

        return urls

    def _scrape_results(self, url, category, limit):
        leads = []
        self.session.headers["User-Agent"] = self.ua.random

        try:
            response = self.session.get(url, timeout=15)
            if response.status_code != 200:
                return leads

            soup = BeautifulSoup(response.text, "lxml")

            for link_el in soup.select("a[href]"):
                href = link_el.get("href", "")
                if "/url?q=" in href:
                    real_url = href.split("/url?q=")[1].split("&")[0]
                    if self._is_valid_url(real_url):
                        lead = self._extract_from_listing(real_url, category)
                        if lead:
                            leads.append(lead)
                            if len(leads) >= limit:
                                break
                            time.sleep(self.delay * 0.5)
        except Exception:
            pass

        return leads

    def _extract_from_listing(self, url, category):
        try:
            self.session.headers["User-Agent"] = self.ua.random
            response = self.session.get(url, timeout=10, allow_redirects=True)

            if response.status_code != 200:
                return None
            if "text/html" not in response.headers.get("Content-Type", ""):
                return None

            soup = BeautifulSoup(response.text, "lxml")
            text = soup.get_text(separator=" ")
            domain = urlparse(url).netloc

            emails = re.findall(r"[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}", text)
            emails = [e.lower() for e in emails if not e.lower().startswith(("noreply", "no-reply"))][:2]

            phones = []
            for m in re.findall(r"\(?\d{2}\)?\s*\d{4,5}[\-\s]?\d{4}", text):
                digits = re.sub(r"\D", "", m)
                if 10 <= len(digits) <= 13:
                    phones.append(m.strip())
            phones = phones[:2]

            whatsapp = ""
            wa_match = re.search(r"wa\.me/(\d+)", response.text)
            if wa_match:
                whatsapp = wa_match.group(1)

            name = ""
            og = soup.find("meta", property="og:site_name")
            if og and og.get("content"):
                name = og["content"].strip()
            elif soup.find("title") and soup.find("title").string:
                name = soup.find("title").string.strip().split(" | ")[0].split(" - ")[0]
            else:
                name = domain.replace("www.", "").split(".")[0].title()

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
        except Exception:
            return None

    def _is_valid_url(self, url):
        excluded = {"google.com", "youtube.com", "facebook.com", "instagram.com", "twitter.com", "wikipedia.org"}
        try:
            domain = urlparse(url).netloc.lower()
            return domain and not any(ex in domain for ex in excluded)
        except Exception:
            return False
