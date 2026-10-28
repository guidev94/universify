# Design: Itens e Raridades

## Registro

```js
ITEMS = {
  lumi_shard: {
    name: "Fragmento de Lumi",
    desc: "Uma faísca que ainda lembra do sol.",
    rarity: "incomum",
    category: "material",
    stack: 64,
    icon: { kind: "shard", colors: ["#ffe28a", "#ffb347"] }
  }
}
```

Blocos são itens placeáveis: `id` do item = `id` do bloco, com `place: true`.

## Raridade

```js
RARITY = {
  comum:    { label: "Comum",    color: "#9aa7a0", stars: 1, sound: 220 },
  incomum:  { label: "Incomum",  color: "#5fd38a", stars: 2, sound: 330 },
  raro:     { label: "Raro",     color: "#5aa9ff", stars: 3, sound: 440 },
  epico:    { label: "Épico",    color: "#b07cff", stars: 4, sound: 554 },
  lendario: { label: "Lendário", color: "#ffc94d", stars: 5, sound: 660 },
  mitico:   { label: "Mítico",   color: "#ff7ad9", stars: 6, sound: 880 }
}
```

## Drops

| Bloco | Drop base | Raro |
|---|---|---|
| Pedra | Pedra Britada | 4% Fragmento de Lumi |
| Minério de Lumi | Fragmento de Lumi ×1 a 3 | 8% Gema Aurora |
| Minério Aurora | Gema Aurora ×1 a 2 | 3% Pena de Nuvem |
| Cogumelo Estelar | Cogumelo Estelar | não |
| Baú de Santuário | 3 a 5 itens | garantia de 1 raro |

## Efeitos

- Buffs: `speed`, `jump`, `nightVision`, `oreGlow`, `doubleJump`.
- Timer no HUD com ícone e segundos restantes.
- Picareta Aurora: quebra 3×3 no plano perpendicular à face atingida.
- Coração de Lumi: a cada 2 s, emite partículas nos minérios em raio 8.

## Ícones

- Blocos: recorte do atlas de texturas.
- Itens: pixel art procedural em canvas 32×32 (gema, pena, coroa, ferramenta).
- Cache em `Map<id, dataURL>` para não redesenhar.
