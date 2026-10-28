# Design: Save e Mundos

## Chaves

```
lulicraft:index          → [{ id, name, seed, mode, createdAt, updatedAt, playtime, thumb }]
lulicraft:world:<id>     → snapshot completo
lulicraft:settings       → { sound, tutorialDone, quality, sensitivity }
lulicraft:daily          → { date, seed, progress }
```

## Snapshot (schema v1)

```js
{
  version: 1,
  id, name, seed, mode, createdAt, updatedAt, playtime,
  timeOfDay: 0..1,
  player: { x, y, z, yaw, pitch, fly },
  inventory: { hotbar, backpack, pouch, selected },
  chests: { "x,y,z": [{id,count}] },
  diffs: { "cx,cz": { blockIndex: blockId } },
  discovered: ["grass", "lumi_shard"],
  stats: { broken, placed, collected, rare, crafted },
  achievements: { firstBlock: true },
  quests: { daily: { date, progress } }
}
```

## Autosave

- Timer de 15 s; pausa quando a aba está oculta (`visibilitychange`).
- `beforeunload` faz flush síncrono.
- Escrita com try/catch; se `QuotaExceededError`, avisa e sugere exportar.

## Código de mundo

```
LC1.<base64url(JSON)> 
JSON = { s: seed, n: name, m: mode }
```

- Máximo ~120 caracteres para mundos novos.
- Importação valida prefixo, decodifica e cria mundo com a seed.

## Exportação

- `Blob` JSON pretty-printed, nome `lulicraft-<slug>.lulicraft`.
- Importação lê `File.text()`, valida `version` e campos obrigatórios.

## Segurança

- Todo texto do usuário é inserido com `textContent`, nunca `innerHTML`.
- Seed limitada a 64 caracteres.
- Importação rejeita objetos com protótipo inesperado e campos fora do schema.
- Nenhuma chamada de rede.
