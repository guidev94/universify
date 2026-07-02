# Tasks: Campaign Export and Extraction Fix

## 1. Backend: Update Models & API [ID: T-B-1]
- [ ] Add `campaign` parameter to `Lead.get_for_export` in `models.py`.
- [ ] Update `/api/export` in `blueprints/export.py` to accept `campaign` and pass it to `Lead.get_for_export`.
- [ ] Fix/Verify `extract_campaign` in `blueprints/dashboard.py` to ensure it properly handles the campaign name.

## 2. Frontend: Update Campaigns View [ID: T-F-1]
- [ ] Add "Export" button (Lucide `download`) to campaign card template in `templates/campaigns.html`.
- [ ] Implement `exportCampaign(id, name)` function in `templates/campaigns.html`.
- [ ] Update `loadCampaigns()` to include the new button.

## 3. Extraction Improvements [ID: T-B-2]
- [ ] Ensure `run_crawl` properly receives and uses `campaign_name`.
- [ ] Verify `Lead.create` uses the campaign name if provided (or if the job has one).

## 4. Verification & E2E [ID: T-V-1]
- [ ] Create `tests/test_campaign_flow.py` with E2E tests for the new functionality.
- [ ] Run the server and execute tests.
