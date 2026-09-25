import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:dns_sync/presentation/providers/proxy_provider.dart';
import 'package:dns_sync/data/models/proxy_model.dart';

void main() {
  group('Testes de Estado do Riverpod (Sync DNS)', () {
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

    test('Alterar provedor selecionado deve atualizar o estado', () {
      final container = ProviderContainer();
      addTearDown(container.dispose);

      container.read(provedorSelecionadoProvider.notifier).state = "Cloudflare";
      expect(container.read(provedorSelecionadoProvider), "Cloudflare");
    });

    test('Tempo de conexão deve iniciar, incrementar e parar', () async {
      final container = ProviderContainer();
      addTearDown(container.dispose);

      final notifier = container.read(tempoConexaoProvider.notifier);
      expect(container.read(tempoConexaoProvider), 0);

      notifier.iniciar();
      await Future.delayed(const Duration(milliseconds: 1100));
      expect(container.read(tempoConexaoProvider), greaterThanOrEqualTo(1));

      notifier.parar();
      expect(container.read(tempoConexaoProvider), 0);
    });

    test('ProxyModel serialização e propriedades funcionam corretamente', () {
      final model = ProxyModel(
        ip: '104.248.63.15',
        porta: '3128',
        pais: 'Estados Unidos',
        protocolo: 'HTTP',
        latencia: 45,
        anonimato: 'Elite',
      );

      expect(model.enderecoFormatado, '104.248.63.15:3128');
      expect(model.portaInt, 3128);

      final json = model.toMap();
      expect(json['ip'], '104.248.63.15');
      expect(json['porta'], '3128');

      final fromJson = ProxyModel.fromJson(json);
      expect(fromJson.ip, model.ip);
      expect(fromJson.porta, model.porta);
      expect(fromJson.pais, model.pais);
      expect(fromJson, equals(model));
    });
  });
}
