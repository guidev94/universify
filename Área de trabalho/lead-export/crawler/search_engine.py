import time
import logging
import requests
from bs4 import BeautifulSoup
from fake_useragent import UserAgent
from urllib.parse import urlparse, unquote, urlencode
import os

logger = logging.getLogger("leadhunter.search_engine")


class SearchEngine:
    def __init__(self):
        self.ua = UserAgent()
        self.delay = int(os.getenv("REQUEST_DELAY_MS", "1500")) / 1000
        self.session = requests.Session()
        
        self.session.headers.update({
            "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8",
            "Accept-Language": "pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7",
            "DNT": "1",
            "Connection": "keep-alive",
            "Upgrade-Insecure-Requests": "1",
        })
        self._engine_health = {"duckduckgo": 0, "bing": 0, "google": 0}

    def _is_healthy(self, engine):
        cooldown = self._engine_health.get(engine, 0)
        if cooldown == 0: return True
        if time.time() > cooldown:
            self._engine_health[engine] = 0
            return True
        return False

    def _mark_unhealthy(self, engine, duration=600):
        self._engine_health[engine] = time.time() + duration
        logger.warning(f"[search] {engine} marcado como instável por {duration}s")

    def _rotate_identity(self):
        import random
        ua_list = [
            "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36",
            "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
            "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36",
            "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:122.0) Gecko/20100101 Firefox/122.0",
        ]
        self.session.headers["User-Agent"] = random.choice(ua_list)
        self.session.cookies.clear()
        # Add Jitter to delay
        self.current_delay = self.delay * random.uniform(0.8, 2.5)

    def search_linkedin(self, keyword, location="", num_results=10):
        query = f'site:linkedin.com/in/ "{keyword}" "{location}"'
        # Try Bing first for LinkedIn as it's more stable
        return self._search_bing(query, num_results)

    def search(self, query, num_results=30):
        self._rotate_identity()
        from concurrent.futures import ThreadPoolExecutor
        
        all_urls = []
        # Adicionando Google Dorks para encontrar leads específicos
        dorks = [
            f'"{query}" contact',
            f'"{query}" "whatsapp"',
            f'"{query}" "telefone"',
            f'"{query}" "contato"',
            f'"{query}" "fale conosco"',
        ]
        
        # Embaralha dorks para variar resultados
        import random
        active_query = f"{query} {random.choice(dorks)}" if random.random() > 0.5 else query

        engines = [
            ("bing", self._search_bing),
            ("duckduckgo", self._search_duckduckgo),
            ("google", self._search_google),
        ]
        
        healthy_engines = [e for e in engines if self._is_healthy(e[0])]
        if not healthy_engines:
            self._engine_health = {"duckduckgo": 0, "bing": 0, "google": 0}
            healthy_engines = engines

        target_per_engine = max(15, int(num_results / len(healthy_engines)) + 10)

        with ThreadPoolExecutor(max_workers=len(healthy_engines)) as executor:
            future_to_engine = {
                executor.submit(fn, active_query, target_per_engine): name 
                for name, fn in healthy_engines
            }
            
            for future in future_to_engine:
                name = future_to_engine[future]
                try:
                    urls = future.result(timeout=35)
                    if urls:
                        logger.info(f"[search] {name} retornou {len(urls)} URLs")
                        all_urls.extend(urls)
                except Exception as e:
                    logger.debug(f"[search] {name} falhou: {e}")
                    self._mark_unhealthy(name, duration=300)

        unique_urls = list(dict.fromkeys(all_urls))
        return unique_urls[:num_results]

    def _search_duckduckgo(self, query, num_results):
        urls = []
        data = {"q": query, "kl": "br-pt"}
        try:
            # Prefer GET for DDG as it is less likely to trigger challenge 202
            response = self.session.get("https://html.duckduckgo.com/html/", params=data, timeout=15)
            if response.status_code == 202:
                self._mark_unhealthy("duckduckgo", 600)
                return []
            
            if response.status_code == 200:
                soup = BeautifulSoup(response.text, "lxml")
                for link in soup.select("a.result__a"):
                    href = link.get("href", "")
                    if "uddg=" in href:
                        try:
                            real_url = unquote(href.split("uddg=")[1].split("&")[0])
                            if self._is_valid_url(real_url): urls.append(real_url)
                        except: continue
                    elif href.startswith("http") and self._is_valid_url(href):
                        urls.append(href)
        except:
            pass
        return urls[:num_results]

    def _search_bing(self, query, num_results):
        urls = []
        for page in range(min(2, int(num_results // 10) + 1)):
            params = {"q": query, "first": page * 10 + 1, "setlang": "pt-BR"}
            try:
                response = self.session.get("https://www.bing.com/search", params=params, timeout=15)
                if response.status_code == 429:
                    self._mark_unhealthy("bing", 600)
                    break
                if response.status_code == 200:
                    soup = BeautifulSoup(response.text, "lxml")
                    for link in soup.select("li.b_algo h2 a, .b_algo a"):
                        href = link.get("href", "")
                        if href.startswith("http") and self._is_valid_url(href):
                            urls.append(href)
                time.sleep(self.delay)
            except: break
        return urls[:num_results]

    def _search_google(self, query, num_results):
        urls = []
        try:
            params = {"q": query, "hl": "pt-BR"}
            response = self.session.get("https://www.google.com/search", params=params, timeout=15)
            if response.status_code == 429:
                self._mark_unhealthy("google", 1200)
                return []
            if response.status_code == 200:
                soup = BeautifulSoup(response.text, "lxml")
                for link in soup.select("a[href]"):
                    href = link.get("href", "")
                    if href.startswith("/url?q="):
                        try:
                            real_url = href.split("/url?q=")[1].split("&")[0]
                            if self._is_valid_url(real_url): urls.append(real_url)
                        except: continue
        except: pass
        return urls[:num_results]

    def _is_valid_url(self, url):
        excluded = {"google.com", "youtube.com", "facebook.com", "instagram.com", "twitter.com", "linkedin.com", "bing.com", "duckduckgo.com"}
        try:
            domain = urlparse(url).netloc.lower()
            return not any(ex in domain for ex in excluded)
        except: return False

    def find_business_website(self, name, location=""):
        query = f"{name} {location} site oficial".strip()
        urls = self.search(query, num_results=5)
        for url in urls:
            if not any(ex in url.lower() for ex in ["guiamais", "apontador", "cnpj.biz", "facebook"]):
                return url
        return ""
