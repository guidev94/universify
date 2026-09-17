import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_lucide/flutter_lucide.dart';
import '../../core/theme/app_theme.dart';
import '../providers/proxy_provider.dart';
import 'proxy_list_screen.dart';

class HomeScreen extends ConsumerStatefulWidget {
  const HomeScreen({super.key});

  @override
  ConsumerState<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends ConsumerState<HomeScreen> {
  @override
  Widget build(BuildContext context) {
    final estaConectado = ref.watch(dnsStatusProvider);
    final provedorAtual = ref.watch(provedorSelecionadoProvider);
    final modoTema = ref.watch(temaProvider);
    final conexaoInfo = ref.watch(conexaoInfoProvider);

    return Scaffold(
      body: SafeArea(
        child: CustomScrollView(
          physics: const BouncingScrollPhysics(),
          slivers: [
            SliverAppBar(
              floating: true,
              expandedHeight: 140,
              backgroundColor: Colors.transparent,
              elevation: 0,
              flexibleSpace: FlexibleSpaceBar(
                background: Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const SizedBox(height: 20),
                      Hero(
                        tag: 'logo',
                        child: Image.asset(
                          'assets/images/icone.png',
                          height: 70,
                          errorBuilder: (context, error, stackTrace) => 
                              Icon(LucideIcons.shield, size: 50, color: AppCores.primario),
                        ),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        "DNS SYNC",
                        style: GoogleFonts.inter(
                          fontSize: 18,
                          fontWeight: FontWeight.w900,
                          letterSpacing: 2,
                          color: AppCores.primario,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              actions: [
                IconButton(
                  icon: Icon(
                    modoTema == ThemeMode.light ? LucideIcons.moon : LucideIcons.sun,
                    color: AppCores.texto(context),
                  ),
                  onPressed: () {
                    HapticFeedback.mediumImpact();
                    ref.read(temaProvider.notifier).state = 
                        modoTema == ThemeMode.light ? ThemeMode.dark : ThemeMode.light;
                  },
                ).animate().rotate(duration: 400.ms),
                const SizedBox(width: AppEspacamento.md),
              ],
            ),
            SliverPadding(
              padding: const EdgeInsets.symmetric(horizontal: AppEspacamento.lg),
              sliver: SliverList(
                delegate: SliverChildListDelegate([
                  Text(
                    "Sincronização DNS",
                    style: Theme.of(context).textTheme.displayLarge,
                  ).animate().fadeIn(duration: 500.ms).slideX(begin: -0.2),
                  
                  const SizedBox(height: AppEspacamento.sm),
                  
                  Text(
                    "Mantenha sua conexão rápida e segura.",
                    style: Theme.of(context).textTheme.bodyMedium,
                  ).animate().fadeIn(duration: 600.ms, delay: 100.ms).slideX(begin: -0.1),
                  
                  const SizedBox(height: AppEspacamento.xl),
                  
                  // Card de Status
                  _buildStatusCard(estaConectado, provedorAtual),
                  
                  const SizedBox(height: AppEspacamento.lg),
                  
                  // Leak Test / Info de Conexão
                  _buildLeakTestCard(conexaoInfo),
                  
                  const SizedBox(height: AppEspacamento.xl),
                  
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        "Provedores",
                        style: Theme.of(context).textTheme.titleLarge,
                      ),
                      TextButton(
                        onPressed: () {}, 
                        child: const Text("Ver todos"),
                      ),
                    ],
                  ).animate().fadeIn(duration: 600.ms, delay: 200.ms),
                  
                  const SizedBox(height: AppEspacamento.md),
                  
                  _buildProviderCard(
                    "Google DNS", 
                    "8.8.8.8, 8.8.4.4", 
                    LucideIcons.search,
                    () => _selecionarProvedor("Google"),
                  ).animate().fadeIn(duration: 500.ms, delay: 300.ms).slideY(begin: 0.1),
                  
                  const SizedBox(height: AppEspacamento.md),
                  
                  _buildProviderCard(
                    "Cloudflare", 
                    "1.1.1.1, 1.0.0.1", 
                    LucideIcons.zap,
                    () => _selecionarProvedor("Cloudflare"),
                  ).animate().fadeIn(duration: 500.ms, delay: 400.ms).slideY(begin: 0.1),
                  
                  const SizedBox(height: AppEspacamento.xl),
                  
                  // Botão de Proxies
                  _buildProxySection(),
                  
                  const SizedBox(height: AppEspacamento.xl),
                  
                  const Center(
                    child: Text(
                      "Desenvolvido por Aquinodev",
                      style: TextStyle(color: AppCores.textoSuaveClaro, fontSize: 12, fontWeight: FontWeight.w500),
                    ),
                  ).animate().fadeIn(delay: 800.ms),
                  
                  const SizedBox(height: AppEspacamento.xl),
                ]),
              ),
            ),
          ],
        ),
      ),
      bottomNavigationBar: _buildBottomNav(),
    );
  }

  Widget _buildLeakTestCard(AsyncValue<Map<String, dynamic>> info) {
    return info.when(
      data: (data) {
        final ip = data['ip'] ?? 'Desconhecido';
        final cidade = data['city'] ?? 'Localização';
        final pais = data['country_name'] ?? 'indisponível';
        
        return Container(
          padding: const EdgeInsets.all(AppEspacamento.md),
          decoration: BoxDecoration(
            color: AppCores.superficie(context),
            borderRadius: BorderRadius.circular(AppBordas.raioPadrao),
            border: Border.all(color: AppCores.borda(context)),
          ),
          child: Row(
            children: [
              const Icon(LucideIcons.info, color: AppCores.primario, size: 20),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text("Sua Rede Atual", style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                    Text(
                      "$ip ($cidade, $pais)",
                      style: TextStyle(fontSize: 13, color: AppCores.textoSuave(context)),
                    ),
                  ],
                ),
              ),
              IconButton(
                icon: const Icon(LucideIcons.refresh_cw, size: 16),
                onPressed: () => ref.refresh(conexaoInfoProvider),
              ),
            ],
          ),
        );
      },
      loading: () => Container(
        height: 50,
        decoration: BoxDecoration(
          color: AppCores.superficie(context),
          borderRadius: BorderRadius.circular(AppBordas.raioPadrao),
        ),
        child: const Center(child: SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2))),
      ),
      error: (e, s) => Container(
        padding: const EdgeInsets.all(AppEspacamento.md),
        decoration: BoxDecoration(
          color: AppCores.superficie(context),
          borderRadius: BorderRadius.circular(AppBordas.raioPadrao),
        ),
        child: const Text("Erro ao carregar info de conexão", style: TextStyle(fontSize: 12)),
      ),
    ).animate().fadeIn(delay: 300.ms);
  }

  Widget _buildStatusCard(bool estaConectado, String provedorAtual) {
    return AnimatedContainer(
      duration: 500.ms,
      padding: const EdgeInsets.all(AppEspacamento.lg),
      decoration: BoxDecoration(
        color: estaConectado ? AppCores.primario.withOpacity(0.05) : AppCores.fundo(context),
        borderRadius: BorderRadius.circular(AppBordas.raioGrande),
        boxShadow: [
          BoxShadow(
            color: AppCores.primario.withOpacity(0.05),
            blurRadius: 20,
            offset: const Offset(0, 10),
          ),
        ],
        border: Border.all(
          color: estaConectado ? AppCores.primario : AppCores.borda(context),
          width: estaConectado ? 2 : 1,
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
                    "Status da Conexão",
                    style: TextStyle(fontSize: 14, color: AppCores.textoSuave(context)),
                  ),
                  const SizedBox(height: 4),
                  AnimatedSwitcher(
                    duration: 300.ms,
                    child: Text(
                      estaConectado ? "Protegido" : "Desconectado",
                      key: ValueKey(estaConectado),
                      style: TextStyle(
                        fontSize: 24, 
                        fontWeight: FontWeight.bold,
                        color: estaConectado ? AppCores.primario : AppCores.texto(context),
                      ),
                    ),
                  ),
                ],
              ),
              Transform.scale(
                scale: 1.2,
                child: Switch.adaptive(
                  value: estaConectado, 
                  activeColor: AppCores.primario,
                  onChanged: (val) {
                    HapticFeedback.lightImpact();
                    ref.read(dnsStatusProvider.notifier).state = val;
                  },
                ),
              ),
            ],
          ),
          const SizedBox(height: AppEspacamento.xl),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            decoration: BoxDecoration(
              color: AppCores.superficie(context),
              borderRadius: BorderRadius.circular(AppBordas.raioPadrao),
            ),
            child: Row(
              children: [
                Icon(LucideIcons.globe, color: AppCores.secundario, size: 20),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    "Provedor Ativo: $provedorAtual",
                    style: const TextStyle(fontWeight: FontWeight.w600),
                  ),
                ),
                if (estaConectado)
                  Icon(LucideIcons.shield_check, color: AppCores.sucesso, size: 20)
                      .animate()
                      .scale(duration: 400.ms)
                      .shimmer(delay: 500.ms),
              ],
            ),
          ),
        ],
      ),
    ).animate().fadeIn(duration: 700.ms).scale(begin: const Offset(0.95, 0.95));
  }

  Widget _buildProviderCard(String nome, String ips, IconData icone, VoidCallback onTap) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(AppBordas.raioPadrao),
        child: AnimatedContainer(
          duration: 300.ms,
          padding: const EdgeInsets.all(AppEspacamento.md),
          decoration: BoxDecoration(
            color: AppCores.fundo(context),
            borderRadius: BorderRadius.circular(AppBordas.raioPadrao),
            border: Border.all(color: AppCores.borda(context)),
          ),
          child: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: AppCores.primario.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(AppBordas.raioPadrao),
                ),
                child: Icon(icone, color: AppCores.primario, size: 24),
              ),
              const SizedBox(width: AppEspacamento.md),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      nome,
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 17),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      ips,
                      style: TextStyle(color: AppCores.textoSuave(context), fontSize: 13),
                    ),
                  ],
                ),
              ),
              Icon(LucideIcons.chevron_right, size: 18, color: AppCores.borda(context)),
            ],
          ),
        ),
      ).animate(onPlay: (controller) => controller.repeat(reverse: true))
          .shimmer(duration: 3.seconds, color: Colors.white.withOpacity(0.05)),
    );
  }

  Widget _buildProxySection() {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(AppEspacamento.lg),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [AppCores.primario, Color(0xFFE31C5F)],
        ),
        borderRadius: BorderRadius.circular(AppBordas.raioGrande),
        boxShadow: [
          BoxShadow(
            color: AppCores.primario.withOpacity(0.3),
            blurRadius: 15,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(LucideIcons.unplug, color: Colors.white, size: 36),
          const SizedBox(height: AppEspacamento.md),
          const Text(
            "Proxies Gratuitos",
            style: TextStyle(
              color: Colors.white, 
              fontSize: 24, 
              fontWeight: FontWeight.bold
            ),
          ),
          const SizedBox(height: 6),
          Text(
            "Navegue com total privacidade usando servidores globais.",
            style: TextStyle(color: Colors.white.withOpacity(0.8), fontSize: 15),
          ),
          const SizedBox(height: AppEspacamento.lg),
          ElevatedButton(
            onPressed: () {
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
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.white,
              foregroundColor: AppCores.primario,
              elevation: 0,
              padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 14),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(AppBordas.raioPadrao),
              ),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Text("Explorar Proxies"),
                const SizedBox(width: 8),
                Icon(LucideIcons.arrow_right, size: 18),
              ],
            ),
          ),
        ],
      ),
    ).animate().slideY(begin: 0.2, duration: 600.ms, delay: 500.ms).fadeIn();
  }

  Widget _buildBottomNav() {
    return Container(
      decoration: BoxDecoration(
        border: Border(top: BorderSide(color: AppCores.borda(context))),
      ),
      child: BottomNavigationBar(
        elevation: 0,
        backgroundColor: AppCores.fundo(context),
        selectedItemColor: AppCores.primario,
        unselectedItemColor: AppCores.textoSuave(context),
        currentIndex: 0,
        type: BottomNavigationBarType.fixed,
        selectedFontSize: 12,
        unselectedFontSize: 12,
        items: const [
          BottomNavigationBarItem(icon: Icon(Icons.home_outlined), label: "Início"),
          BottomNavigationBarItem(icon: Icon(Icons.settings_outlined), label: "Configurações"),
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
        backgroundColor: AppCores.secundario,
        margin: const EdgeInsets.all(AppEspacamento.md),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
      ),
    );
  }
}
