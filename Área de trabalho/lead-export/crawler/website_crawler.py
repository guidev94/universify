import re
import time
import requests
from bs4 import BeautifulSoup
from fake_useragent import UserAgent
from urllib.parse import urljoin, urlparse
import os


class WebsiteCrawler:
    def __init__(self):
        self.ua = UserAgent()
        self.delay = int(os.getenv("REQUEST_DELAY_MS", "1500")) / 1000
        self.session = requests.Session()
        self.session.headers.update({
            "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
            "Accept-Language": "pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7",
        })

    def crawl(self, query, quantity, on_progress=None):
        leads = []
        urls = self._search_google(query, quantity * 3)

        for i, url in enumerate(urls):
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

    def _search_google(self, query, num_results):
        urls = []
        search_query = f"{query} contato email telefone"
        page = 0

        while len(urls) < num_results and page < 10:
            try:
                self.session.headers["User-Agent"] = self.ua.random
                params = {
                    "q": search_query,
                    "start": page * 10,
                    "num": 10,
                    "hl": "pt-BR",
                }
                response = self.session.get(
                    "https://www.google.com/search",
                    params=params,
                    timeout=15,
                )

                if response.status_code == 429:
                    time.sleep(30)
                    continue

                soup = BeautifulSoup(response.text, "lxml")
                links = soup.select("a[href]")

                for link in links:
                    href = link.get("href", "")
                    if href.startswith("/url?q="):
                        real_url = href.split("/url?q=")[1].split("&")[0]
                        if self._is_valid_url(real_url):
                            urls.append(real_url)

                page += 1
                time.sleep(self.delay * 2)

            except Exception:
                page += 1
                continue

        return urls

    def _is_valid_url(self, url):
        excluded = {"google.com", "youtube.com", "facebook.com", "instagram.com", "twitter.com", "linkedin.com"}
        try:
            parsed = urlparse(url)
            domain = parsed.netloc.lower()
            return parsed.scheme in ("http", "https") and not any(ex in domain for ex in excluded)
        except Exception:
            return False

    def _extract_from_url(self, url):
        leads = []

        self.session.headers["User-Agent"] = self.ua.random
        response = self.session.get(url, timeout=15, allow_redirects=True)

        if response.status_code != 200:
            return leads

        content_type = response.headers.get("Content-Type", "")
        if "text/html" not in content_type:
            return leads

        soup = BeautifulSoup(response.text, "lxml")
        text = soup.get_text(separator=" ")
        domain = urlparse(url).netloc

        emails = self._extract_emails(text, response.text)
        phones = self._extract_phones(text)
        whatsapp = self._extract_whatsapp(response.text)
        name = self._extract_business_name(soup, domain)

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

    def _extract_emails(self, text, html):
        pattern = r"[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}"
        found = set(re.findall(pattern, text))
        found.update(re.findall(pattern, html))

        excluded_extensions = {".png", ".jpg", ".jpeg", ".gif", ".svg", ".webp", ".css", ".js"}
        excluded_domains = {"example.com", "email.com", "sentry.io", "wixpress.com"}

        valid = []
        for email in found:
            email_lower = email.lower()
            domain = email_lower.split("@")[1] if "@" in email_lower else ""
            ext = "." + domain.split(".")[-1] if domain else ""

            if ext in excluded_extensions:
                continue
            if domain in excluded_domains:
                continue
            if email_lower.startswith(("noreply", "no-reply", "mailer")):
                continue

            valid.append(email_lower)

        return list(valid)[:5]

    def _extract_phones(self, text):
        patterns = [
            r"\(?\d{2}\)?\s*\d{4,5}[\-\s]?\d{4}",
            r"\+55\s*\(?\d{2}\)?\s*\d{4,5}[\-\s]?\d{4}",
            r"\d{2}\s\d{5}\s\d{4}",
        ]

        phones = set()
        for pattern in patterns:
            matches = re.findall(pattern, text)
            for match in matches:
                digits = re.sub(r"\D", "", match)
                if 10 <= len(digits) <= 13:
                    phones.add(match.strip())

        return list(phones)[:5]

    def _extract_whatsapp(self, html):
        patterns = [
            r"wa\.me/(\d+)",
            r"api\.whatsapp\.com/send\?phone=(\d+)",
            r"whatsapp.*?(\d{12,13})",
        ]

        for pattern in patterns:
            match = re.search(pattern, html, re.IGNORECASE)
            if match:
                number = match.group(1)
                if len(number) >= 12:
                    return number

        return ""

    def _extract_business_name(self, soup, domain):
        og_title = soup.find("meta", property="og:site_name")
        if og_title and og_title.get("content"):
            return og_title["content"].strip()

        title = soup.find("title")
        if title and title.string:
            name = title.string.strip()
            separators = [" | ", " - ", " — ", " – ", " :: "]
            for sep in separators:
                if sep in name:
                    name = name.split(sep)[0].strip()
                    break
            if len(name) <= 60:
                return name

        domain_name = domain.replace("www.", "").split(".")[0]
        return domain_name.title()
