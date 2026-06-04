import os
from dotenv import load_dotenv

load_dotenv()


class Config:
    SECRET_KEY = os.getenv("FLASK_SECRET_KEY", "dev-secret-change-in-production")
    GOOGLE_PLACES_API_KEY = os.getenv("GOOGLE_PLACES_API_KEY", "")
    DATABASE_PATH = os.path.join(os.path.dirname(__file__), "data", "leads.db")
    EXPORT_DIR = os.path.join(os.path.dirname(__file__), "exports")
    MAX_CONCURRENT_REQUESTS = int(os.getenv("MAX_CONCURRENT_REQUESTS", "5"))
    REQUEST_DELAY_MS = int(os.getenv("REQUEST_DELAY_MS", "1500"))
    MAX_LEADS_PER_CRAWL = 500
