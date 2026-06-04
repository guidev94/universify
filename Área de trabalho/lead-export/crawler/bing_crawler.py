import re
import time
import requests
from bs4 import BeautifulSoup
from fake_useragent import UserAgent
from urllib.parse import urlparse
import os


class BingCrawler:
    def __init__(self):
        self.ua = UserAgent()
        self.delay = int(os.getenv("REQUEST_DELAY_MS", "1500")) / 1000
        self.session = requests.Session()

    def crawl(self, query, quantity, on_progress=None):
        leads = []
        urls = self._search_bing(query, quantity * 3)

        for url in urls:
            if len(leads) >= quantity:
                break
            try:
                page_leads = self._extract_from_url(url)
                leads.extend(page_leads)
                if on_progress:
                    on_progress(len(leads))
                time.sleep(self.delay)
            except Exception:
                continue

        return leads[:quantity]

    def _search_bing(self, query, num_results):
        urls = []
        search_query = f"{query} contato email telefone site"
        page = 0

        while len(urls) < num_results and page < 5:
            try:
                self.session.headers["User-Agent"] = self.ua.random
                params = {"q": search_query, "first": page * 10 + 1, "setlang": "pt-BR"}
                response = self.session.get("https://www.bing.com/search", params=params, timeout=15)

                if response.status_code != 200:
                    break

                soup = BeautifulSoup(response.text, "lxml")
                for link in soup.select("li.b_algo h2 a"):
                    href = link.get("href", "")
                    if href.startswith("http") and self._is_valid_url(href):
                        urls.append(href)

                page += 1
                time.sleep(self.delay * 2)
            except Exception:
                page += 1

        return urls

    def _is_valid_url(self, url):
        excluded = {"bing.com", "microsoft.com", "google.com", "youtube.com", "facebook.com", "instagram.com", "twitter.com", "linkedin.com", "wikipedia.org"}
        try:
            domain = urlparse(url).netloc.lower()
            return not any(ex in domain for ex in excluded)
        except Exception:
            return False

    def _extract_from_url(self, url):
        leads = []
        self.session.headers["User-Agent"] = self.ua.random
        response = self.session.get(url, timeout=12, allow_redirects=True)

        if response.status_code != 200 or "text/html" not in response.headers.get("Content-Type", ""):
            return leads

        soup = BeautifulSoup(response.text, "lxml")
        text = soup.get_text(separator=" ")
        domain = urlparse(url).netloc

        emails = self._extract_emails(text, response.text)
        phones = self._extract_phones(text)
        whatsapp = self._extract_whatsapp(response.text)
        name = self._extract_name(soup, domain)

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

    def _extract_emails(self, text, html):
        found = set(re.findall(r"[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}", text))
        found.update(re.findall(r"[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}", html))
        excluded = {"example.com", "email.com", "sentry.io", "wixpress.com"}
        return [e.lower() for e in found if not any(x in e.lower() for x in excluded) and not e.lower().startswith(("noreply", "no-reply", "mailer"))][:3]

    def _extract_phones(self, text):
        patterns = [r"\(?\d{2}\)?\s*\d{4,5}[\-\s]?\d{4}", r"\+55\s*\(?\d{2}\)?\s*\d{4,5}[\-\s]?\d{4}"]
        phones = set()
        for p in patterns:
            for m in re.findall(p, text):
                digits = re.sub(r"\D", "", m)
                if 10 <= len(digits) <= 13:
                    phones.add(m.strip())
        return list(phones)[:3]

    def _extract_whatsapp(self, html):
        match = re.search(r"wa\.me/(\d+)", html)
        if match and len(match.group(1)) >= 12:
            return match.group(1)
        return ""

    def _extract_name(self, soup, domain):
        og = soup.find("meta", property="og:site_name")
        if og and og.get("content"):
            return og["content"].strip()
        title = soup.find("title")
        if title and title.string:
            name = title.string.strip().split(" | ")[0].split(" - ")[0]
            if len(name) <= 60:
                return name
        return domain.replace("www.", "").split(".")[0].title()
