# Design: Core DNS & Proxy Management

## Architecture
The app will follow a Clean Architecture / Layered approach:
- **Presentation:** Flutter Widgets, Riverpod Providers for UI state.
- **Domain:** Models for DNS Provider, Proxy, and use cases (ToggleDNS, FetchProxies).
- **Data:** Repository implementations fetching from Proxy APIs and interacting with native platform channels for DNS/VPN.

## UI Components (Airbnb Style)
- **Top Bar:** Large title, minimalist icons.
- **DNS Card:** Large card with current status, toggle switch, and provider selection.
- **Proxy Card:** List of available proxies in a clean card format with speed/latency indicator.
- **Navigation:** Bottom navigation bar (Home, Proxies, Settings).

## Color Palette
- **Primary:** `#FF385C` (Airbnb Red)
- **Secondary:** `#008489` (Airbnb Teal)
- **Background:** `#FFFFFF`
- **Surface:** `#F7F7F7`
- **Text:** `#222222`
- **Success:** `#4CAF50`
- **Warning:** `#FFB400`

## Native Integration
- **Android:** `MethodChannel` to start/stop a `VpnService` that sets the desired DNS.
- **iOS:** `MethodChannel` to use `NEVPNManager` or `NEDNSSettingsManager`.

## Data Models
```dart
class DnsProvider {
  final String name;
  final String primary;
  final String secondary;
  final String icon;
}

class Proxy {
  final String ip;
  final String port;
  final String country;
  final String protocol;
  final int latency;
}
```
