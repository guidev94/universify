import 'dart:convert';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:dns_sync/data/services/dns_leak_service.dart';

void main() {
  group('DnsLeakService Tests', () {
    test('obterInfoConexao deve parsear JSON com sucesso', () async {
      final mockClient = MockClient((request) async {
        return http.Response(
          json.encode({
            'ip': '200.180.10.5',
            'city': 'Curitiba',
            'country_name': 'Brasil',
            'org': 'Sync DNS Secure Resolver'
          }),
          200,
        );
      });

      final service = DnsLeakService(client: mockClient);
      final info = await service.obterInfoConexao();

      expect(info['ip'], '200.180.10.5');
      expect(info['city'], 'Curitiba');
      expect(info['country_name'], 'Brasil');
    });

    test('obterInfoConexao retorna fallback em caso de erro', () async {
      final mockClient = MockClient((request) async {
        return http.Response('Error', 500);
      });

      final service = DnsLeakService(client: mockClient);
      final info = await service.obterInfoConexao();

      expect(info['ip'], isNotEmpty);
      expect(info['country_name'], isNotEmpty);
    });
  });
}
