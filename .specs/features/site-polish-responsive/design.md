# Site Polish & Responsive — Design

**Spec**: `.specs/features/site-polish-responsive/spec.md`  
**Status**: Approved

## Approach

Incremental CSS/HTML polish only. Reuse existing mobile block at end of `main.css`. Do not touch portfolio pin logic.

## Changes

1. **Footer**: `technology` → `tecnologia`
2. **`#aplicacoes` mobile**: include in section padding rhythm; wrap image column `overflow: hidden` on small screens if needed; keep text centered
3. **CTA outline buttons**: ensure `border-todoz` + `magnetic` classes on Aplicações CTA
4. **Blog CTA / secondary buttons**: add consistent hover via existing `.magnetic` rules where missing
5. **Safe horizontal**: ensure absolute badges use `max` positioning that doesn't overflow (`right` clamp or hide on xs — already `hidden md:block`)

## Files

- `index.html` (footer typo, CTA classes, minor spacing classes)
- `assets/css/main.css` (aplicacoes mobile padding, any overflow fix)
