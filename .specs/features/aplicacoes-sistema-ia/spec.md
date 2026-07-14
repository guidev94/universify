# Aplicações e Sistema com IA — Specification

## Problem Statement

A homepage ainda vende a seção **"Marketing de Conversão"**, desalinhada da oferta real da WV_Todoz (software house: apps, sistemas e IA). Visitantes B2B veem uma narrativa genérica de performance/marketing em vez de engenharia e sistemas inteligentes — enfraquecendo posicionamento premium.

## Goals

- [ ] Substituir seção Marketing por **Aplicações e Sistema com IA** (título, copy, bullets, CTA, alt)
- [ ] Atualizar anchors para `#aplicacoes` em HTML/CSS/JS/tests/nav/footer
- [ ] Preservar continuidade visual (layout 2 colunas + glow bleed)
- [ ] Alinhar hero/meta description à nova oferta (sem reescrever o site inteiro)

## Out of Scope

| Feature | Reason |
| --- | --- |
| Página dedicada /IA | Milestone B (~R$50k) |
| Novos cases IA com mídia custom | Sem assets; roadmap 50k |
| Remover Inter / redesign total | Elevação high-level |
| Alterar taxonomy do blog "Marketing" | Categoria de conteúdo distinta |

---

## User Stories

### P1: Seção Aplicações IA no lugar de Marketing ⭐ MVP

**User Story**: Como visitante B2B, quero entender que a WV_Todoz entrega aplicações e sistemas com IA, para avaliar a empresa como parceira de engenharia inteligente.

**Why P1**: Core repositioning do ciclo.

**Acceptance Criteria**:

1. WHEN a página carrega THEN o DOM SHALL conter exatamente uma seção `#aplicacoes` e NÃO `#marketing`
2. WHEN o usuário lê o H2 THEN o texto SHALL comunicar "Aplicações" + "Sistema com IA" (ou equivalente premium PT)
3. WHEN o usuário lê o corpo THEN a copy SHALL falar de aplicações/sistemas com IA (não "marketing de conversão" / inbound genérico)
4. WHEN o glow ambient é inspecionado THEN `#aplicacoes::after` SHALL manter o padrão de bleed bilateral
5. WHEN o layout é visto em desktop THEN SHALL permanecer grid 2 colunas (imagem + conteúdo)
6. WHEN o layout é visto em mobile THEN conteúdo SHALL estar centralizado e legível, sem overflow horizontal da seção

**Independent Test**: Abrir homepage, scroll até a seção pós-Produtos; verificar id, título e copy.

**Requirements**: `REQ-AI-01` … `REQ-AI-06`

---

### P1: Navegação e descoberta ⭐ MVP

**User Story**: Como visitante, quero links de nav/footer para a seção de Aplicações/IA, para chegar rápido à oferta.

**Acceptance Criteria**:

1. WHEN desktop nav é renderizado THEN SHALL existir link para `#aplicacoes` com label curta (ex.: Aplicações)
2. WHEN mobile menu é aberto THEN SHALL existir o mesmo destino
3. WHEN footer navegação é renderizado THEN SHALL incluir link `#aplicacoes`
4. WHEN `NAV_HASHES` / e2e rodam THEN `#aplicacoes` SHALL resolver para um elemento existente

**Independent Test**: Clicar Aplicações no nav e no footer; hash scroll funciona.

**Requirements**: `REQ-AI-07` … `REQ-AI-10`

---

### P2: Alinhamento de messaging global

**User Story**: Como visitante no hero, quero ver a oferta coerente com IA/sistemas, não marketing de conversão.

**Acceptance Criteria**:

1. WHEN hero paragraph é lido THEN SHALL NÃO promover "marketing de conversão" como pilar
2. WHEN meta description é lida THEN SHALL refletir apps/sistemas/IA

**Requirements**: `REQ-AI-11`, `REQ-AI-12`

---

## Requirement Traceability

| ID | Story | Status |
| --- | --- | --- |
| REQ-AI-01 | P1 seção id | Pending |
| REQ-AI-02 | P1 H2 | Pending |
| REQ-AI-03 | P1 copy | Pending |
| REQ-AI-04 | P1 glow | Pending |
| REQ-AI-05 | P1 desktop layout | Pending |
| REQ-AI-06 | P1 mobile | Pending |
| REQ-AI-07 | Nav desktop | Pending |
| REQ-AI-08 | Nav mobile | Pending |
| REQ-AI-09 | Footer | Pending |
| REQ-AI-10 | Tests hashes | Pending |
| REQ-AI-11 | Hero copy | Pending |
| REQ-AI-12 | Meta | Pending |

## Success Metrics

- Zero referências de âncora `#marketing` no código vivo da homepage
- Unit + e2e verdes com `aplicacoes` em `REQUIRED_SECTION_IDS`
