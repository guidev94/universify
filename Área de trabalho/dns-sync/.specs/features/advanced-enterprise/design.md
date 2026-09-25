# Architecture & Design: Advanced Security & Enterprise Subsystems

## 1. DNS-over-HTTPS (DoH) Engine
- **Kotlin `MyVpnService.kt` / `DohClient`:**
  - Upstream DoH endpoints:
    - Cloudflare: `https://cloudflare-dns.com/dns-query`
    - Google: `https://dns.google/dns-query`
    - AdGuard: `https://dns.adguard-dns.com/dns-query`
    - Quad9: `https://dns.quad9.net/dns-query`
  - Encapsulates raw DNS UDP payload into HTTPS POST `application/dns-message`.
  - Parses response payload and builds IPv4 UDP reply back to TUN file descriptor.

## 2. Local AdBlock & Malware Filter Engine
- **`AdBlockFilter`:**
  - Fast domain extractor parsing DNS Question Name from raw DNS payload (RFC 1035 labels).
  - High-performance `HashSet<String>` containing 500+ top ad, telemetry, and tracking root domains (`doubleclick.net`, `googleads.g.doubleclick.net`, `adservice.google.com`, `ads.facebook.com`, `analytics.tiktok.com`, `app-measurement.com`, `criteo.com`, `taboola.com`, `outbrain.com`, etc.).
  - Returns synthetic DNS answer `0.0.0.0` with TTL 300s immediately on device.
  - Emits count increments to `anunciosBloqueadosProvider` for live UI counter.

## 3. Split Tunneling Engine
- **`SplitTunnelService`:**
  - Manages bypassed application package names.
  - Sends package list to native `VpnService.Builder.addDisallowedApplication(pkg)`.

## 4. Android Quick Settings Tile (`SyncDnsTileService.kt`)
- Extends `android.service.quicksettings.TileService`.
- Listens to `MyVpnService.onStateChangeListener`.
- On click: toggles VPN on/off directly.

## 5. Authenticated Private Proxies
- `ProxyModel` extended with `usuario` and `senha`.
- `SyncHttpOverrides` handles `Proxy-Authorization: Basic base64(user:password)`.
