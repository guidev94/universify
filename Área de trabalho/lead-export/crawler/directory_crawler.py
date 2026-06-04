import re
import time
import requests
from bs4 import BeautifulSoup
from fake_useragent import UserAgent
import os


class DirectoryCrawler:
    def __init__(self):
        self.ua = UserAgent()
        self.delay = int(os.getenv("REQUEST_DELAY_MS", "1500")) / 1000
        self.session = requests.Session()

    def crawl(self, query, location, quantity, on_progress=None):
        leads = []

        sources = [
            self._crawl_telelistas,
            self._crawl_google_directory,
        ]

        for source_fn in sources:
            if len(leads) >= quantity:
                break
            try:
                results = source_fn(query, location, quantity - len(leads))
                leads.extend(results)
                if on_progress:
                    on_progress(len(leads))
            except Exception:
                continue

        return leads[:quantity]

    def _crawl_telelistas(self, query, location, limit):
        leads = []
        slug_query = query.lower().replace(" ", "-")
        slug_location = location.lower().replace(" ", "-").replace(",", "") if location else "brasil"

        url = f"https://www.telelistas.net/{slug_location}/{slug_query}"
        self.session.headers["User-Agent"] = self.ua.random

        try:
            response = self.session.get(url, timeout=15)
            if response.status_code != 200:
                return leads

            soup = BeautifulSoup(response.text, "lxml")
            listings = soup.select(".result-item, .listing-item, article")

            for item in listings[:limit]:
                name = ""
                phone = ""
                website = ""

                name_el = item.select_one("h2, h3, .company-name, .title")
                if name_el:
                    name = name_el.get_text(strip=True)

                phone_el = item.select_one("a[href^='tel:'], .phone, .telefone")
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
        except Exception:
            pass

        return leads

    def _crawl_google_directory(self, query, location, limit):
        leads = []
        search_query = f"{query} {location} telefone site contato"
        self.session.headers["User-Agent"] = self.ua.random

        try:
            params = {"q": search_query, "num": 20, "hl": "pt-BR"}
            response = self.session.get("https://www.google.com/search", params=params, timeout=15)

            if response.status_code != 200:
                return leads

            soup = BeautifulSoup(response.text, "lxml")

            for div in soup.select("div.g, div[data-hveid]"):
                name = ""
                website = ""
                phone = ""

                title = div.select_one("h3")
                if title:
                    name = title.get_text(strip=True)

                link = div.select_one("a[href^='http']")
                if link:
                    href = link.get("href", "")
                    if "/url?q=" in href:
                        href = href.split("/url?q=")[1].split("&")[0]
                    website = href

                snippet = div.select_one(".VwiC3b, .st, span")
                if snippet:
                    text = snippet.get_text()
                    phone_match = re.search(r"\(?\d{2}\)?\s*\d{4,5}[\-\s]?\d{4}", text)
                    if phone_match:
                        phone = phone_match.group()

                if name and website and "google.com" not in website:
                    leads.append({
                        "name": name,
                        "email": "",
                        "phone": phone,
                        "whatsapp": "",
                        "website": website,
                        "source": "directory_google",
                        "category": query,
                    })

                if len(leads) >= limit:
                    break

            time.sleep(self.delay)
        except Exception:
            pass

        return leads
