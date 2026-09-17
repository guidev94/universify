# Feature Spec: Core DNS & Proxy Management

## Overview
This feature handles the core logic of switching DNS settings and managing free proxies.

## Requirements
- **REQ-1 (DNS-GOOG):** The app must allow switching device DNS to Google (8.8.8.8, 8.8.4.4).
- **REQ-2 (DNS-CF):** The app must allow switching device DNS to Cloudflare (1.1.1.1, 1.0.0.1).
- **REQ-3 (PROXY-LIST):** The app must fetch a list of free proxies from at least one public API.
- **REQ-4 (PROXY-APPLY):** The app must allow applying a selected proxy to the device network settings.
- **REQ-5 (UI-AIRBNB):** The interface must follow Airbnb's design language:
    - High-quality typography (Inter-like).
    - Large rounded corners (16px+).
    - Subtle shadows.
    - Clean whitespace.
    - Bottom sheet for proxy selection.
- **REQ-6 (CROSS-PLATFORM):** Must work on both Android and iOS.

## Gray Areas (Need Discussion/Research)
- **Permissions:** How to programmatically set DNS/Proxy without user manual intervention? (VPN Service might be needed).
- **Proxy APIs:** Which free APIs are stable? (ProxyScrape, PubProxy, etc.)

## Success Criteria
- DNS change is verified (e.g., via a DNS leak test or internal ping).
- Proxy list is populated and searchable.
- UI matches the Airbnb aesthetic.
