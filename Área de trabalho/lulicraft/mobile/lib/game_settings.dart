enum GraphicsQuality {
  auto('auto', 'Automático'),
  pixel('pixel', 'Pixel suave'),
  turbo('turbo', 'Turbo leve');

  const GraphicsQuality(this.id, this.label);

  final String id;
  final String label;

  static GraphicsQuality fromId(Object? value) {
    return values.firstWhere(
      (entry) => entry.id == value,
      orElse: () => GraphicsQuality.auto,
    );
  }
}

enum InterfaceScale {
  compact('compact', 'Compacta'),
  normal('normal', 'Normal'),
  large('large', 'Grande');

  const InterfaceScale(this.id, this.label);

  final String id;
  final String label;

  static InterfaceScale fromId(Object? value) {
    return values.firstWhere(
      (entry) => entry.id == value,
      orElse: () => InterfaceScale.normal,
    );
  }
}

class GameSettings {
  const GameSettings({
    this.sound = true,
    this.sensitivity = 1,
    this.touchSensitivity = 1.6,
    this.invertY = false,
    this.fov = 74,
    this.viewBob = true,
    this.leftHand = false,
    this.startThird = false,
    this.showFps = false,
    this.particles = true,
    this.clouds = true,
    this.showCoords = false,
    this.quality = GraphicsQuality.auto,
    this.uiScale = InterfaceScale.normal,
    this.character,
  });

  final bool sound;
  final double sensitivity;
  final double touchSensitivity;
  final bool invertY;
  final double fov;
  final bool viewBob;
  final bool leftHand;
  final bool startThird;
  final bool showFps;
  final bool particles;
  final bool clouds;
  final bool showCoords;
  final GraphicsQuality quality;
  final InterfaceScale uiScale;
  final Map<String, dynamic>? character;

  static const GameSettings defaults = GameSettings();

  static GameSettings fromMap(Map<String, dynamic> map) {
    return GameSettings(
      sound: _flag(map['sound'], true),
      sensitivity: _number(map['sensitivity'], 0.4, 2.2, 1),
      touchSensitivity: _number(map['touchSensitivity'], 0.6, 2.6, 1.6),
      invertY: _flag(map['invertY'], false),
      fov: _number(map['fov'], 60, 100, 74),
      viewBob: _flag(map['viewBob'], true),
      leftHand: _flag(map['leftHand'], false),
      startThird: _flag(map['startThird'], false),
      showFps: _flag(map['showFps'], false),
      particles: _flag(map['particles'], true),
      clouds: _flag(map['clouds'], true),
      showCoords: _flag(map['showCoords'], false),
      quality: GraphicsQuality.fromId(map['quality']),
      uiScale: InterfaceScale.fromId(map['uiScale']),
      character: sanitizeCharacter(map['character']),
    );
  }

  GameSettings copyWith({
    bool? sound,
    double? sensitivity,
    double? touchSensitivity,
    bool? invertY,
    double? fov,
    bool? viewBob,
    bool? leftHand,
    bool? startThird,
    bool? showFps,
    bool? particles,
    bool? clouds,
    bool? showCoords,
    GraphicsQuality? quality,
    InterfaceScale? uiScale,
    Map<String, dynamic>? character,
  }) {
    return GameSettings(
      sound: sound ?? this.sound,
      sensitivity: sensitivity ?? this.sensitivity,
      touchSensitivity: touchSensitivity ?? this.touchSensitivity,
      invertY: invertY ?? this.invertY,
      fov: fov ?? this.fov,
      viewBob: viewBob ?? this.viewBob,
      leftHand: leftHand ?? this.leftHand,
      startThird: startThird ?? this.startThird,
      showFps: showFps ?? this.showFps,
      particles: particles ?? this.particles,
      clouds: clouds ?? this.clouds,
      showCoords: showCoords ?? this.showCoords,
      quality: quality ?? this.quality,
      uiScale: uiScale ?? this.uiScale,
      character: character ?? this.character,
    );
  }

  Map<String, dynamic> toMap() {
    return {
      'sound': sound,
      'sensitivity': sensitivity,
      'touchSensitivity': touchSensitivity,
      'invertY': invertY,
      'fov': fov,
      'viewBob': viewBob,
      'leftHand': leftHand,
      'startThird': startThird,
      'showFps': showFps,
      'particles': particles,
      'clouds': clouds,
      'showCoords': showCoords,
      'quality': quality.id,
      'uiScale': uiScale.id,
      if (character != null) 'character': character,
    };
  }

  Map<String, String> toQueryParameters() {
    return {
      'sound': _bit(sound),
      'invert': _bit(invertY),
      'lefthand': _bit(leftHand),
      'viewbob': _bit(viewBob),
      'coords': _bit(showCoords),
      'fps': _bit(showFps),
      'particles': _bit(particles),
      'clouds': _bit(clouds),
      'third': _bit(startThird),
      'quality': quality.id,
      'ui': uiScale.id,
      'sens': sensitivity.toStringAsFixed(2),
      'touchsens': touchSensitivity.toStringAsFixed(2),
      'fov': fov.round().toString(),
    };
  }

  static Map<String, dynamic>? sanitizeCharacter(Object? value) {
    if (value is! Map) return null;
    final clean = <String, dynamic>{};
    for (final entry in value.entries) {
      final key = entry.key;
      if (key is! String || key.isEmpty || key.length > 32) continue;
      final item = entry.value;
      if (item is String && item.length <= 48) {
        clean[key] = item;
      } else if (item is bool) {
        clean[key] = item;
      } else if (item is num && item.isFinite) {
        clean[key] = item;
      }
    }
    return clean.isEmpty ? null : clean;
  }

  static bool _flag(Object? value, bool fallback) {
    if (value is bool) return value;
    if (value is num) return value != 0;
    if (value is String) {
      final normalized = value.toLowerCase();
      if (const ['1', 'true', 'on', 'yes'].contains(normalized)) return true;
      if (const ['0', 'false', 'off', 'no'].contains(normalized)) return false;
      return fallback;
    }
    return fallback;
  }

  static double _number(Object? value, double min, double max, double fallback) {
    final parsed = value is num ? value.toDouble() : double.tryParse('$value');
    if (parsed == null || !parsed.isFinite) return fallback;
    return parsed.clamp(min, max).toDouble();
  }

  static String _bit(bool value) => value ? '1' : '0';

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;
    return other is GameSettings &&
        other.sound == sound &&
        other.sensitivity == sensitivity &&
        other.touchSensitivity == touchSensitivity &&
        other.invertY == invertY &&
        other.fov == fov &&
        other.viewBob == viewBob &&
        other.leftHand == leftHand &&
        other.startThird == startThird &&
        other.showFps == showFps &&
        other.particles == particles &&
        other.clouds == clouds &&
        other.showCoords == showCoords &&
        other.quality == quality &&
        other.uiScale == uiScale &&
        _sameCharacter(other.character, character);
  }

  @override
  int get hashCode => Object.hash(
    sound,
    sensitivity,
    touchSensitivity,
    invertY,
    fov,
    viewBob,
    leftHand,
    startThird,
    showFps,
    particles,
    clouds,
    showCoords,
    quality,
    uiScale,
    character == null ? 0 : Object.hashAll(character!.entries.map((e) => Object.hash(e.key, e.value))),
  );

  static bool _sameCharacter(Map<String, dynamic>? a, Map<String, dynamic>? b) {
    if (a == null || b == null) return a == b;
    if (a.length != b.length) return false;
    for (final entry in a.entries) {
      if (b[entry.key] != entry.value) return false;
    }
    return true;
  }
}
