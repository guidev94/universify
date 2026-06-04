import time
import logging
import requests
from bs4 import BeautifulSoup
from fake_useragent import UserAgent
from urllib.parse import urlparse, unquote
import os

logger = logging.getLogger("leadhunter.search_engine")


class SearchEngine:
    def __init__(self):
        self.ua = UserAgent()
        self.delay = int(os.getenv("REQUEST_DELAY_MS", "1500")) / 1000
        self.session = requests.Session()
        self.session.headers.update({
            "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
            "Accept-Language": "pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7",
        })
        self._engine_health = {"duckduckgo": True, "bing": True, "google": True}

    def search(self, query, num_results=30):
        engines = [
            ("duckduckgo", self._search_duckduckgo),
            ("bing", self._search_bing),
            ("google", self._search_google),
        ]

        for name, fn in engines:
            if not self._engine_health.get(name, True):
                logger.info(f"[search] pulando {name} (marcado como indisponível)")
                continue

            try:
                urls = fn(query, num_results)
                if urls:
                    logger.info(f"[search] {name} retornou {len(urls)} URLs para '{query[:40]}'")
                    return urls
                else:
                    logger.warning(f"[search] {name} retornou 0 resultados para '{query[:40]}'")
            except Exception as e:
                logger.error(f"[search] {name} falhou: {e}")
                self._engine_health[name] = False

        logger.error(f"[search] todas as engines falharam para '{query[:40]}'")
        return []

    def _search_duckduckgo(self, query, num_results):
        urls = []
        self.session.headers["User-Agent"] = self.ua.random

        data = {"q": query, "kl": "br-pt"}

        for page in range(min(3, (num_results // 20) + 1)):
            response = self.session.post(
                "https://html.duckduckgo.com/html/",
                data=data,
                timeout=15,
            )

            if response.status_code != 200:
                break

            soup = BeautifulSoup(response.text, "lxml")

            for link in soup.select("a.result__a"):
                href = link.get("href", "")
                if href.startswith("//duckduckgo.com/l/?uddg="):
                    real_url = unquote(href.split("uddg=")[1].split("&")[0])
                    if self._is_valid_url(real_url):
                        urls.append(real_url)
                elif href.startswith("http") and self._is_valid_url(href):
                    urls.append(href)

            next_input = soup.select_one("form.nav-link input[name='s']")
            if next_input and len(urls) < num_results:
                data["s"] = next_input.get("value", "")
                data["dc"] = str((page + 1) * 30)
                time.sleep(self.delay)
            else:
                break

        return urls[:num_results]

    def _search_bing(self, query, num_results):
        urls = []
        self.session.headers["User-Agent"] = self.ua.random

        for page in range(min(3, (num_results // 10) + 1)):
            params = {"q": query, "first": page * 10 + 1, "setlang": "pt-BR"}

            response = self.session.get(
                "https://www.bing.com/search",
                params=params,
                timeout=15,
            )

            if response.status_code == 429:
                self._engine_health["bing"] = False
                break
            if response.status_code != 200:
                break

            soup = BeautifulSoup(response.text, "lxml")

            for selector in ["li.b_algo h2 a", "#b_results .b_algo a", "h2 a[href^='http']"]:
                for link in soup.select(selector):
                    href = link.get("href", "")
                    if href.startswith("http") and self._is_valid_url(href):
                        urls.append(href)
                if urls:
                    break

            time.sleep(self.delay * 2)

        return urls[:num_results]

    def _search_google(self, query, num_results):
        urls = []
        self.session.headers["User-Agent"] = self.ua.random

        for page in range(min(3, (num_results // 10) + 1)):
            params = {"q": query, "start": page * 10, "num": 10, "hl": "pt-BR"}

            response = self.session.get(
                "https://www.google.com/search",
                params=params,
                timeout=15,
            )

            if response.status_code == 429:
                self._engine_health["google"] = False
                logger.warning("[search] Google retornou 429 — marcado como indisponível")
                break
            if response.status_code != 200:
                break

            soup = BeautifulSoup(response.text, "lxml")

            for link in soup.select("a[href]"):
                href = link.get("href", "")
                if href.startswith("/url?q="):
                    real_url = href.split("/url?q=")[1].split("&")[0]
                    if self._is_valid_url(real_url):
                        urls.append(real_url)

            time.sleep(self.delay * 2)

        return urls[:num_results]

    def _is_valid_url(self, url):
        excluded = {
            "google.com", "youtube.com", "facebook.com", "instagram.com",
            "twitter.com", "linkedin.com", "wikipedia.org", "bing.com",
            "microsoft.com", "duckduckgo.com",
        }
        try:
            parsed = urlparse(url)
            domain = parsed.netloc.lower()
            return parsed.scheme in ("http", "https") and not any(ex in domain for ex in excluded)
        except Exception:
            return False

    def reset_health(self):
        self._engine_health = {"duckduckgo": True, "bing": True, "google": True}
