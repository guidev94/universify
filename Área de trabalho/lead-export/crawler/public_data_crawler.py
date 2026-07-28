
import requests
import re
import logging
import time
from bs4 import BeautifulSoup
from fake_useragent import UserAgent

logger = logging.getLogger("leadhunter.public_data")

class PublicDataCrawler:
    """Crawls public data sites like CNPJ.biz to find new high-potential companies."""
    
    def __init__(self):
        self.ua = UserAgent()
        self.session = requests.Session()
        
    def crawl(self, query, location, quantity):
        """
        Query example: 'Logística', 'Indústria'
        Uses CNPJ.biz filters for NEW companies if possible.
        """
        leads = []
        # CNPJ.biz structure for search
        search_query = f"{query} {location}".replace(" ", "+")
        url = f"https://cnpj.biz/procura/{search_query}"
        
        try:
            self.session.headers["User-Agent"] = self.ua.random
            response = self.session.get(url, timeout=15)
            
            if response.status_code != 200:
                return []
                
            soup = BeautifulSoup(response.text, "lxml")
            # This is a generic selector, might need adjustment per site structure
            items = soup.select("div.list-item, div.card, article")
            
            for item in items[:quantity]:
                name_el = item.select_one("h2, h3, a.title")
                if not name_el: continue
                
                name = name_el.get_text(strip=True)
                # CNPJ.biz often has the link to details
                link = name_el.get("href") if name_el.name == "a" else item.select_one("a")["href"]
                
                if link:
                    if not link.startswith("http"):
                        link = "https://cnpj.biz" + link
                    
                    detail_lead = self._get_details(link)
                    if detail_lead:
                        detail_lead["category"] = query
                        detail_lead["source"] = "cnpj_biz"
                        leads.append(detail_lead)
                        
            return leads
        except Exception as e:
            logger.error(f"[public-data] crawl failed: {e}")
            return []

    def _get_details(self, url):
        try:
            time.sleep(2) # Avoid aggressive scraping
            res = self.session.get(url, timeout=10)
            if res.status_code != 200: return None
            
            soup = BeautifulSoup(res.text, "lxml")
            text = soup.get_text()
            
            # Extract basic info
            phone_match = re.search(r"\(?\d{2}\)?\s?9?\d{4}[-\s]?\d{4}", text)
            email_match = re.search(r"[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}", text)
            
            # CNPJ.biz specific: often lists website and capital social
            website = ""
            website_el = soup.find("a", string=re.compile(r"Visitar Website|Site Oficial", re.I))
            if website_el: website = website_el["href"]
            
            return {
                "name": soup.find("h1").get_text(strip=True) if soup.find("h1") else "Empresa",
                "phone": phone_match.group() if phone_match else "",
                "email": email_match.group() if email_match else "",
                "website": website,
                "whatsapp": "", # Will be filled by validator/scorer
            }
        except:
            return None
