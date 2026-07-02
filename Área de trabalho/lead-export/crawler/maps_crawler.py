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
        return self._crawl_scrape(query, location, quantity, on_progress)

    def _crawl_scrape(self, query, location, quantity, on_progress):
        leads = []
        search_term = f"{query} {location}".strip()
        crawl_start = time.time()
        MAPS_TIMEOUT = 90

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

                    results = driver.find_elements(By.CSS_SELECTOR, "a[href*='/maps/place/'], [role='article'] a[href*='/maps/place/']")

                    if len(results) == last_count:
                        scroll_attempts += 1
                        if scroll_attempts > 4: # Aumentado de 3 para 4
                            break
                    else:
                        scroll_attempts = 0

                    last_count = len(results)

                    feed = driver.find_elements(By.CSS_SELECTOR, "div[role='feed'], div.m679ee-PK9Sce")
                    if feed:
                        driver.execute_script(
                            "arguments[0].scrollTop = arguments[0].scrollHeight", feed[0]
                        )
                    time.sleep(2.5) # Aumentado de 2 para 2.5

                results = driver.find_elements(By.CSS_SELECTOR, "a[href*='/maps/place/'], [role='article'] a[href*='/maps/place/']")
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
            for selector in ["h1.DUwDvf", "h1.fontHeadlineLarge", "h1"]:
                els = driver.find_elements(By.CSS_SELECTOR, selector)
                if els:
                    for el in els:
                        text = el.text.strip()
                        if text and text != "Resultados" and text != "Filtros":
                            name = text
                            break
                if name: break

            # Tenta múltiplos seletores para o telefone
            info_buttons = driver.find_elements(
                By.CSS_SELECTOR, "button[data-item-id*='phone'], a[data-item-id*='phone'], [aria-label*='Telefone'], [aria-label*='Phone']"
            )
            for btn in info_buttons:
                aria = btn.get_attribute("aria-label") or ""
                text = btn.text or ""
                combined = f"{aria} {text}"
                phone_match = re.search(r"(\(?\d{2}\)?\s?\d{4,5}[-\s]?\d{4})", combined)
                if phone_match:
                    phone = phone_match.group(1).strip()
                    break
                # Fallback se não bater regex mas tiver aria-label com Telefone
                if "Telefone" in aria or "Phone" in aria:
                    # Limpa o texto para pegar só números e símbolos comuns
                    clean_phone = re.sub(r"[^\d\s\(\)\-\+]", "", aria.replace("Telefone:", "").replace("Phone:", ""))
                    if len(clean_phone.strip()) > 8:
                        phone = clean_phone.strip()
                        break

            website_els = driver.find_elements(
                By.CSS_SELECTOR, "a[data-item-id='authority'], a[aria-label*='website'], a[aria-label*='Site']"
            )
            if website_els:
                website = website_els[0].get_attribute("href") or ""

            cat_els = driver.find_elements(By.CSS_SELECTOR, "button[jsaction*='category'], .fontBodyMedium span")
            if cat_els:
                category = cat_els[0].text.strip()

            if not name:
                return None

            # Se não tiver telefone, tenta pegar do site se existir
            if not website:
                # Fallback: tenta buscar o site pelo nome no motor de busca
                try:
                    from crawler.search_engine import SearchEngine
                    se = SearchEngine()
                    website = se.find_business_website(name, category)
                    if website:
                        logger.debug(f"[maps] site encontrado via busca para {name}: {website}")
                except Exception as e:
                    logger.debug(f"[maps] erro na busca de fallback por site: {e}")

            email = ""
            whatsapp = ""
            if website:
                try:
                    email, whatsapp = self._extract_contact_from_website(website)
                except Exception as e:
                    logger.debug(f"[maps] erro ao extrair do site {website}: {e}")

            if not phone and not whatsapp and not email:
                # Se não tem nenhum contato, o lead é de baixa qualidade
                return None

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
