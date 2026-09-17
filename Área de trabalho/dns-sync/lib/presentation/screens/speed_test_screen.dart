import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_lucide/flutter_lucide.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:flutter_animate/flutter_animate.dart';
import '../../core/theme/app_theme.dart';
import '../providers/proxy_provider.dart';

class SpeedTestScreen extends ConsumerWidget {
  const SpeedTestScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final state = ref.watch(speedTestProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text("Teste de Velocidade"),
      ),
      body: Center(
        child: Padding(
          padding: const EdgeInsets.all(AppEspacamento.xl),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              _buildSpeedGauge(context, state),
              const SizedBox(height: 60),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceAround,
                children: [
                  _buildStatItem("Ping", "${state.latency} ms", LucideIcons.activity),
                  _buildStatItem("Download", "${state.downloadSpeed.toStringAsFixed(1)} Mbps", LucideIcons.download),
                ],
              ),
              const SizedBox(height: 80),
              ElevatedButton(
                onPressed: state.isRunning ? null : () => ref.read(speedTestProvider.notifier).runTest(),
                style: ElevatedButton.styleFrom(
                  padding: const EdgeInsets.symmetric(horizontal: 40, vertical: 18),
                  backgroundColor: AppCores.primario,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(100)),
                ),
                child: Text(
                  state.isRunning ? "TESTANDO..." : "INICIAR TESTE",
                  style: const TextStyle(letterSpacing: 1.5, fontWeight: FontWeight.w900),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildSpeedGauge(BuildContext context, SpeedTestState state) {
    return Container(
      width: 250,
      height: 250,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        border: Border.all(color: AppCores.primario.withOpacity(0.1), width: 10),
      ),
      child: Stack(
        alignment: Alignment.center,
        children: [
          if (state.isRunning)
            const SizedBox(
              width: 230,
              height: 230,
              child: CircularProgressIndicator(
                strokeWidth: 4,
                valueColor: AlwaysStoppedAnimation<Color>(AppCores.primario),
              ),
            ).animate(onPlay: (c) => c.repeat()).rotate(duration: 2.seconds),
          Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                state.downloadSpeed.toStringAsFixed(1),
                style: GoogleFonts.inter(
                  fontSize: 64,
                  fontWeight: FontWeight.w900,
                  color: AppCores.texto(context),
                ),
              ),
              const Text(
                "Mbps",
                style: TextStyle(fontWeight: FontWeight.bold, letterSpacing: 2, color: AppCores.primario),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildStatItem(String label, String value, IconData icon) {
    return Column(
      children: [
        Icon(icon, color: AppCores.primario, size: 24),
        const SizedBox(height: 8),
        Text(label, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.grey)),
        Text(value, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w900)),
      ],
    );
  }
}
