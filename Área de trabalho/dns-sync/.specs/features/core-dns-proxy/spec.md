# Feature Spec: Core DNS & Proxy Management (Full Fix & Polish)

## Overview
Comprehensive fix and enhancement of the DNS & Proxy subsystem in Sync DNS, ensuring real network forwarding without blackholing internet connectivity, foreground notification controls, rich visual feedback, beautiful home widgets, automated unit & widget tests, and ADB deployment.

## Requirements
- **REQ-1 (DNS-FORWARDING):** Native Android `VpnService` must correctly intercept DNS queries (UDP port 53) and forward them to selected DNS servers (Google `8.8.8.8`/`8.8.4.4`, Cloudflare `1.1.1.1`/`1.0.0.1`, AdGuard `94.140.14.14`/`94.140.15.15`, OpenDNS `208.67.222.222`/`208.67.220.220`) using protected sockets without dropping standard IPv4/IPv6 web traffic.
- **REQ-2 (PROXY-ROUTING):** Native VPN service & Flutter network layers must support HTTP/SOCKS proxying with latency verification, live status, and disconnect management.
- **REQ-3 (VISUAL-FEEDBACK):** The app must provide rich, real-time visual feedback:
  - Connection state transitions (Disconnected -> Connecting -> Connected / Error)
  - Animated pulsing glow, live connection duration timer, real ping meter
  - Real-time DNS Leak test & public IP indicator
  - Interactive proxy selection with health indicator and active connection card.
- **REQ-4 (NOTIFICATIONS-STATUS):** Persistent Foreground Service notification with:
  - Active provider & IP status
  - Quick action button: "Desconectar"
  - In-app notification triggers for status changes via `flutter_local_notifications`.
- **REQ-5 (HOME-WIDGETS):** High-end Android home screen widgets (Standard and Compact) with:
  - Modern glassmorphism UI
  - Real-time status, active provider, latency
  - One-tap interactive quick toggle action.
- **REQ-6 (AUTOMATED-TESTS):** Complete test suite covering services, providers, models, channels, and widget UI components with 100% pass rate in `flutter test`.
- **REQ-7 (ADB-REBUILD):** Full build and deployment to connected Android device via ADB with logcat verification.
