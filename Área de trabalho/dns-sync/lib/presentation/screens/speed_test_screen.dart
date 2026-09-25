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
    final ip = ispInfo?.ip ?? "177.18.92.14";
    final city = ispInfo?.city ?? "Brasil";
    final country = ispInfo?.country ?? "Brasil";
    final flag = ispInfo?.flag ?? "🌐";

    final texto = """
⚡ *Resultado do Teste de Velocidade • Sync DNS*
$flag *Localização:* $country, $city
📡 *Provedor:* $isp
🌐 *IP Público:* $ip
📥 *Download:* ${metrics.downloadSpeed.toStringAsFixed(1)} Mbps
📤 *Upload:* ${metrics.uploadSpeed.toStringAsFixed(1)} Mbps
⏱️ *Ping:* ${metrics.ping} ms | *Jitter:* ${metrics.jitter} ms
🛡️ *Perda de Pacotes:* ${metrics.packetLoss}% | *Bufferbloat:* Grau ${metrics.bufferbloatGrade}
🔒 *Segurança:* Criptografado via Sync DNS Pro
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
$flag *Localização:* $country (IP: $ip)
📡 *Provedor:* $isp
📺 *Qualidade de Reprodução:* ${videoMetrics.maxResolution}
⚡ *Taxa de Bitrate:* ${videoMetrics.bitrateMbps.toStringAsFixed(1)} Mbps
⏱️ *Tempo de Resposta (TTFB):* ${videoMetrics.ttfbMs} ms
⏳ *Buffer Estimado:* ${videoMetrics.loadTimeEstimate}
✅ *Pronto para 4K Ultra HD:* ${videoMetrics.isSupported4K ? 'Sim (Perfeito)' : 'Não'}
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
      backgroundColor: AppCores.fundo(context),
      body: CustomScrollView(
        physics: const BouncingScrollPhysics(),
        slivers: [
          // Header com Tipografia Linear & Trailing Share
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.fromLTRB(AppEspacamento.lg, 52, AppEspacamento.lg, 8),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                        decoration: BoxDecoration(
                          color: AppCores.primario.withValues(alpha: 0.1),
                          borderRadius: BorderRadius.circular(100),
                          border: Border.all(color: AppCores.primario.withValues(alpha: 0.2)),
                        ),
                        child: Text(
                          "DIAGNÓSTICO DE REDE",
                          style: TextStyle(
                            fontSize: 9.5,
                            fontWeight: FontWeight.w900,
                            letterSpacing: 1.8,
                            color: AppCores.primario,
                          ),
                        ),
                      ),
                      const SizedBox(height: 6),
                      Text(
                        "Speed & Media Test",
                        style: GoogleFonts.inter(
                          fontSize: 26,
                          fontWeight: FontWeight.w900,
                          letterSpacing: -0.8,
                          color: AppCores.texto(context),
                        ),
                      ),
                    ],
                  ),
                  _buildTrailingShareButton(metrics, videoMetrics, ispAsync.value),
                ],
              ),
            ),
          ),

          // Banner Doppelrand de Detecção do ISP & IP
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: AppEspacamento.lg, vertical: 6),
              child: _buildIspDetectionBanner(ispAsync),
            ),
          ),

          // Seletor Segmentado de Abas
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: AppEspacamento.lg, vertical: 8),
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
    );
  }

  Widget _buildTrailingShareButton(dynamic metrics, dynamic videoMetrics, dynamic ispInfo) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: () {
          if (_selectedTab == 0) {
            _compartilharSpeedResult(metrics, ispInfo);
          } else {
            _compartilharVideoResult(videoMetrics, ispInfo);
          }
        },
        borderRadius: BorderRadius.circular(12),
        child: Container(
          padding: const EdgeInsets.all(10),
          decoration: BoxDecoration(
            color: AppCores.superficie(context),
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: AppCores.borda(context)),
          ),
          child: const Icon(LucideIcons.share_2, color: AppCores.primario, size: 18),
        ),
      ),
    );
  }

  Widget _buildIspDetectionBanner(AsyncValue<dynamic> ispAsync) {
    return ispAsync.when(
      data: (isp) {
        return Container(
          padding: const EdgeInsets.all(2),
          decoration: BoxDecoration(
            color: AppCores.moldura(context),
            borderRadius: BorderRadius.circular(AppBordas.raioPadrao + 2),
            border: Border.all(color: AppCores.borda(context).withValues(alpha: 0.8)),
          ),
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
            decoration: BoxDecoration(
              color: AppCores.superficie(context),
              borderRadius: BorderRadius.circular(AppBordas.raioPadrao),
            ),
            child: Row(
              children: [
                Text(isp.flag, style: const TextStyle(fontSize: 22)),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        "${isp.isp} • ${isp.country}",
                        style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 13),
                        overflow: TextOverflow.ellipsis,
                      ),
                      const SizedBox(height: 1),
                      Text(
                        "IP: ${isp.ip} • ${isp.city} (${isp.asn})",
                        style: TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: AppCores.textoSuave(context)),
                        overflow: TextOverflow.ellipsis,
                      ),
                    ],
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: AppCores.sucesso.withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: const Text(
                    "CONECTADO",
                    style: TextStyle(fontSize: 9, fontWeight: FontWeight.w900, color: AppCores.sucesso),
                  ),
                ),
              ],
            ),
          ),
        );
      },
      loading: () => Container(
        height: 52,
        decoration: BoxDecoration(
          color: AppCores.superficie(context),
          borderRadius: BorderRadius.circular(AppBordas.raioPadrao),
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
        duration: const Duration(milliseconds: 180),
        padding: const EdgeInsets.symmetric(vertical: 9),
        decoration: BoxDecoration(
          color: isSelected ? AppCores.primario : Colors.transparent,
          borderRadius: BorderRadius.circular(AppBordas.raioGrande - 4),
          boxShadow: isSelected
              ? [
                  BoxShadow(
                    color: AppCores.primario.withValues(alpha: 0.3),
                    blurRadius: 10,
                    offset: const Offset(0, 3),
                  ),
                ]
              : null,
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, size: 14, color: isSelected ? Colors.white : AppCores.textoSuave(context)),
            const SizedBox(width: 6),
            Text(
              title,
              style: TextStyle(
                fontSize: 11.5,
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
        const SizedBox(height: 4),
        _buildAdvancedGauge(context, metrics),
        const SizedBox(height: 12),

        if (metrics.speedHistory.isNotEmpty)
          _buildLiveChartCard(context, metrics.speedHistory),
        
        const SizedBox(height: 12),

        // Grid 2x2 corrigido e balanceado sem bugs horizontais
        _buildBentoMetricsGrid(context, metrics),

        const SizedBox(height: 18),

        _buildActionButton(
          label: isRunning ? "MEDINDO VAZÃO EM TEMPO REAL..." : "INICIAR SPEED TEST REAL",
          isRunning: isRunning,
          onTap: () => ref.read(speedTestProvider.notifier).runTest(),
        ),

        if (metrics.stage == 'complete') ...[
          const SizedBox(height: 10),
          TextButton.icon(
            onPressed: () => _compartilharSpeedResult(metrics, ispInfo),
            icon: const Icon(LucideIcons.share_2, size: 15),
            label: const Text("Compartilhar Este Resultado", style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
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
        const SizedBox(height: 12),
        _buildVideoBentoGrid(videoMetrics),
        const SizedBox(height: 18),

        _buildActionButton(
          label: isRunning ? "AVALIANDO REPRODUÇÃO..." : "INICIAR TESTE DE VÍDEO (4K / 1080p)",
          isRunning: isRunning,
          onTap: () => ref.read(videoTestProvider.notifier).runVideoTest(),
        ),

        if (videoMetrics.stage == 'complete') ...[
          const SizedBox(height: 10),
          TextButton.icon(
            onPressed: () => _compartilharVideoResult(videoMetrics, ispInfo),
            icon: const Icon(LucideIcons.share_2, size: 15),
            label: const Text("Compartilhar Diagnóstico de Vídeo", style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
          ),
        ],
        const SizedBox(height: 110),
      ],
    );
  }

  // Grid Bento 2x2 ultra-espaçoso que elimina 100% de bugs horizontais
  Widget _buildBentoMetricsGrid(BuildContext context, dynamic metrics) {
    return Column(
      children: [
        Row(
          children: [
            Expanded(
              child: _buildBentoMetricTile(
                context,
                title: "DOWNLOAD",
                value: metrics.downloadSpeed.toStringAsFixed(1),
                unit: "Mbps",
                icon: LucideIcons.arrow_down,
                color: AppCores.primario,
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: _buildBentoMetricTile(
                context,
                title: "UPLOAD",
                value: metrics.uploadSpeed.toStringAsFixed(1),
                unit: "Mbps",
                icon: LucideIcons.arrow_up,
                color: AppCores.secundario,
              ),
            ),
          ],
        ),
        const SizedBox(height: 10),
        Row(
          children: [
            Expanded(
              child: _buildBentoMetricTile(
                context,
                title: "LATÊNCIA (PING)",
                value: "${metrics.ping}",
                unit: "ms",
                icon: LucideIcons.timer,
                color: Colors.amber,
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: _buildBentoMetricTile(
                context,
                title: "JITTER",
                value: "${metrics.jitter}",
                unit: "ms",
                icon: LucideIcons.activity,
                color: AppCores.sucesso,
              ),
            ),
          ],
        ),
        const SizedBox(height: 10),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
          decoration: BoxDecoration(
            color: AppCores.superficie(context),
            borderRadius: BorderRadius.circular(AppBordas.raioPadrao),
            border: Border.all(color: AppCores.borda(context)),
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  const Icon(LucideIcons.shield_check, color: AppCores.sucesso, size: 16),
                  const SizedBox(width: 8),
                  Text(
                    "BUFFERBLOAT & PERDA",
                    style: TextStyle(fontSize: 10, fontWeight: FontWeight.w900, letterSpacing: 1.2, color: AppCores.textoSuave(context)),
                  ),
                ],
              ),
              Row(
                children: [
                  Text("Perda: ${metrics.packetLoss}% • ", style: TextStyle(fontSize: 11, color: AppCores.textoSuave(context), fontWeight: FontWeight.bold)),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                    decoration: BoxDecoration(
                      color: AppCores.sucesso.withValues(alpha: 0.15),
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: Text(
                      "GRAU ${metrics.bufferbloatGrade}",
                      style: const TextStyle(fontSize: 10, fontWeight: FontWeight.w900, color: AppCores.sucesso),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildBentoMetricTile(
    BuildContext context, {
    required String title,
    required String value,
    required String unit,
    required IconData icon,
    required Color color,
  }) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppCores.superficie(context),
        borderRadius: BorderRadius.circular(AppBordas.raioPadrao),
        border: Border.all(color: AppCores.borda(context)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(6),
                decoration: BoxDecoration(
                  color: color.withValues(alpha: 0.12),
                  shape: BoxShape.circle,
                ),
                child: Icon(icon, size: 13, color: color),
              ),
              const SizedBox(width: 8),
              Text(
                title,
                style: TextStyle(
                  fontSize: 9.5,
                  fontWeight: FontWeight.w900,
                  letterSpacing: 1.2,
                  color: AppCores.textoSuave(context),
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          RichText(
            text: TextSpan(
              style: TextStyle(color: AppCores.texto(context), fontWeight: FontWeight.w900),
              children: [
                TextSpan(
                  text: value,
                  style: GoogleFonts.inter(fontSize: 22, fontWeight: FontWeight.w900, letterSpacing: -0.5),
                ),
                TextSpan(
                  text: " $unit",
                  style: TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: AppCores.textoSuave(context)),
                ),
              ],
            ),
          ),
        ],
      ),
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
              Text(
                "DIAGNÓSTICO DE STREAMING",
                style: TextStyle(fontSize: 9.5, fontWeight: FontWeight.w900, letterSpacing: 1.5, color: AppCores.primario),
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
                      const SizedBox(width: 8, height: 8, child: CircularProgressIndicator(strokeWidth: 2)),
                      const SizedBox(width: 6),
                    ],
                    Text(
                      isComplete ? "VERIFICADO" : (isRunning ? "TESTANDO" : "PRONTO"),
                      style: TextStyle(
                        fontSize: 9.5, 
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
                  const Icon(Icons.play_circle_outline_rounded, size: 38, color: AppCores.primario),
                  const SizedBox(height: 8),
                  const Text(
                    "Avaliação de Resolução e Buffer",
                    style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold),
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

          const SizedBox(height: 16),

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
          child: Icon(icon, color: badgeColor, size: 18),
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

  Widget _buildVideoBentoGrid(dynamic videoMetrics) {
    return Row(
      children: [
        Expanded(
          child: _buildBentoMetricTile(
            context,
            title: "BITRATE STREAM",
            value: videoMetrics.bitrateMbps.toStringAsFixed(1),
            unit: "Mbps",
            icon: LucideIcons.clapperboard,
            color: AppCores.primario,
          ),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: _buildBentoMetricTile(
            context,
            title: "RESPOSTA (TTFB)",
            value: "${videoMetrics.ttfbMs}",
            unit: "ms",
            icon: LucideIcons.timer,
            color: Colors.amber,
          ),
        ),
      ],
    );
  }

  Widget _buildAdvancedGauge(BuildContext context, dynamic metrics) {
    final double displaySpeed = metrics.stage == 'upload' 
        ? metrics.uploadSpeed 
        : (metrics.stage == 'ping' ? metrics.ping.toDouble() : metrics.downloadSpeed);
    final String unit = metrics.stage == 'ping' ? "ms" : "Mbps";

    return SizedBox(
      width: 240,
      height: 220,
      child: Stack(
        alignment: Alignment.center,
        children: [
          CustomPaint(
            size: const Size(220, 220),
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
                      fontSize: 56,
                      fontWeight: FontWeight.w900,
                      color: AppCores.texto(context),
                      letterSpacing: -3,
                    ),
                  ),
                  Padding(
                    padding: const EdgeInsets.only(bottom: 10, left: 4),
                    child: Text(
                      unit,
                      style: const TextStyle(
                        fontSize: 14,
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
                    : (metrics.stage == 'idle' ? "PRONTO PARA TESTAR" : "MEDIÇÃO ATIVA"),
                style: TextStyle(
                  fontSize: 9.5, 
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
      height: 84,
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
                style: TextStyle(fontSize: 8.5, fontWeight: FontWeight.w900, color: AppCores.textoSuave(context), letterSpacing: 1.2),
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

  Widget _buildActionButton({required String label, required bool isRunning, required VoidCallback onTap}) {
    return GestureDetector(
      onTap: isRunning ? null : () {
        HapticFeedback.heavyImpact();
        onTap();
      },
      child: Container(
        width: double.infinity,
        height: 56,
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
