# WordPress Theme + ACF Free — Specification

## Problem Statement

The WV_Todoz landing page exists as a static HTML/CSS/JS site. Marketing and content teams cannot edit copy, images, cases, products, or client logos without a developer. We need a complete WordPress theme that preserves the current visual design while making every major homepage section editable via WP Admin, using **ACF free only** (no Pro features).

## Goals

- [ ] Ship a standalone installable theme at `wvtodoz-theme/` that renders the homepage with the same sections and visual language as `index.html`
- [ ] Make all major section copy/media editable via WP Admin (ACF free on the Front Page + CPTs for repeating items)
- [ ] Register ACF field groups in PHP so fields ship with the theme (no manual ACF UI setup)
- [ ] Document install steps (WP + ACF free + Front Page assignment)
- [ ] Backup static HTML before conversion; keep originals

## Out of Scope

| Feature | Reason |
| ------- | ------ |
| ACF Pro (Repeater, Flexible Content, Gallery, Clone, Options Pages) | Constraint: free plugin only |
| Full WooCommerce / e-commerce | Not needed for institutional LP |
| Multilingual (WPML/Polylang) | Deferred |
| Rebuilding design system / new visual identity | Preserve current CSS |
| Migrating away from Tailwind CDN / GSAP / Lenis / AOS | Keep stack parity |
| Automated content import from production WP | Manual seed via Admin is enough for MVP |

---

## User Stories

### P1: Theme installs and shows homepage ⭐ MVP

**User Story**: As a site admin, I want to activate the WV Todoz theme and set a static Front Page so that visitors see the redesigned homepage.

**Why P1**: Without a working front-page template, nothing else matters.

**Acceptance Criteria**:

1. WHEN the theme is activated THEN WordPress SHALL list it as "WV Todoz" with a valid `style.css` theme header
2. WHEN a Page is set as Front Page THEN `front-page.php` SHALL render header, all homepage sections, footer, modals, and WhatsApp float
3. WHEN assets are requested THEN theme SHALL enqueue CSS/JS (local + CDN for GSAP/Lenis/AOS/Tailwind/Phosphor) without 404s for theme-bundled files
4. WHEN ACF is inactive THEN theme SHALL still render with hardcoded fallbacks for critical copy (no fatal errors)

**Independent Test**: Activate theme on a blank WP, set Front Page → homepage loads with hero + footer.

---

### P1: Home section fields editable via ACF free ⭐ MVP

**User Story**: As an editor, I want to edit hero, sobre, missão, mobile-bridge, section intros, aplicações, contato CTA, and footer strings on the Home page so that I can update marketing copy without code.

**Why P1**: Core content editing requirement.

**Acceptance Criteria**:

1. WHEN ACF free is active and Home is the Front Page THEN Admin SHALL show field groups organized by tabs (Hero, Sobre, Missão, Mobile Bridge, Serviços, Portfolio, Produtos, Aplicações, Contato, Footer)
2. WHEN an editor saves a field THEN the front-end SHALL display the new value (escaped with `esc_html` / `esc_url` / `wp_kses_post` as appropriate)
3. WHEN a field is empty THEN the theme SHALL fall back to the current static-site default copy
4. WHEN fields are registered THEN they SHALL use Portuguese labels
5. WHEN implementing fields THEN theme SHALL NOT use Repeater, Flexible Content, Gallery, Clone, or Options Pages

**Independent Test**: Change hero title in Admin → refresh Front Page → new title visible.

---

### P1: Repeating content via CPTs ⭐ MVP

**User Story**: As an editor, I want Cases, Produtos, Clientes, and Serviços as custom post types so that I can add/reorder/edit cards without ACF Pro repeaters.

**Why P1**: ACF free cannot repeat field groups; CPTs are the correct free architecture.

**Acceptance Criteria**:

