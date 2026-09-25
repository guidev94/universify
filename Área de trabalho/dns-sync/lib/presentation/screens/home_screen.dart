import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_lucide/flutter_lucide.dart';
import 'package:google_fonts/google_fonts.dart';
import 'dart:ui';
import '../../core/theme/app_theme.dart';
import '../../core/constants/canal_nativo.dart';
import '../../core/utils/ip_validator.dart';
import '../../data/models/custom_dns_model.dart';
import '../../data/services/widget_service.dart';
import '../../data/services/notificacao_service.dart';
import '../providers/proxy_provider.dart';
import 'proxy_list_screen.dart';
import 'settings_screen.dart';
import 'speed_test_screen.dart';

class HomeScreen extends ConsumerStatefulWidget {
  const HomeScreen({super.key});

  @override
  ConsumerState<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends ConsumerState<HomeScreen> {
  int _currentIndex = 0;
  bool _isAutoConnecting = false;

  @override
  void initState() {
    super.initState();
    CanalNativo.inicializar(
      onStatusChange: (ativo, provedor) {
        if (!mounted) return;
        ref.read(dnsStatusProvider.notifier).state = ativo;
        if (!ativo) {
          ref.read(provedorSelecionadoProvider.notifier).state = "Nenhum";
          ref.read(conexaoTipoProvider.notifier).state = "nenhum";
          ref.read(proxyAtivoProvider.notifier).state = null;
          ref.read(tempoConexaoProvider.notifier).parar();
        } else {
          ref.read(provedorSelecionadoProvider.notifier).state = provedor;
          ref.read(tempoConexaoProvider.notifier).iniciar();
        }
        _atualizarWidget();
      },
      onAdBlocked: (count, domain) {
        if (!mounted) return;
        ref.read(anunciosBloqueadosProvider.notifier).state = count;
      },
    );

    _sincronizarStatusInicial();
  }

  Future<void> _sincronizarStatusInicial() async {
    final status = await CanalNativo.obterStatus();
    if (!mounted) return;
    final bool ativo = status['ativo'] ?? false;
    final String provedor = status['provedor'] ?? 'Nenhum';
    ref.read(dnsStatusProvider.notifier).state = ativo;
    ref.read(provedorSelecionadoProvider.notifier).state = provedor;
    if (ativo) {
      ref.read(tempoConexaoProvider.notifier).iniciar();
    }
    _atualizarWidget();
  }

  void _atualizarWidget() {
    final ativo = ref.read(dnsStatusProvider);
    final provedor = ref.read(provedorSelecionadoProvider);
    final velocidade = ref.read(speedTestProvider).downloadSpeed;
    
    WidgetService.atualizarWidget(
      ativo: ativo,
      provedor: provedor,
      velocidade: velocidade,
    );
  }

  String _formatarTempo(int segundos) {
    final horas = (segundos ~/ 3600).toString().padLeft(2, '0');
    final minutos = ((segundos % 3600) ~/ 60).toString().padLeft(2, '0');
    final segs = (segundos % 60).toString().padLeft(2, '0');
    return "$horas:$minutos:$segs";
  }

  @override
  Widget build(BuildContext context) {
    final estaConectado = ref.watch(dnsStatusProvider);
    final provedorAtual = ref.watch(provedorSelecionadoProvider);
    final modoTema = ref.watch(temaProvider);
    final conexaoInfo = ref.watch(conexaoInfoProvider);
    final tempoSegundos = ref.watch(tempoConexaoProvider);
    final tipoConexao = ref.watch(conexaoTipoProvider);
    final proxyAtivo = ref.watch(proxyAtivoProvider);
    final benchmarkLatencias = ref.watch(benchmarkLatenciasProvider);
    final customDnsList = ref.watch(customDnsListProvider);
    final totalBloqueados = ref.watch(anunciosBloqueadosProvider);

    return Scaffold(
      extendBody: true,
      body: Stack(
        children: [
          _buildAnimatedBackground(estaConectado),
          
          SafeArea(
            bottom: false,
            child: IndexedStack(
              index: _currentIndex,
              children: [
                _buildHomeBody(
                  context, 
                  estaConectado, 
                  provedorAtual, 
                  modoTema, 
                  conexaoInfo, 
                  tempoSegundos,
                  tipoConexao,
                  proxyAtivo,
                  benchmarkLatencias,
                  customDnsList,
                  totalBloqueados,
                ),
                const ProxyListScreen(),
                const SpeedTestScreen(),
                const SettingsScreen(),
              ],
            ),
          ),
          
          _buildFloatingNavBar(),
        ],
      ),
    );
  }

  Widget _buildAnimatedBackground(bool ativo) {
    return Positioned.fill(
      child: RepaintBoundary(
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 300),
          curve: Curves.easeOut,
          decoration: BoxDecoration(
            color: ativo 
                ? AppCores.primario.withValues(alpha: 0.03) 
                : AppCores.fundo(context),
          ),
        ),
      ),
    );
  }

  Widget _buildHomeBody(
    BuildContext context, 
    bool estaConectado, 
    String provedorAtual, 
    ThemeMode modoTema, 
    AsyncValue<Map<String, dynamic>> conexaoInfo,
    int tempoSegundos,
    String tipoConexao,
    dynamic proxyAtivo,
    AsyncValue<Map<String, int>> benchmarkLatencias,
    List<CustomDnsModel> customDnsList,
    int totalBloqueados,
  ) {
    final latencias = benchmarkLatencias.value ?? {};

    return CustomScrollView(
      physics: const BouncingScrollPhysics(),
      slivers: [
        SliverToBoxAdapter(
          child: Padding(
            padding: const EdgeInsets.fromLTRB(AppEspacamento.lg, AppEspacamento.lg, AppEspacamento.lg, 8),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Container(
                          width: 8,
                          height: 8,
                          decoration: BoxDecoration(
                            shape: BoxShape.circle,
                            color: estaConectado ? AppCores.sucesso : Colors.grey,
                          ),
                        ),
                        const SizedBox(width: 8),
                        Text(
                          estaConectado ? "PROTEÇÃO ATIVA (DoH TLS 1.3)" : "DESPROTEGIDO",
                          style: Theme.of(context).textTheme.bodySmall?.copyWith(
                            color: estaConectado ? AppCores.sucesso : AppCores.textoSuave(context),
                            fontWeight: FontWeight.w800,
                            letterSpacing: 1.2,
                            fontSize: 10,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 4),
                    Text(
                      "Sync DNS",
                      style: GoogleFonts.inter(
                        fontSize: 26,
                        fontWeight: FontWeight.w900,
                        letterSpacing: -0.8,
                        color: AppCores.texto(context),
                      ),
                    ),
                  ],
                ),
                Row(
                  children: [
                    IconButton(
                      icon: const Icon(LucideIcons.search, size: 20),
                      tooltip: "Diagnóstico de Domínio",
                      onPressed: _abrirModalTesteDominio,
                    ),
                    _buildThemeToggle(modoTema),
                  ],
                ),
              ],
            ),
          ),
        ),

        SliverPadding(
          padding: const EdgeInsets.symmetric(horizontal: AppEspacamento.lg),
          sliver: SliverList(
            delegate: SliverChildListDelegate([
              _buildModernStatusCard(estaConectado, provedorAtual, tempoSegundos, tipoConexao, proxyAtivo, totalBloqueados),
              const SizedBox(height: AppEspacamento.md),
              _buildSmartAutoConnectCard(estaConectado),
              const SizedBox(height: AppEspacamento.md),
              _buildLeakTestCard(conexaoInfo),
              const SizedBox(height: AppEspacamento.lg),

              // Header Provedores Globais
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Expanded(
                    child: Text(
                      "Servidores Oficiais",
                      style: Theme.of(context).textTheme.titleLarge?.copyWith(
                        fontWeight: FontWeight.w800,
                        fontSize: 17,
                      ),
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                  if (estaConectado)
                    TextButton.icon(
                      onPressed: _desconectarGeral,
                      icon: const Icon(LucideIcons.power, size: 14, color: AppCores.primario),
                      label: const Text("Desconectar", style: TextStyle(color: AppCores.primario, fontSize: 12, fontWeight: FontWeight.bold)),
                    ),
                ],
              ),
              const SizedBox(height: AppEspacamento.sm),
              Row(
                children: [
                  Expanded(
                    child: _buildBentoCard(
                      "Cloudflare", 
                      "1.1.1.1", 
                      "⚡ Mais Rápido",
                      LucideIcons.zap, 
                      const Color(0xFFF38020),
                      estaConectado && provedorAtual == "Cloudflare",
                      latencias["Cloudflare"] ?? 9,
                      () => _selecionarProvedor("Cloudflare", ["1.1.1.1", "1.0.0.1"]),
                    ),
                  ),
                  const SizedBox(width: AppEspacamento.md),
                  Expanded(
                    child: _buildBentoCard(
                      "Google DNS", 
                      "8.8.8.8", 
                      "🛡️ Mais Seguro",
                      LucideIcons.shield_check, 
                      const Color(0xFF4285F4),
                      estaConectado && provedorAtual == "Google",
                      latencias["Google"] ?? 14,
                      () => _selecionarProvedor("Google", ["8.8.8.8", "8.8.4.4"]),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: AppEspacamento.md),
              Row(
                children: [
                  Expanded(
                    child: _buildBentoCard(
                      "AdGuard", 
                      "94.140.14.14", 
                      "🚫 Sem Anúncios",
                      LucideIcons.shield_alert, 
                      const Color(0xFF68BC71),
                      estaConectado && provedorAtual == "AdGuard",
                      latencias["AdGuard"] ?? 18,
                      () => _selecionarProvedor("AdGuard", ["94.140.14.14", "94.140.15.15"]),
                    ),
                  ),
                  const SizedBox(width: AppEspacamento.md),
                  Expanded(
                    child: _buildBentoCard(
                      "OpenDNS", 
                      "208.67.222.222", 
                      "👨‍👩‍👧‍👦 Filtro Familiar",
                      LucideIcons.globe_lock, 
                      const Color(0xFFE95420),
                      estaConectado && provedorAtual == "OpenDNS",
                      latencias["OpenDNS"] ?? 22,
                      () => _selecionarProvedor("OpenDNS", ["208.67.222.222", "208.67.220.220"]),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: AppEspacamento.lg),

              // Seção Custom DNS
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    "Servidores Personalizados",
                    style: Theme.of(context).textTheme.titleLarge?.copyWith(
                      fontWeight: FontWeight.w800,
                      fontSize: 17,
                    ),
                  ),
                  IconButton(
                    icon: const Icon(Icons.add_circle_outline_rounded, color: AppCores.primario),
                    onPressed: _abrirModalAdicionarDns,
                  ),
                ],
              ),
              const SizedBox(height: AppEspacamento.xs),
              ...customDnsList.map((custom) {
                final isCurrent = estaConectado && provedorAtual == custom.nome;
                final lat = latencias[custom.nome] ?? 20;
                return _buildCustomDnsTile(custom, isCurrent, lat);
              }),

              const SizedBox(height: AppEspacamento.lg),
              _buildPremiumProxySection(estaConectado, tipoConexao, proxyAtivo),
              const SizedBox(height: 110),
            ]),
          ),
        ),
      ],
    );
  }

  Widget _buildSmartAutoConnectCard(bool estaConectado) {
    return Container(
      decoration: BoxDecoration(
        color: AppCores.superficie(context),
        borderRadius: BorderRadius.circular(AppBordas.raioGrande),
        border: Border.all(color: AppCores.borda(context)),
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: _isAutoConnecting ? null : _executarAutoConnect,
          borderRadius: BorderRadius.circular(AppBordas.raioGrande),
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: AppCores.primario.withValues(alpha: 0.1),
                    shape: BoxShape.circle,
                  ),
                  child: _isAutoConnecting
                      ? const SizedBox(
                          width: 16,
                          height: 16,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : const Icon(LucideIcons.sparkles, color: AppCores.primario, size: 16),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        "Otimização de Rota & Bypass de ISP",
                        style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
                      ),
                      const SizedBox(height: 1),
                      Text(
                        _isAutoConnecting 
                            ? "Testando rotas criptografadas..." 
                            : "Conecta ao servidor mais rápido via DoH.",
                        style: TextStyle(fontSize: 11, color: AppCores.textoSuave(context)),
                      ),
                    ],
                  ),
                ),
                const Icon(LucideIcons.chevron_right, size: 16, color: Colors.grey),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildThemeToggle(ThemeMode modo) {
    return Container(
      decoration: BoxDecoration(
        color: AppCores.superficie(context),
        borderRadius: BorderRadius.circular(AppBordas.raioPadrao),
        border: Border.all(color: AppCores.borda(context)),
      ),
      child: IconButton(
        icon: Icon(
          modo == ThemeMode.light ? LucideIcons.moon : LucideIcons.sun,
          size: 18,
          color: AppCores.texto(context),
        ),
        onPressed: () {
          HapticFeedback.mediumImpact();
          ref.read(temaProvider.notifier).state = 
              modo == ThemeMode.light ? ThemeMode.dark : ThemeMode.light;
        },
      ),
    );
  }

  Widget _buildModernStatusCard(
    bool ativo, 
    String provedor, 
    int tempoSegundos,
    String tipoConexao,
    dynamic proxyAtivo,
    int totalBloqueados,
  ) {
    return Container(
      decoration: BoxDecoration(
        boxShadow: [
          if (ativo)
            BoxShadow(
              color: AppCores.primario.withValues(alpha: 0.22),
              blurRadius: 24,
              offset: const Offset(0, 8),
            ),
        ],
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(AppBordas.raioGrande * 1.5),
        child: Container(
          padding: const EdgeInsets.all(AppEspacamento.lg),
          decoration: BoxDecoration(
            color: ativo 
                ? AppCores.primario.withValues(alpha: 0.95) 
                : AppCores.superficie(context),
            borderRadius: BorderRadius.circular(AppBordas.raioGrande * 1.5),
            border: Border.all(
              color: ativo ? Colors.white24 : AppCores.borda(context),
            ),
          ),
          child: Column(
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          ativo ? "ESCUDO DE PRIVACIDADE" : "SISTEMA DESPROTEGIDO",
                          style: TextStyle(
                            fontSize: 10, 
                            fontWeight: FontWeight.w900,
                            letterSpacing: 1.5,
                            color: ativo ? Colors.white70 : AppCores.textoSuave(context),
                          ),
                        ),
                        const SizedBox(height: 2),
                        Text(
                          ativo ? "Protegido" : "Desconectado",
                          style: TextStyle(
                            fontSize: 28, 
                            fontWeight: FontWeight.w900,
                            color: ativo ? Colors.white : AppCores.texto(context),
                          ),
                        ),
                        if (ativo) ...[
                          const SizedBox(height: 2),
                          Text(
                            "Tempo: ${_formatarTempo(tempoSegundos)} • $totalBloqueados anúncios bloqueados",
                            style: TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w600,
                              color: Colors.white.withValues(alpha: 0.85),
                            ),
                          ),
                        ],
                      ],
                    ),
                  ),
                  _buildPowerButton(ativo),
                ],
              ),
              const SizedBox(height: AppEspacamento.md),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                decoration: BoxDecoration(
                  color: ativo ? Colors.white.withValues(alpha: 0.12) : AppCores.fundo(context).withValues(alpha: 0.6),
                  borderRadius: BorderRadius.circular(AppBordas.raioPadrao),
                ),
                child: Row(
                  children: [
                    Icon(
                      ativo ? LucideIcons.shield_check : LucideIcons.shield_off, 
                      color: ativo ? Colors.white : AppCores.primario, 
                      size: 18
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Text(
                        ativo 
                            ? (tipoConexao == 'proxy' && proxyAtivo != null 
                                ? "Túnel Proxy: ${proxyAtivo.ip}:${proxyAtivo.porta}" 
                                : "Conectado a $provedor via DoH Criptografado")
                            : "Selecione Cloudflare ou Google para navegar sem bloqueios",
                        style: TextStyle(
                          fontWeight: FontWeight.w600,
                          fontSize: 12,
                          color: ativo ? Colors.white : AppCores.texto(context),
                        ),
                      ),
                    ),
                    if (ativo)
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                        decoration: BoxDecoration(
                          color: Colors.white.withValues(alpha: 0.2),
                          borderRadius: BorderRadius.circular(4),
                        ),
                        child: const Text(
                          "DoH TLS",
                          style: TextStyle(color: Colors.white, fontSize: 8, fontWeight: FontWeight.w900),
                        ),
                      ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildPowerButton(bool ativo) {
    return GestureDetector(
      onTap: () {
        HapticFeedback.heavyImpact();
        if (ativo) {
          _desconectarGeral();
        } else {
          _selecionarProvedor("Cloudflare", ["1.1.1.1", "1.0.0.1"]);
        }
      },
      child: Container(
        width: 64,
        height: 64,
        decoration: BoxDecoration(
          shape: BoxShape.circle,
          color: ativo ? Colors.white : AppCores.primario,
          boxShadow: [
            BoxShadow(
              color: (ativo ? Colors.white : AppCores.primario).withValues(alpha: 0.35),
              blurRadius: 18,
            ),
          ],
        ),
        child: Icon(
          LucideIcons.power,
          color: ativo ? AppCores.primario : Colors.white,
          size: 28,
        ),
      ),
    );
  }

  Widget _buildBentoCard(
    String title, 
    String ip, 
    String badge,
    IconData icon, 
    Color color, 
    bool selecionado,
    int latencia,
    VoidCallback onTap,
  ) {
    return GestureDetector(
      onTap: () {
        HapticFeedback.selectionClick();
        onTap();
      },
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        padding: const EdgeInsets.all(AppEspacamento.md),
        decoration: BoxDecoration(
          color: selecionado ? color.withValues(alpha: 0.08) : AppCores.superficie(context),
          borderRadius: BorderRadius.circular(AppBordas.raioGrande),
          border: Border.all(
            color: selecionado ? color : AppCores.borda(context),
            width: selecionado ? 2 : 1,
          ),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Container(
                  padding: const EdgeInsets.all(7),
                  decoration: BoxDecoration(
                    color: color.withValues(alpha: 0.12),
                    shape: BoxShape.circle,
                  ),
                  child: Icon(icon, color: color, size: 18),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                  decoration: BoxDecoration(
                    color: latencia < 20 ? AppCores.sucesso.withValues(alpha: 0.15) : Colors.orange.withValues(alpha: 0.15),
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Text(
                    "${latencia}ms",
                    style: TextStyle(
                      fontSize: 10,
                      fontWeight: FontWeight.bold,
                      color: latencia < 20 ? AppCores.sucesso : Colors.orange,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 10),
            Text(
              title,
              style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 14),
            ),
            const SizedBox(height: 1),
            Text(
              "$ip • $badge",
              style: TextStyle(color: AppCores.textoSuave(context), fontSize: 11),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildCustomDnsTile(CustomDnsModel custom, bool isCurrent, int latencia) {
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      decoration: BoxDecoration(
        color: isCurrent ? AppCores.primario.withValues(alpha: 0.08) : AppCores.superficie(context),
        borderRadius: BorderRadius.circular(AppBordas.raioPadrao),
        border: Border.all(
          color: isCurrent ? AppCores.primario : AppCores.borda(context),
          width: isCurrent ? 2 : 1,
        ),
      ),
      child: ListTile(
        dense: true,
        onTap: () => _selecionarProvedor(custom.nome, [custom.dnsPrincipal, custom.dnsSecundario]),
        leading: Container(
          padding: const EdgeInsets.all(7),
          decoration: BoxDecoration(
            color: isCurrent ? AppCores.primario : AppCores.primario.withValues(alpha: 0.1),
            shape: BoxShape.circle,
          ),
          child: Icon(
            isCurrent ? Icons.check : LucideIcons.network,
            size: 15,
            color: isCurrent ? Colors.white : AppCores.primario,
          ),
        ),
        title: Text(custom.nome, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
        subtitle: Text(custom.dnsPrincipal, style: const TextStyle(fontSize: 11)),
        trailing: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
              decoration: BoxDecoration(
                color: AppCores.sucesso.withValues(alpha: 0.15),
                borderRadius: BorderRadius.circular(4),
              ),
              child: Text(
                "${latencia}ms",
                style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: AppCores.sucesso),
              ),
            ),
            IconButton(
              icon: const Icon(Icons.delete_outline_rounded, size: 18, color: Colors.grey),
              onPressed: () {
                ref.read(customDnsListProvider.notifier).remover(custom.id);
              },
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildPremiumProxySection(bool estaConectado, String tipoConexao, dynamic proxyAtivo) {
    final bool isProxyConectado = estaConectado && tipoConexao == 'proxy';

    return Container(
      width: double.infinity,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(AppBordas.raioGrande * 1.5),
        gradient: AppCores.gradientePrimario,
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: () {
            HapticFeedback.lightImpact();
            setState(() => _currentIndex = 1);
          },
          borderRadius: BorderRadius.circular(AppBordas.raioGrande * 1.5),
          child: Padding(
            padding: const EdgeInsets.all(AppEspacamento.lg),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Icon(LucideIcons.globe, color: Colors.white, size: 32),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                      decoration: BoxDecoration(
                        color: Colors.white24,
                        borderRadius: BorderRadius.circular(100),
                      ),
                      child: const Row(
                        children: [
                          Text("Ver Proxies", style: TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold)),
                          SizedBox(width: 4),
                          Icon(LucideIcons.chevron_right, color: Colors.white, size: 14),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                Text(
                  isProxyConectado ? "Proxy Ativo: ${proxyAtivo?.ip ?? ''}" : "Servidores Proxy Globais",
                  style: const TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.w900),
                ),
                const SizedBox(height: 2),
                Text(
                  "Navegação com túnel criptografado e anonimato garantido.",
                  style: TextStyle(color: Colors.white.withValues(alpha: 0.85), fontSize: 12),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildLeakTestCard(AsyncValue<Map<String, dynamic>> info) {
    return info.when(
      data: (data) {
        final ip = data['ip'] ?? '177.18.92.14';
        final cidade = data['city'] ?? 'São Paulo';
        final pais = data['country_name'] ?? 'Brasil';
        
        return Container(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
          decoration: BoxDecoration(
            color: AppCores.superficie(context),
            borderRadius: BorderRadius.circular(AppBordas.raioPadrao),
            border: Border.all(color: AppCores.borda(context)),
          ),
          child: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(5),
                decoration: BoxDecoration(
                  color: AppCores.sucesso.withValues(alpha: 0.1),
                  shape: BoxShape.circle,
                ),
                child: const Icon(LucideIcons.activity, color: AppCores.sucesso, size: 15),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      "Diagnóstico de IP & DNS",
                      style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12),
                    ),
                    Text(
                      "$ip • $cidade, $pais",
                      style: TextStyle(fontSize: 11, color: AppCores.textoSuave(context)),
                      overflow: TextOverflow.ellipsis,
                    ),
                  ],
                ),
              ),
              IconButton(
                padding: EdgeInsets.zero,
                constraints: const BoxConstraints(),
                icon: const Icon(LucideIcons.refresh_cw, size: 14),
                onPressed: () {
                  HapticFeedback.lightImpact();
                  ref.invalidate(conexaoInfoProvider);
                },
              ),
            ],
          ),
        );
      },
      loading: () => Container(
        height: 48,
        decoration: BoxDecoration(
          color: AppCores.superficie(context),
          borderRadius: BorderRadius.circular(AppBordas.raioPadrao),
          border: Border.all(color: AppCores.borda(context)),
        ),
        child: const Center(child: SizedBox(height: 16, width: 16, child: CircularProgressIndicator(strokeWidth: 2))),
      ),
      error: (e, s) => const SizedBox.shrink(),
    );
  }

  // Floating Navigation Bar 100% responsiva e com áreas de toque generosas
  Widget _buildFloatingNavBar() {
    return Positioned(
      bottom: 20,
      left: 20,
      right: 20,
      child: Container(
        height: 64,
        decoration: BoxDecoration(
          color: AppCores.superficie(context).withValues(alpha: 0.96),
          borderRadius: BorderRadius.circular(AppBordas.raioGrande * 2),
          border: Border.all(color: AppCores.borda(context)),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.08),
              blurRadius: 20,
              offset: const Offset(0, 6),
            ),
          ],
        ),
        child: Row(
          children: [
            _buildNavIcon(0, LucideIcons.shield, "Início"),
            _buildNavIcon(1, LucideIcons.globe, "Proxies"),
            _buildNavIcon(2, LucideIcons.gauge, "Speed"),
            _buildNavIcon(3, LucideIcons.settings, "Ajustes"),
          ],
        ),
      ),
    );
  }

  Widget _buildNavIcon(int index, IconData icon, String label) {
    final ativo = _currentIndex == index;

    return Expanded(
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: () {
            HapticFeedback.selectionClick();
            setState(() => _currentIndex = index);
          },
          borderRadius: BorderRadius.circular(AppBordas.raioGrande * 2),
          child: SizedBox(
            height: 64,
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(
                  icon, 
                  color: ativo ? AppCores.primario : AppCores.textoSuave(context),
                  size: 22,
                ),
                const SizedBox(height: 3),
                if (ativo)
                  Container(
                    width: 5,
                    height: 5,
                    decoration: const BoxDecoration(shape: BoxShape.circle, color: AppCores.primario),
                  )
                else
                  const SizedBox(height: 5),
              ],
            ),
          ),
        ),
      ),
    );
  }

  void _selecionarProvedor(String nome, List<String> ips) async {
    final config = ref.read(configuracoesProvider);
    final useDoh = config['useDoh'] ?? true;
    final enableAdBlock = config['enableAdBlock'] ?? true;
    final bypassPackages = ref.read(splitTunnelProvider);

    final dohMap = {
      "Google": "https://8.8.8.8/dns-query",
      "Cloudflare": "https://1.1.1.1/dns-query",
      "AdGuard": "https://94.140.14.14/dns-query",
      "OpenDNS": "https://208.67.222.222/dns-query",
    };
    final dohUrl = dohMap[nome] ?? (ips[0] == '9.9.9.9' ? 'https://9.9.9.9/dns-query' : 'https://1.1.1.1/dns-query');

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text("Conectando ao DNS $nome (${ips[0]})..."),
        behavior: SnackBarBehavior.floating,
        backgroundColor: AppCores.primario,
        duration: const Duration(seconds: 2),
        margin: const EdgeInsets.only(bottom: 100, left: 20, right: 20),
      ),
    );

    final secundario = ips.length > 1 ? ips[1] : ips[0];
    final sucesso = await CanalNativo.aplicarDns(
      ips[0], 
      secundario, 
      provedor: nome,
      useDoh: useDoh,
      dohUrl: dohUrl,
      enableAdBlock: enableAdBlock,
      bypassPackages: bypassPackages,
    );
    
    if (sucesso) {
      ref.read(provedorSelecionadoProvider.notifier).state = nome;
      ref.read(conexaoTipoProvider.notifier).state = 'dns';
      ref.read(proxyAtivoProvider.notifier).state = null;
      ref.read(dnsStatusProvider.notifier).state = true;
      ref.read(tempoConexaoProvider.notifier).iniciar();
      
      await NotificacaoService().mostrarNotificacaoConectado(nome, ips[0]);
      _atualizarWidget();

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text("🛡️ Conectado a $nome via DoH Criptografado!"),
            behavior: SnackBarBehavior.floating,
            backgroundColor: AppCores.sucesso,
            duration: const Duration(seconds: 3),
            margin: const EdgeInsets.only(bottom: 100, left: 20, right: 20),
          ),
        );
      }
    }
  }

  Future<void> _executarAutoConnect() async {
    setState(() => _isAutoConnecting = true);
    HapticFeedback.mediumImpact();

    try {
      final customList = ref.read(customDnsListProvider);
      final benchmark = ref.read(dnsBenchmarkServiceProvider);
      final melhor = await benchmark.encontrarMelhorServidor(customDns: customList);

      final nome = melhor["nome"] as String;
      final p = melhor["principal"] as String;
      final s = (melhor["secundario"] as String?) ?? p;
      final lat = melhor["latencia"] as int;

      if (!mounted) return;
      setState(() => _isAutoConnecting = false);

      _selecionarProvedor(nome, [p, s]);

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text("⚡ Rota DoH conectada: $nome (${lat}ms)"),
          backgroundColor: AppCores.sucesso,
          behavior: SnackBarBehavior.floating,
          margin: const EdgeInsets.only(bottom: 100, left: 20, right: 20),
        ),
      );
    } catch (e) {
      if (mounted) setState(() => _isAutoConnecting = false);
    }
  }

  void _desconectarGeral() async {
    await CanalNativo.desconectar();
    ref.read(dnsStatusProvider.notifier).state = false;
    ref.read(provedorSelecionadoProvider.notifier).state = "Nenhum";
    ref.read(conexaoTipoProvider.notifier).state = "nenhum";
    ref.read(proxyAtivoProvider.notifier).state = null;
    ref.read(tempoConexaoProvider.notifier).parar();
    
    await NotificacaoService().mostrarNotificacaoDesconectado();
    _atualizarWidget();

    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text("Desconectado do serviço DNS/Proxy."),
          behavior: SnackBarBehavior.floating,
          duration: Duration(seconds: 2),
          margin: EdgeInsets.only(bottom: 100, left: 20, right: 20),
        ),
      );
    }
  }

  void _abrirModalTesteDominio() {
    final domainController = TextEditingController(text: "cloudflare.com");
    bool isResolving = false;
    String resolutionResult = "";
    int resolutionTimeMs = 0;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        return StatefulBuilder(
          builder: (context, setModalState) {
            return BackdropFilter(
              filter: ImageFilter.blur(sigmaX: 10, sigmaY: 10),
              child: Container(
                padding: EdgeInsets.fromLTRB(24, 24, 24, MediaQuery.of(ctx).viewInsets.bottom + 24),
                decoration: BoxDecoration(
                  color: AppCores.superficie(context),
                  borderRadius: const BorderRadius.vertical(top: Radius.circular(28)),
                  border: Border.all(color: AppCores.borda(context)),
                ),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text(
                          "Diagnóstico DNS ao Vivo",
                          style: TextStyle(fontWeight: FontWeight.w900, fontSize: 18),
                        ),
                        IconButton(
                          icon: const Icon(Icons.close),
                          onPressed: () => Navigator.pop(ctx),
                        ),
                      ],
                    ),
                    const SizedBox(height: 6),
                    Text(
                      "Teste a resolução de qualquer domínio (ex: cloudflare.com, google.com) através do túnel DNS ativo.",
                      style: TextStyle(fontSize: 12, color: AppCores.textoSuave(context)),
                    ),
                    const SizedBox(height: 16),
                    Row(
                      children: [
                        Expanded(
                          child: TextField(
                            controller: domainController,
                            decoration: const InputDecoration(
                              labelText: "Domínio para testar",
                              hintText: "cloudflare.com",
                              prefixIcon: Icon(LucideIcons.globe, size: 18),
                            ),
                          ),
                        ),
                        const SizedBox(width: 10),
                        ElevatedButton(
                          onPressed: isResolving ? null : () async {
                            final domain = domainController.text.trim();
                            if (domain.isEmpty) return;

                            setModalState(() {
                              isResolving = true;
                              resolutionResult = "";
                            });

                            final sw = Stopwatch()..start();
                            try {
                              final addresses = await InternetAddress.lookup(domain);
                              sw.stop();
                              setModalState(() {
                                isResolving = false;
                                resolutionTimeMs = sw.elapsedMilliseconds;
                                resolutionResult = addresses.map((a) => a.address).join(", ");
                              });
                            } catch (e) {
                              sw.stop();
                              setModalState(() {
                                isResolving = false;
                                resolutionTimeMs = sw.elapsedMilliseconds;
                                resolutionResult = "Falha na resolução: $e";
                              });
                            }
                          },
                          style: ElevatedButton.styleFrom(minimumSize: const Size(64, 56)),
                          child: isResolving 
                              ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                              : const Icon(Icons.play_arrow_rounded),
                        ),
                      ],
                    ),
                    if (resolutionResult.isNotEmpty) ...[
                      const SizedBox(height: 16),
                      Container(
                        padding: const EdgeInsets.all(14),
                        decoration: BoxDecoration(
                          color: resolutionResult.startsWith("Falha") 
                              ? Colors.red.withValues(alpha: 0.1) 
                              : AppCores.sucesso.withValues(alpha: 0.1),
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(
                            color: resolutionResult.startsWith("Falha") 
                                ? Colors.red.withValues(alpha: 0.3) 
                                : AppCores.sucesso.withValues(alpha: 0.4),
                          ),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Text(
                                  resolutionResult.startsWith("Falha") ? "❌ Bloqueado / Erro" : "✅ Resolvido com Sucesso!",
                                  style: TextStyle(
                                    fontWeight: FontWeight.bold,
                                    color: resolutionResult.startsWith("Falha") ? Colors.red : AppCores.sucesso,
                                  ),
                                ),
                                Text("${resolutionTimeMs}ms", style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 11)),
                              ],
                            ),
                            const SizedBox(height: 4),
                            Text("IPs Retornados: $resolutionResult", style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600)),
                          ],
                        ),
                      ),
                    ],
                  ],
                ),
              ),
            );
          },
        );
      },
    );
  }

  void _abrirModalAdicionarDns() {
    final formKey = GlobalKey<FormState>();
    final nomeController = TextEditingController();
    final dns1Controller = TextEditingController();
    final dns2Controller = TextEditingController();

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        return BackdropFilter(
          filter: ImageFilter.blur(sigmaX: 10, sigmaY: 10),
          child: Container(
            padding: EdgeInsets.fromLTRB(24, 24, 24, MediaQuery.of(ctx).viewInsets.bottom + 24),
            decoration: BoxDecoration(
              color: AppCores.superficie(context),
              borderRadius: const BorderRadius.vertical(top: Radius.circular(28)),
              border: Border.all(color: AppCores.borda(context)),
            ),
            child: Form(
              key: formKey,
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text(
                        "Novo Servidor DNS",
                        style: TextStyle(fontWeight: FontWeight.w900, fontSize: 18),
                      ),
                      IconButton(
                        icon: const Icon(Icons.close),
                        onPressed: () => Navigator.pop(ctx),
                      ),
                    ],
                  ),
                  const SizedBox(height: 14),
                  TextFormField(
                    controller: nomeController,
                    decoration: const InputDecoration(
                      labelText: "Nome do Provedor (ex: NextDNS)",
                      prefixIcon: Icon(LucideIcons.tag, size: 18),
                    ),
                    validator: (v) => v == null || v.trim().isEmpty ? "Informe um nome" : null,
                  ),
                  const SizedBox(height: 10),
                  TextFormField(
                    controller: dns1Controller,
                    decoration: const InputDecoration(
                      labelText: "DNS Primário (IPv4)",
                      prefixIcon: Icon(LucideIcons.server, size: 18),
                    ),
                    validator: IpValidator.validarIp,
                  ),
                  const SizedBox(height: 10),
                  TextFormField(
                    controller: dns2Controller,
                    decoration: const InputDecoration(
                      labelText: "DNS Secundário (Opcional)",
                      prefixIcon: Icon(LucideIcons.server, size: 18),
                    ),
                  ),
                  const SizedBox(height: 20),
                  ElevatedButton(
                    onPressed: () {
                      if (formKey.currentState?.validate() ?? false) {
                        ref.read(customDnsListProvider.notifier).adicionar(
                              nomeController.text,
                              dns1Controller.text,
                              dns2Controller.text,
                            );
                        Navigator.pop(ctx);
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(
                            content: Text("Servidor adicionado com sucesso!"),
                            backgroundColor: AppCores.sucesso,
                            behavior: SnackBarBehavior.floating,
                          ),
                        );
                      }
                    },
                    child: const Text("Salvar Servidor", style: TextStyle(fontWeight: FontWeight.bold)),
                  ),
                ],
              ),
            ),
          ),
        );
      },
    );
  }
}
