import 'dart:async';

import 'package:flutter/material.dart';

import 'game_settings.dart';
import 'settings_controller.dart';
import 'theme.dart';
import 'ui_kit.dart';

class SettingsPage extends StatefulWidget {
  const SettingsPage({super.key, required this.settings});

  final SettingsController settings;

  @override
  State<SettingsPage> createState() => _SettingsPageState();
}

class _SettingsPageState extends State<SettingsPage> {
  double? _fovPreview;
  double? _touchPreview;

  GameSettings get _value => widget.settings.value;
  double get _fov => _fovPreview ?? _value.fov;
  double get _touch => _touchPreview ?? _value.touchSensitivity;

  void _update(GameSettings next) {
    unawaited(widget.settings.update(next));
  }

  Future<void> _confirmReset() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Restaurar padrões?'),
        content: const Text(
          'Som, câmera, controles e interface voltam ao jeito original. '
          'Seu personagem e seus mundos continuam salvos.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(false),
            child: const Text('Cancelar'),
          ),
          FilledButton(
            onPressed: () => Navigator.of(context).pop(true),
            child: const Text('Restaurar'),
          ),
        ],
      ),
    );
    if (confirmed != true) return;
    await widget.settings.reset();
    if (!mounted) return;
    setState(() {
      _fovPreview = null;
      _touchPreview = null;
    });
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Configurações restauradas para o padrão.')),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Stack(
        children: [
          const Positioned.fill(child: LumiBackdrop()),
          SafeArea(
            child: AnimatedBuilder(
              animation: widget.settings,
              builder: (context, _) {
                return Column(
                  children: [
                    LumiPageHeader(
                      title: 'Ajustes',
                      subtitle: 'Salvos neste aparelho',
                      trailing: LumiRoundIconButton(
                        icon: Icons.restart_alt_rounded,
                        tooltip: 'Restaurar padrões',
                        onPressed: _confirmReset,
                      ),
                    ),
                    Expanded(
                      child: ListView(
                        padding: const EdgeInsets.fromLTRB(16, 4, 16, 28),
                        children: [
                          _Section(
                            icon: Icons.graphic_eq_rounded,
                            title: 'Áudio e câmera',
                            children: [
                              _SwitchRow(
                                icon: Icons.volume_up_rounded,
                                title: 'Som',
                                subtitle: 'Efeitos e trilha suave',
                                value: _value.sound,
                                onChanged: (next) => _update(_value.copyWith(sound: next)),
                              ),
                              const Divider(),
                              _SliderRow(
                                icon: Icons.landscape_rounded,
                                title: 'Campo de visão',
                                subtitle: 'Quanto do mundo aparece na tela',
                                value: _fov,
                                min: 60,
                                max: 100,
                                divisions: 40,
                                display: '${_fov.round()}°',
                                onChanged: (next) => setState(() => _fovPreview = next),
                                onChangeEnd: (next) {
                                  _update(_value.copyWith(fov: next));
                                  setState(() => _fovPreview = null);
                                },
                              ),
                              const Divider(),
                              _SliderRow(
                                icon: Icons.touch_app_rounded,
                                title: 'Sensibilidade do toque',
                                subtitle: 'Velocidade de giro da câmera no celular',
                                value: _touch,
                                min: 0.6,
                                max: 2.6,
                                divisions: 20,
                                display: '${_touch.toStringAsFixed(1)}×',
                                onChanged: (next) => setState(() => _touchPreview = next),
                                onChangeEnd: (next) {
                                  _update(_value.copyWith(touchSensitivity: next));
                                  setState(() => _touchPreview = null);
                                },
                              ),
                              const Divider(),
                              _SwitchRow(
                                icon: Icons.swap_vert_rounded,
                                title: 'Inverter vertical',
                                subtitle: 'Inverte o olhar para cima e para baixo',
                                value: _value.invertY,
                                onChanged: (next) => _update(_value.copyWith(invertY: next)),
                              ),
                              const Divider(),
                              _SwitchRow(
                                icon: Icons.vibration_rounded,
                                title: 'Balanço de câmera',
                                subtitle: 'Movimento suave ao caminhar',
                                value: _value.viewBob,
                                onChanged: (next) => _update(_value.copyWith(viewBob: next)),
                              ),
                            ],
                          ),
                          const SizedBox(height: 14),
                          _Section(
                            icon: Icons.sports_esports_rounded,
                            title: 'Controles',
                            children: [
                              _SwitchRow(
                                icon: Icons.back_hand_rounded,
                                title: 'Modo canhoto',
                                subtitle: 'Espelha joystick e botões para a esquerda',
                                value: _value.leftHand,
                                onChanged: (next) => _update(_value.copyWith(leftHand: next)),
                              ),
                              const Divider(),
                              _SwitchRow(
                                icon: Icons.visibility_rounded,
                                title: 'Começar em 3ª pessoa',
                                subtitle: 'Cada mundo abre vendo o personagem',
                                value: _value.startThird,
                                onChanged: (next) => _update(_value.copyWith(startThird: next)),
                              ),
                              const Divider(),
                              const _InfoRow(
                                icon: Icons.swap_horiz_rounded,
                                title: 'Trocar de visão',
                                subtitle: 'Use o botão 1ª/3ª no canto da tela durante o jogo',
                              ),
                            ],
                          ),
                          const SizedBox(height: 14),
                          _Section(
                            icon: Icons.dashboard_customize_rounded,
                            title: 'Interface',
                            children: [
                              _SwitchRow(
                                icon: Icons.speed_rounded,
                                title: 'Mostrar FPS',
                                subtitle: 'Taxa de quadros no canto da tela',
                                value: _value.showFps,
                                onChanged: (next) => _update(_value.copyWith(showFps: next)),
                              ),
                              const Divider(),
                              _SwitchRow(
                                icon: Icons.pin_drop_rounded,
                                title: 'Mostrar coordenadas',
                                subtitle: 'X, Y e Z no topo da tela',
                                value: _value.showCoords,
                                onChanged: (next) => _update(_value.copyWith(showCoords: next)),
                              ),
                              const Divider(),
                              _ChoiceRow<InterfaceScale>(
                                icon: Icons.text_fields_rounded,
                                title: 'Escala da interface',
                                subtitle: 'Tamanho dos botões e textos do jogo',
                                value: _value.uiScale,
                                options: InterfaceScale.values,
                                labelFor: (entry) => entry.label,
                                onChanged: (next) => _update(_value.copyWith(uiScale: next)),
                              ),
                              const Divider(),
                              _ChoiceRow<GraphicsQuality>(
                                icon: Icons.auto_awesome_mosaic_rounded,
                                title: 'Pacote gráfico',
                                subtitle: 'Automático equilibra beleza e desempenho',
                                value: _value.quality,
                                options: GraphicsQuality.values,
                                labelFor: (entry) => entry.label,
                                onChanged: (next) => _update(_value.copyWith(quality: next)),
                              ),
                            ],
                          ),
                          const SizedBox(height: 14),
                          _Section(
                            icon: Icons.blur_on_rounded,
                            title: 'Efeitos',
                            children: [
                              _SwitchRow(
                                icon: Icons.auto_awesome_rounded,
                                title: 'Partículas de Lumi',
                                subtitle: 'Lumis e faíscas flutuando no ar',
                                value: _value.particles,
                                onChanged: (next) => _update(_value.copyWith(particles: next)),
                              ),
                              const Divider(),
                              _SwitchRow(
                                icon: Icons.cloud_rounded,
                                title: 'Nuvens',
                                subtitle: 'Camada de nuvens no céu',
                                value: _value.clouds,
                                onChanged: (next) => _update(_value.copyWith(clouds: next)),
                              ),
                            ],
                          ),
                          const SizedBox(height: 18),
                          Container(
                            padding: const EdgeInsets.all(16),
                            decoration: BoxDecoration(
                              borderRadius: BorderRadius.circular(18),
                              color: LumiColors.surface.withValues(alpha: 0.45),
                              border: Border.all(color: LumiColors.outline),
                            ),
                            child: const Row(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Icon(Icons.info_outline_rounded, size: 19, color: LumiColors.sky),
                                SizedBox(width: 10),
                                Expanded(
                                  child: Text(
                                    'Estas opções também aparecem dentro do jogo, no botão de engrenagem. '
                                    'Tudo é salvo automaticamente.',
                                    style: TextStyle(
                                      fontSize: 12.5,
                                      height: 1.45,
                                      color: LumiColors.muted,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(height: 18),
                          OutlinedButton.icon(
                            onPressed: _confirmReset,
                            icon: const Icon(Icons.restart_alt_rounded, size: 19),
                            label: const Text('Restaurar configurações padrão'),
                            style: OutlinedButton.styleFrom(
                              foregroundColor: LumiColors.muted,
                              minimumSize: const Size.fromHeight(52),
                              side: const BorderSide(color: LumiColors.outline),
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(16),
                              ),
                            ),
                          ),
                          const SizedBox(height: 20),
                          const LumiCopyright(),
                        ],
                      ),
                    ),
                  ],
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}

class _Section extends StatelessWidget {
  const _Section({
    required this.icon,
    required this.title,
    required this.children,
  });

  final IconData icon;
  final String title;
  final List<Widget> children;

  @override
  Widget build(BuildContext context) {
    return LumiCard(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 6),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, size: 18, color: LumiColors.primary),
              const SizedBox(width: 9),
              Text(
                title,
                style: const TextStyle(
                  fontSize: 15.5,
                  fontWeight: FontWeight.w800,
                  letterSpacing: -0.1,
                ),
              ),
            ],
          ),
          const SizedBox(height: 6),
          ...children.map(
            (child) => child is Divider
                ? const Divider(indent: 42)
                : child,
          ),
          const SizedBox(height: 6),
        ],
      ),
    );
  }
}

class _SwitchRow extends StatelessWidget {
  const _SwitchRow({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.value,
    required this.onChanged,
  });

  final IconData icon;
  final String title;
  final String subtitle;
  final bool value;
  final ValueChanged<bool> onChanged;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 7),
      child: Row(
        children: [
          _RowIcon(icon: icon),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: const TextStyle(fontSize: 14.5, fontWeight: FontWeight.w700),
                ),
                const SizedBox(height: 2),
                Text(
                  subtitle,
                  style: const TextStyle(fontSize: 11.8, color: LumiColors.muted, height: 1.35),
                ),
              ],
            ),
          ),
          const SizedBox(width: 8),
          Switch(value: value, onChanged: onChanged),
        ],
      ),
    );
  }
}

