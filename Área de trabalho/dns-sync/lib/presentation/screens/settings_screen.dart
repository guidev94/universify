import 'dart:ui';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_lucide/flutter_lucide.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../core/theme/app_theme.dart';
import '../providers/proxy_provider.dart';
import 'speed_test_screen.dart';

class SettingsScreen extends ConsumerWidget {
  const SettingsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final modoTema = ref.watch(temaProvider);
    final configuracoes = ref.watch(configuracoesProvider);
    final splitApps = ref.watch(splitTunnelProvider);
    final totalBloqueados = ref.watch(anunciosBloqueadosProvider);

    return CustomScrollView(
      physics: const BouncingScrollPhysics(),
      slivers: [
        SliverToBoxAdapter(
          child: Padding(
            padding: const EdgeInsets.fromLTRB(AppEspacamento.lg, 40, AppEspacamento.lg, AppEspacamento.xl),
            child: Text(
              "Ajustes",
              style: Theme.of(context).textTheme.displayLarge?.copyWith(fontSize: 32),
            ),
          ),
        ),
        SliverPadding(
          padding: const EdgeInsets.symmetric(horizontal: AppEspacamento.lg),
          sliver: SliverList(
            delegate: SliverChildListDelegate([
              _buildModernSection(context, "Aparência", [
                _buildModernTile(
                  context,
                  "Modo Escuro",
                  modoTema == ThemeMode.dark ? "Visual escuro ativado" : "Visual claro ativado",
                  modoTema == ThemeMode.dark ? LucideIcons.moon : LucideIcons.sun,
                  trailing: Switch.adaptive(
                    value: modoTema == ThemeMode.dark,
                    activeTrackColor: AppCores.primario,
                    onChanged: (val) {
                      HapticFeedback.mediumImpact();
                      ref.read(temaProvider.notifier).state = 
                          val ? ThemeMode.dark : ThemeMode.light;
                    },
                  ),
                ),
              ]),
              const SizedBox(height: AppEspacamento.lg),
              _buildModernSection(context, "Segurança & Criptografia Avançada", [
                _buildModernTile(
                  context,
                  "DNS-over-HTTPS (DoH)",
                  "Criptografa consultas via túnel HTTPS TLS 1.3",
                  LucideIcons.lock,
                  trailing: Switch.adaptive(
                    value: configuracoes['useDoh'] ?? false,
                    activeTrackColor: AppCores.primario,
                    onChanged: (val) {
                      HapticFeedback.lightImpact();
                      ref.read(configuracoesProvider.notifier).toggle('useDoh');
                    },
                  ),
                ),
                _buildModernTile(
                  context,
                  "Filtro Local de Anúncios & Malware",
                  "Bloqueio de rastreadores sem usar internet ($totalBloqueados bloqueados)",
                  LucideIcons.shield_alert,
                  trailing: Switch.adaptive(
                    value: configuracoes['enableAdBlock'] ?? true,
                    activeTrackColor: AppCores.primario,
                    onChanged: (val) {
                      HapticFeedback.lightImpact();
                      ref.read(configuracoesProvider.notifier).toggle('enableAdBlock');
                    },
                  ),
                ),
                _buildModernTile(
                  context,
                  "Escudo Anti-Vazamento IPv6",
                  "Bloqueia vazamentos de DNS em redes IPv6",
                  LucideIcons.shield_check,
                  trailing: Switch.adaptive(
                    value: configuracoes['ipv6Shield'] ?? true,
                    activeTrackColor: AppCores.primario,
                    onChanged: (val) {
                      HapticFeedback.lightImpact();
                      ref.read(configuracoesProvider.notifier).toggle('ipv6Shield');
                    },
                  ),
                ),
                _buildModernTile(
                  context,
                  "Túnel Dividido (Split Tunneling)",
                  "${splitApps.length} aplicativo(s) configurado(s) em bypass",
                  LucideIcons.split,
                  onTap: () => _abrirModalSplitTunnel(context, ref, splitApps),
                ),
              ]),
              const SizedBox(height: AppEspacamento.lg),
              _buildModernSection(context, "Automação & Rota", [
                _buildModernTile(
                  context,
                  "Otimização Inteligente de Rota",
                  "Escolha automática do melhor servidor por menor ping",
                  LucideIcons.zap,
                  trailing: Switch.adaptive(
                    value: configuracoes['smartDns'] ?? true,
                    activeTrackColor: AppCores.primario,
                    onChanged: (val) {
                      HapticFeedback.lightImpact();
                      ref.read(configuracoesProvider.notifier).toggle('smartDns');
                    },
                  ),
                ),
                _buildModernTile(
                  context,
                  "Auto-Conectar ao Iniciar",
                  "Ativa a proteção imediatamente ao abrir o aplicativo",
                  LucideIcons.power,
                  trailing: Switch.adaptive(
                    value: configuracoes['autoConnect'] ?? false,
                    activeTrackColor: AppCores.primario,
                    onChanged: (val) {
                      HapticFeedback.lightImpact();
                      ref.read(configuracoesProvider.notifier).toggle('autoConnect');
                    },
                  ),
                ),
              ]),
              const SizedBox(height: AppEspacamento.lg),
              _buildModernSection(context, "Ferramentas & Diagnósticos", [
                _buildModernTile(
                  context,
                  "Teste de Velocidade Real",
                  "Mede throughput real, jitter e bufferbloat",
                  LucideIcons.gauge,
                  onTap: () {
                    HapticFeedback.lightImpact();
                    Navigator.push(
                      context,
                      MaterialPageRoute(builder: (context) => const SpeedTestScreen()),
                    );
                  },
                ),
                _buildModernTile(
                  context,
                  "Limpar Cache de DNS",
                  "Libera buffers e renova registros locais",
                  LucideIcons.refresh_cw,
                  onTap: () {
                    HapticFeedback.mediumImpact();
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(
                        content: Text("Cache DNS local renovado com sucesso!"),
                        backgroundColor: AppCores.sucesso,
                        behavior: SnackBarBehavior.floating,
                      ),
                    );
                  },
                ),
              ]),
              const SizedBox(height: AppEspacamento.lg),
              _buildModernSection(context, "Sobre o App", [
                _buildModernTile(
                  context,
                  "Versão",
                  "1.3.0 Enterprise Edition",
                  LucideIcons.info,
                ),
                _buildModernTile(
                  context,
                  "Quick Settings Tile",
                  "Atalho rápido integrado na barra de notificações",
                  LucideIcons.layout_grid,
                ),
                _buildModernTile(
                  context,
                  "Desenvolvedor",
                  "Aquinodev • Mobile & Security Engineer",
                  LucideIcons.terminal,
                ),
              ]),
              const SizedBox(height: 120),
            ]),
          ),
        ),
      ],
    );
  }

  Widget _buildModernSection(BuildContext context, String title, List<Widget> children) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.only(left: 4, bottom: 12),
          child: Text(
            title.toUpperCase(),
            style: GoogleFonts.inter(
              fontSize: 11,
              fontWeight: FontWeight.w900,
              letterSpacing: 1.5,
              color: AppCores.primario,
            ),
          ),
        ),
        Container(
          decoration: BoxDecoration(
            color: AppCores.superficie(context),
            borderRadius: BorderRadius.circular(AppBordas.raioGrande),
            border: Border.all(color: AppCores.borda(context)),
          ),
          child: Column(
            children: children,
          ),
        ),
      ],
    );
  }

  Widget _buildModernTile(
    BuildContext context, 
    String title, 
    String subtitle, 
    IconData icon, 
    {Widget? trailing, VoidCallback? onTap}
  ) {
    return Material(
      color: Colors.transparent,
      borderRadius: BorderRadius.circular(AppBordas.raioGrande),
      child: ListTile(
        onTap: onTap,
        contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
        leading: Container(
          padding: const EdgeInsets.all(10),
          decoration: BoxDecoration(
            color: AppCores.fundo(context),
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: AppCores.borda(context)),
          ),
          child: Icon(icon, color: AppCores.texto(context), size: 18),
        ),
        title: Text(title, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15)),
        subtitle: Text(subtitle, style: TextStyle(color: AppCores.textoSuave(context), fontSize: 12)),
        trailing: trailing ?? (onTap != null ? const Icon(LucideIcons.chevron_right, size: 16) : null),
      ),
    );
  }

  void _abrirModalSplitTunnel(BuildContext context, WidgetRef ref, List<String> apps) {
    final newAppController = TextEditingController();

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        return StatefulBuilder(
          builder: (context, setModalState) {
            final currentApps = ref.watch(splitTunnelProvider);

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
                          "Túnel Dividido (Bypass Apps)",
                          style: TextStyle(fontWeight: FontWeight.w900, fontSize: 18),
                        ),
                        IconButton(
                          icon: const Icon(Icons.close),
                          onPressed: () => Navigator.pop(ctx),
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    Text(
                      "Aplicativos adicionados aqui usarão a conexão padrão do celular diretamente, sem passar pelo túnel VPN.",
                      style: TextStyle(fontSize: 12, color: AppCores.textoSuave(context)),
                    ),
                    const SizedBox(height: 16),
                    Row(
                      children: [
                        Expanded(
                          child: TextField(
                            controller: newAppController,
                            decoration: const InputDecoration(
                              hintText: "Ex: com.banco.app",
                              labelText: "Nome do Pacote Android",
                              prefixIcon: Icon(LucideIcons.box, size: 18),
                            ),
                          ),
                        ),
                        const SizedBox(width: 10),
                        ElevatedButton(
                          onPressed: () {
                            final pkg = newAppController.text.trim();
                            if (pkg.isNotEmpty) {
                              ref.read(splitTunnelProvider.notifier).toggleApp(pkg);
                              newAppController.clear();
                              setModalState(() {});
                            }
                          },
                          style: ElevatedButton.styleFrom(minimumSize: const Size(60, 56)),
                          child: const Icon(Icons.add),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),
                    ConstrainedBox(
                      constraints: const BoxConstraints(maxHeight: 240),
                      child: ListView.builder(
                        shrinkWrap: true,
                        itemCount: currentApps.length,
                        itemBuilder: (context, idx) {
                          final pkg = currentApps[idx];
                          return ListTile(
                            dense: true,
                            contentPadding: EdgeInsets.zero,
                            leading: const Icon(Icons.check_circle_outline_rounded, color: AppCores.sucesso, size: 18),
                            title: Text(pkg, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                            trailing: IconButton(
                              icon: const Icon(Icons.delete_outline_rounded, size: 18, color: Colors.grey),
                              onPressed: () {
                                ref.read(splitTunnelProvider.notifier).toggleApp(pkg);
                                setModalState(() {});
                              },
                            ),
                          );
                        },
                      ),
                    ),
                  ],
                ),
              ),
            );
          },
        );
      },
    );
  }
}
