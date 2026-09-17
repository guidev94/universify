import 'package:flutter_test/flutter_test.dart';
import 'package:dns_sync/presentation/providers/proxy_provider.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

void main() {
  group('Testes de Estado (Riverpod)', () {
    test('O status inicial do DNS deve ser desativado (false)', () {
      final container = ProviderContainer();
      addTearDown(container.dispose);

      expect(container.read(dnsStatusProvider), false);
    });

    test('Alterar o status do DNS deve refletir no provider', () {
      final container = ProviderContainer();
      addTearDown(container.dispose);

      container.read(dnsStatusProvider.notifier).state = true;
      expect(container.read(dnsStatusProvider), true);
    });

    test('O provedor selecionado inicial deve ser "Nenhum"', () {
      final container = ProviderContainer();
      addTearDown(container.dispose);

      expect(container.read(provedorSelecionadoProvider), "Nenhum");
    });
  });
}
