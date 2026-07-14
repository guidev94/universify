# Roadmap

**Current Milestone:** Elevação LP — Aplicações IA + Polish Responsivo  
**Status:** Complete

---

## Milestone A — Quick Wins (este ciclo / baixo custo)

**Goal:** Trocar a narrativa de Marketing por IA, corrigir mobile quebrado e elevar craft sem rebuild.  
**Target:** Ship em `master`/`main` (Vercel)

### Features

**Aplicações e Sistema com IA** — COMPLETE

- Renomear seção `#marketing` → `#aplicacoes`
- Copy premium B2B (PT), bullets, CTA, imagem/alt
- Nav desktop/mobile + footer + meta/hero
- Manter glow bleed (`::after`) e layout 2 colunas

**Polish & Responsive Fixes** — COMPLETE

- Tipografia/hierarquia e ritmo vertical consistentes
- Overflow/alinhamento mobile (seções, cards, CTAs)
- Footer typo + hover/CTA consistency
- Fix Lenis scroll recursion (modal stack overflow)
- Testes unit/e2e atualizados (`aplicacoes` em IDs)

**Spec-Driven Artifacts** — COMPLETE

- `.specs/project/*`, `.specs/codebase/*`, feature specs

---

## Milestone B — Elevação High-Level (~R$50k)

**Goal:** LP de nível agência premium / software house top-tier, com motion, conteúdo e sistema de design sustentáveis.  
**Target:** Sprint dedicado (não neste ciclo)

### Features

**Design System & Tipografia Custom** — PLANNED

- Fontes display/corpo proprietárias (fim do Inter default)
- Tokens (spacing, type scale, radii, motion) documentados
- Componentes de botão/card/modal unificados

**Motion System Profissional** — PLANNED

- Timeline hero cinematográfica; scroll storytelling
- Portfolio pin refinements + microinterações acessíveis
- Reduced-motion paths e performance budget

**Conteúdo & Cases AI-First** — PLANNED

- Cases reais de IA / sistemas (copy + mídia)
- Página de serviço IA dedicada ou deep-dive
- SEO/OG estruturado + schema.org Organization/Service

**Produção Visual** — PLANNED

- Fotografia/3D/custom illustrations (não stock genérico)
- Hero video grade cinema + poster otimizado
- Favicon/OG set completo

**QA & Performance** — PLANNED

- Lighthouse ≥90 mobile; CLS/LCP budget
- A11y WCAG 2.1 AA nas superfícies principais
- Visual regression (Playwright screenshots)

---

## Quick Wins vs ~R$50k (resumo)

| Quick wins (agora) | Elevação ~R$50k |
| --- | --- |
| Swap Marketing → Aplicações/IA | Design system + tipografia custom |
| Anchors/nav/footer/meta | Motion system + storytelling |
| Glow continuity preservado | Cases IA + mídia custom |
| Mobile overflow / centering | Foto/3D/hero cinema |
| CTA/hover polish | Perf + a11y + visual QA |

---

## Future Considerations

- Multilíngue (EN)
- Área de clientes / login
- Integração CRM no formulário (hoje WhatsApp)
