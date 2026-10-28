import 'package:flutter_test/flutter_test.dart';
import 'package:lulicraft/game_settings.dart';
import 'package:lulicraft/settings_controller.dart';
import 'package:shared_preferences/shared_preferences.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  setUp(() {
    SharedPreferences.setMockInitialValues({});
  });

  test('starts with defaults when nothing is stored', () async {
    final controller = SettingsController();
    await controller.load();
    expect(controller.value, GameSettings.defaults);
  });

  test('persists changes across instances', () async {
    final controller = SettingsController();
    await controller.load();
    await controller.update(
      controller.value.copyWith(fov: 92, leftHand: true, sound: false),
    );

    final reloaded = SettingsController();
    await reloaded.load();
    expect(reloaded.value.fov, 92);
    expect(reloaded.value.leftHand, isTrue);
    expect(reloaded.value.sound, isFalse);
  });

  test('merges settings sent by the game', () async {
    final controller = SettingsController();
    await controller.load();
    await controller.mergeFromGame({
      'fov': 81,
      'touchSensitivity': 2.0,
      'character': {'preset': 'theo', 'custom': false},
    });
    expect(controller.value.fov, 81);
    expect(controller.value.touchSensitivity, 2.0);
    expect(controller.value.character, {'preset': 'theo', 'custom': false});
  });

  test('reset keeps the chosen character', () async {
    final controller = SettingsController();
    await controller.load();
    await controller.update(
      controller.value.copyWith(
        fov: 95,
        character: const {'preset': 'nina', 'custom': true},
      ),
    );
    await controller.reset();
    expect(controller.value.fov, 74);
    expect(controller.value.character, {'preset': 'nina', 'custom': true});
  });

  test('ignores malformed stored JSON', () async {
    SharedPreferences.setMockInitialValues({
      SettingsController.storageKey: '{not valid json',
    });
    final controller = SettingsController();
    await controller.load();
    expect(controller.value, GameSettings.defaults);
  });
}
