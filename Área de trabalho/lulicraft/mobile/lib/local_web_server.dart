import 'dart:async';
import 'dart:convert';
import 'dart:io';

import 'package:flutter/foundation.dart';
import 'package:flutter/services.dart' show rootBundle;

class LocalWebServer {
  LocalWebServer({this.bootstrapSettings});

  static const String assetRoot = 'assets/web';
  static const int maxPathLength = 512;

  static const Map<String, String> mimeTypes = {
    'html': 'text/html; charset=utf-8',
    'js': 'text/javascript; charset=utf-8',
    'mjs': 'text/javascript; charset=utf-8',
    'css': 'text/css; charset=utf-8',
    'json': 'application/json; charset=utf-8',
    'png': 'image/png',
    'jpg': 'image/jpeg',
    'jpeg': 'image/jpeg',
    'webp': 'image/webp',
    'svg': 'image/svg+xml',
    'ico': 'image/x-icon',
    'woff2': 'font/woff2',
    'wasm': 'application/wasm',
    'txt': 'text/plain; charset=utf-8',
  };

  final Map<String, dynamic>? bootstrapSettings;
  HttpServer? _server;

  int get port => _server?.port ?? 0;
  bool get isRunning => _server != null;

  Future<int> start() async {
    final current = _server;
    if (current != null) return current.port;
    final server = await HttpServer.bind(InternetAddress.loopbackIPv4, 0);
    _server = server;
    server.listen(
      _handle,
      onError: (Object error) => debugPrint('Servidor local: $error'),
      cancelOnError: false,
    );
    return server.port;
  }

  Future<void> stop() async {
    final server = _server;
    _server = null;
    await server?.close(force: true);
  }

  static String? resolveAssetPath(Uri uri) {
    final segments = <String>[];
    for (final segment in uri.pathSegments) {
      if (segment.isEmpty || segment == '.') return null;
      if (segment.contains('..') ||
          segment.contains('/') ||
          segment.contains('\\') ||
          segment.contains(':') ||
          segment.contains('\u0000')) {
        return null;
      }
      if (segment.runes.any((rune) => rune < 0x20)) return null;
      segments.add(segment);
    }
    if (segments.isEmpty) return 'index.html';
    final path = segments.join('/');
    if (path.length > maxPathLength) return null;
    return path;
  }

  static String contentTypeFor(String path) {
    final dot = path.lastIndexOf('.');
    final extension = dot == -1 ? '' : path.substring(dot + 1).toLowerCase();
    return mimeTypes[extension] ?? 'application/octet-stream';
  }

  Future<void> _handle(HttpRequest request) async {
    try {
      if (request.method != 'GET' && request.method != 'HEAD') {
        await _respondText(request, HttpStatus.methodNotAllowed, 'Método não permitido');
        return;
      }
      final relative = resolveAssetPath(request.uri);
      if (relative == null) {
        await _respondText(request, HttpStatus.badRequest, 'Caminho inválido');
        return;
      }
      if (relative == 'favicon.ico') {
        request.response.statusCode = HttpStatus.noContent;
        await request.response.close();
        return;
      }
      if (relative.endsWith('/')) {
        await _respondText(request, HttpStatus.notFound, 'Arquivo não encontrado');
        return;
      }

      List<int> bytes;
      String type = contentTypeFor(relative);
      if (relative == 'index.html') {
        final html = await rootBundle.loadString('$assetRoot/index.html');
        final body = bootstrapSettings == null
            ? html
            : injectBootstrap(html, bootstrapSettings!);
        bytes = utf8.encode(body);
        type = mimeTypes['html']!;
      } else {
        final data = await rootBundle.load('$assetRoot/$relative');
        bytes = data.buffer.asUint8List(data.offsetInBytes, data.lengthInBytes);
      }

      final response = request.response;
      response.statusCode = HttpStatus.ok;
      response.headers.contentType = ContentType.parse(type);
      response.headers.set('Cache-Control', kReleaseMode ? 'public, max-age=3600' : 'no-store');
      response.headers.set('X-Content-Type-Options', 'nosniff');
      response.headers.set('Referrer-Policy', 'no-referrer');
      response.headers.contentLength = bytes.length;
      if (request.method == 'GET') {
        response.add(bytes);
      }
      await response.close();
    } on FlutterError {
      await _respondText(request, HttpStatus.notFound, 'Arquivo não encontrado');
    } on FileSystemException {
      await _respondText(request, HttpStatus.notFound, 'Arquivo não encontrado');
    } catch (error) {
      debugPrint('Falha ao servir ${request.uri}: $error');
      await _respondText(request, HttpStatus.internalServerError, 'Falha ao carregar o jogo');
    }
  }

  static String injectBootstrap(String html, Map<String, dynamic> settings) {
    final payload = jsonEncode(settings).replaceAll('<', r'\u003c');
    final script = '''
<script>
try {
  const patch = $payload;
  const key = 'lulicraft:settings';
  const current = JSON.parse(localStorage.getItem(key) || '{}') || {};
  localStorage.setItem(key, JSON.stringify(Object.assign({}, current, patch)));
} catch (error) {
  console.warn('Falha ao aplicar configurações nativas', error);
}
</script>''';
    final head = html.indexOf('</head>');
    if (head == -1) return '$script$html';
    return html.replaceRange(head, head, script);
  }

  static Future<void> _respondText(
    HttpRequest request,
    int status,
    String message,
  ) async {
    final response = request.response;
    response.statusCode = status;
    response.headers.contentType = ContentType('text', 'plain', charset: 'utf-8');
    response.headers.set('Cache-Control', 'no-store');
    response.headers.set('X-Content-Type-Options', 'nosniff');
    if (request.method == 'GET') {
      response.write(message);
    }
    await response.close();
  }
}
