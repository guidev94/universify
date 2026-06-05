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
            from selenium import webdriver
            from selenium.webdriver.chrome.service import Service
            from selenium.webdriver.chrome.options import Options
            from selenium.webdriver.common.by import By
            from webdriver_manager.chrome import ChromeDriverManager

            options = Options()
            options.add_argument("--headless=new")
            options.add_argument("--no-sandbox")
            options.add_argument("--disable-dev-shm-usage")
            options.add_argument(f"--user-agent={self.ua.random}")
            options.add_argument("--disable-blink-features=AutomationControlled")
            options.add_argument("--lang=pt-BR")
            options.add_argument("--window-size=1920,1080")
            options.add_experimental_option("excludeSwitches", ["enable-automation"])

            service = Service(ChromeDriverManager().install())
            driver = webdriver.Chrome(service=service, options=options)
            driver.set_page_load_timeout(20)
            driver.set_script_timeout(15)

            try:
                url = f"https://www.google.com/maps/search/{requests.utils.quote(search_term)}"
                driver.get(url)
                time.sleep(3)

                self._accept_cookies(driver)

                last_count = 0
                scroll_attempts = 0
                max_scrolls = 15

                while len(leads) < quantity and scroll_attempts < max_scrolls:
                    if time.time() - crawl_start > MAPS_TIMEOUT:
                        logger.warning(f"[maps] timeout global de {MAPS_TIMEOUT}s atingido")
                        break

                    results = driver.find_elements(By.CSS_SELECTOR, "a[href*='/maps/place/']")

                    if len(results) == last_count:
                        scroll_attempts += 1
                        if scroll_attempts > 3:
                            break
                    else:
                        scroll_attempts = 0

                    last_count = len(results)

                    feed = driver.find_elements(By.CSS_SELECTOR, "div[role='feed']")
                    if feed:
                        driver.execute_script(
                            "arguments[0].scrollTop = arguments[0].scrollHeight", feed[0]
                        )
                    time.sleep(2)

                results = driver.find_elements(By.CSS_SELECTOR, "a[href*='/maps/place/']")
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
                driver.quit()

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

        try:
            element.click()
            time.sleep(2)

            name = ""
            phone = ""
            website = ""
            category = ""

            name_el = driver.find_elements(By.CSS_SELECTOR, "h1.DUwDvf, h1.fontHeadlineLarge")
            if name_el:
                name = name_el[0].text.strip()

            info_buttons = driver.find_elements(
                By.CSS_SELECTOR, "button[data-item-id*='phone'], a[data-item-id*='phone']"
            )
            for btn in info_buttons:
                aria = btn.get_attribute("aria-label") or ""
                if aria:
                    phone_match = re.search(r"[\d\s\(\)\-\+]+", aria)
                    if phone_match:
                        phone = phone_match.group().strip()
                        break

            website_els = driver.find_elements(
                By.CSS_SELECTOR, "a[data-item-id='authority']"
            )
            if website_els:
                website = website_els[0].get_attribute("href") or ""

            cat_els = driver.find_elements(By.CSS_SELECTOR, "button[jsaction*='category']")
            if cat_els:
                category = cat_els[0].text.strip()

            if not name or not phone:
                return None

            email = ""
            whatsapp = ""
            if website:
                email, whatsapp = self._extract_contact_from_website(website)

            return {
                "name": name,
                "email": email,
                "phone": phone,
                "whatsapp": whatsapp or phone,
                "website": website,
                "source": "google_maps",
                "category": category,
            }

        except Exception:
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
