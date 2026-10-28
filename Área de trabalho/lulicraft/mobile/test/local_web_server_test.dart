import 'package:flutter_test/flutter_test.dart';
import 'package:lulicraft/local_web_server.dart';

void main() {
  Uri uri(String path) => Uri.parse('http://127.0.0.1:8080$path');

  group('resolveAssetPath', () {
    test('serves index.html for the root', () {
      expect(LocalWebServer.resolveAssetPath(uri('/')), 'index.html');
    });

    test('resolves known game assets', () {
      expect(LocalWebServer.resolveAssetPath(uri('/index.html')), 'index.html');
      expect(
        LocalWebServer.resolveAssetPath(uri('/src/game/main.js')),
        'src/game/main.js',
      );
      expect(
        LocalWebServer.resolveAssetPath(uri('/assets/styles.css?cache=1')),
        'assets/styles.css',
      );
    });

    test('rejects decoded traversal segments', () {
      expect(LocalWebServer.resolveAssetPath(uri('/a/..%2fsecret')), isNull);
      expect(LocalWebServer.resolveAssetPath(uri('/..%5csecret')), isNull);
      expect(LocalWebServer.resolveAssetPath(uri('/a/%2e%2e%2fsecret')), isNull);
    });

    test('rejects absolute and drive-like paths', () {
      expect(LocalWebServer.resolveAssetPath(uri('/C:/Windows/win.ini')), isNull);
      expect(LocalWebServer.resolveAssetPath(uri('/file:///etc/passwd')), isNull);
    });

    test('rejects control characters and oversized paths', () {
      expect(LocalWebServer.resolveAssetPath(uri('/a%00b')), isNull);
      final long = '/${'a' * (LocalWebServer.maxPathLength + 1)}';
      expect(LocalWebServer.resolveAssetPath(uri(long)), isNull);
    });

    test('normalized dot segments stay inside the asset root', () {
      expect(LocalWebServer.resolveAssetPath(uri('/../index.html')), 'index.html');
    });
  });

  group('content types', () {
    test('maps game extensions', () {
      expect(LocalWebServer.contentTypeFor('index.html'), contains('text/html'));
      expect(LocalWebServer.contentTypeFor('src/game/main.js'), contains('javascript'));
      expect(LocalWebServer.contentTypeFor('assets/styles.css'), contains('text/css'));
      expect(LocalWebServer.contentTypeFor('logo.png'), 'image/png');
      expect(LocalWebServer.contentTypeFor('app.wasm'), 'application/wasm');
    });

    test('falls back to binary', () {
      expect(LocalWebServer.contentTypeFor('data.bin'), 'application/octet-stream');
      expect(LocalWebServer.contentTypeFor('LICENSE'), 'application/octet-stream');
    });
  });

  group('injectBootstrap', () {
    const html = '<html><head><title>Lulicraft</title></head><body></body></html>';

    test('injects before </head>', () {
      final result = LocalWebServer.injectBootstrap(html, {'sound': true});
      expect(result.indexOf('lulicraft:settings'), greaterThanOrEqualTo(0));
      expect(result.indexOf('<script>'), lessThan(result.indexOf('</head>')));
      expect(result, endsWith('</body></html>'));
    });

    test('escapes closing script sequences from settings', () {
      final result = LocalWebServer.injectBootstrap(html, {
        'character': {'name': '</script><script>alert(1)</script>'},
      });
      expect(result, isNot(contains('</script><script>alert(1)')));
      expect(result, contains(r'\u003c/script>'));
    });

    test('prepends when there is no head', () {
      final result = LocalWebServer.injectBootstrap('<body>oi</body>', {'fov': 80});
      expect(result, startsWith('<script>'));
      expect(result, endsWith('<body>oi</body>'));
    });
  });
}
