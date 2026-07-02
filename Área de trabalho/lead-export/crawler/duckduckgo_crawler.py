import re
import time
import logging
import requests
from bs4 import BeautifulSoup
from fake_useragent import UserAgent
from urllib.parse import urlparse, urlencode
import os
from crawler.utils import (
    safe_request,
    extract_emails_from_text,
    extract_phones_from_text,
    extract_whatsapp_from_html,
    extract_business_name,
)

logger = logging.getLogger("leadhunter.duckduckgo")


class DuckDuckGoCrawler:
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
        urls = self._search(query, quantity * 3)

        logger.info(f"[duckduckgo] buscando '{query}' — {len(urls)} URLs encontradas")

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
                logger.debug(f"[duckduckgo] erro extraindo {url[:60]}: {e}")
                continue

        logger.info(f"[duckduckgo] '{query}' — {len(leads)} leads extraídos")
        return leads[:quantity]

    def _search(self, query, num_results):
        urls = []
        queries = [
            f"{query} contato telefone whatsapp",
            f"{query} endereço telefone",
            f"{query} instagram whatsapp",
        ]
        search_query = queries[hash(query) % len(queries)]

        for page in range(min(5, (num_results // 10) + 1)):
            self.session.headers["User-Agent"] = self.ua.random

            data = {
                "q": search_query,
                "kl": "br-pt",
                "df": "",
            }

            if page > 0 and hasattr(self, "_next_params"):
                data.update(self._next_params)

            try:
                response = self.session.post(
                    "https://html.duckduckgo.com/html/",
                    data=data,
                    timeout=15,
                )

                if response.status_code == 202:
                    logger.warning(f"[duckduckgo] desafio detectado na busca — tentando rota alternativa")
                    # Try GET instead of POST
                    try:
                        response = self.session.get(
                            f"https://html.duckduckgo.com/html/?{urlencode(data)}",
                            timeout=15,
                        )
                    except:
                        break
                    if response.status_code != 200:
                        break
                
                if response.status_code != 200:
                    logger.warning(f"[duckduckgo] status {response.status_code} na busca")
                    break

                soup = BeautifulSoup(response.text, "lxml")

                for link in soup.select("a.result__a"):
                    href = link.get("href", "")
                    if href.startswith("//duckduckgo.com/l/?uddg="):
                        from urllib.parse import unquote
                        real_url = unquote(href.split("uddg=")[1].split("&")[0])
                        if self._is_valid_url(real_url):
                            urls.append(real_url)
                    elif href.startswith("http") and self._is_valid_url(href):
                        urls.append(href)

                next_form = soup.select_one("form.nav-link input[name='s']")
                if next_form:
                    self._next_params = {
                        "s": next_form.get("value", ""),
                        "nextParams": "",
                        "v": "l",
                        "o": "json",
                        "dc": str((page + 1) * 30),
                        "api": "d.js",
                    }
                else:
                    break

                if len(urls) >= num_results:
                    break

                time.sleep(self.delay * 1.5)

            except Exception as e:
                logger.warning(f"[duckduckgo] erro na busca página {page}: {e}")
                break

        return urls[:num_results]

    def _is_valid_url(self, url):
        excluded = {
            "duckduckgo.com", "google.com", "youtube.com", "facebook.com",
            "instagram.com", "twitter.com", "linkedin.com", "wikipedia.org",
            "bing.com", "microsoft.com",
        }
        try:
            parsed = urlparse(url)
            domain = parsed.netloc.lower()
            return parsed.scheme in ("http", "https") and not any(ex in domain for ex in excluded)
        except Exception:
            return False

    def _extract_from_url(self, url):
        leads = []
        self.session.headers["User-Agent"] = self.ua.random

        # Reduced timeout from 12 to 8
        response = safe_request(self.session, url, timeout=8)
        if not response or response.status_code != 200:
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
            leads.append({
                "name": name,
                "email": emails[0] if emails else "",
                "phone": phones[0] if phones else "",
                "whatsapp": whatsapp or (phones[0] if phones else ""),
                "website": url,
                "source": "duckduckgo",
                "category": "",
            })

        return leads
