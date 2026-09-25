# State: DNS Sync

## Decisions
- **UI Aesthetic:** Airbnb-inspired minimal glassmorphism, responsive light/dark themes, tactile microinteractions.
- **Architecture:** Clean Layered Architecture with Riverpod 2.0 State Management.
- **Native Android Forwarder:** Kotlin-based high-performance user-space DNS packet forwarder using protected `DatagramSocket` over `VpnService` TUN interface without breaking standard IPv4/IPv6 web traffic.
- **Proxy Handling:** Android `VpnService.setHttpProxy` + Dart `HttpOverrides` global client proxying.
- **Notifications:** Android Foreground Service persistent notification with quick "Desconectar" action + Flutter Local Notifications.
- **Testing:** 100% automated unit and widget test coverage with mocked HTTP responses.
- **Deployment:** Direct ADB deploy and verification.

## Current Milestone
- [x] Spec-Driven Feature Architecture & Design
- [ ] Native Kotlin DNS Forwarder + Proxy Implementation
- [ ] Notification System & Real Visual Feedback
- [ ] Beautiful Home Widgets
- [ ] Automated Unit Tests
- [ ] ADB Rebuild & Device Verification
