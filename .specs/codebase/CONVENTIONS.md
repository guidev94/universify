# Conventions

**Analyzed:** 2026-07-14

## HTML

- `lang="pt-BR"`; sections use `id` kebab-case for anchors
- Layout width: `.site-container` (not ad-hoc Tailwind `container`)
- CTAs: `.magnetic` + `open-modal` / WhatsApp links
- Uppercase display headlines with lime accent span + `.text-glow`

## CSS

- Brand lime: `--todoz: #CFFF04` and Tailwind `todoz`
- Section ambience: `#section::after` radial gradients + mask fade
- Responsive: mobile-first overrides near file end (`@media (max-width: 767px)`)
- Prefer `overflow: visible` on section canvases to preserve glow bleed

## JS

- Classic IIFE / global functions; expose helpers on `window.WVSiteUtils`
- Pure logic duplicated in `site-utils.mjs` for Node tests — keep in sync when changing constants
- Feature flags via `window.__wvBooted` / `__wvAnimationsBooted`

## Copy

- Portuguese, assertive B2B, short sentences
- Accent words in lime; avoid generic "marketing de conversão" going forward

## Git / deploy

- Primary branch: `master`; also push `main` for Vercel
- No force push; no secrets; no root `hero.mp4` dumps