class _SliderRow extends StatelessWidget {
  const _SliderRow({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.value,
    required this.min,
    required this.max,
    required this.divisions,
    required this.display,
    required this.onChanged,
    required this.onChangeEnd,
  });

  final IconData icon;
  final String title;
  final String subtitle;
  final double value;
  final double min;
  final double max;
  final int divisions;
  final String display;
  final ValueChanged<double> onChanged;
  final ValueChanged<double> onChangeEnd;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 7),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              _RowIcon(icon: icon),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      style: const TextStyle(fontSize: 14.5, fontWeight: FontWeight.w700),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      subtitle,
                      style: const TextStyle(fontSize: 11.8, color: LumiColors.muted, height: 1.35),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 8),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(999),
                  color: LumiColors.primary.withValues(alpha: 0.12),
                  border: Border.all(color: LumiColors.primary.withValues(alpha: 0.28)),
                ),
                child: Text(
                  display,
                  style: const TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w800,
                    color: LumiColors.primary,
                  ),
                ),
              ),
            ],
          ),
          Slider(
            value: value.clamp(min, max),
            min: min,
            max: max,
            divisions: divisions,
            label: display,
            onChanged: onChanged,
            onChangeEnd: onChangeEnd,
          ),
        ],
      ),
    );
  }
}

