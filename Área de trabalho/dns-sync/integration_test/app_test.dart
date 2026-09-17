import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';
import 'package:dns_sync/main.dart' as app;

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();

  group('E2E - Fluxos de Funcionalidade', () {
    testWidgets('Ativar DNS e verificar mudança de UI', (tester) async {
      app.main();
      await tester.pumpAndSettle();

      // Verifica se o texto inicial de status está correto (Desativada)
      expect(find.text('Desativada'), findsOneWidget);

      // Clica no botão de ligar/desligar (Power Button)
      // Procuramos pelo ícone de power dentro do GestureDetector
      final powerButton = find.byType(GestureDetector).first;
      await tester.tap(powerButton);
      await tester.pumpAndSettle();

      // Verifica se o texto mudou para Ativada
      expect(find.text('Ativada'), findsOneWidget);
    });

    testWidgets('Navegação via Floating NavBar para Ajustes', (tester) async {
      app.main();
      await tester.pumpAndSettle();

      // Tenta encontrar o ícone de configurações na NavBar flutuante
      final settingsNav = find.byIcon(Icons.settings_rounded);
      await tester.tap(settingsNav);
      await tester.pumpAndSettle();

      // Verifica se o título da tela de Ajustes está visível
      expect(find.text('Ajustes'), findsOneWidget);
    });

    testWidgets('Explorar Proxies e ver lista', (tester) async {
      app.main();
      await tester.pumpAndSettle();

      // Rola até o botão de explorar servidores se necessário
      await tester.drag(find.byType(CustomScrollView), const Offset(0, -500));
      await tester.pumpAndSettle();

      final exploreButton = find.text('Explorar Servidores');
      await tester.tap(exploreButton);
      await tester.pumpAndSettle();

      // Verifica se a tela de lista de proxies abriu
      expect(find.text('Lista de Proxies'), findsOneWidget);
    });
  });
}
