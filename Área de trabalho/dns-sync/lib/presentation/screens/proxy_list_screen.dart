import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_lucide/flutter_lucide.dart';
import 'package:shimmer/shimmer.dart';
import 'dart:ui';
import '../../core/theme/app_theme.dart';
import '../providers/proxy_provider.dart';

class ProxyListScreen extends ConsumerWidget {
  const ProxyListScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final proxiesAsync = ref.watch(listaProxyProvider);

    return Scaffold(
      body: Stack(
        children: [
          CustomScrollView(
            physics: const BouncingScrollPhysics(),
            slivers: [
              _buildHeader(context),
              
              SliverPadding(
                padding: const EdgeInsets.all(AppEspacamento.lg),
                sliver: SliverList(
                  delegate: SliverChildListDelegate([
                    _buildSecurityAlert(context),
                    const SizedBox(height: AppEspacamento.lg),
                  ]),
                ),
              ),

              proxiesAsync.when(
                data: (proxies) => SliverPadding(
                  padding: const EdgeInsets.symmetric(horizontal: AppEspacamento.lg),
                  sliver: SliverList(
                    delegate: SliverChildBuilderDelegate(
                      (context, index) => _buildModernProxyCard(context, proxies[index], index),
                      childCount: proxies.length,
                    ),
                  ),
                ),
                loading: () => SliverPadding(
                  padding: const EdgeInsets.symmetric(horizontal: AppEspacamento.lg),
                  sliver: SliverList(
                    delegate: SliverChildBuilderDelegate(
                      (context, index) => _buildSkeletonCard(context),
                      childCount: 6,
                    ),
                  ),
                ),
                error: (err, stack) => SliverFillRemaining(
                  child: _buildErrorState(context, ref),
                ),
              ),
              const SizedBox(height: 100),
            ],
          ),

          // Custom Floating Back Button
          Positioned(
            top: 50,
            left: 20,
            child: _buildBackButton(context),
          ),
        ],
      ),
    );
  }

  Widget _buildHeader(BuildContext context) {
    return SliverToBoxAdapter(
      child: Container(
        padding: const EdgeInsets.fromLTRB(AppEspacamento.lg, 120, AppEspacamento.lg, AppEspacamento.xl),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              "Servidores",
              style: TextStyle(
                fontSize: 14, 
                fontWeight: FontWeight.w800, 
                letterSpacing: 2, 
                color: AppCores.primario
              ),
            ),
            const SizedBox(height: 8),
            Text(
              "Lista de Proxies",
              style: Theme.of(context).textTheme.displayLarge?.copyWith(fontSize: 32),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildBackButton(BuildContext context) {
    return ClipRRect(
      borderRadius: BorderRadius.circular(AppBordas.raioPadrao),
      child: BackdropFilter(
        filter: ImageFilter.blur(sigmaX: 10, sigmaY: 10),
        child: Container(
          decoration: BoxDecoration(
            color: AppCores.superficie(context).withOpacity(0.8),
            borderRadius: BorderRadius.circular(AppBordas.raioPadrao),
            border: Border.all(color: AppCores.borda(context)),
          ),
          child: IconButton(
            icon: const Icon(LucideIcons.chevron_left),
            onPressed: () => Navigator.pop(context),
          ),
        ),
      ),
    ).animate().fadeIn().slideX(begin: -0.2);
  }

  Widget _buildSecurityAlert(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(AppEspacamento.md),
      decoration: BoxDecoration(
        color: AppCores.alerta.withOpacity(0.05),
        borderRadius: BorderRadius.circular(AppBordas.raioPadrao),
        border: Border.all(color: AppCores.alerta.withOpacity(0.2)),
      ),
      child: Row(
        children: [
          const Icon(LucideIcons.shield_alert, color: AppCores.alerta, size: 20),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              "Use proxies apenas para navegação anônima básica. Evite dados sensíveis.",
              style: TextStyle(fontSize: 12, color: AppCores.alerta, fontWeight: FontWeight.w500),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildModernProxyCard(BuildContext context, dynamic proxy, int index) {
    return Container(
      margin: const EdgeInsets.only(bottom: AppEspacamento.md),
      decoration: BoxDecoration(
        color: AppCores.superficie(context),
        borderRadius: BorderRadius.circular(AppBordas.raioGrande),
        border: Border.all(color: AppCores.borda(context)),
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: () {
            HapticFeedback.selectionClick();
             ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(
                content: Text("Conectando ao proxy ${proxy['ip']}..."),
                behavior: SnackBarBehavior.floating,
                backgroundColor: AppCores.primario,
              ),
            );
          },
          borderRadius: BorderRadius.circular(AppBordas.raioGrande),
          child: Padding(
            padding: const EdgeInsets.all(AppEspacamento.lg),
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: AppCores.primario.withOpacity(0.1),
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(LucideIcons.server, color: AppCores.primario, size: 22),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        "${proxy['ip']}:${proxy['porta']}",
                        style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 17),
                      ),
                      const SizedBox(height: 4),
                      Row(
                        children: [
                          Icon(LucideIcons.globe, size: 12, color: AppCores.textoSuave(context)),
                          const SizedBox(width: 4),
                          Text(
                            proxy['pais'],
                            style: TextStyle(fontSize: 12, color: AppCores.textoSuave(context)),
                          ),
                          const SizedBox(width: 12),
                          const Icon(LucideIcons.check_circle_2, size: 12, color: AppCores.sucesso),
                          const SizedBox(width: 4),
                          Text(
                            "Online",
                            style: TextStyle(fontSize: 12, color: AppCores.sucesso, fontWeight: FontWeight.bold),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                const Icon(LucideIcons.chevron_right, size: 18, color: AppCores.bordaClaro),
              ],
            ),
          ),
        ),
      ),
    ).animate().fadeIn(delay: (index * 50).ms).slideX(begin: 0.1);
  }

  Widget _buildSkeletonCard(BuildContext context) {
    return Shimmer.fromColors(
      baseColor: AppCores.borda(context),
      highlightColor: AppCores.fundo(context),
      child: Container(
        height: 90,
        margin: const EdgeInsets.only(bottom: AppEspacamento.md),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(AppBordas.raioGrande),
        ),
      ),
    );
  }

  Widget _buildErrorState(BuildContext context, WidgetRef ref) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(AppEspacamento.xl),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(LucideIcons.cloud_off, size: 64, color: AppCores.textoSuaveClaro),
            const SizedBox(height: 20),
            const Text(
              "Indisponível",
              style: TextStyle(fontSize: 22, fontWeight: FontWeight.w900),
            ),
            const SizedBox(height: 10),
            const Text(
              "Não conseguimos carregar os servidores agora.",
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 30),
            ElevatedButton(
              onPressed: () => ref.refresh(listaProxyProvider),
              child: const Text("Tentar Novamente"),
            ),
          ],
        ),
      ),
    );
  }
}
