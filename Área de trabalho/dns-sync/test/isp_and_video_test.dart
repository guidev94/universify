import 'dart:convert';
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter/services.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:dns_sync/data/models/isp_info_model.dart';
import 'package:dns_sync/data/services/isp_detection_service.dart';
import 'package:dns_sync/data/services/video_streaming_test_service.dart';
import 'package:dns_sync/core/constants/canal_nativo.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  group('IspInfoModel & IspDetectionService Tests', () {
    test('IspInfoModel parseia dados e bandeira emoji com sucesso', () {
      final jsonMap = {
        'org': 'AS28573 Claro S.A.',
        'asn': 'AS28573',
        'ip': '177.18.92.14',
        'city': 'São Paulo',
        'country_name': 'Brasil',
        'country_code': 'BR',
      };

      final model = IspInfoModel.fromJson(jsonMap);
      expect(model.isp, 'Claro S.A.');
      expect(model.city, 'São Paulo');
      expect(model.flag, '🇧🇷');
      expect(model.ip, '177.18.92.14');
    });

    test('IspDetectionService retorna Provedor com Mock HTTP', () async {
      final mockClient = MockClient((request) async {
        return http.Response(
          json.encode({
            'isp': 'Vivo Fibra',
            'city': 'Rio de Janeiro',
            'country_name': 'Brasil',
            'country_code': 'BR',
            'query': '189.10.20.30',
          }),
          200,
        );
      });

      final service = IspDetectionService(client: mockClient);
      final isp = await service.detectarProvedor();

      expect(isp.isp, 'Vivo Fibra');
      expect(isp.city, 'Rio de Janeiro');
      expect(isp.flag, '🇧🇷');
    });
  });

  group('VideoStreamingTestService Tests', () {
    test('VideoTestMetrics define resoluções e tiers adequadamente', () {
      final metrics = VideoTestMetrics(
        stage: 'complete',
        progress: 1.0,
        bitrateMbps: 35.5,
        ttfbMs: 45,
        maxResolution: '4K Ultra HD (2160p)',
        isSupported4K: true,
        isSupported1080p: true,
        isSupported720p: true,
      );

      expect(metrics.isSupported4K, true);
      expect(metrics.bitrateMbps, 35.5);
      expect(metrics.maxResolution, '4K Ultra HD (2160p)');

      final updated = metrics.copyWith(bitrateMbps: 8.5, maxResolution: 'HD (720p)');
      expect(updated.bitrateMbps, 8.5);
      expect(updated.maxResolution, 'HD (720p)');
    });

    test('VideoStreamingTestService executa fluxo e calcula métricas', () async {
      final mockClient = MockClient((request) async {
        return http.Response('0' * 500000, 200);
      });

      final service = VideoStreamingTestService(client: mockClient);
      final result = await service.executarTesteVideo(onProgress: (m) {});

      expect(result.stage, 'complete');
      expect(result.bitrateMbps, greaterThan(0));
      expect(result.maxResolution, isNotEmpty);
    });
  });

  group('CanalNativo Compartilhar Resultado Tests', () {
    const channel = MethodChannel('com.dnssync.app/dns');
    final List<MethodCall> log = [];

    setUp(() {
      TestDefaultBinaryMessengerBinding.instance.defaultBinaryMessenger
          .setMockMethodCallHandler(channel, (MethodCall methodCall) async {
        log.add(methodCall);
        if (methodCall.method == 'compartilharResultado') {
          return true;
        }
        return null;
      });
      log.clear();
    });

    tearDown(() {
      TestDefaultBinaryMessengerBinding.instance.defaultBinaryMessenger
          .setMockMethodCallHandler(channel, null);
    });

    test('compartilharResultado envia texto para intent nativo', () async {
      final res = await CanalNativo.compartilharResultado("Resultado 100Mbps", titulo: "Compartilhar");
      expect(res, true);
      expect(log.length, 1);
      expect(log.first.method, 'compartilharResultado');
      expect(log.first.arguments['texto'], 'Resultado 100Mbps');
    });
  });
}
