# Feature Spec: Campaign Export and Extraction Fix

## Description
This feature adds the ability to export leads from a specific campaign and ensures that the lead extraction process for campaigns is functioning correctly and reliably.

## Requirements

### 1. Campaign Export (ID: CAM-EXP)
- **CAM-EXP-1:** Add an "Export" button to each campaign card in the Campaigns view (`templates/campaigns.html`).
- **CAM-EXP-2:** When clicked, the button should offer (or default to) exporting all leads belonging to that campaign.
- **CAM-EXP-3:** Support XLSX format (and optionally CSV).
- **CAM-EXP-4:** Implement the backend logic to filter leads by campaign name during export.

### 2. Campaign Extraction Fix (ID: CAM-EXT)
- **CAM-EXT-1:** Verify and fix the heuristic assignment of existing leads to a campaign (`blueprints/dashboard.py:246-261`).
- **CAM-EXT-2:** Ensure the `continuous_extract` thread reliably creates crawl jobs and associates leads with the campaign.
- **CAM-EXT-3:** Improve progress reporting for campaign extraction in the UI.

### 3. Verification & E2E (ID: CAM-VFY)
- **CAM-VFY-1:** Create E2E tests to:
    - Create a campaign.
    - Run extraction for that campaign.
    - Verify leads are assigned.
    - Export leads and verify the file is generated.

## Technical Details
- **Lead filtering:** `Lead.get_for_export` needs to support `campaign`.
- **UI:** Use Lucide `download` icon for export.
- **API:** Reuse `/api/export` with a new `campaign` parameter.
