import 'dart:io';
import 'package:flutter/services.dart';

class SyncHttpOverrides extends HttpOverrides {
  final String host;
  final int port;
  final String? usuario;
  final String? senha;

  SyncHttpOverrides(this.host, this.port, {this.usuario, this.senha});

  @override
  HttpClient createHttpClient(SecurityContext? context) {
    final client = super.createHttpClient(context);
    client.findProxy = (uri) => 'PROXY $host:$port; DIRECT';
    if (usuario != null && usuario!.isNotEmpty && senha != null) {
      client.addProxyCredentials(
        host, 
        port, 
        '', 
        HttpClientBasicCredentials(usuario!, senha!),
      );
    }
    client.badCertificateCallback = (cert, host, port) => true;
    return client;
  }
}

class CanalNativo {
  static const MethodChannel _canal = MethodChannel('com.dnssync.app/dns');
  static void Function(bool ativo, String provedor)? _statusCallback;
  static void Function(int totalBlocked, String domain)? _adBlockedCallback;

  static void inicializar({
    void Function(bool ativo, String provedor)? onStatusChange,
    void Function(int totalBlocked, String domain)? onAdBlocked,
  }) {
    _statusCallback = onStatusChange;
    _adBlockedCallback = onAdBlocked;

    _canal.setMethodCallHandler((call) async {
      if (call.method == 'onStatusChanged') {
        final args = call.arguments as Map<dynamic, dynamic>?;
        final bool ativo = args?['ativo'] ?? false;
        final String provedor = args?['provedor'] ?? 'Desconectado';
        if (!ativo) {
          HttpOverrides.global = null;
        }
        _statusCallback?.call(ativo, provedor);
      } else if (call.method == 'onAdBlocked') {
        final args = call.arguments as Map<dynamic, dynamic>?;
        final int totalBlocked = args?['totalBlocked'] ?? 0;
        final String domain = args?['domain'] ?? '';
        _adBlockedCallback?.call(totalBlocked, domain);
      }
    });
  }

  // Método para aplicar DNS nativo via VPN com DoH, AdBlock e Split Tunneling
  static Future<bool> aplicarDns(
    String dnsPrincipal, 
    String dnsSecundario, {
    String provedor = "Custom",
    bool useDoh = false,
    String dohUrl = "",
    bool enableAdBlock = true,
    List<String> bypassPackages = const [],
  }) async {
    try {
      HttpOverrides.global = null;
      final bool? sucesso = await _canal.invokeMethod<bool>('aplicarDns', {
        'principal': dnsPrincipal,
        'secundario': dnsSecundario,
        'provedor': provedor,
        'useDoh': useDoh,
        'dohUrl': dohUrl,
        'enableAdBlock': enableAdBlock,
        'bypassPackages': bypassPackages,
      });
      return sucesso ?? false;
    } on PlatformException catch (e) {
      // ignore: avoid_print
      print("Erro ao aplicar DNS: ${e.message}");
      return false;
    }
  }

  // Método para aplicar Proxy nativo com suporte a Autenticação
  static Future<bool> aplicarProxy({
    required String ip,
    required int porta,
    String pais = "Global",
    String? usuario,
    String? senha,
  }) async {
    try {
      HttpOverrides.global = SyncHttpOverrides(ip, porta, usuario: usuario, senha: senha);

      final bool? sucesso = await _canal.invokeMethod<bool>('aplicarProxy', {
        'ip': ip,
        'porta': porta,
        'pais': pais,
      });
      return sucesso ?? true;
    } on PlatformException catch (e) {
      // ignore: avoid_print
      print("Erro ao aplicar Proxy: ${e.message}");
      return false;
    }
  }

  // Desconectar VPN e restaurar rede
  static Future<bool> desconectar() async {
    try {
      HttpOverrides.global = null;
      final bool? sucesso = await _canal.invokeMethod<bool>('desconectar');
      return sucesso ?? true;
    } on PlatformException catch (e) {
      // ignore: avoid_print
      print("Erro ao desconectar: ${e.message}");
      return false;
    }
  }

  // Compartilhar resultado via Share Sheet Nativo do Android / iOS
  static Future<bool> compartilharResultado(String texto, {String titulo = "Resultado Sync DNS"}) async {
    try {
      final bool? sucesso = await _canal.invokeMethod<bool>('compartilharResultado', {
        'texto': texto,
        'titulo': titulo,
      });
      return sucesso ?? true;
    } on PlatformException catch (e) {
      // ignore: avoid_print
      print("Erro ao compartilhar: ${e.message}");
      return false;
    }
  }

  // Consulta o status real atual do serviço nativo
  static Future<Map<String, dynamic>> obterStatus() async {
    try {
      final Map<dynamic, dynamic>? status = 
          await _canal.invokeMethod<Map<dynamic, dynamic>>('obterStatus');
      if (status == null) {
        return {
          'ativo': false, 
          'provedor': 'Nenhum', 
          'dns': '', 
          'startTimeMs': 0,
          'anunciosBloqueados': 0
        };
      }
      return {
        'ativo': status['ativo'] ?? false,
        'provedor': status['provedor'] ?? 'Nenhum',
        'dns': status['dns'] ?? '',
        'startTimeMs': status['startTimeMs'] ?? 0,
        'anunciosBloqueados': status['anunciosBloqueados'] ?? 0,
      };
    } on PlatformException catch (e) {
      // ignore: avoid_print
      print("Erro ao obter status: ${e.message}");
      return {
        'ativo': false, 
        'provedor': 'Nenhum', 
        'dns': '', 
        'startTimeMs': 0,
        'anunciosBloqueados': 0
      };
    }
  }
}
