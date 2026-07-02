# Tasks: Pipeline Overhaul

## 1. Intelligence Enhancements [ID: T-INT]
- [ ] Refine `calculate_score` in `crawler/lead_scorer.py` to weight Ads and Technical Debt.
- [ ] Improve `_check_quality` in `crawler/site_verifier.py` with more legacy indicators (e.g., `<center>`, `<font>`, missing `<meta viewport>`).

## 2. Frontend Overhaul [ID: T-FE]
- [ ] Implement new CSS variables for Kanban in `static/css/app.css` (if needed).
- [ ] Redesign `templates/pipeline.html` with premium glass layout.
- [ ] Update `static/js/kanban.js` for better card generation and bulk actions.
- [ ] Add tooltips/explanations for tech badges.

## 3. Mass Management Features [ID: T-MASS]
- [ ] Implement `/api/leads/bulk-status-update` endpoint.
- [ ] Add "Move All" and "Delete All" buttons to Kanban columns.

## 4. Verification & Validation [ID: T-VFY]
- [ ] Create E2E test for drag-and-drop status update.
- [ ] Verify Tech Opportunity detection with mock legacy HTML.
