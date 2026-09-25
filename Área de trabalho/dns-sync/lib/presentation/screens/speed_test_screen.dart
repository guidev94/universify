import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_lucide/flutter_lucide.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import 'dart:math' as math;
import '../../core/theme/app_theme.dart';
import '../../core/constants/canal_nativo.dart';
import '../providers/proxy_provider.dart';

class SpeedTestScreen extends ConsumerStatefulWidget {
  const SpeedTestScreen({super.key});

  @override
  ConsumerState<SpeedTestScreen> createState() => _SpeedTestScreenState();
}

class _SpeedTestScreenState extends ConsumerState<SpeedTestScreen> {
  int _selectedTab = 0; // 0 = Velocidade, 1 = Teste de Vídeo

  void _compartilharSpeedResult(dynamic metrics, dynamic ispInfo) {
    HapticFeedback.mediumImpact();
    final isp = ispInfo?.isp ?? "Provedor Local";
    final ip = ispInfo?.ip ?? "";
    final city = ispInfo?.city ?? "Brasil";
    final country = ispInfo?.country ?? "Brasil";
    final flag = ispInfo?.flag ?? "🌐";

    final texto = """
🚀 *Resultado do Speed Test • Sync DNS Pro*
$flag *País & Cidade:* $country, $city
📡 *Provedor:* $isp
🌐 *IP Público:* $ip
📥 *Download:* ${metrics.downloadSpeed.toStringAsFixed(1)} Mbps
📤 *Upload:* ${metrics.uploadSpeed.toStringAsFixed(1)} Mbps
⚡ *Ping:* ${metrics.ping} ms | *Jitter:* ${metrics.jitter} ms
🛡️ *Perda:* ${metrics.packetLoss}% | *Bufferbloat:* ${metrics.bufferbloatGrade}
🔒 *Proteção:* DNS Criptografado via Sync DNS
""";

    CanalNativo.compartilharResultado(texto, titulo: "Compartilhar Teste de Velocidade");
  }

  void _compartilharVideoResult(dynamic videoMetrics, dynamic ispInfo) {
    HapticFeedback.mediumImpact();
    final isp = ispInfo?.isp ?? "Provedor Local";
    final ip = ispInfo?.ip ?? "";
    final country = ispInfo?.country ?? "Brasil";
    final flag = ispInfo?.flag ?? "🌐";

    final texto = """
🎬 *Diagnóstico de Streaming de Vídeo • Sync DNS*
$flag *Localização:* $country
📡 *Provedor:* $isp (IP: $ip)
📺 *Qualidade Máxima:* ${videoMetrics.maxResolution}
⚡ *Bitrate de Vídeo:* ${videoMetrics.bitrateMbps.toStringAsFixed(1)} Mbps
⏱️ *Tempo de Resposta (TTFB):* ${videoMetrics.ttfbMs} ms
⏳ *Tempo de Buffer:* ${videoMetrics.loadTimeEstimate}
✅ *Pronto para 4K Ultra HD:* ${videoMetrics.isSupported4K ? 'Sim (Fluido)' : 'Não'}
""";

    CanalNativo.compartilharResultado(texto, titulo: "Compartilhar Diagnóstico de Vídeo");
  }

