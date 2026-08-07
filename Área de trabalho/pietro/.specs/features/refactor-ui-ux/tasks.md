# Refactor Tasks

## T1: Hero & Header Spacing [P]
- [ ] Add `pt-40` or equivalent to Hero to prevent header overlap.
- [ ] Refactor Hero text: increase contrast for secondary text (Zinc-500 -> Zinc-600/700).
- [ ] Update H1: "Onde sua marca se torna memorável."
- [ ] Update P: "Curadoria exclusiva de brindes premium que transformam clientes em defensores da sua marca."

## T2: Category Navigation [P]
- [ ] Update `index.html` category cards to link to `catalogo.html?cat=NAME`.
- [ ] Fix `catalogo.html` init logic to auto-filter based on URL param.
- [ ] Ensure `Explorar Linhas` grid is responsive and visually consistent.

## T3: Color Palette & Standard Styling
- [ ] Define CSS variables for palette: Background (#FAFAFA), Surface (#FFFFFF), Text (#09090B), Muted (#71717A).
- [ ] Standardize `premium-card` across all files.
- [ ] Fix broken grid layouts (missing gap, alignment issues).

## T4: Animations & Micro-interactions
- [ ] Add GSAP scroll reveal to each section.
- [ ] Implement smooth transition on product card hover (transform + subtle shadow).
- [ ] Add loading state shimmer (skeleton) for smoother initial load.

## T5: Final Verification
- [ ] Check for "undefined" in rendered HTML.
- [ ] Test category filtering.
- [ ] Validate mobile responsiveness.
