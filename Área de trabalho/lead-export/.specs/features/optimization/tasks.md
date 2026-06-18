# Implementation Tasks: Performance & Scale

## Task 1: Keyword Multiplier [REQ-002 / REQ-007]
- [x] Create a list of major SP/RJ neighborhoods in `crawler/utils.py`.
- [ ] Implement `expand_local_keywords(keyword, location)` in `crawler/utils.py`.
- [ ] Update `blueprints/crawl.py` to handle multiple keywords.
- [ ] Add UI field for "Expand by Neighborhood" in `crawl.html`.

## Task 2: Flexible Export & Cleanup [REQ-005 / REQ-006]
- [ ] Update `Lead.get_for_export` in `models.py` to handle very large limits.
- [ ] Add `Lead.delete_by_score(min_score)` method in `models.py`.
- [ ] Create API endpoint `POST /api/leads/cleanup` for score-based deletion.
- [ ] Update Export UI to allow entering any quantity.

## Task 3: Database Performance [REQ-008]
- [ ] Ensure `leads` table has indexes on `score`, `tier`, and `status`.
- [ ] Optimize `Lead.create` to use a shared connection or batching if called in loops.
- [ ] Add WAL mode to SQLite for better concurrency during extraction.

## Task 4: WhatsApp Link in Export [REQ-004]
- [ ] Update `crawler/exporter.py` to include `WA_Link` column.
- [ ] Sanitize phone numbers for the link (digits only).
