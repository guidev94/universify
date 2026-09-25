import 'package:flutter/material.dart';

class AppCores {
  // Cores da Identidade Visual (Electric Cobalt & Cyan Aurora)
  static const Color primario = Color(0xFF0052FF); 
  static const Color primarioVariacao = Color(0xFF0038B8);
  static const Color secundario = Color(0xFF00F0FF); 
  static const Color acentoRoxo = Color(0xFF8B5CF6);
  
  // Gradientes Titanium & Neon
  static const LinearGradient gradientePrimario = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [Color(0xFF0052FF), Color(0xFF00D1FF)],
  );

  static const LinearGradient gradienteEscuro = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [Color(0xFF070B14), Color(0xFF0F172A)],
  );

  static const LinearGradient gradienteHero = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [Color(0xFF0F1B38), Color(0xFF0A1022)],
  );
  
  // Modo Claro (Alabaster Luxury)
  static const Color fundoClaro = Color(0xFFF8FAFC);
  static const Color superficieClaro = Color(0xFFFFFFFF);
  static const Color molduraClaro = Color(0xFFF1F5F9);
  static const Color textoClaro = Color(0xFF090D16);
  static const Color textoSuaveClaro = Color(0xFF64748B);
  static const Color bordaClaro = Color(0xFFE2E8F0);

  // Modo Escuro (Obsidian OLED)
  static const Color fundoEscuro = Color(0xFF040711);
  static const Color superficieEscuro = Color(0xFF0A0F1D);
  static const Color molduraEscuro = Color(0xFF10172A);
  static const Color textoEscuro = Color(0xFFF8FAFC);
  static const Color textoSuaveEscuro = Color(0xFF94A3B8);
  static const Color bordaEscuro = Color(0xFF1E293B);

  static const Color sucesso = Color(0xFF10B981);
  static const Color alerta = Color(0xFFF59E0B);
  static const Color erro = Color(0xFFEF4444);

  // Getters dinâmicos
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
  static const double raioGrande = 26.0;
  static const double raioExtraGrande = 34.0;
  static const double raioCirculo = 100.0;
}
