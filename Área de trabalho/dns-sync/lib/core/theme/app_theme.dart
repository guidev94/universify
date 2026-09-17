import 'package:flutter/material.dart';

class AppCores {
  // Cores da Identidade Visual (Azul Brand)
  static const Color primario = Color(0xFF0052FF); // Azul Moderno (Stripe/Coinbase style)
  static const Color primarioVariacao = Color(0xFF0041CC);
  static const Color secundario = Color(0xFF00D1FF); // Azul Ciano para acentos
  
  // Modo Claro
  static const Color fundoClaro = Color(0xFFFFFFFF);
  static const Color superficieClaro = Color(0xFFF0F5FF); // Azul bem clarinho
  static const Color textoClaro = Color(0xFF1A1D21);
  static const Color textoSuaveClaro = Color(0xFF64748B);
  static const Color bordaClaro = Color(0xFFE2E8F0);

  // Modo Escuro
  static const Color fundoEscuro = Color(0xFF0B1120); // Azul Escuro Profundo
  static const Color superficieEscuro = Color(0xFF1E293B);
  static const Color textoEscuro = Color(0xFFF1F5F9);
  static const Color textoSuaveEscuro = Color(0xFF94A3B8);
  static const Color bordaEscuro = Color(0xFF334155);

  static const Color sucesso = Color(0xFF10B981);
  static const Color alerta = Color(0xFFF59E0B);
  static const Color erro = Color(0xFFEF4444);

  // Getters dinâmicos baseados no brilho
  static Color fundo(BuildContext context) => 
      Theme.of(context).brightness == Brightness.light ? fundoClaro : fundoEscuro;
  static Color superficie(BuildContext context) => 
      Theme.of(context).brightness == Brightness.light ? superficieClaro : superficieEscuro;
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
  static const double raioPadrao = 12.0;
  static const double raioGrande = 24.0;
  static const double raioCirculo = 100.0;
}
