# WV_Todoz Site v2

**Vision:** Landing page premium da software house WV_Todoz (escuro, accent lima #CFFF04, GSAP/AOS/Lenis), posicionando a marca como parceira B2B de agentes de IA, automações e integrações de sistemas. Conteúdo gerenciável via WordPress + ACF free.
**For:** Decisores de marketing, produto e TI em empresas que precisam aliviar operação com IA e software sob medida; editores de conteúdo no WP Admin.
**Solves:** Site institucional genérico/desatualizado; e a dor de editar HTML estático sem CMS.

## Goals

- Reposicionar a seção antiga de Marketing como **Aplicações e Sistema com IA**.
- Elevar craft visual sem reinventar a identidade.
- Entregar artefatos Spec-Driven para orientar próximos ciclos.
- **Entregar tema WordPress (`wvtodoz-theme/`) com ACF free + CPTs** para edição via Admin.

## Tech Stack

**Core (estático legado):**

- Static HTML/CSS/JS + Tailwind CDN + GSAP/AOS/Lenis

**Core (CMS):**

- WordPress classic theme `wvtodoz-theme/`
- ACF free (local field groups em PHP)
- CPTs: `wv_case`, `wv_produto`, `wv_cliente`, `wv_servico`

## Scope

**v1 includes:**

- Spec-Driven completo + swap Aplicações IA + polish
- Tema WP completo com seções editáveis + backup estático

**Explicitly out of scope:**

- ACF Pro
- Redesign total da marca
- Multilíngue

## Constraints

- ACF free only (sem Repeater / Options Page / Flexible Content / Gallery)
- Preservar visual do `main.css` atual
- Copy PT premium B2B
