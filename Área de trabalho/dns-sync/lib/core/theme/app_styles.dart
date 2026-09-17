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

    return ThemeData(
      useMaterial3: true,
      brightness: brilho,
      colorScheme: ColorScheme.fromSeed(
        seedColor: AppCores.primario,
        brightness: brilho,
        primary: AppCores.primario,
        secondary: AppCores.secundario,
        surface: isClaro ? AppCores.superficieClaro : AppCores.superficieEscuro,
      ),
      scaffoldBackgroundColor: corFundo,
      textTheme: GoogleFonts.interTextTheme().copyWith(
        displayLarge: GoogleFonts.inter(
          fontSize: 32,
          fontWeight: FontWeight.bold,
          color: corTexto,
        ),
        titleLarge: GoogleFonts.inter(
          fontSize: 22,
          fontWeight: FontWeight.w600,
          color: corTexto,
        ),
        bodyLarge: GoogleFonts.inter(
          fontSize: 16,
          color: corTexto,
        ),
        bodyMedium: GoogleFonts.inter(
          fontSize: 14,
          color: isClaro ? AppCores.textoSuaveClaro : AppCores.textoSuaveEscuro,
        ),
      ),
      cardTheme: CardThemeData(
        color: isClaro ? AppCores.fundoClaro : AppCores.superficieEscuro,
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
          minimumSize: const Size(double.infinity, 56),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(AppBordas.raioPadrao),
          ),
          textStyle: GoogleFonts.inter(
            fontSize: 16,
            fontWeight: FontWeight.w600,
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
          fontWeight: FontWeight.w600,
        ),
        iconTheme: IconThemeData(color: corTexto),
      ),
    );
  }
}
