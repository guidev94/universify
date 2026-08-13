# Feature: Google Ads Performance Dashboard

## Overview
A WordPress dashboard feature that visualizes Google Ads performance metrics.

## Requirements

### R1: General Layout
- **R1.1:** Title: "Search Performance Dashboard".
- **R1.2:** Date Picker (Current: July 14, 2026 - Aug 12, 2026).
- **R1.3:** Filter Row: Account, Campaign, Ad Group, Device.

### R2: Search Performance View (Image 1)
- **R2.1: Impressions vs Clicks Chart**
    - Line chart comparing Impressions (Left Y-axis) and Clicks (Right Y-axis) by day.
    - Legend at top-left of the chart.
- **R2.2: Performance Table**
    - Columns: Day, Campaign, Clicks, Impressions, Conversions (by conv. date), Cost.
    - Summary row or total count (e.g., 1 - 54 / 54).

### R3: Keyword Performance View (Image 2)
- **R3.1: Avg CPC Chart**
    - Bar chart showing Avg CPC by Quality Score (1 to 7+).
- **R3.2: Clicks vs Cost by Match Type**
    - Horizontal bar chart comparing Clicks and Cost for Phrase and Broad match types.
- **R3.3: Keyword Overview Table**
    - Columns: Keyword, Quality Score, Match Type, Network Status, Keyword State, Campaign, Clicks, Impressions, CTR, Cost.
    - Heatmap-style coloring for metrics like Clicks.

### R4: Data Integration
- **R4.1:** Dynamic fetching from Google Ads API via GAQL.
- **R4.2:** Filter persistence during session.
