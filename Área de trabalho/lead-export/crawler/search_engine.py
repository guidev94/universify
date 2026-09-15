import time
import logging
import random
import requests
from bs4 import BeautifulSoup
from urllib.parse import urlparse, unquote
from concurrent.futures import ThreadPoolExecutor, as_completed
from config import Config

logger = logging.getLogger("leadhunter.search_engine")


class SearchEngine:
    """Motor de busca multi-engine com fallback automático e delay mínimo.

    Otimizações vs versão anterior:
    - Delay reduzido de 1.2-3.5x para 0.3-0.8x do REQUEST_DELAY_MS
    - Busca paralela em múltiplos engines simultaneamente
    - Sem sleep() bloqueante em _rotate_identity()
    - Pool de User-Agents fixo (sem import dinâmico)
    - Cooldown inteligente por engine
    """

    # Pool fixo de User-Agents realistas (evita import de fake_useragent a cada instância)
    _UA_POOL = [
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36",
        "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36",
        "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36",
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:125.0) Gecko/20100101 Firefox/125.0",
        "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4 Safari/605.1.15",
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36 Edg/123.0.0.0",
        "Mozilla/5.0 (X11; Linux x86_64; rv:125.0) Gecko/20100101 Firefox/125.0",
        "Mozilla/5.0 (iPhone; CPU iPhone OS 17_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4 Mobile/15E148 Safari/604.1",
    ]

    # Domínios que nunca devem aparecer como resultados
    _EXCLUDED_DOMAINS = frozenset({
        "google.com", "google.com.br", "youtube.com", "facebook.com",
        "instagram.com", "twitter.com", "x.com", "linkedin.com",
        "bing.com", "duckduckgo.com", "microsoft.com", "apple.com",
        "pinterest.com", "tiktok.com", "reddit.com", "wikipedia.org",
    })

    def __init__(self):
        self.delay = Config.REQUEST_DELAY_MS / 1000  # em segundos
        self.session = requests.Session()
        self.session.headers.update({
            "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8",
            "Accept-Language": "pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7",
            "DNT": "1",
            "Connection": "keep-alive",
            "Upgrade-Insecure-Requests": "1",
        })
        self._engine_health = {"duckduckgo": 0, "bing": 0, "google": 0}
        self._lock = __import__("threading").Lock()

    def _is_healthy(self, engine):
        cooldown = self._engine_health.get(engine, 0)
        if cooldown == 0:
            return True
        if time.time() > cooldown:
            self._engine_health[engine] = 0
            return True
        return False

    def _mark_unhealthy(self, engine, duration=300):
        with self._lock:
            self._engine_health[engine] = time.time() + duration
        logger.warning(f"[search] {engine} marcado instável por {duration}s")

    def _rotate_identity(self):
        """Troca User-Agent SEM sleep bloqueante."""
        self.session.headers["User-Agent"] = random.choice(self._UA_POOL)
        self.session.headers.update({
            "Sec-Ch-Ua": '"Chromium";v="124", "Google Chrome";v="124", "Not-A.Brand";v="99"',
            "Sec-Ch-Ua-Mobile": "?0",
            "Sec-Ch-Ua-Platform": random.choice(['"Windows"', '"macOS"', '"Linux"']),
            "Sec-Fetch-Dest": "document",
            "Sec-Fetch-Mode": "navigate",
            "Sec-Fetch-Site": "none",
            "Sec-Fetch-User": "?1",
        })
        self.session.cookies.clear()

        # Proxy rotation
        if Config.PROXY_LIST:
            proxy = random.choice(Config.PROXY_LIST)
            self.session.proxies = {"http": proxy, "https": proxy}

    def search(self, query, num_results=30):
        """Busca paralela em múltiplos engines. Retorna lista de URLs únicos."""
        self._rotate_identity()

        # Boost Brazilian results by adding site:.br or Brazilian keywords to some engine queries
        br_queries = [
            query,
            f"{query} site:.br",
            f"{query} brasil",
            f"{query} \"contato\"",
        ]
        
        engines = [
            ("duckduckgo", self._search_duckduckgo),
            ("bing", self._search_bing),
            ("google", self._search_google),
        ]

        healthy_engines = [e for e in engines if self._is_healthy(e[0])]
        if not healthy_engines:
            # Reset forçado
            self._engine_health = {"duckduckgo": 0, "bing": 0, "google": 0}
            healthy_engines = engines

        target_per_engine = max(15, num_results // len(healthy_engines) + 10)

        all_urls = []

        # Busca paralela em todos os engines saudáveis
        with ThreadPoolExecutor(max_workers=len(healthy_engines)) as executor:
            futures = {}
            for i, (name, fn) in enumerate(healthy_engines):
                # Distribute different BR query variations among engines
                engine_query = br_queries[i % len(br_queries)]
                futures[executor.submit(fn, engine_query, target_per_engine)] = name

            for future in as_completed(futures, timeout=25):
                name = futures[future]
                try:
                    urls = future.result(timeout=20)
                    if urls:
                        logger.debug(f"[search] {name} retornou {len(urls)} URLs")
                        all_urls.extend(urls)
                    elif name != "google":  # Google é mais restritivo
                        self._mark_unhealthy(name, duration=300) # Aumentado de 120 para 300
                except Exception as e:
                    logger.debug(f"[search] {name} falhou: {e}")
                    self._mark_unhealthy(name, duration=600) # Aumentado de 180 para 600

        # Dedup preservando ordem
        unique_urls = list(dict.fromkeys(all_urls))
        return unique_urls[:num_results]

    def search_linkedin(self, keyword, location="", num_results=10):
        query = f'site:linkedin.com/in/ "{keyword}" "{location}" (CEO OR "Sócio" OR "Diretor" OR "Fundador")'
        return self._search_bing(query, num_results)

    def search_instagram(self, keyword, location="", num_results=20):
        query = f'site:instagram.com "{keyword}" "{location}" (contato OR whatsapp OR "bit.ly" OR "linktr.ee")'
        return self._search_bing(query, num_results)

    def find_business_website(self, name, location=""):
        query = f"{name} {location} site oficial".strip()
        urls = self.search(query, num_results=5)
        for url in urls:
            if not any(ex in url.lower() for ex in ["guiamais", "apontador", "cnpj.biz", "facebook"]):
                return url
        return ""

    # ─── Engine Implementations ──────────────────────────────────────────────

    def _search_duckduckgo(self, query, num_results):
        """DuckDuckGo via lite/html — rápido e confiável."""
        urls = []
        try:
            # Tenta lite primeiro (mais rápido)
            self.session.headers["User-Agent"] = random.choice(self._UA_POOL)
            response = self.session.get(
                "https://lite.duckduckgo.com/lite/",
                params={"q": query, "kl": "br-pt"},
                timeout=Config.CRAWLER_TIMEOUT,
            )

            if response.status_code == 200:
                soup = BeautifulSoup(response.text, "lxml")
                for link in soup.select("a.result-link"):
                    href = link.get("href", "")
                    if href.startswith("http") and self._is_valid_url(href):
                        urls.append(href)

            # Fallback para HTML se lite falhou
            if len(urls) < 5:
                response = self.session.get(
                    "https://html.duckduckgo.com/html/",
                    params={"q": query, "kl": "br-pt"},
                    timeout=Config.CRAWLER_TIMEOUT,
                )
                if response.status_code == 200:
                    soup = BeautifulSoup(response.text, "lxml")
                    for link in soup.select("a.result__a"):
                        href = link.get("href", "")
                        if "uddg=" in href:
                            try:
                                real_url = unquote(href.split("uddg=")[1].split("&")[0])
                                if self._is_valid_url(real_url):
                                    urls.append(real_url)
                            except Exception:
                                continue
                        elif href.startswith("http") and self._is_valid_url(href):
                            urls.append(href)

        except Exception as e:
            logger.debug(f"[search] duckduckgo error: {e}")

        return list(dict.fromkeys(urls))[:num_results]

    def _search_bing(self, query, num_results):
        """Bing com paginação — melhor rate limit."""
        urls = []
        pages = min(Config.SEARCH_ENGINE_PAGES, (num_results // 10) + 1)

        for page in range(pages):
            if len(urls) >= num_results:
                break

            params = {"q": query, "first": page * 10 + 1, "setlang": "pt-BR"}
            try:
                self.session.headers["User-Agent"] = random.choice(self._UA_POOL)
                response = self.session.get(
                    "https://www.bing.com/search",
                    params=params,
                    timeout=Config.CRAWLER_TIMEOUT,
                )

                if response.status_code == 429:
                    self._mark_unhealthy("bing", 1200) # Aumentado de 600 para 1200
                    break

                if response.status_code == 200:
                    soup = BeautifulSoup(response.text, "lxml")
                    for link in soup.select("li.b_algo h2 a, .b_algo a, #b_results h2 a"):
                        href = link.get("href", "")
                        if href.startswith("http") and self._is_valid_url(href):
                            if href not in urls:
                                urls.append(href)

                # Delay mínimo entre páginas (0.3-0.6x do delay base)
                if page < pages - 1:
                    time.sleep(self.delay * random.uniform(0.3, 0.6))

            except Exception as e:
                logger.debug(f"[search] bing page {page} error: {e}")
                break

        return urls[:num_results]

    def _search_google(self, query, num_results):
        """Google — mais restritivo, usado como fallback."""
        urls = []
        try:
            self.session.headers["User-Agent"] = random.choice(self._UA_POOL)
            params = {"q": query, "hl": "pt-BR", "num": min(num_results, 30)}
            response = self.session.get(
                "https://www.google.com/search",
                params=params,
                timeout=Config.CRAWLER_TIMEOUT,
            )

            if response.status_code == 429:
                self._mark_unhealthy("google", 900)
                return []

            if response.status_code == 200:
                soup = BeautifulSoup(response.text, "lxml")
                for link in soup.select("a[href^='/url?q='], a[href^='http']"):
                    href = link.get("href", "")
                    if href.startswith("/url?q="):
                        try:
                            real_url = href.split("/url?q=")[1].split("&")[0]
                            if self._is_valid_url(real_url):
                                urls.append(real_url)
                        except Exception:
                            continue
                    elif href.startswith("http") and self._is_valid_url(href):
                        if "google.com" not in href and "google.com.br" not in href:
                            urls.append(href)

        except Exception as e:
            logger.debug(f"[search] google error: {e}")

        return list(dict.fromkeys(urls))[:num_results]

    def _is_valid_url(self, url):
        """Validação rápida de URL."""
        try:
            domain = urlparse(url).netloc.lower()
            return not any(ex in domain for ex in self._EXCLUDED_DOMAINS)
        except Exception:
            return False
