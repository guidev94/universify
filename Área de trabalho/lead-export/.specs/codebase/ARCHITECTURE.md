# Architecture Overview

## Component Diagram
- **Web App (Flask):** Handles HTTP requests, serves templates, provides JSON APIs.
- **SQLite DB:** Persistent storage for leads, jobs, campaigns, etc.
- **Lead Pipeline:** Async worker that processes leads (verification, enrichment).
- **Scheduler:** Manages periodic tasks (checking job status, cleanup).
- **Crawlers:** Specialized modules for extracting data from various sources.

## Data Flow
1. User starts a crawl via Web UI.
2. `blueprints/crawl.py` creates a `CrawlJob` and starts a background thread.
3. Crawler finds raw leads and saves them to DB via `Lead.create`.
4. `LeadPipeline` picks up new leads, verifies sites, and enriches data.
5. UI polls `/api/crawl/status/<job_uuid>` for progress.
6. Leads appear in Dashboard and Leads pages.
