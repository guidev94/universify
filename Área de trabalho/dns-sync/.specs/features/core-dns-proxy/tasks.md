# Tasks: DNS & Proxy Full Fix & Polish

## Native & Network Fixes
- [x] T1: Design native DNS Packet Forwarder and Proxy architecture in `design.md` `[done:yes]`
- [x] T2: Implement `MyVpnService.kt` with DNS packet forwarder, protected DatagramSocket, Proxy support, and Foreground Notification with disconnect action `[done:yes]`
- [x] T3: Implement `MainActivity.kt` MethodChannel handlers (`aplicarDns`, `aplicarProxy`, `desconectar`, `obterStatus`) `[done:yes]`

## Services & State
- [x] T4: Enhance `CanalNativo.dart` with proxy support, status query, and disconnect methods `[done:yes]`
- [x] T5: Implement `NotificacaoService.dart` for local notifications and status alerts `[done:yes]`
- [x] T6: Update `ProxyProvider.dart` with real proxy connection state, timer, ping, and active proxy data `[done:yes]`

## UI & Visual Feedback
- [x] T7: Enhance `HomeScreen.dart` with live timer, active ping meter, animated connection states, and quick actions `[done:yes]`
- [x] T8: Enhance `ProxyListScreen.dart` with active proxy banner, live connection testing, and connect/disconnect controls `[done:yes]`
- [x] T9: Enhance Android Home Widgets (`sync_dns_widget.xml`, `sync_dns_compact_widget.xml`, and `SyncDnsWidgetProvider.kt`) `[done:yes]`

## Testing & Deployment
- [x] T10: Write comprehensive automated unit & widget tests (`test/unit_test.dart`, `test/proxy_service_test.dart`, `test/speed_test_test.dart`, `test/widget_test.dart`, `test/canal_nativo_test.dart`) `[done:yes]`
- [x] T11: Run `flutter test` and achieve 100% pass rate `[done:yes]`
- [x] T12: Build and deploy via ADB to `R9QL1002AAT` and verify logs `[done:yes]`
