# Aplicações e Sistema com IA — Tasks

**Design**: `.specs/features/aplicacoes-sistema-ia/design.md`  
**Status**: Done

---

## Execution Plan

### Phase 1: Foundation

```
T1 → T2 → T3
     ↘ T4 [P with polish feature after T2]
```

### Phase 2: Verification

```
T5 (tests + gates)
```

---

## Tasks

### T1: Rename section markup + premium PT copy

- **What**: Trocar `#marketing` por `#aplicacoes`; atualizar eyebrow/H2/body/bullets/CTA/alt/decor
- **Where**: `index.html`
- **Depends on**: —
- **Reuses**: Layout 2 colunas existente
- **Done when**: DOM tem `#aplicacoes`; copy não menciona marketing de conversão; CTA `open-modal` funciona
- **Tests**: none (structure covered in T5)
- **Gate**: visual/DOM grep
- **Status**: Done

### T2: CSS selectors + comments `#aplicacoes`

- **What**: Renomear `#marketing` / `#marketing::after` e comentários relacionados (produtos/blog bleed)
- **Where**: `assets/css/main.css`
- **Depends on**: T1 (id must exist)
- **Done when**: Grep sem `#marketing` no CSS; glow mobile media inclui `#aplicacoes::after`
- **Tests**: none
- **Gate**: grep
- **Status**: Done

### T3: Nav, footer, hero, meta

- **What**: Links Aplicações; hero + meta description alinhados a IA/sistemas
- **Where**: `index.html`
- **Depends on**: T1
- **Done when**: REQ-AI-07–12 satisfeitos
- **Tests**: none (e2e T5)
- **Gate**: manual link check
- **Status**: Done

### T4: Update site-utils constants

- **What**: `REQUIRED_SECTION_IDS`: `aplicacoes`; `NAV_HASHES` inclui `#aplicacoes`
- **Where**: `assets/js/site-utils.mjs`
- **Depends on**: T1
- **Done when**: Constants exportam novos valores
- **Tests**: unit asserts updated in T5
- **Gate**: `npm run test:unit`
- **Status**: Done

### T5: Tests + verify gates

- **What**: Atualizar unit assert se necessário; rodar unit + e2e desktop
- **Where**: `tests/unit/site-utils.test.js`, e2e
- **Depends on**: T1–T4
- **Done when**: `npm run test:unit` pass; e2e structure tests pass
- **Gate**: full unit + e2e structure
- **Status**: Done

---

## Diagram-Definition Cross-Check

| Task | Depends on (field) | Diagram edge | OK |
| --- | --- | --- | --- |
| T1 | — | start | ✅ |
| T2 | T1 | T1→T2 | ✅ |
| T3 | T1 | T1→T3 | ✅ |
| T4 | T1 | T1→T4 | ✅ |
| T5 | T1–T4 | →T5 | ✅ |

## Test Co-location Validation

| Task | Layer | Required tests | Present in Done when | OK |
| --- | --- | --- | --- | --- |
| T4/T5 | constants | unit + e2e | T5 | ✅ |
| T1–T3 | HTML/CSS | e2e sections | T5 | ✅ |
