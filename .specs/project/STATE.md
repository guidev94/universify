# State

**Last updated:** 2026-07-14  
**Session:** Spec quick-win elevation + IA image + Sobre/Missao glow seam

## Decisions

- Anchor semântico `#aplicacoes` (não manter `#marketing`).
- Nav label: **Aplicações**; H2: **Aplicações e Sistema com IA.**
- Scripts clássicos; Lenis sem `dispatchEvent('scroll')`.
- Push `origin/master` + `origin/main` (sem force).
- `#missao` background **transparent** so `#sobre::after` bottom bleed is not covered by an opaque paint box.
- IA visual: local `assets/img/aplicacoes-ia.jpg` (WV AI-first asset), not Gemini political infographic.

## Blockers

- Nenhum ativo.

## Lessons

- Opaque sibling `background-color` covers overflowing section `::after` glow — use transparent paint box on the lower section + upward bleed on its own `::after`.
- Soft masks that fade too early at the bottom of `::after` hide the seam glow.

## Todos

- [x] Spec-Driven artifacts
- [x] Aplicações IA section + anchors
- [x] Polish / responsive / Lenis fix
- [x] Hero/contato screenshot fixes
- [x] Spec quick-win elevation (type hierarchy, CTA magnetic consistency, reduced-motion)
- [x] IA section image swap + presentation
- [x] Sobre→Missao glow seam fix
- [ ] Commit + push (em andamento)

## Deferred

- Design system / tipografia custom (~R$50k)
- Cases IA com mídia custom nova
- Blog taxonomy "Marketing"
- `webServer` no playwright.config.js

## Preferences

- Commits atômicos; copy PT premium B2B.
