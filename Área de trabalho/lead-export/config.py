import os
from dotenv import load_dotenv

load_dotenv()


class Config:
    SECRET_KEY = os.getenv("FLASK_SECRET_KEY", "dev-secret-change-in-production")
    DATABASE_PATH = os.path.join(os.path.dirname(__file__), "data", "leads.db")
    EXPORT_DIR = os.path.join(os.path.dirname(__file__), "exports")
    LOG_FILE = os.path.join(os.path.dirname(__file__), "data", "crawl.log")

    # ─── Performance Tuning ───────────────────────────────────────────────────
    MAX_CONCURRENT_REQUESTS = int(os.getenv("MAX_CONCURRENT_REQUESTS", "8"))
    REQUEST_DELAY_MS = int(os.getenv("REQUEST_DELAY_MS", "400"))
    MAX_LEADS_PER_CRAWL = 1000

    # ─── Scheduler Intervals (segundos) ──────────────────────────────────────
    SCHEDULER_CRAWL_INTERVAL = int(os.getenv("SCHEDULER_CRAWL_INTERVAL", "3600"))
    SCHEDULER_VERIFY_INTERVAL = int(os.getenv("SCHEDULER_VERIFY_INTERVAL", "1800"))

    # ─── Pipeline Tuning ─────────────────────────────────────────────────────
    PIPELINE_VERIFY_WORKERS = int(os.getenv("PIPELINE_VERIFY_WORKERS", "8"))
    PIPELINE_ENRICH_WORKERS = int(os.getenv("PIPELINE_ENRICH_WORKERS", "4"))
    PIPELINE_VERIFY_BATCH = int(os.getenv("PIPELINE_VERIFY_BATCH", "50"))
    PIPELINE_ENRICH_BATCH = int(os.getenv("PIPELINE_ENRICH_BATCH", "20"))
    PIPELINE_VERIFY_QUEUE_SIZE = int(os.getenv("PIPELINE_VERIFY_QUEUE_SIZE", "5000"))
    PIPELINE_ENRICH_QUEUE_SIZE = int(os.getenv("PIPELINE_ENRICH_QUEUE_SIZE", "3000"))

    # ─── Crawler Tuning ──────────────────────────────────────────────────────
    CRAWLER_TIMEOUT = int(os.getenv("CRAWLER_TIMEOUT", "6"))
    CRAWLER_WORKERS = int(os.getenv("CRAWLER_WORKERS", "12"))         # Reduzido de 40 para 12 para estabilidade
    SEARCH_ENGINE_PAGES = int(os.getenv("SEARCH_ENGINE_PAGES", "3"))  # Reduzido de 5 para 3 (focar em qualidade)

    # ─── Advanced ────────────────────────────────────────────────────────────
    PROXY_LIST = os.getenv("PROXY_LIST", "").split(",") if os.getenv("PROXY_LIST") else []
    GOOGLE_PLACES_API_KEY = os.getenv("GOOGLE_PLACES_API_KEY", "")
    SERPAPI_KEY = os.getenv("SERPAPI_KEY", "")

    # ─── Job Management ──────────────────────────────────────────────────────
    JOB_STALE_TIMEOUT = int(os.getenv("JOB_STALE_TIMEOUT", "600"))    # 10 min (era 15-20)
    JOB_MAX_DURATION = int(os.getenv("JOB_MAX_DURATION", "300"))      # 5 min max por job
