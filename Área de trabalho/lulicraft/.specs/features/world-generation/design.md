# Design: Geração de Mundos

## Pipeline

```
seed (string)
  → hash32 (FNV-1a)
  → mulberry32 (PRNG determinístico)
  → fbm2 / noise3 (value noise com interpolação suave)
  → mapa de altura + bioma + cavernas + minérios + decoração
  → Uint8Array do chunk (16×48×16)
  → diffs do save aplicados por cima
  → mesher (face culling + AO) → BufferGeometry
```

## Parâmetros

| Parâmetro | Valor |
|---|---|
| Chunk | 16 × 48 × 16 |
| Nível do mar | 20 |
| Raio de render | 4 chunks (9×9) |
| Altura mínima/máxima | 5 / 42 |
| Frequência continental | 0.006 |
| Frequência detalhe | 0.035 |
| Caverna (3D) | fbm3 > 0.62 |

## Biomas

Determinados por dois campos de ruído de baixa frequência:

| Bioma | Condição | Blocos de superfície | Decoração |
|---|---|---|---|
| Prado Musgo | padrão | grama + terra | flores rosa/azul, árvore de mel |
| Bosque Arco-Íris | umidade > 0.62 | grama + terra | árvore arco-íris, cogumelo estelar |
| Deserto Coral | temperatura > 0.68 e umidade < 0.40 | areia coral | corais, cristais solares |
| Tundra Lumina | temperatura < 0.32 | neve + gelo | cristais azuis, aurora ore |
| Costa de Nuvens | altura ≤ mar + 1 | areia | água, blocos de nuvem, ilhas |

## Estruturas

- **Santuário de Lumi**: plataforma 5×5 de pedra, 4 pilares, cristal central e baú. Origem determinística em grade de 7×7 chunks; só é construído se couber inteiro no chunk.
- **Ilha de Nuvem**: 3×3 de blocos de nuvem, 1 cristal, flutua em y≈34; 1 por chunk com probabilidade 1/8.
- **Loot do baú**: tabela ponderada, comum 55%, incomum 25%, raro 15%, épico 5%; garantia de 1 item raro.

## Dados

```js
chunk = {
  cx, cz, key: "cx,cz",
  blocks: Uint8Array(16*48*16),
  mesh, waterMesh, plantMesh,
  dirty: false
}
```

Índice: `x + z*16 + y*256`.

## Riscos e mitigação

| Risco | Mitigação |
|---|---|
| Costura entre chunks | Consultar `world.getBlock` global no mesher |
| Vazamento de memória | `dispose()` de geometria/materiais ao sair do raio |
| Spawn dentro de árvore | Buscar coluna e subir até 2 blocos de ar |
| Seed inválida | Normalizar para string e hashear |
