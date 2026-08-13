# Design: Ads Performance Dashboards Architecture

## 1. Controller Layer
- Update `DashboardController` to include:
    - `clickPerformanceAction()`
    - `keywordPerformanceAction()`
- Update `CampaignController` or create a new `AnalyticsApiController` for AJAX data fetching.

## 2. Service Layer
- **PerformanceDataService:** New service to aggregate and format data for charts.
    - Methods: `getClickPerformanceData($filters)`, `getKeywordPerformanceData($filters)`.
- Use existing `GoogleAdsProvider`, `MicrosoftAdsProvider`, and `LinkedInAdsProvider`.

## 3. Data Providers (Google Ads API)
- **GAQL Queries:**
    - Click Performance: `SELECT segments.date, campaign.name, metrics.clicks, metrics.impressions, metrics.conversions, metrics.cost_micros FROM campaign WHERE ...`
    - Keyword Performance: `SELECT ad_group_criterion.keyword.text, ad_group_criterion.quality_info.quality_score, ad_group_criterion.keyword.match_type, metrics.clicks, metrics.cost_micros ... FROM keyword_view WHERE ...`

## 4. Frontend Layer (Twig + JS)
- **Templates:**
    - `Resources/views/Dashboard/click_performance.html.twig`
    - `Resources/views/Dashboard/keyword_performance.html.twig`
- **JS Components:**
    - Use Chart.js (already used in Mautic) for all visualizations.
    - Implement custom logic for multi-axis charts.
    - Data tables using Mautic's standard table patterns.

## 5. Caching Strategy
- Use Mautic's cache helper to store API results for 1 hour (configurable) to avoid hitting API rate limits.
