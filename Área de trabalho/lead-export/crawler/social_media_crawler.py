import logging
import requests
import re
from bs4 import BeautifulSoup
from concurrent.futures import ThreadPoolExecutor, as_completed
from config import Config
from crawler.search_engine import SearchEngine
from crawler.utils import (
    safe_request,
    extract_emails_from_text,
    extract_phones_from_text,
    extract_whatsapp_from_html,
)

logger = logging.getLogger("leadhunter.social")


class SocialMediaCrawler:
    """Crawler de redes sociais via dorks (sem API).

    Busca perfis comerciais com contatos expostos em:
    - Facebook Pages (com telefone/email visíveis)
    - Instagram Business (com links na bio)
    - Linktree/links em bio
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

        with ThreadPoolExecutor(max_workers=4) as executor:
            futures = {
                executor.submit(self._crawl_facebook_pages, keyword, location, quantity): "facebook",
                executor.submit(self._crawl_instagram_bios, keyword, location, quantity): "instagram",
                executor.submit(self._crawl_linktrees, keyword, location, quantity): "linktree",
                executor.submit(self._crawl_google_maps_organic, keyword, location, quantity): "gmaps_organic",
            }

            for future in as_completed(futures, timeout=90):
                source = futures[future]
                try:
                    results = future.result(timeout=60)
                    leads.extend(results)
                    logger.debug(f"[social] {source}: {len(results)} leads")
                except Exception as e:
                    logger.debug(f"[social] {source} falhou: {e}")

        if on_progress and leads:
            on_progress(len(leads))

        logger.info(f"[social] '{keyword}' — {len(leads)} leads de redes sociais")
        return leads[:quantity]

    def _crawl_facebook_pages(self, keyword, location, quantity):
        """Busca Facebook Pages comerciais com contatos."""
        leads = []
        dorks = [
            f'site:facebook.com "{keyword}" "{location}" "telefone"',
            f'site:facebook.com "{keyword}" "{location}" "@" ".com.br"',
            f'site:facebook.com/pages "{keyword}" "{location}"',
        ]

        for dork in dorks[:2]:
            try:
                urls = self.search_engine._search_bing(dork, 15)
                for url in urls[:10]:
                    lead = self._extract_facebook_lead(url, keyword)
                    if lead:
                        leads.append(lead)
            except Exception:
                continue

        return leads[:quantity // 3]

    def _crawl_instagram_bios(self, keyword, location, quantity):
        """Busca perfis Instagram comerciais com links de contato."""
        leads = []
        dorks = [
            f'site:instagram.com "{keyword}" "{location}" "whatsapp"',
            f'site:instagram.com "{keyword}" "contato" "link"',
        ]

        for dork in dorks[:1]:
            try:
                urls = self.search_engine._search_bing(dork, 15)
                for url in urls[:10]:
                    lead = self._extract_instagram_lead(url, keyword)
                    if lead:
                        leads.append(lead)
            except Exception:
                continue

        return leads[:quantity // 3]

    def _crawl_linktrees(self, keyword, location, quantity):
        """Busca Linktree/bio links de negócios."""
        leads = []
        dorks = [
            f'site:linktr.ee "{keyword}" "{location}"',
            f'site:linktr.ee "{keyword}" "whatsapp"',
            f'site:bio.link "{keyword}"',
        ]

        for dork in dorks[:2]:
            try:
                urls = self.search_engine._search_bing(dork, 10)
                for url in urls[:8]:
                    lead = self._extract_linktree_lead(url, keyword)
                    if lead:
                        leads.append(lead)
            except Exception:
                continue

        return leads[:quantity // 4]

    def _crawl_google_maps_organic(self, keyword, location, quantity):
        """Busca listagens do Google Maps via HTML (sem API)."""
        leads = []
        dork = f'site:google.com/maps/place "{keyword}" "{location}"'

        try:
            urls = self.search_engine._search_bing(dork, 15)
            for url in urls[:10]:
                lead = self._extract_gmaps_lead(url, keyword)
                if lead:
                    leads.append(lead)
        except Exception:
            pass

        return leads[:quantity // 4]

    def _extract_facebook_lead(self, url, keyword):
        """Extrai dados de uma Facebook Page."""
        try:
            response = safe_request(self.session, url, timeout=Config.CRAWLER_TIMEOUT)
            if not response or response.status_code != 200:
                return None

            html = response.text
            text = BeautifulSoup(html, "lxml").get_text(separator=" ")

            emails = extract_emails_from_text(text, html)
            phones = extract_phones_from_text(text)
            whatsapp = extract_whatsapp_from_html(html)

            if not (emails or phones or whatsapp):
                return None

            # Extrair nome do perfil
            name_match = re.search(r'"name":"([^"]+)"', html)
            name = name_match.group(1) if name_match else url.split("/")[-1].replace("-", " ").title()

            return {
                "name": name[:100],
                "email": ";".join(emails),
                "phone": ";".join(phones),
                "whatsapp": whatsapp or (phones[0] if phones else ""),
                "website": url,
                "source": "facebook_page",
                "category": keyword,
            }
        except Exception:
            return None

    def _extract_instagram_lead(self, url, keyword):
        """Extrai dados de perfil Instagram."""
        try:
            # Instagram bloqueia scraping direto, então extraímos da SERP
            handle = url.rstrip("/").split("/")[-1]
            if handle in ("p", "explore", "reel", "stories", "accounts", ""):
                return None

            return {
                "name": handle.replace(".", " ").replace("_", " ").title(),
                "email": "",
                "phone": "",
                "whatsapp": "",
                "website": url,
                "instagram": f"@{handle}",
                "source": "instagram_bio",
                "category": keyword,
            }
        except Exception:
            return None

    def _extract_linktree_lead(self, url, keyword):
        """Extrai dados de um Linktree."""
        try:
            response = safe_request(self.session, url, timeout=Config.CRAWLER_TIMEOUT)
            if not response or response.status_code != 200:
                return None

            html = response.text
            soup = BeautifulSoup(html, "lxml")
            text = soup.get_text(separator=" ")

            emails = extract_emails_from_text(text, html)
            phones = extract_phones_from_text(text)
            whatsapp = extract_whatsapp_from_html(html)

            if not (emails or phones or whatsapp):
                return None

            # Nome do linktree
            title = soup.find("title")
            name = title.get_text().strip() if title else url.split("/")[-1].replace("-", " ").title()
            name = name.replace("| Linktree", "").replace("| Instagram", "").strip()

            # Website externo
            website = ""
            for a in soup.find_all("a", href=True):
                href = a["href"]
                if href.startswith("http") and "linktr.ee" not in href and "instagram.com" not in href:
                    website = href
                    break

            return {
                "name": name[:100],
                "email": ";".join(emails),
                "phone": ";".join(phones),
                "whatsapp": whatsapp or (phones[0] if phones else ""),
                "website": website or url,
                "source": "linktree",
                "category": keyword,
            }
        except Exception:
            return None

    def _extract_gmaps_lead(self, url, keyword):
        """Extrai dados de listagem Google Maps."""
        try:
            # Google Maps pages têm dados estruturados
            response = safe_request(self.session, url, timeout=Config.CRAWLER_TIMEOUT)
            if not response or response.status_code != 200:
                return None

            html = response.text
            text = BeautifulSoup(html, "lxml").get_text(separator=" ")

            phones = extract_phones_from_text(text)
            if not phones:
                return None

            # Extrai nome do lugar
            name_match = re.search(r'"([^"]{3,50})".*?maps', html[:5000])
            name = name_match.group(1) if name_match else "Negócio Local"

            return {
                "name": name[:100],
                "email": "",
                "phone": ";".join(phones),
                "whatsapp": phones[0] if phones else "",
                "website": "",
                "source": "google_maps_organic",
                "category": keyword,
            }
        except Exception:
            return None
