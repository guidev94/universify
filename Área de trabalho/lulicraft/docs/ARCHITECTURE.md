# Lulicraft: Arquitetura Técnica

## Visão

Aplicação web estática, ES Modules, Three.js vendorizado, sem build step. Estado do jogo em memória, persistência em `localStorage`, renderização WebGL.

```
index.html
assets/styles.css
src/
  vendor/three.module.js
  core/rng.js            PRNG + hash + noise
  game/blocks.js         registro de blocos + atlas procedural
  game/items.js          itens, raridades, drops, ícones
  game/recipes.js        receitas da forja
  game/world.js          chunks, geração, get/set, estruturas
  game/mesher.js         face culling, AO, água, plantas
  game/player.js         câmera, física, raycast, interação
  game/inventory.js      modelo de inventário
  game/save.js           mundos, snapshot, autosave, export
  game/characters.js     presets, cores e normalização do personagem
  game/prebuilt.js       mundos prontos e flags de Kit de Raros
  game/community.js      mundos curados da comunidade
  game/brazil.js         mundos inspirados em cidades brasileiras
  game/seeds.js          seeds aleatórias e nomes de mundo
  game/multiplayer.js    PeerJS P2P, salas, protocolo e sanitização
  game/audio.js          WebAudio procedural
  game/ui.js             HUD, modais, tutorial, cartões
  game/main.js           bootstrap + game loop
```

## Fluxo

```
main.js
  ├─ Save.listWorlds() → menu
  ├─ Save.load(id) → World(seed)
  ├─ ChunkManager.update(player) → gera/mesha raio 4
  ├─ Player.update(dt) → física + colisão + câmera
  ├─ Interaction → raycast → break/place → World.setBlock → diff
  ├─ Multiplayer.update() → player/block/chest sync (se conectado)
  ├─ UI.render() → hotbar, buffs, toasts, jogadores, pets, NPCs e hints
  ├─ updateCompanion() → pet segue, reage e anima
  ├─ updateNpcs() → proximidade, fala e tecla R
  └─ Save.autosave() a cada 15 s (host/single-player)
```

## Decisões

| Decisão | Alternativa descartada | Motivo |
|---|---|---|
| Three.js vendorizado | CDN | Offline e determinismo de versão |
| Atlas procedural | Assets PNG | Zero dependência, estilo coeso |
| Chunk 16×48×16 | 32×256×32 | Performance em hardware simples |
| Meshing síncrono por frame | Worker (v1) | Simplicidade; worker no M4 |
| localStorage + diffs | IndexedDB | Suficiente para mundos cozy |
| Crafting por receitas | Grade 3×3 | Acessível para crianças |
| Sem combate | Mobs hostis | Segurança e conforto |
| PeerJS vendorizado + P2P | Servidor próprio | Sem backend, convite simples e baixo custo |
| Host autoritativo para o mundo | Sincronização total | Evita conflitos e mantém o mundo no anfitrião |
| Convidado continua sozinho se o host sair | Encerrar sessão | Evita perder a exploração |
| Pets e NPCs no cliente | Servidor de entidades | Zero backend e comportamento previsível |
| Ranking local por tempo | Ranking global | Sem contas, sem competição entre crianças |
| Mochila expansível em memória | Slots fixos | Evita perda de itens e mantém save simples |
| Pacotes gráficos via CSS | Builds separados | Ajuste imediato sem pipeline |

## Modelo de dados

### Chunk

```js
{ cx, cz, blocks: Uint8Array(12288), mesh, waterMesh, plantMesh, dirty }
```

Índice `x + z*16 + y*256`.

### Mundo

```js
{
  seed, mode, chunks: Map, diffs: Map, chests: Map,
  timeOfDay, player, inventory, discovered, stats
}
```

### Save

Ver `.specs/features/save-worlds/design.md`.

## Performance

- Raio 4 → 81 chunks; geração distribuída (máx. 2 chunks/frame).
- `dispose()` imediato de geometria ao sair do raio.
- Materiais compartilhados (opaco, cutout, água).
- Pixel ratio limitado a 2 (1.5 em mobile).
- Sombras desligadas; iluminação por hemisphere + directional.
- `frustumCulled` padrão; meshes por chunk.
- Pets e NPCs são poucos modelos reutilizados; animações são transformações simples.
- Modo `turbo` reduz animações decorativas e `image-rendering` para hardware fraco.
- Mochila cresce sob demanda, sem alocar 512 linhas por padrão.

## Multiplayer

- `Multiplayer` mantém `role` (`host`/`guest`), sala, jogadores e conexões.
- Código de 5 caracteres em `ABCDEFGHJKLMNPQRSTUVWXYZ23456789`; link `?join=CODE`.
- Máximo de 6 jogadores; host retransmite mensagens e mantém o snapshot.
- Estado sincronizado: posição/yaw/pitch, blocos, baús, patches de chunk e lista de jogadores.
- Convidado recebe starter kit, não salva o mundo durante a sala e continua localmente se o host sair.
- Sem chat, voz, contas, matchmaking público ou moderação.

### Protocolo resumido

`welcome`, `hello`, `join`, `player`, `block`, `chest`, `patch`, `players`, `leave`, `left`.

## Segurança

- Nenhum `eval`, nenhum `innerHTML` com dado de usuário.
- Importação de save validada campo a campo.
- Seed, nome, personagem, coordenadas, blocos, baús e patches são sanitizados.
- Multiplayer é opt-in: só há rede após criar/entrar em uma sala.
- PeerJS Cloud é usado apenas para signaling; o estado do jogo vai por WebRTC P2P.
- Código/link é convite, não senha; não há autenticação de sala.
- CSP recomendada em produção:
  `default-src 'self'; img-src 'self' data: blob:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; script-src 'self'; connect-src 'self' https://0.peerjs.com wss://0.peerjs.com`
- Sem cookies, fingerprint ou analytics.

## Acessibilidade

- Modais com `role="dialog"`, `aria-modal`, foco preso.
- Botões ≥ 44 px; foco visível.
- `prefers-reduced-motion` desliga transições.
- Raridade com rótulo textual e estrelas.
- Navegação por teclado nos painéis.

## Testes

- `npm test`: 14 testes unitários de personagens, pets, mundos prontos, comunidade, cidades, códigos de sala, geração, inventário expansível, receitas, save e slots.
- `npm run test:e2e`: smoke test no Chrome headless (menu, cidades, personagem/pet, gráficos, Kit de Raros, mundo, NPCs, companheiro, pouso e inventário).
- `npm run test:mp`: dois Chrome headless separados (host/convidado), convite, sync de bloco, saída e host-offline.
- `npm run test:all`: executa os três em sequência.
- Roteiro manual em `docs/TEST-PLAN.md`.

## Deploy

Qualquer servidor estático (GitHub Pages, Netlify, Vercel, `python3 -m http.server`). Sem backend de jogo; o multiplayer usa signaling público PeerJS e WebRTC.
