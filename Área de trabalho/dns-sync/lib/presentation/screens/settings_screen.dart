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
                  modoTema == ThemeMode.dark ? "Visual noturno ativado" : "Visual claro ativado",
                  modoTema == ThemeMode.dark ? LucideIcons.moon : LucideIcons.sun,
                  trailing: Switch.adaptive(
                    value: modoTema == ThemeMode.dark,
                    activeColor: AppCores.primario,
                    onChanged: (val) {
                      HapticFeedback.mediumImpact();
                      ref.read(temaProvider.notifier).state = 
                          val ? ThemeMode.dark : ThemeMode.light;
                    },
                  ),
                ),
              ]),
              const SizedBox(height: AppEspacamento.lg),
              _buildModernSection(context, "Performance", [
                _buildModernTile(
                  context,
                  "Teste de Velocidade",
                  "Mede latência e download",
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
                  "Conexão Inteligente",
                  "Escolha automática do servidor",
                  LucideIcons.zap,
                  trailing: Switch.adaptive(
                    value: false,
                    activeColor: AppCores.primario,
                    onChanged: (val) {},
                  ),
                ),
              ]),
              const SizedBox(height: AppEspacamento.lg),
              _buildModernSection(context, "Sobre", [
                _buildModernTile(
                  context,
                  "Versão",
                  "1.0.0 Premium",
                  LucideIcons.info,
                ),
                _buildModernTile(
                  context,
                  "Desenvolvedor",
                  "Aquinodev",
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
    return ListTile(
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
    );
  }
}