  @override
  Widget build(BuildContext context) {
    final metrics = ref.watch(speedTestProvider);
    final videoMetrics = ref.watch(videoTestProvider);
    final ispAsync = ref.watch(ispInfoProvider);

    final isSpeedRunning = metrics.stage == 'ping' || metrics.stage == 'download' || metrics.stage == 'upload';
    final isVideoRunning = videoMetrics.stage == 'initiating' || videoMetrics.stage == 'buffering' || videoMetrics.stage == 'streaming';

    return Scaffold(
      body: Stack(
        children: [
          _buildTechBackground(context),

          CustomScrollView(
            physics: const BouncingScrollPhysics(),
            slivers: [
              // Header Principal
              SliverToBoxAdapter(
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(AppEspacamento.lg, 52, AppEspacamento.lg, 10),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text(
                            "DIAGNÓSTICO DE REDE & MÍDIA",
                            style: TextStyle(
                              fontSize: 10,
                              fontWeight: FontWeight.w900,
                              letterSpacing: 2,
                              color: AppCores.primario,
                            ),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            "Speed & Video Test",
                            style: GoogleFonts.inter(
                              fontSize: 26,
                              fontWeight: FontWeight.w900,
                              letterSpacing: -0.5,
                              color: AppCores.texto(context),
                            ),
                          ),
                        ],
                      ),
                      IconButton(
                        icon: const Icon(LucideIcons.share_2, color: AppCores.primario),
                        tooltip: "Compartilhar Resultado",
                        onPressed: () {
                          if (_selectedTab == 0) {
                            _compartilharSpeedResult(metrics, ispAsync.value);
                          } else {
                            _compartilharVideoResult(videoMetrics, ispAsync.value);
                          }
                        },
                      ),
                    ],
                  ),
                ),
              ),

              // Card Inteligente de Detecção de ISP e IP
              SliverToBoxAdapter(
                child: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: AppEspacamento.lg),
                  child: _buildIspDetectionBanner(ispAsync),
                ),
              ),

              // Seletor Segmentado de Abas (Internet vs Vídeo)
              SliverToBoxAdapter(
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(AppEspacamento.lg, 12, AppEspacamento.lg, 12),
                  child: _buildSegmentedTabs(),
                ),
              ),

              // Conteúdo da Aba Ativa
              SliverToBoxAdapter(
                child: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: AppEspacamento.lg),
                  child: _selectedTab == 0
                      ? _buildSpeedTabContent(metrics, isSpeedRunning, ispAsync.value)
                      : _buildVideoTabContent(videoMetrics, isVideoRunning, ispAsync.value),
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
      child: Opacity(
        opacity: isDark ? 0.04 : 0.02,
        child: CustomPaint(
          size: Size.infinite,
          painter: GridPainter(color: AppCores.texto(context)),
        ),
      ),
    );
  }

  Widget _buildIspDetectionBanner(AsyncValue<dynamic> ispAsync) {
    return ispAsync.when(
      data: (isp) {
        return Container(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
          decoration: BoxDecoration(
            color: AppCores.superficie(context),
            borderRadius: BorderRadius.circular(AppBordas.raioGrande),
            border: Border.all(color: AppCores.borda(context)),
          ),
          child: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: AppCores.fundo(context),
                  shape: BoxShape.circle,
                  border: Border.all(color: AppCores.borda(context)),
                ),
                child: Text(isp.flag, style: const TextStyle(fontSize: 22)),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Flexible(
                          child: Text(
                            "${isp.isp} • ${isp.country}",
                            style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 13),
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 2),
                    Text(
                      "IP Público: ${isp.ip} • ${isp.city}",
                      style: TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: AppCores.texto(context).withValues(alpha: 0.85)),
                    ),
                    Text(
                      "Servidor: ${isp.server} (${isp.asn})",
                      style: TextStyle(fontSize: 10, color: AppCores.textoSuave(context)),
                    ),
                  ],
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: AppCores.primario.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(6),
                ),
                child: const Text(
                  "CONECTADO",
                  style: TextStyle(fontSize: 9, fontWeight: FontWeight.w900, color: AppCores.primario),
                ),
              ),
            ],
          ),
        );
      },
      loading: () => Container(
        height: 54,
        decoration: BoxDecoration(
          color: AppCores.superficie(context),
          borderRadius: BorderRadius.circular(AppBordas.raioGrande),
          border: Border.all(color: AppCores.borda(context)),
        ),
        child: const Center(child: SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2))),
      ),
      error: (e, s) => const SizedBox.shrink(),
    );
  }

  Widget _buildSegmentedTabs() {
    return Container(
      padding: const EdgeInsets.all(4),
      decoration: BoxDecoration(
        color: AppCores.superficie(context),
        borderRadius: BorderRadius.circular(AppBordas.raioGrande),
        border: Border.all(color: AppCores.borda(context)),
      ),
      child: Row(
        children: [
          Expanded(
            child: _buildTabButton(
              index: 0,
              title: "Velocidade de Internet",
              icon: LucideIcons.gauge,
            ),
          ),
          Expanded(
            child: _buildTabButton(
              index: 1,
              title: "Teste de Streaming",
              icon: LucideIcons.clapperboard,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildTabButton({required int index, required String title, required IconData icon}) {
    final isSelected = _selectedTab == index;

    return GestureDetector(
      onTap: () {
        HapticFeedback.selectionClick();
        setState(() => _selectedTab = index);
      },
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        padding: const EdgeInsets.symmetric(vertical: 10),
        decoration: BoxDecoration(
          color: isSelected ? AppCores.primario : Colors.transparent,
          borderRadius: BorderRadius.circular(AppBordas.raioGrande - 2),
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, size: 15, color: isSelected ? Colors.white : AppCores.textoSuave(context)),
            const SizedBox(width: 6),
            Text(
              title,
              style: TextStyle(
                fontSize: 12,
                fontWeight: isSelected ? FontWeight.w900 : FontWeight.w600,
                color: isSelected ? Colors.white : AppCores.textoSuave(context),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildSpeedTabContent(dynamic metrics, bool isRunning, dynamic ispInfo) {
    return Column(
      key: const ValueKey("SpeedTab"),
      children: [
        _buildAdvancedGauge(context, metrics),
        const SizedBox(height: 12),

        if (metrics.speedHistory.isNotEmpty)
          _buildLiveChartCard(context, metrics.speedHistory),
        
        const SizedBox(height: 14),

        _buildDetailedMetricsGrid(context, metrics, ispInfo),

        const SizedBox(height: 20),

        _buildActionButton(
          label: isRunning ? "MEDINDO VAZÃO REAL..." : "INICIAR SPEED TEST REAL",
          isRunning: isRunning,
          onTap: () => ref.read(speedTestProvider.notifier).runTest(),
        ),

        if (metrics.stage == 'complete') ...[
          const SizedBox(height: 10),
          TextButton.icon(
            onPressed: () => _compartilharSpeedResult(metrics, ispInfo),
            icon: const Icon(LucideIcons.share_2, size: 16),
            label: const Text("Compartilhar Este Resultado"),
          ),
        ],
        const SizedBox(height: 110),
      ],
    );
  }

  Widget _buildVideoTabContent(dynamic videoMetrics, bool isRunning, dynamic ispInfo) {
    return Column(
      key: const ValueKey("VideoTab"),
      children: [
        const SizedBox(height: 4),
        _buildVideoResolutionHub(videoMetrics, isRunning),
        const SizedBox(height: 14),
        _buildVideoMetricsGrid(videoMetrics),
        const SizedBox(height: 20),

        _buildActionButton(
          label: isRunning ? "AVALIANDO REPRODUÇÃO..." : "INICIAR TESTE DE VÍDEO (4K / 1080p)",
          isRunning: isRunning,
          onTap: () => ref.read(videoTestProvider.notifier).runVideoTest(),
        ),

        if (videoMetrics.stage == 'complete') ...[
          const SizedBox(height: 10),
          TextButton.icon(
            onPressed: () => _compartilharVideoResult(videoMetrics, ispInfo),
            icon: const Icon(LucideIcons.share_2, size: 16),
            label: const Text("Compartilhar Diagnóstico de Vídeo"),
          ),
        ],
        const SizedBox(height: 110),
      ],
    );
  }

  Widget _buildVideoResolutionHub(dynamic videoMetrics, bool isRunning) {
    final bool isIdle = videoMetrics.stage == 'idle';
    final bool isComplete = videoMetrics.stage == 'complete';

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(AppEspacamento.lg),
      decoration: BoxDecoration(
        color: AppCores.superficie(context),
        borderRadius: BorderRadius.circular(AppBordas.raioGrande * 1.5),
        border: Border.all(color: AppCores.borda(context)),
        boxShadow: [
          BoxShadow(color: Colors.black.withValues(alpha: 0.03), blurRadius: 20, offset: const Offset(0, 8)),
        ],
      ),
      child: Column(
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text(
                "DIAGNÓSTICO DE STREAMING",
                style: TextStyle(fontSize: 10, fontWeight: FontWeight.w900, letterSpacing: 1.5, color: AppCores.primario),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: isComplete 
                      ? AppCores.sucesso.withValues(alpha: 0.15) 
                      : (isRunning ? AppCores.primario.withValues(alpha: 0.15) : Colors.grey.withValues(alpha: 0.15)),
                  borderRadius: BorderRadius.circular(6),
                ),
                child: Row(
                  children: [
                    if (isRunning) ...[
                      const SizedBox(width: 10, height: 10, child: CircularProgressIndicator(strokeWidth: 2)),
                      const SizedBox(width: 6),
                    ],
                    Text(
                      isComplete ? "VERIFICADO" : (isRunning ? "TESTANDO" : "PRONTO"),
                      style: TextStyle(
                        fontSize: 10, 
                        fontWeight: FontWeight.bold, 
                        color: isComplete ? AppCores.sucesso : (isRunning ? AppCores.primario : Colors.grey),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),

          if (isIdle) ...[
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: AppCores.fundo(context),
                borderRadius: BorderRadius.circular(AppBordas.raioGrande),
              ),
              child: Column(
                children: [
                  const Icon(Icons.play_circle_outline_rounded, size: 40, color: AppCores.primario),
                  const SizedBox(height: 8),
                  const Text(
                    "Avaliação de Resolução e Buffer",
                    style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    "Verifica a estabilidade para YouTube, Netflix, Prime Video e Twitch em 4K e 1080p.",
                    textAlign: TextAlign.center,
                    style: TextStyle(fontSize: 11, color: AppCores.textoSuave(context)),
                  ),
                ],
              ),
            ),
          ] else ...[
            Text(
              videoMetrics.maxResolution,
              style: GoogleFonts.inter(
                fontSize: 24,
                fontWeight: FontWeight.w900,
                color: AppCores.texto(context),
                letterSpacing: -0.5,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              "Estimativa de buffer: ${videoMetrics.loadTimeEstimate}",
              style: TextStyle(fontSize: 12, color: AppCores.textoSuave(context), fontWeight: FontWeight.w600),
            ),
            if (isRunning) ...[
              const SizedBox(height: 12),
              ClipRRect(
                borderRadius: BorderRadius.circular(10),
                child: LinearProgressIndicator(
                  value: videoMetrics.progress,
                  backgroundColor: AppCores.fundo(context),
                  valueColor: const AlwaysStoppedAnimation<Color>(AppCores.primario),
                  minHeight: 6,
                ),
              ),
            ],
          ],

          const SizedBox(height: 18),

          // Tiers de Resoluções
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceAround,
            children: [
              _buildResolutionBadge("4K (2160p)", isComplete ? videoMetrics.isSupported4K : null, LucideIcons.tv),
              _buildResolutionBadge("1080p FHD", isComplete ? videoMetrics.isSupported1080p : null, LucideIcons.monitor),
              _buildResolutionBadge("720p HD", isComplete ? videoMetrics.isSupported720p : null, LucideIcons.smartphone),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildResolutionBadge(String label, bool? isSupported, IconData icon) {
    Color badgeColor = Colors.grey;
    String statusText = "Aguardando";

    if (isSupported != null) {
      badgeColor = isSupported ? AppCores.sucesso : Colors.red;
      statusText = isSupported ? "Fluido" : "Instável";
    }

    return Column(
      children: [
        Container(
          padding: const EdgeInsets.all(10),
          decoration: BoxDecoration(
            color: badgeColor.withValues(alpha: 0.12),
            shape: BoxShape.circle,
          ),
          child: Icon(icon, color: badgeColor, size: 20),
        ),
        const SizedBox(height: 6),
        Text(
          label,
          style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: badgeColor),
        ),
        Text(
          statusText,
          style: TextStyle(fontSize: 9, color: badgeColor, fontWeight: FontWeight.bold),
        ),
      ],
    );
  }

  Widget _buildVideoMetricsGrid(dynamic videoMetrics) {
    return Container(
      padding: const EdgeInsets.all(AppEspacamento.lg),
      decoration: BoxDecoration(
        color: AppCores.superficie(context),
        borderRadius: BorderRadius.circular(AppBordas.raioGrande),
        border: Border.all(color: AppCores.borda(context)),
      ),
      child: Row(
        children: [
          _buildMetricItem(context, "BITRATE DE VÍDEO", videoMetrics.bitrateMbps.toStringAsFixed(1), "Mbps", LucideIcons.clapperboard, AppCores.primario),
          _buildVerticalDivider(context),
          _buildMetricItem(context, "RESPOSTA (TTFB)", "${videoMetrics.ttfbMs}", "ms", LucideIcons.timer, Colors.amber),
          _buildVerticalDivider(context),
          _buildMetricItem(context, "BUFFER RATIO", "${videoMetrics.bufferTimePercent.toStringAsFixed(1)}s", "", LucideIcons.activity, AppCores.sucesso),
        ],
      ),
    );
  }

  Widget _buildAdvancedGauge(BuildContext context, dynamic metrics) {
    final double displaySpeed = metrics.stage == 'upload' 
        ? metrics.uploadSpeed 
        : (metrics.stage == 'ping' ? metrics.ping.toDouble() : metrics.downloadSpeed);
    final String unit = metrics.stage == 'ping' ? "ms" : "Mbps";

    return SizedBox(
      width: 250,
      height: 230,
      child: Stack(
        alignment: Alignment.center,
        children: [
          CustomPaint(
            size: const Size(230, 230),
            painter: GaugePainter(
              progress: metrics.progress,
              color: AppCores.primario,
              isDark: Theme.of(context).brightness == Brightness.dark,
            ),
          ),

          Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Row(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Text(
                    displaySpeed.toStringAsFixed(displaySpeed >= 100 ? 0 : 1),
                    style: GoogleFonts.inter(
                      fontSize: 60,
                      fontWeight: FontWeight.w900,
                      color: AppCores.texto(context),
                      letterSpacing: -3,
                    ),
                  ),
                  Padding(
                    padding: const EdgeInsets.only(bottom: 12, left: 4),
                    child: Text(
                      unit,
                      style: const TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.bold,
                        color: AppCores.primario,
                      ),
                    ),
                  ),
                ],
              ),
              Text(
                metrics.stage == 'complete' 
                    ? "TESTE FINALIZADO" 
                    : (metrics.stage == 'idle' ? "TOQUE EM INICIAR" : "MEDIÇÃO ATIVA"),
                style: TextStyle(
                  fontSize: 10, 
                  letterSpacing: 1.5, 
                  fontWeight: FontWeight.w800, 
                  color: AppCores.textoSuave(context),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildLiveChartCard(BuildContext context, List<double> history) {
    return Container(
      width: double.infinity,
      height: 86,
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: AppCores.superficie(context),
        borderRadius: BorderRadius.circular(AppBordas.raioGrande),
        border: Border.all(color: AppCores.borda(context)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                "FLUXO DE TRANSFERÊNCIA REAL",
                style: TextStyle(fontSize: 9, fontWeight: FontWeight.w900, color: AppCores.textoSuave(context), letterSpacing: 1),
              ),
              Text(
                "${history.isNotEmpty ? history.last.toStringAsFixed(1) : 0} Mbps",
                style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: AppCores.primario),
              ),
            ],
          ),
          const SizedBox(height: 6),
          Expanded(
            child: CustomPaint(
              size: Size.infinite,
              painter: SpeedChartPainter(
                data: history,
                color: AppCores.primario,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildDetailedMetricsGrid(BuildContext context, dynamic metrics, dynamic ispInfo) {
    return Container(
      padding: const EdgeInsets.all(AppEspacamento.lg),
      decoration: BoxDecoration(
        color: AppCores.superficie(context),
        borderRadius: BorderRadius.circular(AppBordas.raioGrande),
        border: Border.all(color: AppCores.borda(context)),
        boxShadow: [
          BoxShadow(color: Colors.black.withValues(alpha: 0.03), blurRadius: 20, offset: const Offset(0, 8)),
        ],
      ),
      child: Column(
        children: [
          Row(
            children: [
              _buildMetricItem(context, "DOWNLOAD", metrics.downloadSpeed.toStringAsFixed(1), "Mbps", LucideIcons.arrow_down, AppCores.primario),
              _buildVerticalDivider(context),
              _buildMetricItem(context, "UPLOAD", metrics.uploadSpeed.toStringAsFixed(1), "Mbps", LucideIcons.arrow_up, AppCores.secundario),
            ],
          ),
          const Padding(
            padding: EdgeInsets.symmetric(vertical: 12),
            child: Divider(height: 1),
          ),
          Row(
            children: [
              _buildMetricItem(context, "LATÊNCIA", "${metrics.ping}", "ms", LucideIcons.timer, Colors.amber),
              _buildVerticalDivider(context),
              _buildMetricItem(context, "JITTER", "${metrics.jitter}", "ms", LucideIcons.activity, AppCores.sucesso),
              _buildVerticalDivider(context),
              _buildMetricItem(context, "PERDA", "${metrics.packetLoss}%", "", LucideIcons.shield_alert, Colors.purple),
            ],
          ),
          const Padding(
            padding: EdgeInsets.symmetric(vertical: 12),
            child: Divider(height: 1),
          ),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text("CLASSIFICAÇÃO BUFFERBLOAT", style: TextStyle(fontSize: 9, fontWeight: FontWeight.w900, color: AppCores.textoSuave(context), letterSpacing: 1)),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 3),
                decoration: BoxDecoration(
                  color: AppCores.sucesso.withValues(alpha: 0.15),
                  borderRadius: BorderRadius.circular(6),
                ),
                child: Text(
                  "GRAU ${metrics.bufferbloatGrade}",
                  style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w900, color: AppCores.sucesso),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildMetricItem(BuildContext context, String label, String value, String unit, IconData icon, Color color) {
    return Expanded(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, size: 13, color: color),
              const SizedBox(width: 6),
              Text(
                label,
                style: TextStyle(fontSize: 9, fontWeight: FontWeight.w900, color: AppCores.textoSuave(context), letterSpacing: 1),
              ),
            ],
          ),
          const SizedBox(height: 4),
          RichText(
            text: TextSpan(
              style: TextStyle(color: AppCores.texto(context), fontWeight: FontWeight.w900),
              children: [
                TextSpan(text: value, style: const TextStyle(fontSize: 18)),
                if (unit.isNotEmpty)
                  TextSpan(text: " $unit", style: TextStyle(fontSize: 10, fontWeight: FontWeight.w500, color: AppCores.textoSuave(context))),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildVerticalDivider(BuildContext context) {
    return Container(width: 1, height: 32, color: AppCores.borda(context), margin: const EdgeInsets.symmetric(horizontal: 10));
  }

  Widget _buildActionButton({required String label, required bool isRunning, required VoidCallback onTap}) {
    return GestureDetector(
      onTap: isRunning ? null : () {
        HapticFeedback.heavyImpact();
        onTap();
      },
      child: Container(
        width: double.infinity,
        height: 58,
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(20),
          gradient: isRunning 
              ? LinearGradient(colors: [Colors.grey.shade400, Colors.grey.shade500])
              : AppCores.gradientePrimario,
          boxShadow: [
            if (!isRunning)
              BoxShadow(
                color: AppCores.primario.withValues(alpha: 0.35),
                blurRadius: 15,
                offset: const Offset(0, 8),
              ),
          ],
        ),
        child: Center(
          child: Text(
            label,
            style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w900, letterSpacing: 1.2, fontSize: 13),
          ),
        ),
      ),
    );
  }
}

// ---- CUSTOM PAINTERS ----

class SpeedChartPainter extends CustomPainter {
  final List<double> data;
  final Color color;

  SpeedChartPainter({required this.data, required this.color});

  @override
  void paint(Canvas canvas, Size size) {
    if (data.length < 2) return;

    final maxVal = data.reduce(math.max).clamp(10.0, 500.0);
    final path = Path();
    final fillPath = Path();

    final stepX = size.width / (data.length - 1);
    path.moveTo(0, size.height - (data[0] / maxVal * size.height));
    fillPath.moveTo(0, size.height);
    fillPath.lineTo(0, size.height - (data[0] / maxVal * size.height));

    for (int i = 1; i < data.length; i++) {
      final x = i * stepX;
      final y = size.height - (data[i] / maxVal * size.height);
      path.lineTo(x, y);
      fillPath.lineTo(x, y);
    }

    fillPath.lineTo(size.width, size.height);
    fillPath.close();

    final fillPaint = Paint()
      ..shader = LinearGradient(
        begin: Alignment.topCenter,
        end: Alignment.bottomCenter,
        colors: [color.withValues(alpha: 0.3), color.withValues(alpha: 0.0)],
      ).createShader(Rect.fromLTWH(0, 0, size.width, size.height));

    canvas.drawPath(fillPath, fillPaint);

    final linePaint = Paint()
      ..color = color
      ..strokeWidth = 2.5
      ..style = PaintingStyle.stroke
      ..strokeCap = StrokeCap.round;

    canvas.drawPath(path, linePaint);
  }

  @override
  bool shouldRepaint(covariant SpeedChartPainter oldDelegate) => true;
}

class GridPainter extends CustomPainter {
  final Color color;
  GridPainter({required this.color});
  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()..color = color.withValues(alpha: 0.08)..strokeWidth = 0.5;
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

    final bgPaint = Paint()
      ..color = isDark ? Colors.white.withValues(alpha: 0.05) : Colors.black.withValues(alpha: 0.04)
      ..style = PaintingStyle.stroke
      ..strokeWidth = 10
      ..strokeCap = StrokeCap.round;
    
    canvas.drawArc(Rect.fromCircle(center: center, radius: radius), math.pi * 0.8, math.pi * 1.4, false, bgPaint);

    final activePaint = Paint()
      ..shader = AppCores.gradientePrimario.createShader(Rect.fromCircle(center: center, radius: radius))
      ..style = PaintingStyle.stroke
      ..strokeWidth = 10
      ..strokeCap = StrokeCap.round;
    
    canvas.drawArc(Rect.fromCircle(center: center, radius: radius), math.pi * 0.8, math.pi * 1.4 * progress, false, activePaint);

    final dotPaint = Paint()..color = color.withValues(alpha: 0.2);
    for (int i = 0; i < 20; i++) {
      double angle = math.pi * 0.8 + (math.pi * 1.4 * (i / 20));
      double dotRadius = radius - 18;
      Offset dotPos = Offset(center.dx + dotRadius * math.cos(angle), center.dy + dotRadius * math.sin(angle));
      canvas.drawCircle(dotPos, 1.5, dotPaint);
    }
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => true;
}
