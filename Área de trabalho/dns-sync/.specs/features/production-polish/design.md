# Architecture & Design: Production Polish, Security & Custom DNS

## 1. Domain & State Architecture
- **`CustomDnsModel` & `CustomDnsNotifier`:**
  - Persisted in `SharedPreferences` as JSON array.
  - CRUD operations: `adicionarDns`, `removerDns`, `atualizarDns`.
  - Input validation: IPv4 regex format `^((25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$`.
- **`DnsBenchmarkService` & `dnsBenchmarkProvider`:**
  - Pings all configured DNS providers concurrently via raw socket handshake on port 53.
  - Computes the fastest provider with minimum latency.
- **`ConfiguracoesNotifier`:**
  - Auto-connect on startup.
  - Smart best DNS selector.
  - Anti-leak IPv6 shield enabled.

## 2. UI/UX & Micro-Interactions
- **Pulsing Halo Radar:** Custom-painted / animated wave rings radiating from the connection button when VPN is active.
- **Provider Cards with Live Latency Badges:** Real-time ping chips on Google (e.g. `12ms`), Cloudflare (`9ms`), AdGuard (`18ms`), OpenDNS (`22ms`).
- **Custom DNS Bottom Sheet Modal:** Elegant modal with glassmorphic styling, real-time input error hints, and 1-tap save & connect.
- **Interactive Speed Gauge:** High-precision canvas arc, glowing gradient trail, and animated digital typography.

## 3. Security Enhancements
- **Native IPv6 Anti-Leak Shield:** In `MyVpnService.kt`, add IPv6 private address (`fd00:1::1/128`) and route (`fd00:1::/64`) to prevent IPv6 DNS leak fallbacks.
- **Input Sanitization:** Sanitize and validate all user inputs for proxy and custom DNS IPs.
