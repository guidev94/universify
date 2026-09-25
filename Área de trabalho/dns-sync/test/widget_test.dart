import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_lucide/flutter_lucide.dart';
import 'package:dns_sync/main.dart';
import 'package:dns_sync/data/models/isp_info_model.dart';
import 'package:dns_sync/presentation/screens/home_screen.dart';
import 'package:dns_sync/presentation/providers/proxy_provider.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  testWidgets('Renderização do DnsSyncApp com ProviderScope', (WidgetTester tester) async {
    tester.view.physicalSize = const Size(1080, 2400);
    tester.view.devicePixelRatio = 2.0;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);

    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          benchmarkLatenciasProvider.overrideWith((ref) async => {
            'Google': 14,
            'Cloudflare': 9,
            'AdGuard': 18,
            'OpenDNS': 22,
          }),
          conexaoInfoProvider.overrideWith((ref) async => {
            'ip': '200.180.10.5',
            'city': 'Curitiba',
            'country_name': 'Brasil',
          }),
          ispInfoProvider.overrideWith((ref) async => IspInfoModel(
            isp: 'Claro Fibra',
            asn: 'AS28573',
            ip: '177.18.92.14',
            city: 'São Paulo',
            country: 'Brasil',
            countryCode: 'BR',
            flag: '🇧🇷',
            server: 'Cloudflare Edge GRU',
          )),
        ],
        child: const DnsSyncApp(),
      ),
    );

    await tester.pump();
    await tester.pump(const Duration(milliseconds: 300));

    // Verifica elementos fundamentais da Home
    expect(find.byType(HomeScreen), findsOneWidget);
    expect(find.text('Sync DNS'), findsWidgets);
    expect(find.text('Google DNS'), findsOneWidget);
    expect(find.text('Cloudflare'), findsOneWidget);
    expect(find.text('AdGuard'), findsOneWidget);
    expect(find.text('OpenDNS'), findsOneWidget);

    // Verifica ícones da barra de navegação inferior
    expect(find.byIcon(LucideIcons.shield), findsOneWidget);
    expect(find.byIcon(LucideIcons.globe), findsWidgets);
    expect(find.byIcon(LucideIcons.gauge), findsOneWidget);
    expect(find.byIcon(LucideIcons.settings), findsOneWidget);
  });
}
