import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'game_settings.dart';

class SettingsController extends ChangeNotifier {
  static const storageKey = 'lulicraft:settings';
  static const versionKey = 'lulicraft:settingsVersion';
  static const schemaVersion = 2;

  GameSettings _value = GameSettings.defaults;
  bool _loaded = false;

  GameSettings get value => _value;
  bool get isLoaded => _loaded;

  Future<void> load() async {
    if (_loaded) return;
    try {
      final prefs = await SharedPreferences.getInstance();
      final raw = prefs.getString(storageKey);
      if (raw != null && raw.isNotEmpty) {
        final decoded = jsonDecode(raw);
        if (decoded is Map) {
          _value = GameSettings.fromMap(decoded.cast<String, dynamic>());
        }
      }
      await prefs.setInt(versionKey, schemaVersion);
    } catch (error) {
      debugPrint('Configurações nativas indisponíveis: $error');
    }
    _loaded = true;
    notifyListeners();
  }

  Future<void> update(GameSettings next) async {
    if (next == _value) return;
    _value = next;
    notifyListeners();
    await _persist();
  }

  void preview(GameSettings next) {
    if (next == _value) return;
    _value = next;
    notifyListeners();
  }

  Future<void> commit() => _persist();

  Future<void> mergeFromGame(Map<String, dynamic> webSettings) async {
    final merged = GameSettings.fromMap({..._value.toMap(), ...webSettings});
    await update(merged);
  }

  Future<void> reset() async {
    await update(GameSettings.fromMap({
      ...GameSettings.defaults.toMap(),
      if (_value.character != null) 'character': _value.character,
    }));
  }

  Future<void> _persist() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString(storageKey, jsonEncode(_value.toMap()));
      await prefs.setInt(versionKey, schemaVersion);
    } catch (error) {
      debugPrint('Não foi possível salvar as configurações: $error');
    }
  }
}
