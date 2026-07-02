# Technical Concerns

## Performance
- SQLite database locking during high-volume batch writes.
- Large lead lists might slow down the Leads page (pagination is implemented).
- Browser-based crawling (Selenium) is resource-intensive.

## Reliability
- External sources (DuckDuckGo, Yellow Pages) may change their HTML, breaking crawlers.
- Background threads in Flask can be killed by Gunicorn workers if not managed carefully.

## Security
- SQL injection risk if bound parameters are not used (already prioritized in `CLAUDE.md`).
- XSS in templates if user input is not escaped (Jinja2 escapes by default).
- CSRF protection needs to be verified on all POST/PATCH/DELETE routes.
