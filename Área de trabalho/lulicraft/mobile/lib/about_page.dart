import 'package:flutter/material.dart';

import 'theme.dart';
import 'ui_kit.dart';

class AboutPage extends StatelessWidget {
  const AboutPage({super.key, required this.version});

  final String version;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Stack(
        children: [
          const Positioned.fill(child: LumiBackdrop()),
          SafeArea(
            child: Column(
              children: [
                LumiPageHeader(
                  title: 'Sobre',
                  subtitle: 'Lulicraft para celular',
                  trailing: LumiRoundIconButton(
                    icon: Icons.code_rounded,
                    tooltip: 'GitHub de guidev94',
                    onPressed: () => openExternalLink(context, LumiBranding.githubUrl),
                  ),
                ),
                Expanded(
                  child: ListView(
                    padding: const EdgeInsets.fromLTRB(16, 4, 16, 28),
                    children: [
                      LumiCard(
                        child: Column(
                          children: [
                            ClipRRect(
                              borderRadius: BorderRadius.circular(24),
                              child: Image.asset(
                                'assets/branding/icon.png',
                                width: 96,
                                height: 96,
                                semanticLabel: 'Ícone do Lulicraft',
                              ),
                            ),
                            const SizedBox(height: 16),
                            Text(
                              LumiBranding.appName,
                              style: Theme.of(context).textTheme.headlineMedium,
                            ),
                            const SizedBox(height: 6),
                            const Text(
                              LumiBranding.tagline,
                              textAlign: TextAlign.center,
                              style: TextStyle(color: LumiColors.muted, fontSize: 13.5),
                            ),
                            const SizedBox(height: 14),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 5),
                              decoration: BoxDecoration(
                                borderRadius: BorderRadius.circular(999),
                                color: LumiColors.primary.withValues(alpha: 0.12),
                                border: Border.all(
                                  color: LumiColors.primary.withValues(alpha: 0.28),
                                ),
                              ),
                              child: Text(
                                'Versão $version',
                                style: const TextStyle(
                                  fontSize: 11.5,
                                  fontWeight: FontWeight.w800,
                                  color: LumiColors.primary,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 14),
                      const LumiCard(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            _SectionTitle(icon: Icons.auto_awesome_rounded, label: 'A ideia'),
                            SizedBox(height: 10),
                            Text(
                              'Lulicraft é um sandbox de blocos 3D, cozy e familiar. '
                              'Você é um Jardineiro de Lumi: explora biomas, coleta fragmentos '
                              'de luz, acorda cristais adormecidos e devolve cor ao mundo.',
                              style: TextStyle(
                                fontSize: 13.8,
                                height: 1.55,
                                color: LumiColors.muted,
                              ),
                            ),
                            SizedBox(height: 12),
                            Text(
                              'Sem combate, sem morte, sem pressa. Cada mundo nasce de uma '
                              'semente compartilhável e fica salvo no seu aparelho.',
                              style: TextStyle(
                                fontSize: 13.8,
                                height: 1.55,
                                color: LumiColors.muted,
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 14),
                      LumiCard(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const _SectionTitle(icon: Icons.favorite_rounded, label: 'Feito para famílias'),
                            const SizedBox(height: 12),
                            Wrap(
                              spacing: 8,
                              runSpacing: 8,
                              children: const [
                                _Pill(label: 'Sem combate'),
                                _Pill(label: 'Sem anúncios'),
                                _Pill(label: 'Sem contas'),
                                _Pill(label: 'Sem coleta de dados'),
                                _Pill(label: 'Salvo no aparelho'),
                                _Pill(label: 'Controles grandes'),
                              ],
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 14),
                      LumiCard(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const _SectionTitle(icon: Icons.person_rounded, label: 'Criado por'),
                            const SizedBox(height: 8),
                            Text(
                              '${LumiBranding.githubHandle} desenvolve o Lulicraft com carinho, '
                              'de forma independente.',
                              style: const TextStyle(
                                fontSize: 13.5,
                                height: 1.5,
                                color: LumiColors.muted,
                              ),
                            ),
                            const SizedBox(height: 14),
                            SizedBox(
                              width: double.infinity,
                              child: OutlinedButton.icon(
                                onPressed: () => openExternalLink(
                                  context,
                                  LumiBranding.githubUrl,
                                ),
                                icon: const Icon(Icons.code_rounded, size: 19),
                                label: const Text('github.com/guidev94'),
                                style: OutlinedButton.styleFrom(
                                  foregroundColor: LumiColors.primary,
                                  minimumSize: const Size.fromHeight(50),
                                  side: BorderSide(
                                    color: LumiColors.primary.withValues(alpha: 0.3),
                                  ),
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(15),
                                  ),
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 14),
                      const LumiCard(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            _SectionTitle(icon: Icons.inventory_2_rounded, label: 'Créditos'),
                            SizedBox(height: 10),
                            _CreditRow(name: 'Three.js', detail: 'renderização 3D · licença MIT'),
                            _CreditRow(name: 'PeerJS', detail: 'multiplayer entre navegadores · licença MIT'),
                            _CreditRow(name: 'Flutter', detail: 'app mobile · licença BSD-3-Clause'),
                            SizedBox(height: 8),
                            Text(
                              'As licenças de terceiros são preservadas nos arquivos do projeto.',
                              style: TextStyle(
                                fontSize: 11.8,
                                height: 1.45,
                                color: LumiColors.muted,
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 16),
                      const Text(
                        'Lulicraft é um projeto original e independente. '
                        'Não é afiliado à Mojang ou à Microsoft.',
                        textAlign: TextAlign.center,
                        style: TextStyle(
                          fontSize: 11.5,
                          height: 1.5,
                          color: LumiColors.muted,
                        ),
                      ),
                      const SizedBox(height: 16),
                      const LumiCopyright(),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _SectionTitle extends StatelessWidget {
  const _SectionTitle({required this.icon, required this.label});

  final IconData icon;
  final String label;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Icon(icon, size: 18, color: LumiColors.primary),
        const SizedBox(width: 9),
        Text(
          label,
          style: const TextStyle(
            fontSize: 15.5,
            fontWeight: FontWeight.w800,
            letterSpacing: -0.1,
          ),
        ),
      ],
    );
  }
}

class _Pill extends StatelessWidget {
  const _Pill({required this.label});

  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 7),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(999),
        color: Colors.white.withValues(alpha: 0.045),
        border: Border.all(color: LumiColors.outline),
      ),
      child: Text(
        label,
        style: const TextStyle(
          fontSize: 11.5,
          fontWeight: FontWeight.w600,
          color: LumiColors.muted,
        ),
      ),
    );
  }
}

class _CreditRow extends StatelessWidget {
  const _CreditRow({required this.name, required this.detail});

  final String name;
  final String detail;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 5),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 5,
            height: 5,
            margin: const EdgeInsets.only(top: 7, right: 9),
            decoration: const BoxDecoration(
              shape: BoxShape.circle,
              color: LumiColors.primary,
            ),
          ),
          Expanded(
            child: Text.rich(
              TextSpan(
                children: [
                  TextSpan(
                    text: name,
                    style: const TextStyle(
                      fontWeight: FontWeight.w700,
                      color: LumiColors.ink,
                    ),
                  ),
                  TextSpan(
                    text: ' — $detail',
                    style: const TextStyle(color: LumiColors.muted),
                  ),
                ],
              ),
              style: const TextStyle(fontSize: 12.8, height: 1.45),
            ),
          ),
        ],
      ),
    );
  }
}
