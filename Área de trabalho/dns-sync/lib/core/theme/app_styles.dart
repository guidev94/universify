import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'app_theme.dart';

class AppEstilos {
  static ThemeData get temaClaro {
    return _buildTema(Brightness.light);
  }

  static ThemeData get temaEscuro {
    return _buildTema(Brightness.dark);
  }

  static ThemeData _buildTema(Brightness brilho) {
    bool isClaro = brilho == Brightness.light;
    Color corFundo = isClaro ? AppCores.fundoClaro : AppCores.fundoEscuro;
    Color corTexto = isClaro ? AppCores.textoClaro : AppCores.textoEscuro;
    Color corBorda = isClaro ? AppCores.bordaClaro : AppCores.bordaEscuro;

    final baseTextTheme = GoogleFonts.interTextTheme();

    return ThemeData(
      useMaterial3: true,
      brightness: brilho,
      colorScheme: ColorScheme.fromSeed(
        seedColor: AppCores.primario,
        brightness: brilho,
        primary: AppCores.primario,
        secondary: AppCores.cianoNeon,
        surface: isClaro ? AppCores.superficieClaro : AppCores.superficieEscuro,
      ),
      scaffoldBackgroundColor: corFundo,
      textTheme: baseTextTheme.copyWith(
        displayLarge: GoogleFonts.inter(
          fontSize: 32,
          fontWeight: FontWeight.w900,
          letterSpacing: -1.2,
          color: corTexto,
        ),
        titleLarge: GoogleFonts.inter(
          fontSize: 20,
          fontWeight: FontWeight.w800,
          letterSpacing: -0.5,
          color: corTexto,
        ),
        titleMedium: GoogleFonts.inter(
          fontSize: 16,
          fontWeight: FontWeight.w700,
          color: corTexto,
        ),
        bodyLarge: GoogleFonts.inter(
          fontSize: 14,
          fontWeight: FontWeight.w500,
          color: corTexto,
        ),
        bodyMedium: GoogleFonts.inter(
          fontSize: 13,
          color: isClaro ? AppCores.textoSuaveClaro : AppCores.textoSuaveEscuro,
        ),
      ),
      cardTheme: CardThemeData(
        color: isClaro ? AppCores.superficieClaro : AppCores.superficieEscuro,
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(AppBordas.raioPadrao),
          side: BorderSide(color: corBorda),
        ),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: AppCores.primario,
          foregroundColor: Colors.white,
          minimumSize: const Size(double.infinity, 54),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(AppBordas.raioPadrao),
          ),
          textStyle: GoogleFonts.inter(
            fontSize: 14,
            fontWeight: FontWeight.w800,
            letterSpacing: 0.5,
          ),
          elevation: 0,
        ),
      ),
      appBarTheme: AppBarTheme(
        backgroundColor: corFundo,
        elevation: 0,
        centerTitle: false,
        titleTextStyle: GoogleFonts.inter(
          color: corTexto,
          fontSize: 18,
          fontWeight: FontWeight.w800,
          letterSpacing: -0.5,
        ),
        iconTheme: IconThemeData(color: corTexto),
      ),
    );
  }
}
