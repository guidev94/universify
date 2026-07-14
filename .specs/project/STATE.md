# State

**Last updated:** 2026-07-14  
**Session:** Aplicações IA + Spec elevation + polish/responsive — COMPLETE

## Decisions

- Anchor semântico `#aplicacoes` (não manter `#marketing`); atualizar todos os refs CSS/JS/tests.
- Nav label: **Aplicações** (curto); título H2: **Aplicações e Sistema com IA.**
- Manter layout clássico 2 colunas + glow `::after` (continuidade visual).
- Scripts clássicos (sem ES module boot na homepage).
- Push para `origin/master` e `origin/main` (sem force) após commits.
- Lenis: nunca `dispatchEvent('scroll')` no handler `lenis.on('scroll')` — recursão com `onNativeScroll`.

## Blockers

- Nenhum ativo.

## Lessons

- `overflow-x: clip` em seções corta glow vertical — preferir `overflow: visible` + body `overflow-x: hidden`.
- Shell sandbox às vezes falha sem exit status — usar `required_permissions: ["all"]` para git/push.
- Playwright e2e precisa de `serve -l 4173` (config sem webServer).

## Todos

- [x] Spec-Driven artifacts
- [x] Implementar seção Aplicações IA
- [x] Polish + responsive fixes
- [x] Atualizar testes (unit + e2e)
- [x] Fix Lenis scroll stack overflow
- [ ] Commit + push (em andamento)

## Deferred

- Design system completo (~R$50k)
- Tipografia custom (sair do Inter)
- Cases IA com mídia nova
- Blog taxonomy "Marketing" (categoria de conteúdo — fora do swap de seção)
- `webServer` no playwright.config.js

## Preferences

- Commits atômicos 1–2; copy PT premium B2B.
