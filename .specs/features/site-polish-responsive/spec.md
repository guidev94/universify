# Site Polish & Responsive — Specification

## Problem Statement

A LP já tem identidade (dark + lima + glow), mas ainda há inconsistências de craft e pontos quebrados em mobile/desktop: tipografia/ritmo irregular, leftovers amadores (typo no footer), overflow em decorações absolutas, e CTAs com hover inconsistente. O objetivo é elevar o nível profissional sem redesenhar a marca.

## Goals

- [x] Corrigir overflow/alinhamento mobile nas seções críticas (incl. Aplicações)
- [x] Uniformizar ritmo vertical e hierarquia tipográfica onde for seguro
- [x] Consistir hovers/CTAs `.magnetic`
- [x] Remover leftovers óbvios (typo "technology", etc.)

## Out of Scope

| Feature | Reason |
| --- | --- |
| Fonte custom / design system | Roadmap ~R$50k |
| Rebuild de todas as seções | Escopo excessivo |
| Visual regression suite | Milestone B |

---

## User Stories

### P1: Mobile profissional e legível ⭐ MVP

**Acceptance Criteria**:

1. WHEN viewport ≤767px THEN seções principais SHALL não gerar scroll horizontal
2. WHEN seções two-column (sobre, aplicacoes) THEN texto/CTAs SHALL estar centralizados conforme padrão já usado
3. WHEN decor absolute da seção Aplicações THEN SHALL não estourar viewport (overflow hidden no media wrapper ou clamp)

### P1: Craft polish ⭐ MVP

**Acceptance Criteria**:

1. WHEN footer tagline é lida THEN SHALL estar em português correto ("tecnologia")
2. WHEN botões `.magnetic.border-todoz` recebem hover THEN SHALL preencher lima (já em CSS — verificar aplicação)
3. WHEN paddings mobile de `#aplicacoes` THEN SHALL alinhar ao ritmo de `#sobre`/`#servicos` (~4.5rem)

### P2: Tipografia

**Acceptance Criteria**:

1. WHEN headlines grandes em mobile THEN `clamp`/leading SHALL evitar corte de linhas

## Requirements

| ID | Status |
| --- | --- |
| REQ-POL-01 | Done |
| REQ-POL-02 | Done |
| REQ-POL-03 | Done |
| REQ-POL-04 | Done |
| REQ-POL-05 | Done |
| REQ-POL-06 | Done |
