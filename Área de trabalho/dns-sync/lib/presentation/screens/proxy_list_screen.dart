import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_lucide/flutter_lucide.dart';
import 'package:shimmer/shimmer.dart';
import '../../core/theme/app_theme.dart';
import '../providers/proxy_provider.dart';

class ProxyListScreen extends ConsumerWidget {
  const ProxyListScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final proxiesAsync = ref.watch(listaProxyProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text("Lista de Proxies"),
        actions: [
          IconButton(
            onPressed: () => ref.refresh(listaProxyProvider),
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      body: Column(
        children: [
          // Aviso de Segurança
          _buildSecurityWarning(context),
          
          Expanded(
            child: proxiesAsync.when(
              data: (proxies) => ListView.separated(
                physics: const BouncingScrollPhysics(),
                padding: const EdgeInsets.all(AppEspacamento.md),
                itemCount: proxies.length,
                separatorBuilder: (context, index) => const SizedBox(height: AppEspacamento.md),
                itemBuilder: (context, index) {
                  final proxy = proxies[index];
                  return _buildProxyCard(context, proxy, index);
                },
              ),
              loading: () => _buildSkeletonLoading(context),
              error: (err, stack) => _buildErrorState(context, ref),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSecurityWarning(BuildContext context) {
    return Container(
      width: double.infinity,
      margin: const EdgeInsets.all(AppEspacamento.md),
      padding: const EdgeInsets.all(AppEspacamento.md),
      decoration: BoxDecoration(
        color: AppCores.alerta.withOpacity(0.1),
        borderRadius: BorderRadius.circular(AppBordas.raioPadrao),
        border: Border.all(color: AppCores.alerta.withOpacity(0.3)),
      ),
      child: Row(
        children: [
          const Icon(Icons.warning_amber_rounded, color: AppCores.alerta, size: 20),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              "Atenção: Proxies gratuitos podem ser instáveis e comprometer sua privacidade. Use com cautela.",
              style: TextStyle(
                fontSize: 12, 
                color: AppCores.alerta.withOpacity(0.9),
                fontWeight: FontWeight.w500
              ),
            ),
          ),
        ],
      ),
    ).animate().fadeIn().slideY(begin: -0.2);
  }

  Widget _buildSkeletonLoading(BuildContext context) {
    return ListView.separated(
      padding: const EdgeInsets.all(AppEspacamento.md),
      itemCount: 6,
      separatorBuilder: (context, index) => const SizedBox(height: AppEspacamento.md),
      itemBuilder: (context, index) => Shimmer.fromColors(
        baseColor: AppCores.borda(context),
        highlightColor: AppCores.fundo(context),
        child: Container(
          height: 80,
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(AppBordas.raioPadrao),
          ),
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
            const Icon(Icons.error_outline, size: 64, color: AppCores.primario),
            const SizedBox(height: 20),
            const Text(
              "Não foi possível carregar a lista",
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 10),
            const Text(
              "Verifique sua conexão e tente novamente.",
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

  Widget _buildProxyCard(BuildContext context, dynamic proxy, int index) {
    return Container(
      decoration: BoxDecoration(
        color: AppCores.fundo(context),
        borderRadius: BorderRadius.circular(AppBordas.raioPadrao),
        border: Border.all(color: AppCores.borda(context)),
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: () {
             ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(
                content: Text("Conectando ao proxy ${proxy['ip']}..."),
                behavior: SnackBarBehavior.floating,
                backgroundColor: AppCores.primario,
              ),
            );
          },
          borderRadius: BorderRadius.circular(AppBordas.raioPadrao),
          child: Padding(
            padding: const EdgeInsets.all(AppEspacamento.md),
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: AppCores.secundario.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Icon(LucideIcons.server, color: AppCores.secundario, size: 20),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        "${proxy['ip']}:${proxy['porta']}",
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                      ),
                      const SizedBox(height: 4),
                      Row(
                        children: [
                          Icon(LucideIcons.map_pin, size: 12, color: AppCores.sucesso),
                          const SizedBox(width: 4),
                          Text(
                            proxy['pais'],
                            style: TextStyle(fontSize: 12, color: AppCores.textoSuave(context)),
                          ),
                          const SizedBox(width: 12),
                          Icon(LucideIcons.activity, size: 12, color: AppCores.alerta),
                          const SizedBox(width: 4),
                          Text(
                            "Estável",
                            style: TextStyle(fontSize: 12, color: AppCores.textoSuave(context)),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                Icon(LucideIcons.signal_high, color: AppCores.sucesso, size: 20),
              ],
            ),
          ),
        ),
      ),
    ).animate().fadeIn(delay: (index * 50).ms).slideX(begin: 0.1);
  }
}
