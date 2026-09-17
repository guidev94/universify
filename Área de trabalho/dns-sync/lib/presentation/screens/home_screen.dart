import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_lucide/flutter_lucide.dart';
import 'package:google_fonts/google_fonts.dart';
import 'dart:ui';
import '../../core/theme/app_theme.dart';
import '../providers/proxy_provider.dart';
import 'proxy_list_screen.dart';
import 'settings_screen.dart';

class HomeScreen extends ConsumerStatefulWidget {
  const HomeScreen({super.key});

  @override
  ConsumerState<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends ConsumerState<HomeScreen> {
  int _currentIndex = 0;

  @override
  Widget build(BuildContext context) {
    final estaConectado = ref.watch(dnsStatusProvider);
    final provedorAtual = ref.watch(provedorSelecionadoProvider);
    final modoTema = ref.watch(temaProvider);
    final conexaoInfo = ref.watch(conexaoInfoProvider);

    return Scaffold(
      extendBody: true,
      body: Stack(
        children: [
          // Background dinâmico
          _buildAnimatedBackground(estaConectado),
          
          SafeArea(
            bottom: false,
            child: _currentIndex == 0 
                ? _buildHomeBody(context, estaConectado, provedorAtual, modoTema, conexaoInfo)
                : const SettingsScreen(),
          ),
          
          // Navegação Flutuante
          _buildFloatingNavBar(),
        ],
      ),
    );
  }

  Widget _buildAnimatedBackground(bool ativo) {
    return AnimatedContainer(
      duration: 1.seconds,
      curve: Curves.easeInOut,
      decoration: BoxDecoration(
        color: ativo 
            ? AppCores.primario.withOpacity(0.03) 
            : AppCores.fundo(context),
      ),
      child: Stack(
        children: [
          if (ativo)
            Positioned(
              top: -100,
              right: -100,
              child: Container(
                width: 300,
                height: 300,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: AppCores.primario.withOpacity(0.08),
                ),
              ).animate(onPlay: (c) => c.repeat()).scale(
                duration: 4.seconds,
                begin: const Offset(1, 1),
                end: const Offset(1.5, 1.5),
                curve: Curves.easeInOut,
              ).fadeIn(duration: 2.seconds),
            ),
        ],
      ),
    );
  }

  Widget _buildHomeBody(BuildContext context, bool estaConectado, String provedorAtual, ThemeMode modoTema, AsyncValue<Map<String, dynamic>> conexaoInfo) {
    return CustomScrollView(
      physics: const BouncingScrollPhysics(),
      slivers: [
        // Custom Header (Non-generic)
        SliverToBoxAdapter(
          child: Padding(
            padding: const EdgeInsets.all(AppEspacamento.lg),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      "Olá, Aquinodev",
                      style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                        color: AppCores.texto(context).withOpacity(0.6),
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Row(
                      children: [
                        Image.asset('assets/images/icone.png', height: 28),
                        const SizedBox(width: 8),
                        Text(
                          "Sync DNS",
                          style: GoogleFonts.inter(
                            fontSize: 22,
                            fontWeight: FontWeight.w900,
                            letterSpacing: -0.5,
                            color: AppCores.texto(context),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
                _buildThemeToggle(modoTema),
              ],
            ),
          ),
        ),

        SliverPadding(
          padding: const EdgeInsets.symmetric(horizontal: AppEspacamento.lg),
          sliver: SliverList(
            delegate: SliverChildListDelegate([
              const SizedBox(height: AppEspacamento.md),
              
              // Status Card com Efeito de Glow
              _buildModernStatusCard(estaConectado, provedorAtual),
              
              const SizedBox(height: AppEspacamento.xl),
              
              // Conexão Info (Leak Test)
              _buildLeakTestCard(conexaoInfo),
              
              const SizedBox(height: AppEspacamento.xl),
              
              Text(
                "Provedores",
                style: Theme.of(context).textTheme.titleLarge?.copyWith(
                  fontWeight: FontWeight.w800,
                ),
              ),
              const SizedBox(height: AppEspacamento.md),
              
              // Grid Estilo Bento
              Row(
                children: [
                  Expanded(
                    child: _buildBentoCard(
                      "Google", 
                      "8.8.8.8", 
                      LucideIcons.search, 
                      const Color(0xFF4285F4),
                      () => _selecionarProvedor("Google"),
                    ),
                  ),
                  const SizedBox(width: AppEspacamento.md),
                  Expanded(
                    child: _buildBentoCard(
                      "Cloudflare", 
                      "1.1.1.1", 
                      LucideIcons.zap, 
                      const Color(0xFFF38020),
                      () => _selecionarProvedor("Cloudflare"),
                    ),
                  ),
                ],
              ),
              
              const SizedBox(height: AppEspacamento.xl),
              
              // Proxy Section Refatorada
              _buildPremiumProxySection(),
              
              const SizedBox(height: 120), // Espaço para a nav bar
            ]),
          ),
        ),
      ],
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
          size: 20,
          color: AppCores.texto(context),
        ),
        onPressed: () {
          HapticFeedback.mediumImpact();
          ref.read(temaProvider.notifier).state = 
              modo == ThemeMode.light ? ThemeMode.dark : ThemeMode.light;
        },
      ),
    ).animate().fadeIn().scale();
  }

