# State: DNS Sync

## Decisions
- **UI Inspiration:** Airbnb (minimalist, clean, rounded corners, soft shadows, focus on typography).
- **Core DNS Providers:** Google (8.8.8.8) and Cloudflare (1.1.1.1).
- **Project Methodology:** TLC Spec-Driven.
- **State Management:** Riverpod.
- **Proxy APIs:** Using ProxyScrape V2 API.
- **DNS/Proxy Implementation:** Will use a local VPN-based approach for maximum compatibility on Android/iOS.

## Blockers
- **Mobile OS Permissions:** Changing DNS/Proxy system-wide is restricted. Verified that a VPN-based approach is necessary.

## Todos
- [x] Research free proxy APIs.
- [ ] Determine best Flutter package for DNS/VPN management.
- [ ] Implement Airbnb UI components.

## Deferred Ideas
- Custom DNS entry.
- Proxy speed testing.
