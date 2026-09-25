import 'package:flutter/material.dart';

class AppCores {
  // Brand Neon & Cobalt Palette
  static const Color primario = Color(0xFF0052FF); 
  static const Color primarioGlow = Color(0xFF387BFF);
  static const Color primarioEscuro = Color(0xFF0036B3);
  static const Color secundario = Color(0xFF00D1FF); 
  static const Color cianoNeon = Color(0xFF00F0FF); 
  static const Color acentoRoxo = Color(0xFF8B5CF6);
  static const Color esmeralda = Color(0xFF10B981);
  static const Color amberNeon = Color(0xFFF59E0B);
  static const Color coral = Color(0xFFFF3366);

  // Gradientes Futuristas
  static const LinearGradient gradientePrimario = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [Color(0xFF0052FF), Color(0xFF00F0FF)],
  );

  static const LinearGradient gradienteGlowAtivo = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [Color(0xFF0052FF), Color(0xFF7928CA), Color(0xFF00F0FF)],
  );

  static const LinearGradient gradienteEscuro = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [Color(0xFF060913), Color(0xFF0D1424)],
  );

  static const LinearGradient gradienteCard = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [Color(0xFF0F172A), Color(0xFF080D1A)],
  );
  
  // Modo Claro (Alabaster Luxury)
  static const Color fundoClaro = Color(0xFFF6F8FC);
  static const Color superficieClaro = Color(0xFFFFFFFF);
  static const Color molduraClaro = Color(0xFFEDF2F7);
  static const Color textoClaro = Color(0xFF090D16);
  static const Color textoSuaveClaro = Color(0xFF64748B);
  static const Color bordaClaro = Color(0xFFE2E8F0);

  // Modo Escuro (Obsidian OLED)
  static const Color fundoEscuro = Color(0xFF030712); // Deepest Black
  static const Color superficieEscuro = Color(0xFF090E1A); // Titanium Core
  static const Color molduraEscuro = Color(0xFF111827); // Double-Bezel Shell
  static const Color textoEscuro = Color(0xFFF9FAFB);
  static const Color textoSuaveEscuro = Color(0xFF9CA3AF);
  static const Color bordaEscuro = Color(0xFF1F2937);

  static const Color sucesso = Color(0xFF10B981);
  static const Color alerta = Color(0xFFF59E0B);
  static const Color erro = Color(0xFFEF4444);

  // Getters dinâmicos com fallback de tema
  static Color fundo(BuildContext context) => 
      Theme.of(context).brightness == Brightness.light ? fundoClaro : fundoEscuro;
  static Color superficie(BuildContext context) => 
      Theme.of(context).brightness == Brightness.light ? superficieClaro : superficieEscuro;
  static Color moldura(BuildContext context) => 
      Theme.of(context).brightness == Brightness.light ? molduraClaro : molduraEscuro;
  static Color texto(BuildContext context) => 
      Theme.of(context).brightness == Brightness.light ? textoClaro : textoEscuro;
  static Color textoSuave(BuildContext context) => 
      Theme.of(context).brightness == Brightness.light ? textoSuaveClaro : textoSuaveEscuro;
  static Color borda(BuildContext context) => 
      Theme.of(context).brightness == Brightness.light ? bordaClaro : bordaEscuro;
}

class AppEspacamento {
  static const double xs = 4.0;
  static const double sm = 8.0;
  static const double md = 16.0;
  static const double lg = 24.0;
  static const double xl = 32.0;
  static const double xxl = 48.0;
}

class AppBordas {
  static const double raioPequeno = 10.0;
  static const double raioPadrao = 16.0;
  static const double raioGrande = 24.0;
  static const double raioExtraGrande = 32.0;
  static const double raioCirculo = 100.0;
}