  Widget _buildModernStatusCard(bool ativo, String provedor) {
    return Container(
      decoration: BoxDecoration(
        boxShadow: [
          if (ativo)
            BoxShadow(
              color: AppCores.primario.withOpacity(0.2),
              blurRadius: 30,
              offset: const Offset(0, 10),
            ),
        ],
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(AppBordas.raioGrande * 1.5),
        child: BackdropFilter(
          filter: ImageFilter.blur(sigmaX: 10, sigmaY: 10),
          child: Container(
            padding: const EdgeInsets.all(AppEspacamento.xl),
            decoration: BoxDecoration(
              color: ativo 
                  ? AppCores.primario.withOpacity(0.9) 
                  : AppCores.superficie(context).withOpacity(0.8),
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
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          "PROTEÇÃO",
                          style: TextStyle(
                            fontSize: 10, 
                            fontWeight: FontWeight.w900,
                            letterSpacing: 2,
                            color: ativo ? Colors.white70 : AppCores.textoSuave(context),
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          ativo ? "Ativada" : "Desativada",
                          style: TextStyle(
                            fontSize: 32, 
                            fontWeight: FontWeight.w900,
                            color: ativo ? Colors.white : AppCores.texto(context),
                          ),
                        ),
                      ],
                    ),
                    _buildPowerButton(ativo),
                  ],
                ),
                const SizedBox(height: AppEspacamento.xl),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                  decoration: BoxDecoration(
                    color: ativo ? Colors.white.withOpacity(0.1) : AppCores.fundo(context).withOpacity(0.5),
                    borderRadius: BorderRadius.circular(AppBordas.raioPadrao),
                  ),
                  child: Row(
                    children: [
                      Icon(
                        LucideIcons.shield_check, 
                        color: ativo ? Colors.white : AppCores.primario, 
                        size: 20
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Text(
                          ativo ? "Conectado a $provedor" : "Selecione um provedor",
                          style: TextStyle(
                            fontWeight: FontWeight.w600,
                            color: ativo ? Colors.white : AppCores.texto(context),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    ).animate().fadeIn(duration: 800.ms).slideY(begin: 0.1);
  }

  Widget _buildPowerButton(bool ativo) {
    return GestureDetector(
      onTap: () {
        HapticFeedback.heavyImpact();
        ref.read(dnsStatusProvider.notifier).state = !ativo;
      },
      child: Container(
        width: 70,
        height: 70,
        decoration: BoxDecoration(
          shape: BoxShape.circle,
          color: ativo ? Colors.white : AppCores.primario,
          boxShadow: [
            BoxShadow(
              color: (ativo ? Colors.white : AppCores.primario).withOpacity(0.3),
              blurRadius: 20,
            ),
          ],
        ),
        child: Icon(
          LucideIcons.power,
          color: ativo ? AppCores.primario : Colors.white,
          size: 30,
        ),
      ).animate(target: ativo ? 1 : 0).shimmer(duration: 2.seconds, color: Colors.white24),
    );
  }

  Widget _buildBentoCard(String title, String ip, IconData icon, Color color, VoidCallback onTap) {
    return GestureDetector(
      onTap: () {
        HapticFeedback.selectionClick();
        onTap();
      },
      child: Container(
        padding: const EdgeInsets.all(AppEspacamento.lg),
        decoration: BoxDecoration(
          color: AppCores.superficie(context),
          borderRadius: BorderRadius.circular(AppBordas.raioGrande),
          border: Border.all(color: AppCores.borda(context)),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: color.withOpacity(0.1),
                shape: BoxShape.circle,
              ),
              child: Icon(icon, color: color, size: 20),
            ),
            const SizedBox(height: AppEspacamento.md),
            Text(
              title,
              style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 16),
            ),
            Text(
              ip,
              style: TextStyle(color: AppCores.textoSuave(context), fontSize: 12),
            ),
          ],
        ),
      ),
    ).animate().fadeIn(delay: 400.ms).scale(delay: 400.ms);
  }

  Widget _buildPremiumProxySection() {
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
            Navigator.push(
              context,
              PageRouteBuilder(
                pageBuilder: (context, animation, secondaryAnimation) => const ProxyListScreen(),
                transitionsBuilder: (context, animation, secondaryAnimation, child) {
                  return FadeTransition(opacity: animation, child: child);
                },
              ),
            );
          },
          borderRadius: BorderRadius.circular(AppBordas.raioGrande * 1.5),
          child: Padding(
            padding: const EdgeInsets.all(AppEspacamento.xl),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Icon(LucideIcons.globe, color: Colors.white, size: 40),
                    Icon(LucideIcons.chevron_right, color: Colors.white70, size: 24),
                  ],
                ),
                const SizedBox(height: AppEspacamento.xl),
                const Text(
                  "Servidores Proxy",
                  style: TextStyle(
                    color: Colors.white, 
                    fontSize: 24, 
                    fontWeight: FontWeight.w900
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  "Navegação global e anônima em um clique.",
                  style: TextStyle(color: Colors.white.withOpacity(0.8), fontSize: 14),
                ),
              ],
            ),
          ),
        ),
      ),
    ).animate().slideY(begin: 0.2).fadeIn();
  }

  Widget _buildLeakTestCard(AsyncValue<Map<String, dynamic>> info) {
    return info.when(
      data: (data) {
        final ip = data['ip'] ?? 'Não identificado';
        final cidade = data['city'] ?? 'Localização';
        final pais = data['country_name'] ?? 'Desconhecida';
        
        return Container(
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
          decoration: BoxDecoration(
            color: AppCores.superficie(context),
            borderRadius: BorderRadius.circular(AppBordas.raioPadrao),
            border: Border.all(color: AppCores.borda(context)),
          ),
          child: Row(
            children: [
              const Icon(LucideIcons.activity, color: AppCores.sucesso, size: 18),
              const SizedBox(width: 12),
              Expanded(
                child: RichText(
                  text: TextSpan(
                    style: TextStyle(fontSize: 13, color: AppCores.texto(context)),
                    children: [
                      const TextSpan(text: "Rede: ", style: TextStyle(fontWeight: FontWeight.bold)),
                      TextSpan(text: "$ip • $cidade, $pais"),
                    ],
                  ),
                ),
              ),
              IconButton(
                padding: EdgeInsets.zero,
                constraints: const BoxConstraints(),
                icon: const Icon(LucideIcons.refresh_cw, size: 14),
                onPressed: () => ref.refresh(conexaoInfoProvider),
              ),
            ],
          ),
        );
      },
      loading: () => const Center(child: SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2))),
      error: (e, s) => const SizedBox.shrink(),
    ).animate().fadeIn();
  }

  Widget _buildFloatingNavBar() {
    return Positioned(
      bottom: 30,
      left: 30,
      right: 30,
      child: ClipRRect(
        borderRadius: BorderRadius.circular(AppBordas.raioGrande * 2),
        child: BackdropFilter(
          filter: ImageFilter.blur(sigmaX: 15, sigmaY: 15),
          child: Container(
            height: 70,
            padding: const EdgeInsets.symmetric(horizontal: 20),
            decoration: BoxDecoration(
              color: AppCores.superficie(context).withOpacity(0.8),
              borderRadius: BorderRadius.circular(AppBordas.raioGrande * 2),
              border: Border.all(color: AppCores.borda(context).withOpacity(0.5)),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceAround,
              children: [
                _buildNavIcon(0, LucideIcons.home, "Início"),
                _buildNavIcon(1, LucideIcons.settings, "Ajustes"),
              ],
            ),
          ),
        ),
      ),
    ).animate().slideY(begin: 1, duration: 800.ms, curve: Curves.easeOutBack);
  }

  Widget _buildNavIcon(int index, IconData icon, String label) {
    final ativo = _currentIndex == index;
    return GestureDetector(
      onTap: () {
        HapticFeedback.selectionClick();
        setState(() => _currentIndex = index);
      },
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(
            icon, 
            color: ativo ? AppCores.primario : AppCores.textoSuave(context),
            size: 24,
          ),
          const SizedBox(height: 4),
          if (ativo)
            Container(
              width: 4,
              height: 4,
              decoration: const BoxDecoration(shape: BoxShape.circle, color: AppCores.primario),
            ).animate().scale(),
        ],
      ),
    );
  }

  void _selecionarProvedor(String nome) {
    ref.read(provedorSelecionadoProvider.notifier).state = nome;
    ref.read(dnsStatusProvider.notifier).state = true;
    
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text("DNS alterado para $nome"),
        behavior: SnackBarBehavior.floating,
        backgroundColor: AppCores.primario,
        margin: const EdgeInsets.only(bottom: 110, left: 20, right: 20),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
      ),
    );
  }
}
