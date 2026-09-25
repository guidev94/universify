# Tasks: Advanced Enterprise Features (Phases 1-5)

## Phase 1 & 2: DoH and Local AdBlock Filter
- [x] T1: Implement local domain blocker and DNS-over-HTTPS forwarder in `MyVpnService.kt` `[done:yes]`
- [x] T2: Add AdBlock counter and DoH toggle state in `proxy_provider.dart` `[done:yes]`

## Phase 3: Split Tunneling
- [x] T3: Implement Split Tunneling state, model, and UI modal `[done:yes]`
- [x] T4: Connect Split Tunneling to native `MyVpnService` disallowed applications `[done:yes]`

## Phase 4: Quick Settings Tile
- [x] T5: Create `SyncDnsTileService.kt` and register in `AndroidManifest.xml` `[done:yes]`

## Phase 5: Authenticated Private Proxies & UI Polish
- [x] T6: Support proxy authentication in `ProxyModel` and `SyncHttpOverrides` `[done:yes]`
- [x] T7: Add live AdBlock stats and Split Tunnel management to `HomeScreen` and `SettingsScreen` `[done:yes]`

## Testing & Verification
- [x] T8: Write comprehensive automated tests for AdBlock, DoH, Split Tunnel, and Authenticated Proxies `[done:yes]`
- [x] T9: Run `flutter test` and `dart analyze` to ensure 100% pass rate (27 tests) `[done:yes]`
- [x] T10: Compile APK and verify build `[done:yes]`
