import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:dns_sync/core/utils/ip_validator.dart';
import 'package:dns_sync/data/models/custom_dns_model.dart';
import 'package:dns_sync/data/models/proxy_model.dart';
import 'package:dns_sync/data/services/dns_benchmark_service.dart';
import 'package:dns_sync/presentation/providers/proxy_provider.dart';

void main() {
  group('IpValidator Tests', () {
    test('Valida endereços IPv4 corretos', () {
      expect(IpValidator.isIpv4Valido('8.8.8.8'), true);
      expect(IpValidator.isIpv4Valido('1.1.1.1'), true);
      expect(IpValidator.isIpv4Valido('192.168.1.1'), true);
      expect(IpValidator.isIpv4Valido('208.67.222.222'), true);
    });

    test('Rejeita endereços IPv4 inválidos', () {
      expect(IpValidator.isIpv4Valido('999.1.1.1'), false);
      expect(IpValidator.isIpv4Valido('8.8.8'), false);
      expect(IpValidator.isIpv4Valido('google.com'), false);
      expect(IpValidator.isIpv4Valido(''), false);
    });

    test('Valida portas de rede válidas e inválidas', () {
      expect(IpValidator.isPortaValida(80), true);
      expect(IpValidator.isPortaValida(8080), true);
      expect(IpValidator.isPortaValida(65535), true);
      expect(IpValidator.isPortaValida(0), false);
      expect(IpValidator.isPortaValida(70000), false);
    });
  });

  group('CustomDnsModel & Notifier Tests', () {
    test('Serialização JSON de CustomDnsModel', () {
      final model = CustomDnsModel(
        id: '123',
        nome: 'NextDNS',
        dnsPrincipal: '45.90.28.0',
        dnsSecundario: '45.90.30.0',
        latencia: 14,
      );

      final json = model.toJson();
      expect(json['nome'], 'NextDNS');
      expect(json['dnsPrincipal'], '45.90.28.0');

      final fromJson = CustomDnsModel.fromJson(json);
      expect(fromJson, equals(model));
    });

    test('CustomDnsNotifier adicionar e remover', () async {
      final container = ProviderContainer();
      addTearDown(container.dispose);

      final notifier = container.read(customDnsListProvider.notifier);
      await notifier.adicionar('Meu Pi-Hole', '192.168.1.10', '192.168.1.11');

      final list = container.read(customDnsListProvider);
      expect(list.any((item) => item.nome == 'Meu Pi-Hole'), true);

      final added = list.firstWhere((item) => item.nome == 'Meu Pi-Hole');
      await notifier.remover(added.id);

      final listAfter = container.read(customDnsListProvider);
      expect(listAfter.any((item) => item.nome == 'Meu Pi-Hole'), false);
    });
  });

  group('SplitTunnelNotifier Tests', () {
    test('Toggle de pacote adiciona e remove da lista de bypass', () async {
      final container = ProviderContainer();
      addTearDown(container.dispose);

      final notifier = container.read(splitTunnelProvider.notifier);
      await notifier.toggleApp('com.banco.itau');

      expect(container.read(splitTunnelProvider).contains('com.banco.itau'), true);

      await notifier.toggleApp('com.banco.itau');
      expect(container.read(splitTunnelProvider).contains('com.banco.itau'), false);
    });
  });

  group('ProxyModel Authentication Tests', () {
    test('ProxyModel com usuário e senha identifica autenticação', () {
      final proxy = ProxyModel(
        ip: '10.0.0.1',
        porta: '8080',
        pais: 'Brasil',
        protocolo: 'HTTP',
        latencia: 10,
        anonimato: 'Privado',
        usuario: 'user1',
        senha: 'pass123',
      );

      expect(proxy.isAutenticado, true);
      expect(proxy.usuario, 'user1');
      expect(proxy.senha, 'pass123');

      final map = proxy.toMap();
      final fromMap = ProxyModel.fromJson(map);
      expect(fromMap.isAutenticado, true);
      expect(fromMap.usuario, 'user1');
    });
  });

  group('DnsBenchmarkService Tests', () {
    test('encontrarMelhorServidor retorna um provedor válido', () async {
      final benchmarkService = DnsBenchmarkService();
      final melhor = await benchmarkService.encontrarMelhorServidor();

      expect(melhor['nome'], isNotEmpty);
      expect(melhor['principal'], isNotEmpty);
      expect(melhor['latencia'], isA<int>());
    });
  });
}
