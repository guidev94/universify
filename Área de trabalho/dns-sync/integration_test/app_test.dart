import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';
import 'package:dns_sync/main.dart' as app;

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();

  group('Teste de Fluxo Principal', () {
    testWidgets('Verifica se o app inicia e permite alternar DNS', (tester) async {
      app.main();
      await tester.pumpAndSettle();

      // Verifica título
      expect(find.text('Sincronização DNS'), findsOneWidget);

      // Tenta clicar no card do Google DNS
      final googleCard = find.text('Google DNS');
      expect(googleCard, findsOneWidget);
      await tester.tap(googleCard);
      await tester.pumpAndSettle();

      // Verifica se o status mudou para Protegido
      expect(find.text('Protegido'), findsOneWidget);
    });

    testWidgets('Navegação para tela de Proxies', (tester) async {
      app.main();
      await tester.pumpAndSettle();

      final botaoProxies = find.text('Explorar Proxies');
      await tester.tap(botaoProxies);
      await tester.pumpAndSettle();

      // Verifica se entrou na tela de lista
      expect(find.text('Lista de Proxies'), findsOneWidget);
    });
    group('Performance', () {
      testWidgets('Tempo de resposta da UI ao alternar tema', (tester) async {
        app.main();
        await tester.pumpAndSettle();
      });
    });
  });
}
