import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import 'theme.dart';

Future<void> openExternalLink(BuildContext context, String url) async {
  final uri = Uri.tryParse(url);
  if (uri == null) return;
  try {
    final opened = await launchUrl(uri, mode: LaunchMode.externalApplication);
    if (!opened && context.mounted) {
      _report(context);
    }
  } catch (error) {
    debugPrint('Falha ao abrir $url: $error');
    if (context.mounted) _report(context);
  }
}

void _report(BuildContext context) {
  ScaffoldMessenger.of(context).showSnackBar(
    const SnackBar(content: Text('Não foi possível abrir o link agora.')),
  );
}

class LumiBackdrop extends StatelessWidget {
  const LumiBackdrop({super.key});

  @override
  Widget build(BuildContext context) {
    return const RepaintBoundary(
      child: CustomPaint(
        painter: _BackdropPainter(),
        size: Size.infinite,
      ),
    );
  }
}

class _BackdropPainter extends CustomPainter {
  const _BackdropPainter();

  static const _blocks = <(double, double, double, Color, double)>[
    (0.08, 0.16, 46, LumiColors.primary, 0.09),
    (0.9, 0.24, 64, LumiColors.sky, 0.07),
    (0.16, 0.72, 34, LumiColors.sun, 0.07),
    (0.78, 0.8, 52, LumiColors.primary, 0.06),
    (0.52, 0.06, 28, LumiColors.rose, 0.06),
    (0.36, 0.9, 24, LumiColors.sky, 0.05),
  ];

  @override
  void paint(Canvas canvas, Size size) {
    final rect = Offset.zero & size;

    canvas.drawRect(
      rect,
      Paint()
        ..shader = const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [
            LumiColors.background,
            Color(0xFF08121A),
            Color(0xFF07150F),
          ],
        ).createShader(rect),
    );

    _glow(canvas, rect, Offset(size.width * 0.92, -size.height * 0.08),
        size.shortestSide * 0.95, LumiColors.primary, 0.16);
    _glow(canvas, rect, Offset(-size.width * 0.06, size.height * 0.92),
        size.shortestSide * 0.9, LumiColors.sky, 0.11);

    if (size.shortestSide > 260) {
      final grid = Paint()
        ..color = Colors.white.withValues(alpha: 0.028)
        ..strokeWidth = 1;
      const step = 64.0;
      for (double x = 0; x <= size.width; x += step) {
        canvas.drawLine(Offset(x, 0), Offset(x, size.height), grid);
      }
      for (double y = 0; y <= size.height; y += step) {
        canvas.drawLine(Offset(0, y), Offset(size.width, y), grid);
      }
    }

    final scale = (size.shortestSide / 620).clamp(0.55, 1.6);
    for (final block in _blocks) {
      final side = block.$3 * scale;
      final center = Offset(size.width * block.$1, size.height * block.$2);
      canvas.drawRRect(
        RRect.fromRectAndRadius(
          Rect.fromCenter(center: center, width: side, height: side),
          Radius.circular(side * 0.24),
        ),
        Paint()..color = block.$4.withValues(alpha: block.$5),
      );
    }
  }

  void _glow(Canvas canvas, Rect rect, Offset center, double radius, Color color, double alpha) {
    canvas.drawRect(
      rect,
      Paint()
        ..shader = RadialGradient(
          colors: [color.withValues(alpha: alpha), Colors.transparent],
        ).createShader(Rect.fromCircle(center: center, radius: radius)),
    );
  }

  @override
  bool shouldRepaint(covariant _BackdropPainter oldDelegate) => false;
}

class LumiCard extends StatelessWidget {
  const LumiCard({
    super.key,
    required this.child,
    this.padding = const EdgeInsets.all(20),
    this.accent,
  });

  final Widget child;
  final EdgeInsetsGeometry padding;
  final Color? accent;

  @override
  Widget build(BuildContext context) {
    final tint = accent ?? LumiColors.primary;
    return Container(
      padding: padding,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(24),
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [
            LumiColors.surface.withValues(alpha: 0.92),
            LumiColors.surfaceHigh.withValues(alpha: 0.72),
          ],
        ),
        border: Border.all(color: tint.withValues(alpha: 0.16)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.28),
            blurRadius: 22,
            offset: const Offset(0, 10),
          ),
        ],
      ),
      child: child,
    );
  }
}

class LumiButton extends StatefulWidget {
  const LumiButton({
    super.key,
    required this.label,
    required this.icon,
    this.hint,
    this.onPressed,
    this.primary = true,
  });

  final String label;
  final String? hint;
  final IconData icon;
  final VoidCallback? onPressed;
  final bool primary;

  @override
  State<LumiButton> createState() => _LumiButtonState();
}

class _LumiButtonState extends State<LumiButton> {
  bool _pressed = false;

