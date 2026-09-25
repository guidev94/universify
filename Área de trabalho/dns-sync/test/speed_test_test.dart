import 'package:flutter_test/flutter_test.dart';
import 'package:dns_sync/data/services/real_speed_test_service.dart';
import 'package:dns_sync/presentation/providers/proxy_provider.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

void main() {
  group('SpeedTest Provider & Metrics Tests', () {
    test('Estado inicial do SpeedTestMetrics', () {
      final container = ProviderContainer();
      addTearDown(container.dispose);

      final state = container.read(speedTestProvider);
      expect(state.stage, 'idle');
      expect(state.progress, 0.0);
      expect(state.downloadSpeed, 0.0);
      expect(state.uploadSpeed, 0.0);
      expect(state.speedHistory, isEmpty);
    });

    test('SpeedTestMetrics copyWith deve manter imutabilidade e atualizar campos', () {
      final metrics = SpeedTestMetrics();
      final updated = metrics.copyWith(
        ping: 15,
        jitter: 2,
        downloadSpeed: 92.4,
        uploadSpeed: 34.1,
        stage: 'download',
        speedHistory: [10.0, 45.0, 92.4],
      );

      expect(updated.ping, 15);
      expect(updated.jitter, 2);
      expect(updated.downloadSpeed, 92.4);
      expect(updated.uploadSpeed, 34.1);
      expect(updated.stage, 'download');
      expect(updated.speedHistory.length, 3);
      expect(metrics.downloadSpeed, 0.0);
    });

    test('RealSpeedTestService instanciado com sucesso', () {
      final service = RealSpeedTestService();
      expect(service, isNotNull);
    });
  });
}