1. WHEN theme loads THEN it SHALL register CPTs: `wv_case`, `wv_produto`, `wv_cliente`, `wv_servico` with Portuguese admin labels
2. WHEN Front Page renders portfolio THEN it SHALL query published `wv_case` posts ordered by `menu_order` / date
3. WHEN Front Page renders produtos THEN it SHALL query `wv_produto`
4. WHEN Front Page renders clientes THEN it SHALL query `wv_cliente`
5. WHEN Front Page renders serviços cards THEN it SHALL query `wv_servico`
6. WHEN Insights section renders THEN it SHALL use native `post` (latest 3) with featured image + excerpt
7. WHEN a CPT has ACF meta (category tag, external URL, highlights text, layout variant) THEN fields SHALL be local PHP field groups attached to that CPT

**Independent Test**: Create a Case in Admin → it appears in `#portfolio` rail.

---

### P1: Menus, logo, WhatsApp via WP native / Customizer ⭐ MVP

**User Story**: As an admin, I want navigation menus and site logo managed via Appearance → Menus / Site Identity so that chrome stays WordPress-native.

**Why P1**: Avoid Options Page (Pro) for chrome settings.

**Acceptance Criteria**:

1. WHEN theme supports `custom-logo` THEN Customizer logo SHALL replace hardcoded logo URLs when set
2. WHEN menus `primary` and `mobile` are assigned THEN header/mobile overlay SHALL render those links
3. WHEN WhatsApp URL is set via ACF on Home (or theme_mod fallback) THEN header CTA, float button, and contato links SHALL use it

**Independent Test**: Set Custom Logo → logo updates in header/footer.

---

### P2: Single post / blog template

**User Story**: As a visitor, I want to open an insight and see a themed single template so that blog links from the homepage work.

**Why P2**: Homepage links to posts; full blog archive can be thinner.

**Acceptance Criteria**:

1. WHEN viewing a single post THEN `single.php` SHALL use theme header/footer and readable content styles
2. WHEN homepage blog CTA links THEN it SHALL point to posts page or first post archive URL when configured

**Independent Test**: Publish a post → open permalink → themed layout.

---

### P3: Seed documentation

**User Story**: As a developer installing the theme, I want a README listing recommended Home ACF values and sample CPT entries so that I can recreate production content quickly.

**Acceptance Criteria**:

1. WHEN reading `wvtodoz-theme/README.md` THEN install steps and ACF-free constraints SHALL be documented

---

## Edge Cases

- WHEN ACF plugin is missing THEN theme SHALL not call undefined ACF functions without guards (`function_exists('get_field')`)
- WHEN no CPT posts exist THEN sections SHALL show empty-state gracefully or fallback sample markup from defaults helper
- WHEN image fields return attachment IDs THEN theme SHALL use `wp_get_attachment_image_url` / `wp_get_attachment_image`
- WHEN output includes HTML in WYSIWYG THEN theme SHALL use `wp_kses_post`
- WHEN user has `prefers-reduced-motion` THEN existing CSS/JS behavior SHALL remain intact

---

## Requirement Traceability

| ID | Requirement | Story |
| ---- | ----------- | ----- |
| REQ-01 | Theme scaffold with style.css + templates | P1 install |
| REQ-02 | Enqueue assets + CDN libs | P1 install |
| REQ-03 | ACF local field groups (Home tabs) | P1 ACF |
| REQ-04 | CPT case/produto/cliente/servico | P1 CPTs |
| REQ-05 | Section template-parts wired to ACF/CPT | P1 ACF + CPTs |
| REQ-06 | Escape all output | P1 ACF |
| REQ-07 | Menus + custom-logo | P1 chrome |
| REQ-08 | single.php for insights | P2 |
| REQ-09 | README install guide | P3 |
| REQ-10 | Static HTML backup | Ops |
| REQ-11 | Spec-Driven artifacts | Ops |

---

## Success Criteria

- Theme folder is zip-ready and activates without PHP fatals
- Editors can change hero, sobre, missão, and create cases/produtos/clientes/serviços in Admin
- Visual parity is “as close as practical” to static `index.html` using the same `main.css`
- No ACF Pro APIs used
