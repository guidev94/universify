# WV_Todoz Site v2

**Vision:** Landing page premium da software house WV_Todoz — escuro, accent lima (#CFFF04), GSAP/AOS/Lenis — posicionando a marca como parceira B2B de aplicativos, sistemas e inteligência artificial.
**For:** Decisores de marketing, produto e TI em empresas que precisam de software sob medida e sistemas com IA.
**Solves:** Site institucional genérico/desatualizado que ainda vende "marketing de conversão" em vez da oferta real de engenharia + IA.

## Goals

- Reposicionar a seção antiga de Marketing como **Aplicações e Sistema com IA** (copy premium, anchors corretos, glow contínuo).
- Elevar craft visual (tipografia, espaçamento, CTAs, mobile) sem reinventar a identidade.
- Entregar artefatos Spec-Driven (roadmap quick wins vs elevação ~R$50k) para orientar próximos ciclos.
- Manter stack clássica estável: scripts sem ES-module boot quebrado; portfolio horizontal funcional.

## Tech Stack

**Core:**

- Static HTML/CSS/JS (sem bundler de produção)
- Tailwind CDN + `assets/css/main.css`
- GSAP 3.12 + ScrollTrigger, AOS 2.3, Lenis 1.1
- Phosphor Icons CDN

**Key dependencies:** Playwright (e2e), Node test runner (unit), Vercel static hosting

## Scope

**v1 includes:**

- Spec-Driven completo (project + codebase + features)
- Swap Marketing → Aplicações e Sistema com IA
- Nav/footer/meta/hero alinhados à nova oferta
- Polish tipográfico/espaçamento + fixes responsivos prioritários
- Commits + push para deploy

**Explicitly out of scope:**

- Redesign total da marca / nova paleta
- CMS, blog dinâmico, backend
- Vídeo hero novo ou assets pesados
- Migração para framework (Next/React)

## Constraints

- Timeline: ciclo único (Specify → Design → Tasks → Execute)
- Technical: classic script tags; não quebrar Lenis/GSAP portfolio pin
- Visual: lime + dark; manter pattern de glow bleed entre seções
- Copy: português, voz WV_Todoz premium B2B
