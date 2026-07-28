import os
from dotenv import load_dotenv

load_dotenv()


class Config:
    SECRET_KEY = os.getenv("FLASK_SECRET_KEY", "dev-secret-change-in-production")
    DATABASE_PATH = os.path.join(os.path.dirname(__file__), "data", "leads.db")
    EXPORT_DIR = os.path.join(os.path.dirname(__file__), "exports")
    LOG_FILE = os.path.join(os.path.dirname(__file__), "data", "crawl.log")
    MAX_CONCURRENT_REQUESTS = int(os.getenv("MAX_CONCURRENT_REQUESTS", "12"))
    REQUEST_DELAY_MS = int(os.getenv("REQUEST_DELAY_MS", "1000"))
    MAX_LEADS_PER_CRAWL = 1000
    SCHEDULER_CRAWL_INTERVAL = int(os.getenv("SCHEDULER_CRAWL_INTERVAL", "10800"))
    SCHEDULER_VERIFY_INTERVAL = int(os.getenv("SCHEDULER_VERIFY_INTERVAL", "3600"))
    
    # Advanced Scraper Settings
    PROXY_LIST = os.getenv("PROXY_LIST", "").split(",") if os.getenv("PROXY_LIST") else []
    GOOGLE_PLACES_API_KEY = os.getenv("GOOGLE_PLACES_API_KEY", "")
