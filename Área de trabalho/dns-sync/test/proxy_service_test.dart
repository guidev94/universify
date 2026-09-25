import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:dns_sync/data/services/proxy_service.dart';
import 'package:dns_sync/data/models/proxy_model.dart';

void main() {
  group('ProxyService Tests', () {
    test('buscarProxies deve processar resposta de API e retornar lista de ProxyModel', () async {
      final mockClient = MockClient((request) async {
        return http.Response(
          '192.168.1.100:8080\n10.0.0.1:3128\n172.16.0.5:80',
          200,
        );
      });

      final proxyService = ProxyService(client: mockClient);
      final proxies = await proxyService.buscarProxies();

      expect(proxies, isNotEmpty);
      expect(proxies.first, isA<ProxyModel>());
      expect(proxies.first.ip, '192.168.1.100');
      expect(proxies.first.porta, '8080');
    });

    test('buscarProxies retorna lista padrão caso as APIs falhem', () async {
      final mockClient = MockClient((request) async {
        return http.Response('Internal Server Error', 500);
      });

      final proxyService = ProxyService(client: mockClient);
      final proxies = await proxyService.buscarProxies();

      expect(proxies, isNotEmpty);
      expect(proxies.first.ip, isNotEmpty);
      expect(proxies.first.porta, isNotEmpty);
    });

    test('ProxyModel copyWith funciona adequadamente', () {
      final proxy = ProxyModel(
        ip: '1.2.3.4',
        porta: '8080',
        pais: 'Brasil',
        protocolo: 'HTTP',
        latencia: 10,
        anonimato: 'Alto',
      );

      final updated = proxy.copyWith(latencia: 25, pais: 'Alemanha');
      expect(updated.ip, '1.2.3.4');
      expect(updated.latencia, 25);
      expect(updated.pais, 'Alemanha');
    });
  });
}
