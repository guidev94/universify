# Tasks: Ads Performance Dashboards Implementation

## Phase 1: Infrastructure & Routing
- [ ] T1: Register new routes in `Config/config.php`.
- [ ] T2: Add new methods to `DashboardController.php`.
- [ ] T3: Create placeholder Twig files for the two new dashboards.
- [ ] T4: Update Sidebar/Dashboard menu to include new items.

## Phase 2: Backend Data Aggregation
- [ ] T5: Create `PerformanceDataService.php`.
- [ ] T6: Implement Google Ads GAQL queries in `GoogleAdsProvider.php` (if missing).
- [ ] T7: Implement data mapping logic to transform API responses into Chart.js format.
- [ ] T8: Add caching layer for performance data.

## Phase 3: Click Performance Dashboard UI (Image 1)
- [ ] T9: Build the filter row (Account, Campaign, AdGroup, Device).
- [ ] T10: Implement the Impressions vs Clicks multi-axis chart.
- [ ] T11: Build the detailed daily performance table.

## Phase 4: Keyword Performance Dashboard UI (Image 2)
- [ ] T12: Implement Avg CPC by Quality Score chart.
- [ ] T13: Implement Clicks & Cost by Match Type chart.
- [ ] T14: Build the Keyword Performance Overview table.

## Phase 5: Multi-Channel & Polish
- [ ] T15: Integrate Bing/Microsoft Ads data into the same views.
- [ ] T16: Final UI polish (match images perfectly).
- [ ] T17: End-to-end testing with real API responses (mocked where necessary).
