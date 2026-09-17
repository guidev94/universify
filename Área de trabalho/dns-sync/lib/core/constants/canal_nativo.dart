import 'package:flutter/services.dart';

class CanalNativo {
  static const MethodChannel _canal = MethodChannel('com.dnssync.app/dns');

  // Método para aplicar DNS (Placeholder para implementação nativa)
  static Future<bool> aplicarDns(String dnsPrincipal, String dnsSecundario) async {
    try {
      final bool sucesso = await _canal.invokeMethod('aplicarDns', {
        'principal': dnsPrincipal,
        'secundario': dnsSecundario,
      });
      return sucesso;
    } on PlatformException catch (e) {
      // ignore: avoid_print
      print("Erro ao aplicar DNS: ${e.message}");
      return false;
    }
  }

  // Método para desconectar
  static Future<void> desconectar() async {
    try {
      await _canal.invokeMethod('desconectar');
    } on PlatformException catch (e) {
      // ignore: avoid_print
      print("Erro ao desconectar: ${e.message}");
    }
  }
}
