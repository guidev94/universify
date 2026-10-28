import 'package:flutter/material.dart';

abstract final class LumiColors {
  static const background = Color(0xFF0A131B);
  static const backgroundDeep = Color(0xFF060D13);
  static const surface = Color(0xFF12202B);
  static const surfaceHigh = Color(0xFF1A2C39);
  static const primary = Color(0xFF9BE7A1);
  static const primaryDeep = Color(0xFF2E7D5B);
  static const sky = Color(0xFF7CD6FF);
  static const sun = Color(0xFFFFD76A);
  static const rose = Color(0xFFFFA8C8);
  static const ink = Color(0xFFF2F8F4);
  static const muted = Color(0xFF9DB3AF);
  static const outline = Color(0x332F4A5A);
}

abstract final class LumiBranding {
  static const appName = 'Lulicraft';
  static const tagline = 'Um mundo de blocos, luz e descobertas.';
  static const githubUrl = 'https://github.com/guidev94';
  static const githubHandle = 'guidev94';
  static const copyright = '© 2026 guidev94 · Todos os direitos reservados';
}

ThemeData buildLumiTheme() {
  final scheme = ColorScheme.fromSeed(
    seedColor: LumiColors.primary,
    brightness: Brightness.dark,
  ).copyWith(
    primary: LumiColors.primary,
    onPrimary: const Color(0xFF062016),
    primaryContainer: const Color(0xFF1D4A38),
    onPrimaryContainer: const Color(0xFFD8FFE0),
    secondary: LumiColors.sky,
    onSecondary: const Color(0xFF04222E),
    secondaryContainer: const Color(0xFF173B4A),
    onSecondaryContainer: const Color(0xFFD6F3FF),
    surface: LumiColors.surface,
    onSurface: LumiColors.ink,
    surfaceContainerHighest: LumiColors.surfaceHigh,
    onSurfaceVariant: LumiColors.muted,
    outline: LumiColors.outline,
    error: const Color(0xFFFF9A9A),
    onError: const Color(0xFF3A0A0A),
  );

  const textTheme = TextTheme(
    displayLarge: TextStyle(
      fontSize: 46,
      fontWeight: FontWeight.w800,
      letterSpacing: -1.6,
      height: 1.02,
    ),
    displayMedium: TextStyle(
      fontSize: 34,
      fontWeight: FontWeight.w800,
      letterSpacing: -0.9,
      height: 1.08,
    ),
    headlineMedium: TextStyle(
      fontSize: 26,
      fontWeight: FontWeight.w700,
      letterSpacing: -0.5,
    ),
    headlineSmall: TextStyle(
      fontSize: 22,
      fontWeight: FontWeight.w700,
      letterSpacing: -0.3,
    ),
    titleLarge: TextStyle(fontSize: 19, fontWeight: FontWeight.w700),
    titleMedium: TextStyle(fontSize: 16.5, fontWeight: FontWeight.w700),
    bodyLarge: TextStyle(fontSize: 16, height: 1.45),
    bodyMedium: TextStyle(fontSize: 14.5, height: 1.45),
    bodySmall: TextStyle(fontSize: 12.8, height: 1.4),
    labelLarge: TextStyle(
      fontSize: 15,
      fontWeight: FontWeight.w700,
      letterSpacing: 0.1,
    ),
    labelMedium: TextStyle(
      fontSize: 12,
      fontWeight: FontWeight.w700,
      letterSpacing: 0.6,
    ),
  );

  return ThemeData(
    useMaterial3: true,
    colorScheme: scheme,
    scaffoldBackgroundColor: LumiColors.background,
    textTheme: textTheme.apply(
      bodyColor: LumiColors.ink,
      displayColor: LumiColors.ink,
    ),
    splashFactory: InkRipple.splashFactory,
    appBarTheme: const AppBarTheme(
      backgroundColor: Colors.transparent,
      surfaceTintColor: Colors.transparent,
      elevation: 0,
      centerTitle: false,
      foregroundColor: LumiColors.ink,
      titleTextStyle: TextStyle(
        fontSize: 18,
        fontWeight: FontWeight.w700,
        color: LumiColors.ink,
      ),
    ),
    dividerTheme: const DividerThemeData(
      color: Color(0x142F4A5A),
      thickness: 1,
      space: 1,
    ),
    sliderTheme: SliderThemeData(
      activeTrackColor: LumiColors.primary,
      inactiveTrackColor: Colors.white.withValues(alpha: 0.08),
      thumbColor: LumiColors.primary,
      overlayColor: LumiColors.primary.withValues(alpha: 0.12),
      trackHeight: 6,
      valueIndicatorColor: LumiColors.surfaceHigh,
      valueIndicatorTextStyle: const TextStyle(
        color: LumiColors.ink,
        fontWeight: FontWeight.w700,
      ),
    ),
    switchTheme: SwitchThemeData(
      thumbColor: WidgetStateProperty.resolveWith(
        (states) => states.contains(WidgetState.selected)
            ? LumiColors.primary
            : LumiColors.muted,
      ),
      trackColor: WidgetStateProperty.resolveWith(
        (states) => states.contains(WidgetState.selected)
            ? LumiColors.primary.withValues(alpha: 0.3)
            : Colors.white.withValues(alpha: 0.07),
      ),
      trackOutlineColor: WidgetStateProperty.resolveWith(
        (states) => states.contains(WidgetState.selected)
            ? LumiColors.primary.withValues(alpha: 0.35)
            : LumiColors.outline,
      ),
    ),
    segmentedButtonTheme: SegmentedButtonThemeData(
      style: ButtonStyle(
        foregroundColor: WidgetStateProperty.resolveWith(
          (states) => states.contains(WidgetState.selected)
              ? const Color(0xFF062016)
              : LumiColors.muted,
        ),
        backgroundColor: WidgetStateProperty.resolveWith(
          (states) => states.contains(WidgetState.selected)
              ? LumiColors.primary
              : Colors.white.withValues(alpha: 0.04),
        ),
        side: WidgetStateProperty.all(
          const BorderSide(color: LumiColors.outline),
        ),
        textStyle: WidgetStateProperty.all(
          const TextStyle(fontSize: 13, fontWeight: FontWeight.w700),
        ),
        padding: WidgetStateProperty.all(
          const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
        ),
      ),
    ),
    snackBarTheme: SnackBarThemeData(
      behavior: SnackBarBehavior.floating,
      backgroundColor: LumiColors.surfaceHigh,
      contentTextStyle: const TextStyle(color: LumiColors.ink, fontSize: 14),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
    ),
    dialogTheme: DialogThemeData(
      backgroundColor: LumiColors.surface,
      surfaceTintColor: Colors.transparent,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
    ),
    tooltipTheme: TooltipThemeData(
      decoration: BoxDecoration(
        color: LumiColors.surfaceHigh,
        borderRadius: BorderRadius.circular(10),
      ),
      textStyle: const TextStyle(color: LumiColors.ink, fontSize: 12),
    ),
  );
}
