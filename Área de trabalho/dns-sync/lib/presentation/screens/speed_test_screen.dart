import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_lucide/flutter_lucide.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'dart:ui';
import 'dart:math' as math;
import '../../core/theme/app_theme.dart';
import '../providers/proxy_provider.dart';

class SpeedTestScreen extends ConsumerWidget {
  const SpeedTestScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final state = ref.watch(speedTestProvider);

    return Scaffold(
      body: Stack(
        children: [
          // Background Tech Estilo Grid
          _buildTechBackground(context),

          CustomScrollView(
            physics: const BouncingScrollPhysics(),
            slivers: [
              SliverToBoxAdapter(
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(AppEspacamento.lg, 60, AppEspacamento.lg, 20),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                "MÉTRICAS DE REDE",
                                style: TextStyle(
                                  fontSize: 10,
                                  fontWeight: FontWeight.w900,
                                  letterSpacing: 2,
                                  color: AppCores.primario,
                                ),
                              ).animate().fadeIn(),
                              const SizedBox(height: 4),
                              Text(
                                "Speed Test",
                                style: GoogleFonts.inter(
                                  fontSize: 32,
                                  fontWeight: FontWeight.w900,
                                  color: AppCores.texto(context),
                                ),
                              ).animate().fadeIn(delay: 100.ms).slideX(begin: -0.1),
                            ],
                          ),
                          _buildStatusBadge(state),
                        ],
                      ),
                    ],
                  ),
                ),
              ),

              SliverFillRemaining(
                hasScrollBody: false,
                child: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: AppEspacamento.lg),
                  child: Column(
                    children: [
                      const SizedBox(height: 20),
                      _buildAdvancedGauge(context, state),
                      const SizedBox(height: 40),
                      
                      // Painel de Dados
                      _buildMetricsPanel(context, state),
                      
                      const Spacer(),
                      
                      _buildCyberButton(ref, state),
                      const SizedBox(height: 120),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildTechBackground(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return Container(
      color: AppCores.fundo(context),
      child: Stack(
        children: [
          Opacity(
            opacity: isDark ? 0.05 : 0.02,
            child: CustomPaint(
              size: Size.infinite,
              painter: GridPainter(color: AppCores.texto(context)),
            ),
          ),
          Positioned(
            top: 200,
            left: -50,
            child: Container(
              width: 200,
              height: 200,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: AppCores.primario.withOpacity(0.05),
              ),
            ).animate(onPlay: (c) => c.repeat()).scale(duration: 5.seconds, begin: const Offset(1, 1), end: const Offset(1.5, 1.5), curve: Curves.easeInOut),
          ),
        ],
      ),
    );
  }

  Widget _buildStatusBadge(SpeedTestState state) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      decoration: BoxDecoration(
        color: state.isRunning ? AppCores.primario.withOpacity(0.1) : Colors.transparent,
        borderRadius: BorderRadius.circular(100),
        border: Border.all(color: state.isRunning ? AppCores.primario : Colors.grey.withOpacity(0.2)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          if (state.isRunning)
            const SizedBox(
              width: 10,
              height: 10,
              child: CircularProgressIndicator(strokeWidth: 2),
            ),
          if (state.isRunning) const SizedBox(width: 8),
          Text(
            state.isRunning ? state.stage.toUpperCase() : "PRONTO",
            style: TextStyle(
              fontSize: 10,
              fontWeight: FontWeight.bold,
              color: state.isRunning ? AppCores.primario : Colors.grey,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildAdvancedGauge(BuildContext context, SpeedTestState state) {
    double speed = state.downloadSpeed > 0 ? state.downloadSpeed : state.uploadSpeed;
    if (state.stage == 'ping') speed = state.latency.toDouble();

    return Container(
      width: 300,
      height: 300,
      child: Stack(
        alignment: Alignment.center,
        children: [
          // Outer Ring
          CustomPaint(
            size: const Size(280, 280),
            painter: GaugePainter(
              progress: state.progress,
              color: AppCores.primario,
              isDark: Theme.of(context).brightness == Brightness.dark,
            ),
          ),

          // Speed Value
          Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Row(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Text(
                    speed.toStringAsFixed(0),
                    style: GoogleFonts.inter(
                      fontSize: 84,
                      fontWeight: FontWeight.w900,
                      color: AppCores.texto(context),
                      letterSpacing: -4,
                    ),
                  ),
                  Padding(
                    padding: const EdgeInsets.only(bottom: 18, left: 4),
                    child: Text(
                      state.stage == 'ping' ? "ms" : "Mbps",
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                        color: AppCores.primario,
                      ),
                    ),
                  ),
                ],
              ).animate(target: speed).scale(duration: 300.ms, curve: Curves.easeOutBack),
              
              if (state.isRunning)
                 Text(
                   "TESTANDO ${state.stage.toUpperCase()}",
                   style: TextStyle(fontSize: 10, letterSpacing: 2, fontWeight: FontWeight.bold, color: AppCores.textoSuave(context)),
                 ).animate(onPlay: (c) => c.repeat()).shimmer(duration: 1.seconds),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildMetricsPanel(BuildContext context, SpeedTestState state) {
    return Container(
      padding: const EdgeInsets.all(AppEspacamento.xl),
      decoration: BoxDecoration(
        color: AppCores.superficie(context),
        borderRadius: BorderRadius.circular(AppBordas.raioGrande * 1.5),
        border: Border.all(color: AppCores.borda(context)),
        boxShadow: [
           BoxShadow(color: Colors.black.withOpacity(0.02), blurRadius: 20, offset: const Offset(0, 10)),
        ],
      ),
      child: Column(
        children: [
          Row(
            children: [
              _buildMetricItem(context, "DOWNLOAD", state.downloadSpeed.toStringAsFixed(1), "Mbps", LucideIcons.arrow_down, AppCores.primario),
              _buildVerticalDivider(context),
              _buildMetricItem(context, "UPLOAD", state.uploadSpeed.toStringAsFixed(1), "Mbps", LucideIcons.arrow_up, AppCores.secundario),
            ],
          ),
          const Padding(
            padding: EdgeInsets.symmetric(vertical: 20),
            child: Divider(),
          ),
          Row(
            children: [
              _buildMetricItem(context, "LATÊNCIA", "${state.latency}", "ms", LucideIcons.timer, Colors.amber),
              _buildVerticalDivider(context),
              _buildMetricItem(context, "JITTER", "${state.jitter}", "ms", LucideIcons.activity, AppCores.sucesso),
            ],
          ),
        ],
      ),
    ).animate().fadeIn(delay: 400.ms).slideY(begin: 0.2);
  }

  Widget _buildMetricItem(BuildContext context, String label, String value, String unit, IconData icon, Color color) {
    return Expanded(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, size: 14, color: color),
              const SizedBox(width: 8),
              Text(
                label,
                style: TextStyle(fontSize: 10, fontWeight: FontWeight.w900, color: AppCores.textoSuave(context), letterSpacing: 1),
              ),
            ],
          ),
          const SizedBox(height: 8),
          RichText(
            text: TextSpan(
              style: TextStyle(color: AppCores.texto(context), fontWeight: FontWeight.w900),
              children: [
                TextSpan(text: value, style: const TextStyle(fontSize: 22)),
                TextSpan(text: " $unit", style: TextStyle(fontSize: 11, fontWeight: FontWeight.w500, color: AppCores.textoSuave(context))),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildVerticalDivider(BuildContext context) {
    return Container(width: 1, height: 40, color: AppCores.borda(context), margin: const EdgeInsets.symmetric(horizontal: 10));
  }

  Widget _buildCyberButton(WidgetRef ref, SpeedTestState state) {
    return GestureDetector(
      onTap: state.isRunning ? null : () {
        HapticFeedback.heavyImpact();
        ref.read(speedTestProvider.notifier).runTest();
      },
      child: Container(
        width: double.infinity,
        height: 64,
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(20),
          gradient: state.isRunning 
              ? LinearGradient(colors: [Colors.grey.shade300, Colors.grey.shade400])
              : AppCores.gradientePrimario,
          boxShadow: [
            if (!state.isRunning)
              BoxShadow(
                color: AppCores.primario.withOpacity(0.3),
                blurRadius: 15,
                offset: const Offset(0, 8),
              ),
          ],
        ),
        child: Center(
          child: Text(
            state.isRunning ? "ANALISANDO REDE..." : "INICIAR TESTE",
            style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w900, letterSpacing: 2),
          ),
        ),
      ),
    ).animate(target: state.isRunning ? 0 : 1).scale().fadeIn();
  }
}

// ---- CUSTOM PAINTERS ----

class GridPainter extends CustomPainter {
  final Color color;
  GridPainter({required this.color});
  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()..color = color.withOpacity(0.1)..strokeWidth = 0.5;
    for (double i = 0; i < size.width; i += 30) {
      canvas.drawLine(Offset(i, 0), Offset(i, size.height), paint);
    }
    for (double i = 0; i < size.height; i += 30) {
      canvas.drawLine(Offset(0, i), Offset(size.width, i), paint);
    }
  }
  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}

class GaugePainter extends CustomPainter {
  final double progress;
  final Color color;
  final bool isDark;
  GaugePainter({required this.progress, required this.color, required this.isDark});

  @override
  void paint(Canvas canvas, Size size) {
    final center = Offset(size.width / 2, size.height / 2);
    final radius = size.width / 2;

    // Background track
    final bgPaint = Paint()
      ..color = isDark ? Colors.white.withOpacity(0.05) : Colors.black.withOpacity(0.03)
      ..style = PaintingStyle.stroke
      ..strokeWidth = 12
      ..strokeCap = StrokeCap.round;
    
    canvas.drawArc(Rect.fromCircle(center: center, radius: radius), math.pi * 0.8, math.pi * 1.4, false, bgPaint);

    // Active track
    final activePaint = Paint()
      ..shader = AppCores.gradientePrimario.createShader(Rect.fromCircle(center: center, radius: radius))
      ..style = PaintingStyle.stroke
      ..strokeWidth = 12
      ..strokeCap = StrokeCap.round;
    
    canvas.drawArc(Rect.fromCircle(center: center, radius: radius), math.pi * 0.8, math.pi * 1.4 * progress, false, activePaint);

    // Dots
    final dotPaint = Paint()..color = color;
    for (int i = 0; i < 20; i++) {
      double angle = math.pi * 0.8 + (math.pi * 1.4 * (i / 20));
      double dotRadius = radius - 20;
      Offset dotPos = Offset(center.dx + dotRadius * math.cos(angle), center.dy + dotRadius * math.sin(angle));
      canvas.drawCircle(dotPos, 1.5, Paint()..color = color.withOpacity(0.2));
    }
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => true;
}
