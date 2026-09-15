import re
import time
import logging
import requests
from fake_useragent import UserAgent
import os
from crawler.utils import (
    safe_request,
    extract_emails_from_text,
    extract_whatsapp_from_html,
)

logger = logging.getLogger("leadhunter.maps")


class MapsCrawler:
    def __init__(self):
        self.ua = UserAgent()
        self.delay = int(os.getenv("REQUEST_DELAY_MS", "1500")) / 1000
        self.session = requests.Session()

    def crawl(self, query, location, quantity, on_progress=None):
        logger.info(f"[maps] iniciando busca: '{query}' em '{location}'")
        
        # Priority 1: Google Places API (Professional)
        from config import Config
        if Config.GOOGLE_PLACES_API_KEY:
            try:
                leads = self._crawl_api(query, location, quantity, on_progress)
                if leads:
                    logger.info(f"[maps:api] retornou {len(leads)} leads")
                    return leads
            except Exception as e:
                logger.error(f"[maps:api] falha na API Google: {e}")
        
        # Priority 2: SerpApi (Best Fallback - High Reliability)
        if Config.SERPAPI_KEY:
            try:
                leads = self._crawl_serpapi(query, location, quantity, on_progress)
                if leads:
                    logger.info(f"[maps:serpapi] retornou {len(leads)} leads")
                    return leads
            except Exception as e:
                logger.error(f"[maps:serpapi] falha na API SerpApi: {e}")
        
        # Fallback: Selenium Scraping (Free but brittle)
        return self._crawl_scrape(query, location, quantity, on_progress)

    def _crawl_serpapi(self, query, location, quantity, on_progress):
        from config import Config
        api_key = Config.SERPAPI_KEY
        url = "https://serpapi.com/search.json"
        
        params = {
            "engine": "google_maps",
            "q": f"{query} {location}",
            "api_key": api_key,
            "hl": "pt-br",
            "gl": "br"
        }
        
        leads = []
        # SerpApi returns about 20 results per page
        for page in range(3): 
            if len(leads) >= quantity: break
            
            if page > 0:
                params["start"] = page * 20
                
            response = requests.get(url, params=params, timeout=30)
            data = response.json()
            
            results = data.get("local_results", [])
            if not results: break
            
            for item in results:
                if len(leads) >= quantity: break
                
                phone = item.get("phone", "")
                leads.append({
                    "name": item.get("title"),
                    "phone": phone,
                    "whatsapp": phone,
                    "website": item.get("website", ""),
                    "source": "google_maps_serp",
                    "category": item.get("type", "business")
                })
                if on_progress: on_progress(len(leads))
                
        return leads

    def _crawl_api(self, query, location, quantity, on_progress):
        from config import Config
        api_key = Config.GOOGLE_PLACES_API_KEY
        search_url = "https://maps.googleapis.com/maps/api/place/textsearch/json"
        
        params = {
            "query": f"{query} {location}",
            "key": api_key,
            "language": "pt-BR"
        }
        
        leads = []
        next_page_token = None
        
        while len(leads) < quantity:
            if next_page_token:
                params["pagetoken"] = next_page_token
                time.sleep(2) # Google requirements
                
            response = requests.get(search_url, params=params)
            data = response.json()
            
            if data.get("status") != "OK":
                break
                
            for place in data.get("results", []):
                if len(leads) >= quantity: break
                
                place_id = place.get("place_id")
                detail = self._get_place_details(place_id, api_key)
                
                if detail:
                    leads.append(detail)
                    if on_progress: on_progress(len(leads))
            
            next_page_token = data.get("next_page_token")
            if not next_page_token: break
            
        return leads

    def _get_place_details(self, place_id, api_key):
        url = "https://maps.googleapis.com/maps/api/place/details/json"
        params = {
            "place_id": place_id,
            "fields": "name,formatted_phone_number,website,types,international_phone_number",
            "key": api_key,
            "language": "pt-BR"
        }
        
        try:
            response = requests.get(url, params=params)
            data = response.json().get("result", {})
            
            if not data: return None
            
            phone = data.get("formatted_phone_number") or data.get("international_phone_number", "")
            return {
                "name": data.get("name"),
                "phone": phone,
                "whatsapp": phone,
                "website": data.get("website", ""),
                "source": "google_maps_api",
                "category": data.get("types", ["business"])[0]
            }
        except:
            return None

    def _crawl_scrape(self, query, location, quantity, on_progress):
        leads = []
        search_term = f"{query} {location}".strip()
        crawl_start = time.time()
        MAPS_TIMEOUT = 180 # Aumentado de 90 para 180

        try:
            from selenium.webdriver.common.by import By
            from crawler.browser_pool import browser_pool
            
            try:
                driver = browser_pool.get_driver(timeout=45)
            except TimeoutError as e:
                logger.error(f"[maps] pool timeout: {e}")
                return leads

            driver.set_page_load_timeout(20)
            driver.set_script_timeout(15)

            try:
                url = f"https://www.google.com/maps/search/{requests.utils.quote(search_term)}"
                driver.get(url)
                time.sleep(5) # Aumentado de 3 para 5

                self._accept_cookies(driver)

                last_count = 0
                scroll_attempts = 0
                max_scrolls = 20 # Aumentado de 15 para 20

                while len(leads) < quantity and scroll_attempts < max_scrolls:
                    if time.time() - crawl_start > MAPS_TIMEOUT:
                        logger.warning(f"[maps] timeout global de {MAPS_TIMEOUT}s atingido")
                        break

                    # Multiple selectors for robustness
                    results = driver.find_elements(By.CSS_SELECTOR, "a.hfpxzc, a[href*='/maps/place/'], [role='article'] a[href*='/maps/place/']")

                    if len(results) == last_count:
                        scroll_attempts += 1
                        if scroll_attempts > 5:
                            break
                    else:
                        scroll_attempts = 0

                    last_count = len(results)

                    # Try to find the scrollable container
                    feed = driver.find_elements(By.CSS_SELECTOR, "div[role='feed'], div.m679ee-PK9Sce, div.m679ee-PK9Sce-L9619c")
                    if feed:
                        driver.execute_script(
                            "arguments[0].scrollTop = arguments[0].scrollHeight", feed[0]
                        )
                    else:
                        # Fallback scroll
                        driver.execute_script("window.scrollBy(0, 1000);")
                    
                    time.sleep(2.0)

                results = driver.find_elements(By.CSS_SELECTOR, "a.hfpxzc, a[href*='/maps/place/'], [role='article'] a[href*='/maps/place/']")
                logger.info(f"[maps] encontrou {len(results)} resultados no Maps")

                for result in results:
                    if len(leads) >= quantity:
                        break

                    try:
                        lead = self._extract_place_scrape(driver, result)
                        if lead:
                            leads.append(lead)
                            if on_progress:
                                on_progress(len(leads))
                    except Exception as e:
                        logger.debug(f"[maps] erro ao extrair lugar: {e}")
                        continue

            finally:
                from crawler.browser_pool import browser_pool
                browser_pool.return_driver(driver)

        except ImportError:
            logger.error("[maps] selenium não instalado — instale com: pip install selenium webdriver-manager")
        except Exception as e:
            logger.error(f"[maps] erro no scraping: {e}")

        logger.info(f"[maps] '{query}' em '{location}' — {len(leads)} leads extraídos")
        return leads[:quantity]

    def _accept_cookies(self, driver):
        from selenium.webdriver.common.by import By
        try:
            buttons = driver.find_elements(By.CSS_SELECTOR, "button")
            for btn in buttons:
                text = btn.text.lower()
                if "aceitar" in text or "accept" in text or "concordo" in text:
                    btn.click()
                    time.sleep(1)
                    break
        except Exception:
            pass

    def _extract_place_scrape(self, driver, element):
        from selenium.webdriver.common.by import By
        from selenium.webdriver.support.ui import WebDriverWait
        from selenium.webdriver.support import expected_conditions as EC

        try:
            # Garante que o elemento seja clicável
            driver.execute_script("arguments[0].scrollIntoView(true);", element)
            time.sleep(0.5)
            element.click()
            
            # Espera até que o painel lateral carregue (o nome do lugar é um bom indicador)
            WebDriverWait(driver, 5).until(
                EC.presence_of_element_located((By.CSS_SELECTOR, "h1.DUwDvf, h1.fontHeadlineLarge, h1 span"))
            )
            time.sleep(1)

            name = ""
            phone = ""
            website = ""
            category = ""

            # Tenta múltiplos seletores para o nome
            for selector in ["h1.DUwDvf", "h1.fontHeadlineLarge", "h1", "h1 span"]:
                els = driver.find_elements(By.CSS_SELECTOR, selector)
                if els:
                    for el in els:
                        text = el.text.strip()
                        if text and text not in ["Resultados", "Filtros", "Google Maps", "Busca"]:
                            name = text
                            break
                if name: break

            # Tenta múltiplos seletores para o telefone
            info_buttons = driver.find_elements(
                By.CSS_SELECTOR, "button[data-item-id*='phone'], a[data-item-id*='phone'], [aria-label*='Telefone'], [aria-label*='Phone'], [data-tooltip*='Telefone']"
            )
            for btn in info_buttons:
                aria = btn.get_attribute("aria-label") or ""
                tooltip = btn.get_attribute("data-tooltip") or ""
                text = btn.text or ""
                combined = f"{aria} {tooltip} {text}"
                phone_match = re.search(r"(\(?\d{2}\)?\s?\d{4,5}[-\s]?\d{4})", combined)
                if phone_match:
                    phone = phone_match.group(1).strip()
                    break
                
                # Check for international format too
                phone_match_intl = re.search(r"(\+55\s?\(?\d{2}\)?\s?\d{4,5}[-\s]?\d{4})", combined)
                if phone_match_intl:
                    phone = phone_match_intl.group(1).strip()
                    break

            website_els = driver.find_elements(
                By.CSS_SELECTOR, "a[data-item-id='authority'], a[aria-label*='website'], a[aria-label*='Site'], a[data-value='Website']"
            )
            if website_els:
                website = website_els[0].get_attribute("href") or ""

            cat_els = driver.find_elements(By.CSS_SELECTOR, "button[jsaction*='category'], .fontBodyMedium span")
            if cat_els:
                category = cat_els[0].text.strip()

            if not name:
                return None

            # Se não tiver telefone, tenta pegar do site se existir
            # (Removido busca de site aqui para não travar o loop, o pipeline cuida disso)
            
            email = ""
            whatsapp = ""
            # Removido: Deixamos o enriquecimento para o Pipeline assíncrono
            # if website:
            #     try:
            #         email, whatsapp = self._extract_contact_from_website(website)
            #     except Exception as e:
            #         logger.debug(f"[maps] erro ao extrair do site {website}: {e}")

            if not phone and not whatsapp and not email and not website:
                # Se não tem ABSOLUTAMENTE nada, o lead é inútil
                return None

            # Otimização: Não visitamos o site agora para não travar o loop do Maps
            # O LeadPipeline (SiteVerifier/Enricher) fará isso em segundo plano de forma muito mais rápida e paralela
            return {
                "name": name,
                "email": email,
                "phone": phone,
                "whatsapp": whatsapp or phone,
                "website": website,
                "source": "google_maps",
                "category": category,
            }

        except Exception as e:
            logger.debug(f"[maps] erro na extração detalhada: {e}")
            return None

    def _extract_contact_from_website(self, url):
        email = ""
        whatsapp = ""

        response = safe_request(self.session, url, timeout=10)
        if not response or response.status_code != 200:
            return email, whatsapp

        emails = extract_emails_from_text(response.text, response.text)
        if emails:
            email = emails[0]

        whatsapp = extract_whatsapp_from_html(response.text)

        time.sleep(self.delay)
        return email, whatsapp