  @override
  Widget build(BuildContext context) {
    final enabled = widget.onPressed != null;
    final foreground = widget.primary ? const Color(0xFF062016) : LumiColors.ink;
    return AnimatedScale(
      scale: _pressed ? 0.975 : 1,
      duration: const Duration(milliseconds: 120),
      curve: Curves.easeOut,
      child: Semantics(
        button: true,
        label: widget.label,
        enabled: enabled,
        child: GestureDetector(
          onTapDown: enabled ? (_) => setState(() => _pressed = true) : null,
          onTapCancel: enabled ? () => setState(() => _pressed = false) : null,
          onTapUp: enabled
              ? (_) {
                  setState(() => _pressed = false);
                  widget.onPressed?.call();
                }
              : null,
          child: Container(
            constraints: const BoxConstraints(minHeight: 64),
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(20),
              gradient: widget.primary
                  ? const LinearGradient(
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                      colors: [Color(0xFFC4FFB8), LumiColors.primary, Color(0xFF6FD9A6)],
                    )
                  : null,
              color: widget.primary ? null : LumiColors.surface.withValues(alpha: 0.86),
              border: widget.primary
                  ? null
                  : Border.all(color: LumiColors.outline, width: 1),
              boxShadow: widget.primary
                  ? [
                      BoxShadow(
                        color: LumiColors.primary.withValues(alpha: 0.22),
                        blurRadius: 26,
                        offset: const Offset(0, 10),
                      ),
                    ]
                  : null,
            ),
            padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 12),
            child: Row(
              children: [
                Container(
                  width: 40,
                  height: 40,
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(13),
                    color: widget.primary
                        ? Colors.black.withValues(alpha: 0.08)
                        : Colors.white.withValues(alpha: 0.05),
                    border: widget.primary
                        ? null
                        : Border.all(color: LumiColors.outline),
                  ),
                  child: Icon(widget.icon, size: 22, color: foreground),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(
                        widget.label,
                        style: TextStyle(
                          fontSize: 16.5,
                          fontWeight: FontWeight.w800,
                          letterSpacing: -0.2,
                          color: foreground,
                        ),
                      ),
                      if (widget.hint != null) ...[
                        const SizedBox(height: 2),
                        Text(
                          widget.hint!,
                          style: TextStyle(
                            fontSize: 12.5,
                            fontWeight: FontWeight.w500,
                            color: foreground.withValues(alpha: widget.primary ? 0.62 : 0.55),
                          ),
                        ),
                      ],
                    ],
                  ),
                ),
                Icon(
                  Icons.chevron_right_rounded,
                  color: foreground.withValues(alpha: 0.55),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class LumiTileButton extends StatelessWidget {
  const LumiTileButton({
    super.key,
    required this.label,
    required this.icon,
    required this.onPressed,
  });

  final String label;
  final IconData icon;
  final VoidCallback onPressed;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      borderRadius: BorderRadius.circular(18),
      child: InkWell(
        onTap: onPressed,
        borderRadius: BorderRadius.circular(18),
        child: Container(
          height: 86,
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(18),
            color: LumiColors.surface.withValues(alpha: 0.7),
            border: Border.all(color: LumiColors.outline),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Icon(icon, color: LumiColors.muted, size: 22),
              Text(
                label,
                style: const TextStyle(
                  fontSize: 14.5,
                  fontWeight: FontWeight.w700,
                  color: LumiColors.ink,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class LumiPageHeader extends StatelessWidget {
  const LumiPageHeader({
    super.key,
    required this.title,
    this.subtitle,
    this.trailing,
  });

  final String title;
  final String? subtitle;
  final Widget? trailing;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 10, 16, 12),
      child: Row(
        children: [
          LumiRoundIconButton(
            icon: Icons.arrow_back_rounded,
            tooltip: 'Voltar',
            onPressed: () => Navigator.of(context).maybePop(),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: const TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.w800,
                    letterSpacing: -0.4,
                  ),
                ),
                if (subtitle != null)
                  Text(
                    subtitle!,
                    style: const TextStyle(fontSize: 12.5, color: LumiColors.muted),
                  ),
              ],
            ),
          ),
          ?trailing,
        ],
      ),
    );
  }
}

class LumiRoundIconButton extends StatelessWidget {
  const LumiRoundIconButton({
    super.key,
    required this.icon,
    required this.tooltip,
    required this.onPressed,
    this.tinted = false,
  });

  final IconData icon;
  final String tooltip;
  final VoidCallback onPressed;
  final bool tinted;

  @override
  Widget build(BuildContext context) {
    return Tooltip(
      message: tooltip,
      child: IconButton(
        onPressed: onPressed,
        icon: Icon(icon, size: 21),
        style: IconButton.styleFrom(
          foregroundColor: tinted ? LumiColors.primary : LumiColors.ink,
          backgroundColor: tinted
              ? LumiColors.primary.withValues(alpha: 0.12)
              : Colors.white.withValues(alpha: 0.05),
          side: BorderSide(color: LumiColors.outline),
          minimumSize: const Size(44, 44),
        ),
      ),
    );
  }
}

class LumiCopyright extends StatelessWidget {
  const LumiCopyright({super.key, this.version});

  final String? version;

  @override
  Widget build(BuildContext context) {
    final versionLabel = version == null
        ? null
        : Text(
            'v$version',
            style: const TextStyle(fontSize: 11.5, color: LumiColors.muted),
          );
    return Row(
      children: [
        Expanded(
          child: Text(
            LumiBranding.copyright,
            style: const TextStyle(fontSize: 11.5, color: LumiColors.muted, height: 1.35),
          ),
        ),
        ?versionLabel,
      ],
    );
  }
}
