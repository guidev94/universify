# Architecture & Design: DNS & Proxy with Native Android Forwarder

## Native Architecture (Android Kotlin)
1. **`MyVpnService`**:
   - Manages TUN virtual interface via `VpnService.Builder`.
   - Adds route for DNS server IPs (`/32`) or sets HTTP proxy via `setHttpProxy` (API 29+).
   - Runs a lightweight background thread (`DnsPacketForwarder`) reading UDP port 53 packets from TUN `FileDescriptor`.
   - Protects `DatagramSocket` via `protect(socket)` to bypass the VPN tunnel and communicate directly with upstream DNS (Google, Cloudflare, etc.).
   - Reconstructs IPv4 UDP response packets with valid checksums and writes back to TUN `FileOutputStream`.
   - Manages Android Foreground Service Notification with Action Button ("Desconectar") and PendingIntents.
2. **`MainActivity` & MethodChannel (`com.dnssync.app/dns`)**:
   - `aplicarDns(principal, secundario, nome)`: Starts VPN in DNS mode with selected DNS.
   - `aplicarProxy(ip, porta)`: Configures HTTP proxy in VPN and Dart `HttpOverrides`.
   - `desconectar()`: Gracefully closes TUN, stops foreground service, notifies Flutter.
   - `obterStatus()`: Returns current connection state, active provider/proxy, and uptime.
   - Event channel or state broadcast back to Flutter.

## Presentation & UI Design (Airbnb Modern / SaaS Dark & Light)
- **Status Hub**: Elevated glassmorphic card with dynamic glow, status badge, pulsing connect button, connection timer, and live ping meter.
- **Provider Bento Grid**: Cloudflare, Google, AdGuard, OpenDNS, and Custom DNS cards with micro-interactions.
- **Proxy Management**: Searchable list with live ping probe, country badges, favorites, and 1-tap connection with active state feedback.
- **Notification Manager**: Foreground service notification with action controls + `flutter_local_notifications` system.
- **Home Screen Widgets**: Polished 2x2 and 2x1 XML layouts with active status and interactive toggle action.
