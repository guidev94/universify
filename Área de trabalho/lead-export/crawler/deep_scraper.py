import requests
import re
import logging
from bs4 import BeautifulSoup
from database import get_connection

logger = logging.getLogger("leadhunter.deep_scraper")

class DeepContactScraper:
    """Explores internal pages (/contato, /sobre) to find more contacts."""
    
    def __init__(self, session=None):
        from fake_useragent import UserAgent
        self.ua = UserAgent()
        self.session = session or requests.Session()

    def scrape_additional_info(self, lead_id, website):
        if not website:
            return None
            
        try:
            if not website.startswith("http"):
                website = "https://" + website
            
            # Initial home page fetch
            self.session.headers["User-Agent"] = self.ua.random
            response = self.session.get(website, timeout=12)
            if response.status_code != 200:
                return None
                
            soup = BeautifulSoup(response.text, "lxml")
            
            # Find candidate links for "contact" or "about"
            internal_links = []
            for a in soup.find_all("a", href=True):
                href = a["href"].lower()
                text = a.get_text().lower()
                
                if any(k in href or k in text for k in ["contato", "fale", "sobre", "about", "contact", "equipe", "team"]):
                    # Resolve relative URLs
                    if href.startswith("/"):
                        href = website.rstrip("/") + href
                    elif not href.startswith("http"):
                        href = website.rstrip("/") + "/" + href
                        
                    if website in href and href not in internal_links:
                        internal_links.append(href)
            
            found_data = {
                "emails": set(),
                "phones": set(),
                "possible_owners": []
            }
            
            # Scrape up to 3 candidate pages in parallel (Performance)
            from concurrent.futures import ThreadPoolExecutor
            with ThreadPoolExecutor(max_workers=3) as executor:
                futures = []
                for link in internal_links[:3]:
                    def _fetch(l):
                        try:
                            return self.session.get(l, timeout=8).text
                        except: return None
                    futures.append(executor.submit(_fetch, link))
                
                for f in futures:
                    html_page = f.result()
                    if html_page:
                        self._extract_info(html_page, found_data)
            
            if found_data["emails"] or found_data["phones"] or found_data["possible_owners"]:
                self._update_db(lead_id, found_data)
                return found_data
                
        except Exception as e:
            logger.debug(f"[deep-scraper] failed for {website}: {e}")
            
        return None

    def _extract_info(self, html, store):
        from crawler.utils import extract_emails_from_text, extract_phones_from_text, extract_whatsapp_from_html
        
        # Emails and Phones
        store["emails"].update(extract_emails_from_text(html))
        store["phones"].update(extract_phones_from_text(html))
        
        # WhatsApp Direct
        wa = extract_whatsapp_from_html(html)
        if wa: store["phones"].add(wa)
        
        # CNPJ Detection
        cnpj_match = re.search(r"\d{2}\.?\d{3}\.?\d{3}/?\d{4}-?\d{2}", html)
        if cnpj_match:
            store["cnpj"] = cnpj_match.group()
        
        # Advanced Decision Maker Detection
        soup = BeautifulSoup(html, "lxml")
        
        # 1. Check for Social Links (LinkedIn/Instagram) that might point to personal profiles
        for a in soup.find_all("a", href=True):
            href = a["href"].lower()
            if "linkedin.com/in/" in href:
                name = href.split("/in/")[1].split("/")[0].replace("-", " ").title()
                if len(name) > 3: store["possible_owners"].append(f"{name} (via LinkedIn)")

        # 2. Extract names after titles (including plural/variations)
        text = soup.get_text(separator=" ")
        patterns = [
            r"(?:sócio|fundador|diretor|ceo|proprietário|gerente|responsável|quem somos|equipe|nossa história)[:\-\s]+([A-Z][a-z]+(?:\s+[A-Z][a-z]+){1,3})",
            r"([A-Z][a-z]+(?:\s+[A-Z][a-z]+){1,3})[\-\s]+(?:sócio|fundador|diretor|ceo|proprietário)",
        ]
        
        for pattern in patterns:
            matches = re.findall(pattern, text)
            for m in matches:
                if len(m) > 5 and m.lower() not in ["contato", "empresa", "telefone", "todos os direitos", "copyright"]:
                    store["possible_owners"].append(m.strip())
        
        # Clean duplicates
        store["possible_owners"] = list(dict.fromkeys(store["possible_owners"]))[:5]

    def _update_db(self, lead_id, data):
        conn = get_connection()
        try:
            lead = conn.execute("SELECT email, phone, partners FROM leads WHERE id = ?", (lead_id,)).fetchone()
            if not lead: return
            
            current_emails = set((lead["email"] or "").split(";"))
            current_phones = set((lead["phone"] or "").split(";"))
            current_partners = (lead["partners"] or "")
            
            new_emails = current_emails | data["emails"]
            new_emails.discard("")
            
            new_phones = current_phones | data["phones"]
            new_phones.discard("")
            
            updates = {
                "email": ";".join(list(new_emails)[:5]),
                "phone": ";".join(list(new_phones)[:5]),
            }
            
            if data.get("cnpj"):
                updates["cnpj"] = data["cnpj"]
            
            if data["possible_owners"]:
                detected = "; ".join(data["possible_owners"][:3])
                if current_partners:
                    updates["partners"] = f"{current_partners} | Detectado: {detected}"
                else:
                    updates["partners"] = f"Detectado: {detected}"
            
            cols = []
            vals = []
            for k, v in updates.items():
                cols.append(f"{k} = ?")
                vals.append(v)
            
            if cols:
                vals.append(lead_id)
                conn.execute(f"UPDATE leads SET {', '.join(cols)}, updated_at = datetime('now','localtime') WHERE id = ?", vals)
                conn.commit()
        finally:
            conn.close()
