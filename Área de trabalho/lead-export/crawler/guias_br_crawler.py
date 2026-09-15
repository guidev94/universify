import logging
import requests
from bs4 import BeautifulSoup
from urllib.parse import urlparse, quote_plus
from concurrent.futures import ThreadPoolExecutor, as_completed
from config import Config
from crawler.utils import (
    safe_request,
    extract_emails_from_text,
    extract_phones_from_text,
    extract_whatsapp_from_html,
    extract_business_name,
    extract_social_from_html,
)

logger = logging.getLogger("leadhunter.guias_br")


class GuiasBRCrawler:
    """Crawler de diretórios brasileiros: TeleListas, GuiaMais, Apontador, Encontra BR.

    Fontes brasileiras com alta densidade de contatos verificados.
    """

    SOURCES = [
        {
            "name": "telelistas",
            "url": "https://www.telelistas.net/busca/{query}/{location}",
            "selector": ".card-body, .result-item, .listing-item",
        },
        {
            "name": "guiamais",
            "url": "https://www.guiamais.com.br/busca/{location}/{query}",
            "selector": ".empresa, .result, .listing",
        },
        {
            "name": "encontra_br",
            "url": "https://www.encontrabr.com.br/busca?q={query}+{location}",
            "selector": ".resultado, .empresa, .listing-card",
        },
        {
            "name": "hagah",
            "url": "https://www.hagah.com.br/busca/{query}+{location}",
            "selector": ".listing, .result-card, .empresa",
        },
    ]

    def __init__(self):
        self.session = requests.Session()
        self.session.headers.update({
            "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
            "Accept-Language": "pt-BR,pt;q=0.9,en;q=0.8",
            "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36",
        })

    def crawl(self, keyword, location="", quantity=30, on_progress=None):
        leads = []
        keyword_encoded = quote_plus(keyword)
        location_encoded = quote_plus(location) if location else "brasil"

        # Busca em paralelo em todas as fontes
        with ThreadPoolExecutor(max_workers=len(self.SOURCES)) as executor:
            futures = {}
            for source in self.SOURCES:
                url = source["url"].format(
                    query=keyword_encoded,
                    location=location_encoded,
                )
                futures[executor.submit(self._crawl_source, url, source)] = source["name"]

            for future in as_completed(futures, timeout=60):
                name = futures[future]
                try:
                    results = future.result(timeout=30)
                    leads.extend(results)
                    logger.debug(f"[guias_br] {name}: {len(results)} leads")
                except Exception as e:
                    logger.debug(f"[guias_br] {name} falhou: {e}")

        # Também busca por dorks específicas de diretórios
        dork_leads = self._search_directory_dorks(keyword, location, quantity)
        leads.extend(dork_leads)

        if on_progress and leads:
            on_progress(len(leads))

        logger.info(f"[guias_br] '{keyword}' — {len(leads)} leads de diretórios BR")
        return leads[:quantity]

    def _crawl_source(self, url, source):
        """Crawla uma fonte de diretório específica."""
        leads = []
        try:
            response = safe_request(self.session, url, timeout=Config.CRAWLER_TIMEOUT)
            if not response or response.status_code != 200:
                return leads

            html = response.text
            soup = BeautifulSoup(html, "lxml")

            # Extrai informações gerais da página
            text = soup.get_text(separator=" ")
            emails = extract_emails_from_text(text, html)
            phones = extract_phones_from_text(text)
            whatsapp = extract_whatsapp_from_html(html)

            # Tenta encontrar listagens individuais
            selectors = source["selector"].split(", ")
            items_found = []
            for sel in selectors:
                items_found.extend(soup.select(sel))

            if items_found:
                for item in items_found[:20]:  # Max 20 por fonte
                    item_html = str(item)
                    item_text = item.get_text(separator=" ")
                    item_emails = extract_emails_from_text(item_text, item_html)
                    item_phones = extract_phones_from_text(item_text)
                    item_whatsapp = extract_whatsapp_from_html(item_html)

                    if item_emails or item_phones or item_whatsapp:
                        # Tenta extrair nome do item
                        name_el = item.find(["h2", "h3", "h4", "strong", ".nome", ".name"])
                        name = name_el.get_text().strip() if name_el else ""
                        if not name:
                            name = item.get_text()[:50].strip()

                        # Tenta extrair website
                        website = ""
                        for a in item.find_all("a", href=True):
                            href = a["href"]
                            if href.startswith("http") and "guiamais" not in href and "telelistas" not in href:
                                website = href
                                break

                        leads.append({
                            "name": name[:100],
                            "email": ";".join(item_emails),
                            "phone": ";".join(item_phones),
                            "whatsapp": item_whatsapp or (item_phones[0] if item_phones else ""),
                            "website": website,
                            "source": f"guias_br_{source['name']}",
                            "category": "",
                        })
            elif emails or phones:
                # Se não encontrou itens individuais, usa dados gerais
                leads.append({
                    "name": f"Lead de {source['name']}",
                    "email": ";".join(emails[:3]),
                    "phone": ";".join(phones[:3]),
                    "whatsapp": whatsapp or (phones[0] if phones else ""),
                    "website": "",
                    "source": f"guias_br_{source['name']}",
                    "category": "",
                })

        except Exception as e:
            logger.debug(f"[guias_br] erro em {url}: {e}")

        return leads

    def _search_directory_dorks(self, keyword, location, quantity):
        """Busca via dorks em diretórios brasileiros."""
        from crawler.search_engine import SearchEngine

        leads = []
        se = SearchEngine()

        dorks = [
            f'site:telelistas.net "{keyword}" "{location}"',
            f'site:guiamais.com.br "{keyword}" "{location}"',
            f'site:listamais.com.br "{keyword}"',
            f'"{keyword}" "{location}" "telefone" "email" site:.com.br',
        ]

        for dork in dorks[:2]:  # Limita a 2 dorks para não saturar
            try:
                urls = se._search_bing(dork, 10)
                for url in urls[:5]:
                    page_leads = self._extract_from_directory_page(url)
                    leads.extend(page_leads)
            except Exception:
                continue

        return leads[:quantity // 2]

    def _extract_from_directory_page(self, url):
        """Extrai leads de uma página de resultado de diretório."""
        leads = []
        try:
            response = safe_request(self.session, url, timeout=Config.CRAWLER_TIMEOUT)
            if not response or response.status_code != 200:
                return leads

            html = response.text
            soup = BeautifulSoup(html, "lxml")
            text = soup.get_text(separator=" ")

            emails = extract_emails_from_text(text, html)
            phones = extract_phones_from_text(text)
            whatsapp = extract_whatsapp_from_html(html)
            social = extract_social_from_html(html)

            if emails or phones or whatsapp:
                name = extract_business_name(soup, urlparse(url).netloc)
                leads.append({
                    "name": name,
                    "email": ";".join(emails),
                    "phone": ";".join(phones),
                    "whatsapp": whatsapp or (phones[0] if phones else ""),
                    "website": url,
                    "instagram": social.get("instagram", ""),
                    "facebook": social.get("facebook", ""),
                    "source": "guias_br_dork",
                    "category": "",
                })
        except Exception:
            pass

        return leads
