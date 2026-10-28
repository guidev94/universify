import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:package_info_plus/package_info_plus.dart';

import 'about_page.dart';
import 'game_page.dart';
import 'settings_controller.dart';
import 'settings_page.dart';
import 'theme.dart';
import 'ui_kit.dart';

class HomePage extends StatefulWidget {
  const HomePage({super.key, required this.settings});

  final SettingsController settings;

  @override
  State<HomePage> createState() => _HomePageState();
}

class _HomePageState extends State<HomePage> with SingleTickerProviderStateMixin {
  late final AnimationController _entrance;
  String _version = '1.3';

  @override
  void initState() {
    super.initState();
    _entrance = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 820),
    )..forward();
    unawaited(_loadVersion());
  }

  Future<void> _loadVersion() async {
    try {
      final info = await PackageInfo.fromPlatform();
      if (mounted) setState(() => _version = info.version);
    } catch (_) {
      // Mantém a versão exibida como fallback.
    }
  }

  @override
  void dispose() {
    _entrance.dispose();
    super.dispose();
  }

  void _openGame({bool openMenu = false}) {
    unawaited(HapticFeedback.mediumImpact());
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => GamePage(settings: widget.settings, openMenu: openMenu),
      ),
    );
  }

  void _openSettings() {
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => SettingsPage(settings: widget.settings),
      ),
    );
  }

  void _openAbout() {
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => AboutPage(version: _version),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final fade = CurvedAnimation(parent: _entrance, curve: const Interval(0, 0.75, curve: Curves.easeOut));
    final slide = Tween<Offset>(
      begin: const Offset(0, 0.055),
      end: Offset.zero,
    ).animate(CurvedAnimation(parent: _entrance, curve: Curves.easeOutCubic));

    return Scaffold(
      body: Stack(
        children: [
          const Positioned.fill(child: LumiBackdrop()),
          SafeArea(
            child: LayoutBuilder(
              builder: (context, constraints) {
                return SingleChildScrollView(
                  physics: const ClampingScrollPhysics(),
                  padding: const EdgeInsets.fromLTRB(24, 18, 24, 20),
                  child: ConstrainedBox(
                    constraints: BoxConstraints(
                      minHeight: constraints.maxHeight - 38,
                    ),
                    child: Center(
                      child: ConstrainedBox(
                        constraints: const BoxConstraints(maxWidth: 470),
                        child: FadeTransition(
                          opacity: fade,
                          child: SlideTransition(
                            position: slide,
                            child: Column(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                const _HeroBadge(),
                                const SizedBox(height: 26),
                                const _AppMark(),
                                const SizedBox(height: 24),
                                Text(
                                  LumiBranding.appName,
                                  textAlign: TextAlign.center,
                                  style: Theme.of(context).textTheme.displayLarge,
                                ),
                                const SizedBox(height: 10),
                                const Text(
                                  LumiBranding.tagline,
                                  textAlign: TextAlign.center,
                                  style: TextStyle(
                                    fontSize: 15.5,
                                    height: 1.45,
                                    color: LumiColors.muted,
                                  ),
                                ),
                                const SizedBox(height: 30),
                                LumiButton(
                                  label: 'Jogar agora',
                                  hint: 'Continua de onde você parou',
                                  icon: Icons.play_arrow_rounded,
                                  onPressed: () => _openGame(),
                                ),
                                const SizedBox(height: 12),
                                LumiButton(
                                  label: 'Escolher mundo',
                                  hint: 'Criar, importar ou visitar',
                                  icon: Icons.grid_view_rounded,
                                  primary: false,
                                  onPressed: () => _openGame(openMenu: true),
                                ),
                                const SizedBox(height: 12),
                                Row(
                                  children: [
                                    Expanded(
                                      child: LumiTileButton(
                                        label: 'Ajustes',
                                        icon: Icons.tune_rounded,
                                        onPressed: _openSettings,
                                      ),
                                    ),
                                    const SizedBox(width: 12),
                                    Expanded(
                                      child: LumiTileButton(
                                        label: 'Sobre',
                                        icon: Icons.auto_awesome_rounded,
                                        onPressed: _openAbout,
                                      ),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 22),
                                const _MobileHint(),
                                const SizedBox(height: 26),
                                _GithubPill(
                                  onTap: () => openExternalLink(
                                    context,
                                    LumiBranding.githubUrl,
                                  ),
                                ),
                                const SizedBox(height: 10),
                                LumiCopyright(version: _version),
                              ],
                            ),
                          ),
                        ),
                      ),
                    ),
                  ),
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}

class _HeroBadge extends StatelessWidget {
  const _HeroBadge();

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(999),
        color: LumiColors.surface.withValues(alpha: 0.7),
        border: Border.all(color: LumiColors.outline),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 7,
            height: 7,
            decoration: const BoxDecoration(
              shape: BoxShape.circle,
              color: LumiColors.primary,
              boxShadow: [
                BoxShadow(color: LumiColors.primary, blurRadius: 8),
              ],
            ),
          ),
          const SizedBox(width: 9),
          const Text(
            'COZY SANDBOX · SEM COMBATE',
            style: TextStyle(
              fontSize: 10.5,
              fontWeight: FontWeight.w800,
              letterSpacing: 1.05,
              color: LumiColors.muted,
            ),
          ),
        ],
      ),
    );
  }
}

class _AppMark extends StatelessWidget {
  const _AppMark();

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(8),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(34),
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [
            LumiColors.primary.withValues(alpha: 0.5),
            LumiColors.sky.withValues(alpha: 0.22),
            Colors.white.withValues(alpha: 0.05),
          ],
        ),
        boxShadow: [
          BoxShadow(
            color: LumiColors.primary.withValues(alpha: 0.16),
            blurRadius: 40,
            spreadRadius: 2,
          ),
        ],
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(27),
        child: Image.asset(
          'assets/branding/icon.png',
          width: 128,
          height: 128,
          semanticLabel: 'Ícone do Lulicraft',
        ),
      ),
    );
  }
}

class _MobileHint extends StatelessWidget {
  const _MobileHint();

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 11),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(16),
        color: LumiColors.surface.withValues(alpha: 0.4),
        border: Border.all(color: LumiColors.outline),
      ),
      child: const Row(
        children: [
          Icon(Icons.smartphone_rounded, size: 18, color: LumiColors.sky),
          SizedBox(width: 10),
          Expanded(
            child: Text(
              'Botões grandes, joystick e troca de visão com um toque. '
              'Gire o celular para uma tela maior.',
              style: TextStyle(
                fontSize: 12.2,
                height: 1.4,
                color: LumiColors.muted,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _GithubPill extends StatelessWidget {
  const _GithubPill({required this.onTap});

  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(999),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(999),
            color: Colors.white.withValues(alpha: 0.04),
            border: Border.all(color: LumiColors.outline),
          ),
          child: const Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(Icons.code_rounded, size: 15, color: LumiColors.muted),
              SizedBox(width: 7),
              Text(
                'github.com/guidev94',
                style: TextStyle(
                  fontSize: 12.5,
                  fontWeight: FontWeight.w600,
                  color: LumiColors.muted,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
