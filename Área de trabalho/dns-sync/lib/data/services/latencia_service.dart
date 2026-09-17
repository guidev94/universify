import 'dart:io';
import 'dart:async';

class LatenciaService {
  Future<int> testarLatencia(String ip, int porta) async {
    final stopwatch = Stopwatch()..start();
    try {
      final socket = await Socket.connect(ip, porta, timeout: const Duration(seconds: 2));
      stopwatch.stop();
      socket.destroy();
      return stopwatch.elapsedMilliseconds;
    } catch (e) {
      stopwatch.stop();
      return -1; // Erro ou Timeout
    }
  }
}
