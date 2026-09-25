# Feature Spec: Advanced Security, DoH, Local AdBlock, Split Tunneling & Quick Settings

## Overview
Implement the complete enterprise-grade capabilities for Sync DNS across all 5 roadmap phases:
1. DNS-over-HTTPS (DoH) encrypted tunneling with Cloudflare, Google, AdGuard, and Quad9.
2. Local AdBlock & Malware Filter on device with zero-latency NXDOMAIN blocking and blocked count analytics.
3. Split Tunneling allowing user to bypass specific applications from the DNS tunnel.
4. Android Quick Settings Tile (`SyncDnsTileService`) for 1-tap toggle from notification drawer.
5. Authenticated Private Proxy (SOCKS5/HTTP Basic Auth) management.

## Requirements Traceability
- **REQ-DOH-1:** Support DNS-over-HTTPS (DoH) via HTTPS POST wireformat (`application/dns-message`) with upstream DoH gateways.
- **REQ-BLOCK-1:** Local domain filtering matching known ad/telemetry/malware domains and responding with synthetic `0.0.0.0` or `NXDOMAIN` without external network usage.
- **REQ-SPLIT-1:** Split tunneling selection UI and native `addDisallowedApplication` integration.
- **REQ-TILE-1:** Native `TileService` integration in Android with status sync and direct toggle.
- **REQ-AUTH-PROXY-1:** Support for username/password authenticated proxies in UI and `HttpOverrides`.
- **REQ-TEST-1:** 100% automated test coverage across all new services, models, and UI screens.
