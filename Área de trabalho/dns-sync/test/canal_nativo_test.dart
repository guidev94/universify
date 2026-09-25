import 'package:flutter/services.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:dns_sync/core/constants/canal_nativo.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  group('CanalNativo Tests', () {
    const channel = MethodChannel('com.dnssync.app/dns');
    final List<MethodCall> log = [];

    setUp(() {
      TestDefaultBinaryMessengerBinding.instance.defaultBinaryMessenger
          .setMockMethodCallHandler(channel, (MethodCall methodCall) async {
        log.add(methodCall);
        switch (methodCall.method) {
          case 'aplicarDns':
            return true;
          case 'aplicarProxy':
            return true;
          case 'desconectar':
            return true;
          case 'obterStatus':
            return {
              'ativo': true,
              'provedor': 'Google',
              'dns': '8.8.8.8',
              'startTimeMs': 1000,
              'anunciosBloqueados': 12,
            };
          default:
            return null;
        }
      });
      log.clear();
    });

    tearDown(() {
      TestDefaultBinaryMessengerBinding.instance.defaultBinaryMessenger
          .setMockMethodCallHandler(channel, null);
    });

    test('aplicarDns invoca método nativo com DoH, AdBlock e Split Tunnel', () async {
      final res = await CanalNativo.aplicarDns(
        '8.8.8.8', 
        '8.8.4.4', 
        provedor: 'Google',
        useDoh: true,
        dohUrl: 'https://dns.google/dns-query',
        enableAdBlock: true,
        bypassPackages: ['com.banco.app'],
      );
      expect(res, true);
      expect(log.length, 1);
      expect(log.first.method, 'aplicarDns');
      expect(log.first.arguments['principal'], '8.8.8.8');
      expect(log.first.arguments['secundario'], '8.8.4.4');
      expect(log.first.arguments['provedor'], 'Google');
      expect(log.first.arguments['useDoh'], true);
      expect(log.first.arguments['dohUrl'], 'https://dns.google/dns-query');
      expect(log.first.arguments['enableAdBlock'], true);
      expect(log.first.arguments['bypassPackages'], ['com.banco.app']);
    });

    test('aplicarProxy invoca método nativo com credenciais', () async {
      final res = await CanalNativo.aplicarProxy(
        ip: '104.248.63.15', 
        porta: 3128, 
        pais: 'EUA',
        usuario: 'admin',
        senha: 'secretpassword',
      );
      expect(res, true);
      expect(log.last.method, 'aplicarProxy');
      expect(log.last.arguments['ip'], '104.248.63.15');
      expect(log.last.arguments['porta'], 3128);
    });

    test('desconectar invoca canal nativo', () async {
      final res = await CanalNativo.desconectar();
      expect(res, true);
      expect(log.last.method, 'desconectar');
    });

    test('obterStatus retorna mapa estruturado incluindo contagem de bloqueios', () async {
      final status = await CanalNativo.obterStatus();
      expect(status['ativo'], true);
      expect(status['provedor'], 'Google');
      expect(status['dns'], '8.8.8.8');
      expect(status['anunciosBloqueados'], 12);
    });
  });
}
