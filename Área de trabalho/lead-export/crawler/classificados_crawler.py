import logging
import requests
from bs4 import BeautifulSoup
from urllib.parse import quote_plus
from concurrent.futures import ThreadPoolExecutor, as_completed
from config import Config
from crawler.search_engine import SearchEngine
from crawler.utils import (
    safe_request,
    extract_emails_from_text,
    extract_phones_from_text,
    extract_whatsapp_from_html,
    extract_social_from_html,
)

logger = logging.getLogger("leadhunter.classificados")


class ClassificadosCrawler:
    """Crawler de classificados/marketplaces brasileiros.

    Fontes:
    - OLX (anunciantes profissionais)
    - Mercado Livre (vendedores pro)
    - GetNinjas (profissionais)
    - 99freelas (freelancers/empresas)
    - Workana
    - iFood Partner / Rappi

    Estes sites têm alta concentração de pequenos negócios
    que precisam de sites, sistemas e marketing digital.
    """

    def __init__(self):
        self.session = requests.Session()
        self.session.headers.update({
            "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
            "Accept-Language": "pt-BR,pt;q=0.9,en;q=0.8",
            "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36",
        })
        self.search_engine = SearchEngine()

    def crawl(self, keyword, location="", quantity=30, on_progress=None):
        leads = []

        # Busca paralela em múltiplas fontes de classificados
        with ThreadPoolExecutor(max_workers=5) as executor:
            futures = {
                executor.submit(self._crawl_olx_dork, keyword, location, quantity): "olx",
                executor.submit(self._crawl_getninjas_dork, keyword, location, quantity): "getninjas",
                executor.submit(self._crawl_freelancers_dork, keyword, location, quantity): "freelancers",
                executor.submit(self._crawl_local_services, keyword, location, quantity): "local_services",
                executor.submit(self._crawl_marketplace_sellers, keyword, location, quantity): "marketplace",
            }

            for future in as_completed(futures, timeout=90):
                source = futures[future]
                try:
                    results = future.result(timeout=60)
                    leads.extend(results)
                    logger.debug(f"[classificados] {source}: {len(results)} leads")
                except Exception as e:
                    logger.debug(f"[classificados] {source} falhou: {e}")

        if on_progress and leads:
            on_progress(len(leads))

        logger.info(f"[classificados] '{keyword}' — {len(leads)} leads de classificados")
        return leads[:quantity]

    def _crawl_olx_dork(self, keyword, location, quantity):
        """Busca anunciantes profissionais da OLX via dorks."""
        leads = []
        dorks = [
            f'site:olx.com.br "{keyword}" "{location}" "profissional"',
            f'site:olx.com.br/pro "{keyword}" "{location}"',
        ]

        for dork in dorks[:1]:
            try:
                urls = self.search_engine._search_bing(dork, 10)
                for url in urls[:8]:
                    lead = self._extract_classified_lead(url, "olx")
                    if lead:
                        leads.append(lead)
            except Exception:
                continue

        return leads[:quantity // 4]

    def _crawl_getninjas_dork(self, keyword, location, quantity):
        """Busca profissionais do GetNinjas."""
        leads = []
        dorks = [
            f'site:getninjas.com.br "{keyword}" "{location}"',
            f'site:getninjas.com.br "{keyword}" "contato" "orçamento"',
        ]

        for dork in dorks[:1]:
            try:
                urls = self.search_engine._search_bing(dork, 10)
                for url in urls[:8]:
                    lead = self._extract_classified_lead(url, "getninjas")
                    if lead:
                        leads.append(lead)
            except Exception:
                continue

        return leads[:quantity // 4]

    def _crawl_freelancers_dork(self, keyword, location, quantity):
        """Busca empresas/freelancers em plataformas."""
        leads = []
        dorks = [
            f'site:99freelas.com.br "{keyword}"',
            f'site:workana.com "{keyword}" "Brasil"',
        ]

        for dork in dorks[:1]:
            try:
                urls = self.search_engine._search_bing(dork, 8)
                for url in urls[:5]:
                    lead = self._extract_classified_lead(url, "freelancers")
                    if lead:
                        leads.append(lead)
            except Exception:
                continue

        return leads[:quantity // 5]

    def _crawl_local_services(self, keyword, location, quantity):
        """Busca serviços locais em sites de agendamento/serviços."""
        leads = []
        dorks = [
            f'"{keyword}" "{location}" "agendar" "whatsapp" site:.com.br',
            f'"{keyword}" "{location}" "orçamento" "contato" -site:facebook.com -site:instagram.com',
            f'"{keyword}" "{location}" inurl:contato site:.com.br',
        ]

        for dork in dorks[:2]:
            try:
                urls = self.search_engine._search_bing(dork, 10)
                for url in urls[:8]:
                    lead = self._extract_service_page(url)
                    if lead:
                        leads.append(lead)
            except Exception:
                continue

        return leads[:quantity // 3]

    def _crawl_marketplace_sellers(self, keyword, location, quantity):
        """Busca vendedores profissionais em marketplaces."""
        leads = []
        dorks = [
            f'site:shopee.com.br "{keyword}" "loja oficial"',
            f'site:elo7.com.br "{keyword}" "{location}"',
        ]

        for dork in dorks[:1]:
            try:
                urls = self.search_engine._search_bing(dork, 8)
                for url in urls[:5]:
                    lead = self._extract_classified_lead(url, "marketplace")
                    if lead:
                        leads.append(lead)
            except Exception:
                continue

        return leads[:quantity // 5]

    def _extract_classified_lead(self, url, source):
        """Extrai lead de uma página de classificado."""
        try:
            response = safe_request(self.session, url, timeout=Config.CRAWLER_TIMEOUT)
            if not response or response.status_code != 200:
                return None

            html = response.text
            soup = BeautifulSoup(html, "lxml")
            text = soup.get_text(separator=" ")

            phones = extract_phones_from_text(text)
            emails = extract_emails_from_text(text, html)
            whatsapp = extract_whatsapp_from_html(html)

            if not (emails or phones or whatsapp):
                return None

            # Extrair nome
            title = soup.find("title")
            name = title.get_text().strip()[:80] if title else "Lead Classificados"
            # Limpa nome
            for remove in ["- OLX", "| GetNinjas", "| 99Freelas", "| Workana", "- Shopee", "| Elo7"]:
                name = name.replace(remove, "").strip()

            social = extract_social_from_html(html)

            return {
                "name": name,
                "email": ";".join(emails),
                "phone": ";".join(phones),
                "whatsapp": whatsapp or (phones[0] if phones else ""),
                "website": url,
                "instagram": social.get("instagram", ""),
                "facebook": social.get("facebook", ""),
                "source": f"classificados_{source}",
                "category": "",
            }
        except Exception:
            return None

    def _extract_service_page(self, url):
        """Extrai lead de uma página de serviço local."""
        try:
            response = safe_request(self.session, url, timeout=Config.CRAWLER_TIMEOUT)
            if not response or response.status_code != 200:
                return None

            html = response.text
            soup = BeautifulSoup(html, "lxml")
            text = soup.get_text(separator=" ")

            from crawler.utils import extract_business_name
            from urllib.parse import urlparse

            emails = extract_emails_from_text(text, html)
            phones = extract_phones_from_text(text)
            whatsapp = extract_whatsapp_from_html(html)
            social = extract_social_from_html(html)

            if not (emails or phones or whatsapp):
                return None

            name = extract_business_name(soup, urlparse(url).netloc)

            return {
                "name": name,
                "email": ";".join(emails),
                "phone": ";".join(phones),
                "whatsapp": whatsapp or (phones[0] if phones else ""),
                "website": url,
                "instagram": social.get("instagram", ""),
                "facebook": social.get("facebook", ""),
                "source": "local_services",
                "category": "",
            }
        except Exception:
            return None
