import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_lucide/flutter_lucide.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/theme/app_theme.dart';
import '../providers/proxy_provider.dart';

class SettingsScreen extends ConsumerWidget {
  const SettingsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final modoTema = ref.watch(temaProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text("Configurações"),
      ),
      body: ListView(
        padding: const EdgeInsets.all(AppEspacamento.md),
        children: [
          _buildSectionTitle("Aparência"),
          _buildSettingTile(
            context,
            "Modo Escuro",
            modoTema == ThemeMode.dark ? "Ativado" : "Desativado",
            LucideIcons.moon,
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
          const SizedBox(height: AppEspacamento.lg),
          _buildSectionTitle("Conexão"),
          _buildSettingTile(
            context,
            "Teste de Velocidade",
            "Verificar latência e download",
            LucideIcons.gauge,
            onTap: () {
              HapticFeedback.lightImpact();
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(content: Text("Funcionalidade de Speed Test em breve!")),
              );
            },
          ),
          _buildSettingTile(
            context,
            "Notificações de Status",
            "Mostrar ícone quando ativo",
            LucideIcons.bell,
            trailing: Switch.adaptive(
              value: true,
              activeColor: AppCores.primario,
              onChanged: (val) {},
            ),
          ),
          const SizedBox(height: AppEspacamento.lg),
          _buildSectionTitle("Sobre"),
          _buildSettingTile(
            context,
            "Versão do App",
            "1.0.0+1",
            LucideIcons.info,
          ),
          _buildSettingTile(
            context,
            "Desenvolvedor",
            "Aquinodev",
            LucideIcons.code,
          ),
        ],
      ),
    );
  }

  Widget _buildSectionTitle(String title) {
    return Padding(
      padding: const EdgeInsets.only(left: 4, bottom: 8),
      child: Text(
        title.toUpperCase(),
        style: const TextStyle(
          fontSize: 12,
          fontWeight: FontWeight.bold,
          letterSpacing: 1.2,
          color: AppCores.primario,
        ),
      ),
    );
  }

  Widget _buildSettingTile(
    BuildContext context, 
    String title, 
    String subtitle, 
    IconData icon, 
    {Widget? trailing, VoidCallback? onTap}
  ) {
    return Card(
      margin: const EdgeInsets.only(bottom: AppEspacamento.sm),
      child: ListTile(
        onTap: onTap,
        leading: Icon(icon, color: AppCores.primario, size: 22),
        title: Text(title, style: const TextStyle(fontWeight: FontWeight.w600)),
        subtitle: Text(subtitle, style: TextStyle(color: AppCores.textoSuave(context), fontSize: 13)),
        trailing: trailing ?? (onTap != null ? const Icon(LucideIcons.chevron_right, size: 18) : null),
      ),
    );
  }
}
