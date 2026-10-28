import 'package:flutter_test/flutter_test.dart';
import 'package:lulicraft/game_settings.dart';

void main() {
  group('GameSettings', () {
    test('defaults are cozy and safe', () {
      const settings = GameSettings.defaults;
      expect(settings.sound, isTrue);
      expect(settings.fov, 74);
      expect(settings.touchSensitivity, 1.6);
      expect(settings.quality, GraphicsQuality.auto);
      expect(settings.uiScale, InterfaceScale.normal);
      expect(settings.character, isNull);
    });

    test('clamps numeric values into supported ranges', () {
      final settings = GameSettings.fromMap({
        'fov': 200,
        'sensitivity': -3,
        'touchSensitivity': 99,
      });
      expect(settings.fov, 100);
      expect(settings.sensitivity, 0.4);
      expect(settings.touchSensitivity, 2.6);

      final low = GameSettings.fromMap({'fov': 10, 'sensitivity': 0, 'touchSensitivity': 0});
      expect(low.fov, 60);
      expect(low.sensitivity, 0.4);
      expect(low.touchSensitivity, 0.6);
    });

    test('falls back on invalid types', () {
      final settings = GameSettings.fromMap({
        'sound': 'talvez',
        'sensitivity': 'abc',
        'fov': double.nan,
        'quality': 'cinema',
        'uiScale': 42,
      });
      expect(settings.sound, isTrue);
      expect(settings.sensitivity, 1);
      expect(settings.fov, 74);
      expect(settings.quality, GraphicsQuality.auto);
      expect(settings.uiScale, InterfaceScale.normal);
    });

    test('accepts flags from strings and numbers', () {
      final settings = GameSettings.fromMap({
        'sound': 'false',
        'invertY': 1,
        'leftHand': 'on',
        'clouds': 0,
      });
      expect(settings.sound, isFalse);
      expect(settings.invertY, isTrue);
      expect(settings.leftHand, isTrue);
      expect(settings.clouds, isFalse);
    });

    test('round trips through map', () {
      const original = GameSettings(
        sound: false,
        sensitivity: 1.4,
        touchSensitivity: 2.1,
        invertY: true,
        fov: 88,
        viewBob: false,
        leftHand: true,
        startThird: true,
        showFps: true,
        particles: false,
        clouds: false,
        showCoords: true,
        quality: GraphicsQuality.turbo,
        uiScale: InterfaceScale.large,
      );
      final restored = GameSettings.fromMap(original.toMap());
      expect(restored, original);
    });

    test('query parameters mirror the web game contract', () {
      const settings = GameSettings(
        sound: false,
        fov: 90,
        leftHand: true,
        startThird: true,
        showCoords: true,
        quality: GraphicsQuality.pixel,
        uiScale: InterfaceScale.compact,
        sensitivity: 1.25,
        touchSensitivity: 1.75,
      );
      final query = settings.toQueryParameters();
      expect(query['sound'], '0');
      expect(query['lefthand'], '1');
      expect(query['third'], '1');
      expect(query['coords'], '1');
      expect(query['quality'], 'pixel');
      expect(query['ui'], 'compact');
      expect(query['fov'], '90');
      expect(query['sens'], '1.25');
      expect(query['touchsens'], '1.75');
    });

    test('sanitizes character payloads', () {
      final settings = GameSettings.fromMap({
        'character': <dynamic, dynamic>{
          'preset': 'lia',
          'custom': true,
          'size': 3,
          7: 'chave inválida',
          'nested': <String, String>{'nope': 'nope'},
          'tooLong': 'x' * 120,
        },
      });
      expect(settings.character, {'preset': 'lia', 'custom': true, 'size': 3});
    });

    test('ignores empty character payloads', () {
      final settings = GameSettings.fromMap({
        'character': <dynamic, dynamic>{'nested': <String, String>{}},
      });
      expect(settings.character, isNull);
    });
  });
}
