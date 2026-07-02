import threading
import logging
import time
from queue import Queue, Empty
from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from webdriver_manager.chrome import ChromeDriverManager
from fake_useragent import UserAgent

logger = logging.getLogger("leadhunter.browser_pool")

class BrowserPool:
    """Manages a pool of reusable Selenium WebDriver instances."""
    
    _instance = None
    _lock = threading.Lock()

    def __new__(cls, *args, **kwargs):
        with cls._lock:
            if not cls._instance:
                cls._instance = super(BrowserPool, cls).__new__(cls)
                cls._instance._initialized = False
            return cls._instance

    def __init__(self, max_drivers=3):
        if self._initialized:
            return
        self.max_drivers = max_drivers
        self.pool = Queue(maxsize=max_drivers)
        self.active_count = 0
        self.ua = UserAgent()
        self._initialized = True
        self._shutdown = False

    def get_driver(self, timeout=30):
        """Gets a driver from the pool or creates a new one if limit not reached."""
        start_time = time.time()
        
        while time.time() - start_time < timeout:
            try:
                # Try to get an existing driver
                driver = self.pool.get(block=False)
                if self._is_alive(driver):
                    logger.debug("Reusing existing driver from pool")
                    return driver
                else:
                    logger.debug("Driver in pool was dead, removing")
                    self._decrement_active()
            except Empty:
                # Pool is empty, can we create a new one?
                with self._lock:
                    if self.active_count < self.max_drivers:
                        logger.info(f"Creating new driver instance ({self.active_count + 1}/{self.max_drivers})")
                        driver = self._create_driver()
                        self.active_count += 1
                        return driver
                
                # Wait a bit for a driver to return to pool
                time.sleep(1)
        
        raise TimeoutError("Could not acquire a browser from the pool")

    def return_driver(self, driver):
        """Returns a driver to the pool."""
        if self._shutdown:
            self._close_driver(driver)
            return

        if self._is_alive(driver):
            try:
                # Clean up before returning
                driver.delete_all_cookies()
                # Return to pool
                self.pool.put(driver, block=False)
                logger.debug("Driver returned to pool")
            except Exception:
                self._close_driver(driver)
        else:
            self._decrement_active()

    def _create_driver(self):
        options = Options()
        options.add_argument("--headless=new")
        options.add_argument("--no-sandbox")
        options.add_argument("--disable-gpu")
        options.add_argument("--disable-dev-shm-usage")
        options.add_argument("--disable-extensions")
        options.add_argument("--disable-notifications")
        options.add_argument("--disable-popup-blocking")
        options.add_argument("--blink-settings=imagesEnabled=false") # Desabilita imagens para velocidade
        options.add_argument(f"--user-agent={self.ua.random}")
        options.add_argument("--disable-blink-features=AutomationControlled")
        options.add_argument("--lang=pt-BR")
        
        # Otimização de memória e CPU
        options.add_argument("--proxy-server='direct://'")
        options.add_argument("--proxy-bypass-list=*")
        options.add_argument("--start-maximized")
        
        options.add_experimental_option("excludeSwitches", ["enable-automation"])
        options.add_experimental_option('useAutomationExtension', False)
        
        prefs = {
            "profile.managed_default_content_settings.images": 2,
            "profile.default_content_settings.popups": 0,
            "profile.default_content_setting_values.notifications": 2
        }
        options.add_experimental_option("prefs", prefs)
        driver = webdriver.Chrome(service=service, options=options)
        driver.set_page_load_timeout(25)
        return driver

    def _is_alive(self, driver):
        try:
            driver.title
            return True
        except Exception:
            return False

    def _decrement_active(self):
        with self._lock:
            self.active_count = max(0, self.active_count - 1)

    def _close_driver(self, driver):
        try:
            driver.quit()
        except Exception:
            pass
        self._decrement_active()

    def shutdown(self):
        """Closes all drivers in the pool."""
        self._shutdown = True
        while not self.pool.empty():
            try:
                driver = self.pool.get_nowait()
                self._close_driver(driver)
            except Empty:
                break

browser_pool = BrowserPool(max_drivers=12)
