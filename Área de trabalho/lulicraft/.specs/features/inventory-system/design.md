# Design: Inventários Múltiplos

## Modelo

```js
slot = null | { id: "grass", count: 12 }
inventory = {
  hotbar: Array(9).fill(null),
  backpack: Array(27).fill(null),
  pouch: Array(9).fill(null),
  selected: 0
}
```

- `hotbar` e `backpack` formam o "inventário do jogador" (36 slots).
- Baú é um `Map<"x,y,z", Array(27)>` no save.
- O item em mãos (`held`) existe apenas na UI e é devolvido ao fechar.

## Regras de transferência

```
addItem(id, count):
  1. tenta empilhar em hotbar → backpack → pouch (se categoria permitir)
  2. tenta slots vazios na mesma ordem
  3. retorna quantidade que não coube
```

```
quickMove(slot, from, to):
  - calcula destino compatível
  - empilha antes de ocupar vazio
  - operação atômica (sem perda)
```

## UI

- Painel com abas: **Mochila · Sementes · Forja · Coleção**.
- Grid responsivo: 9 colunas no desktop, 6 no mobile.
- Slot 48 px (desktop) / 40 px (mobile), borda por raridade.
- Tooltip: nome, raridade, descrição curta, ação ("Clique para mover").
- Item em mãos segue o cursor; fundo escurece levemente.
- Cores de raridade: comum `#9aa7a0`, incomum `#5fd38a`, raro `#5aa9ff`, épico `#b07cff`, lendário `#ffc94d`, mítico `#ff7ad9` com gradiente.

## Acessibilidade

- Slots são `<button>` com `aria-label` "Slot 3: Terra x12".
- Foco visível de 3 px.
- Cores de raridade acompanhadas de rótulo textual e ícone de estrela.

## Persistência

```js
save.inventory = { hotbar, backpack, pouch, selected }
save.chests = { "12,21,-4": [{id,count}, ...] }
```
