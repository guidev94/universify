# Codebase Stack

## Backend
- **Framework:** Flask (Python 3.12)
- **Asynchronous Processing:** `threading`, `queue`, `concurrent.futures.ThreadPoolExecutor`
- **Database:** SQLite (leads.db)
- **Web Scraping:** `requests`, `BeautifulSoup4`, `Selenium` (Maps), `fake-useragent`

## Frontend
- **Templating:** Jinja2
- **Styling:** Tailwind CSS (Custom Glassmorphism)
- **Visuals:** GSAP (Animations), Chart.js (Statistics)

## Business Logic
- **Scoring Engine:** Custom heuristic in `crawler/lead_scorer.py`
- **Validation:** PII validation with `phonenumbers` and `email-validator`
- **Pipeline:** Linear flow (Capture -> Verify -> Enrich)
