# Site Polish & Responsive — Tasks

**Design**: `.specs/features/site-polish-responsive/design.md`  
**Status**: Done

## Tasks

### T1: Footer typo + CTA class consistency

- **Where**: `index.html`
- **Depends on**: —
- **Done when**: "tecnologia"; Aplicações CTA uses magnetic + border-todoz pattern
- **Status**: Done

### T2: Mobile CSS rhythm + overflow for `#aplicacoes`

- **Where**: `assets/css/main.css`
- **Depends on**: aplicacoes section id exists
- **Done when**: `#aplicacoes` in mobile padding group; no horizontal overflow from section decor
- **Status**: Done

### T3: Light hierarchy/spacing pass on touched sections

- **Where**: `index.html`, `main.css`
- **Depends on**: T2
- **Done when**: Aplicações section spacing matches neighboring sections; no amateur leftover in touched copy
- **Status**: Done

### T4: Fix Lenis scroll recursion (bonus polish)

- **Where**: `assets/js/main.js`
- **Depends on**: —
- **Done when**: e2e `no open-modal button throws` passes
- **Status**: Done

## Cross-check

| Task | Depends | OK |
| --- | --- | --- |
| T1 | — | ✅ |
| T2 | aplicacoes id | ✅ |
| T3 | T2 | ✅ |
| T4 | — | ✅ |
