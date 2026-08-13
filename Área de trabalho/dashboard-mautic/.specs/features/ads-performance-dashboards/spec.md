# Feature: Ads Performance Dashboards (Google, Bing, LinkedIn)

## Overview
Add two new dashboard tabs to `MauticAdsIntelligenceBundle` to visualize detailed performance metrics from Google Ads, with potential for Bing and LinkedIn integration.

## Requirements

### R1: UI/UX (Mautic Integration)
- **R1.1:** Add "Click Performance" tab to the Ads Intelligence dashboard.
- **R1.2:** Add "Keyword Performance" tab to the Ads Intelligence dashboard.
- **R1.3:** Maintain Mautic's standard UI while replicating the clean look of the provided images.
- **R1.4:** Responsive layout.

### R2: Click Performance Dashboard (Image 1)
- **R2.1: Filters**
    - Account, Campaign, Ad Group, Device.
    - Date Range Picker.
- **R2.2: Impressions Vs Clicks Chart**
    - Multi-axis line chart (Impressions on left, Clicks on right).
    - Grouped by Day.
- **R2.3: Detailed Data Table**
    - Columns: Day, Campaign, Clicks, Impressions, Conversions, Cost.
    - Export to CSV/Excel functionality.

### R3: Keyword Performance Dashboard (Image 2)
- **R3.1: Avg CPC by Quality Score Chart**
    - Bar chart showing Average CPC for each Quality Score (1-10).
- **R3.2: Clicks & Cost by Match Type Chart**
    - Horizontal bar chart comparing performance across Broad, Phrase, and Exact match types.
- **R3.3: Keyword Performance Table**
    - Detailed breakdown of keywords with status, match type, quality score, and core metrics.
    - Conditional formatting (heatmaps) for higher performance metrics.

### R4: Data Integration
- **R4.1:** Dynamic fetching from Google Ads API.
- **R4.2:** Support for multi-channel data (Bing, LinkedIn) where applicable.
- **R4.3:** Use existing `Provider` architecture in the bundle.
- **R4.4:** Backend caching to ensure performance.

### R5: Multi-Channel support
- **R5.1:** Detect if credentials exist for Microsoft (Bing) or LinkedIn and provide a channel selector or unified view.
