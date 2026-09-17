# Project: DNS Sync (Flutter)

Mobile application for Android and iOS that allows users to synchronize their device's DNS settings with major providers (Google, Cloudflare/Microsoft) and use free proxies.

## Vision
To provide a simple, beautiful (Airbnb-inspired), and efficient way for users to manage their network privacy and performance through DNS and Proxy settings.

## Goals
- **DNS Sync:** One-tap synchronization with Google DNS and Cloudflare DNS.
- **Proxy Management:** Discover and apply free proxies using public APIs.
- **Modern UI:** High-quality Airbnb-inspired design with fluid animations.
- **Cross-Platform:** Single codebase for Android and iOS using Flutter.

## Tech Stack
- **Framework:** Flutter
- **Language:** Dart
- **State Management:** Provider or Riverpod (to be decided)
- **Networking:** `http` or `dio` for API calls
- **Native Integration:** `MethodChannel` for DNS settings or specialized packages (e.g., `flutter_vpn` or similar logic)

## Constraints
- Mobile OS restrictions on DNS/Proxy settings (may require local VPN profile).
- Availability and reliability of free proxy APIs.
