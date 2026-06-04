import os
import re
import time
import json
import requests
from fake_useragent import UserAgent


class MapsCrawler:
    def __init__(self):
        self.api_key = os.getenv("GOOGLE_PLACES_API_KEY", "")
        self.ua = UserAgent()
        self.delay = int(os.getenv("REQUEST_DELAY_MS", "1500")) / 1000

    def crawl(self, query, location, quantity, on_progress=None):
        if self.api_key:
            return self._crawl_api(query, location, quantity, on_progress)
        return self._crawl_scrape(query, location, quantity, on_progress)

    def _crawl_api(self, query, location, quantity, on_progress):
        leads = []
        next_page_token = None
        search_query = f"{query} {location}".strip()

        while len(leads) < quantity:
            params = {
                "query": search_query,
                "key": self.api_key,
                "language": "pt-BR",
            }

            if next_page_token:
                params["pagetoken"] = next_page_token

            try:
                response = requests.get(
                    "https://maps.googleapis.com/maps/api/place/textsearch/json",
                    params=params,
                    timeout=15,
                )
                data = response.json()

                if data.get("status") not in ("OK", "ZERO_RESULTS"):
                    break

                for place in data.get("results", []):
                    if len(leads) >= quantity:
                        break

                    detail = self._get_place_details(place["place_id"])
                    if detail:
                        leads.append(detail)
                        if on_progress:
                            on_progress(len(leads))

                next_page_token = data.get("next_page_token")
                if not next_page_token:
                    break

                time.sleep(2)

            except Exception:
                break

        return leads[:quantity]

    def _get_place_details(self, place_id):
        params = {
            "place_id": place_id,
            "fields": "name,formatted_phone_number,international_phone_number,website,url,types,business_status",
            "key": self.api_key,
            "language": "pt-BR",
        }

        try:
            response = requests.get(
                "https://maps.googleapis.com/maps/api/place/details/json",
                params=params,
                timeout=15,
            )
            data = response.json()
            result = data.get("result", {})

            if not result:
                return None

            if result.get("business_status") != "OPERATIONAL":
                return None

            phone = result.get("international_phone_number", "") or result.get("formatted_phone_number", "")
            website = result.get("website", "")
            name = result.get("name", "")
            category = ", ".join(result.get("types", [])[:3])

            email = ""
            whatsapp = ""
            if website:
                email, whatsapp = self._extract_contact_from_website(website)

            if not name or (not phone and not email):
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

        except Exception:
            return None

    def _extract_contact_from_website(self, url):
        email = ""
        whatsapp = ""

        try:
            headers = {"User-Agent": self.ua.random}
            response = requests.get(url, headers=headers, timeout=10, allow_redirects=True)

            if response.status_code != 200:
                return email, whatsapp

            text = response.text

            email_matches = re.findall(
                r"[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}", text
            )
            for match in email_matches:
                lower = match.lower()
                if not lower.startswith(("noreply", "no-reply", "mailer")):
                    email = lower
                    break

            wa_match = re.search(r"wa\.me/(\d+)", text)
            if wa_match:
                whatsapp = wa_match.group(1)
            else:
                wa_match = re.search(r"api\.whatsapp\.com/send\?phone=(\d+)", text)
                if wa_match:
                    whatsapp = wa_match.group(1)

            time.sleep(self.delay)

        except Exception:
            pass

        return email, whatsapp

    def _crawl_scrape(self, query, location, quantity, on_progress):
        leads = []
        search_term = f"{query} {location}".strip()

        try:
            from selenium import webdriver
            from selenium.webdriver.chrome.service import Service
            from selenium.webdriver.chrome.options import Options
            from selenium.webdriver.common.by import By
            from selenium.webdriver.common.keys import Keys
            from selenium.webdriver.support.ui import WebDriverWait
            from selenium.webdriver.support import expected_conditions as EC
            from webdriver_manager.chrome import ChromeDriverManager

            options = Options()
            options.add_argument("--headless=new")
            options.add_argument("--no-sandbox")
            options.add_argument("--disable-dev-shm-usage")
            options.add_argument(f"--user-agent={self.ua.random}")
            options.add_argument("--disable-blink-features=AutomationControlled")
            options.add_argument("--lang=pt-BR")
            options.add_experimental_option("excludeSwitches", ["enable-automation"])

            service = Service(ChromeDriverManager().install())
            driver = webdriver.Chrome(service=service, options=options)
            driver.set_page_load_timeout(30)

            try:
                url = f"https://www.google.com/maps/search/{requests.utils.quote(search_term)}"
                driver.get(url)
                time.sleep(3)

                self._accept_cookies(driver)

                last_count = 0
                scroll_attempts = 0
                max_scrolls = 50

                while len(leads) < quantity and scroll_attempts < max_scrolls:
                    results = driver.find_elements(By.CSS_SELECTOR, "a[href*='/maps/place/']")

                    if len(results) == last_count:
                        scroll_attempts += 1
                        if scroll_attempts > 5:
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

                for result in results:
                    if len(leads) >= quantity:
                        break

                    try:
                        lead = self._extract_place_scrape(driver, result)
                        if lead:
                            leads.append(lead)
                            if on_progress:
                                on_progress(len(leads))
                    except Exception:
                        continue

            finally:
                driver.quit()

        except ImportError:
            pass
        except Exception:
            pass

        return leads[:quantity]

    def _accept_cookies(self, driver):
        from selenium.webdriver.common.by import By
        try:
            buttons = driver.find_elements(By.CSS_SELECTOR, "button")
            for btn in buttons:
                text = btn.text.lower()
                if "aceitar" in text or "accept" in text:
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
            element.click()
            time.sleep(2)

            name = ""
            phone = ""
            website = ""

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

            if not name or (not phone and not website):
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
                "category": "",
            }

        except Exception:
            return None