class _ChoiceRow<T> extends StatelessWidget {
  const _ChoiceRow({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.value,
    required this.options,
    required this.labelFor,
    required this.onChanged,
  });

  final IconData icon;
  final String title;
  final String subtitle;
  final T value;
  final List<T> options;
  final String Function(T) labelFor;
  final ValueChanged<T> onChanged;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 9),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              _RowIcon(icon: icon),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      style: const TextStyle(fontSize: 14.5, fontWeight: FontWeight.w700),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      subtitle,
                      style: const TextStyle(fontSize: 11.8, color: LumiColors.muted, height: 1.35),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Row(
            children: [
              for (var index = 0; index < options.length; index++) ...[
                if (index > 0) const SizedBox(width: 8),
                Expanded(
                  child: _ChoiceChip(
                    label: labelFor(options[index]),
                    selected: options[index] == value,
                    onTap: () => onChanged(options[index]),
                  ),
                ),
              ],
            ],
          ),
        ],
      ),
    );
  }
}

class _ChoiceChip extends StatelessWidget {
  const _ChoiceChip({
    required this.label,
    required this.selected,
    required this.onTap,
  });

  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(13),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 160),
          height: 44,
          alignment: Alignment.center,
          padding: const EdgeInsets.symmetric(horizontal: 8),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(13),
            color: selected
                ? LumiColors.primary
                : Colors.white.withValues(alpha: 0.04),
            border: Border.all(
              color: selected
                  ? LumiColors.primary
                  : LumiColors.outline,
            ),
          ),
          child: Text(
            label,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: TextStyle(
              fontSize: 12.2,
              fontWeight: FontWeight.w700,
              color: selected ? const Color(0xFF062016) : LumiColors.muted,
            ),
          ),
        ),
      ),
    );
  }
}

class _InfoRow extends StatelessWidget {
  const _InfoRow({
    required this.icon,
    required this.title,
    required this.subtitle,
  });

  final IconData icon;
  final String title;
  final String subtitle;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(
        children: [
          _RowIcon(icon: icon),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: const TextStyle(fontSize: 14.5, fontWeight: FontWeight.w700),
                ),
                const SizedBox(height: 2),
                Text(
                  subtitle,
                  style: const TextStyle(fontSize: 11.8, color: LumiColors.muted, height: 1.35),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _RowIcon extends StatelessWidget {
  const _RowIcon({required this.icon});

  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 30,
      height: 30,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(9),
        color: Colors.white.withValues(alpha: 0.045),
        border: Border.all(color: LumiColors.outline),
      ),
      child: Icon(icon, size: 16, color: LumiColors.muted),
    );
  }
}
