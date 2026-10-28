# Design: Lulicraft 2.0

## Decisão principal

Manter o engine web e o casco Flutter. A versão 2.0 é uma expansão incremental do jogo web, sincronizada para `mobile/assets/web`. Isso preserva multiplayer, saves e WebGL sem fork de engine.

## Construção

### Problema identificado

`onCreativePick(id)` adicionava o item à mochila, mas não selecionava o slot. O próximo `tryPlace()` usava o slot antigo, muitas vezes vazio ou uma ferramenta. Para a criança, parecia que construir estava quebrado.

### Correção

- Em criativo, o bloco escolhido substitui o slot da hotbar selecionada com `{ id, count: 1, infinite: true }`.
- O slot é selecionado e a hotbar é renderizada imediatamente.
- Um toast curto confirma "Bloco selecionado".
- Em aventura, o comportamento continua: o item precisa estar na hotbar.
- `tryPlace()` passa a devolver um resultado claro para feedback: `placed`, `blocked`, `missing`, `used`.
- A HUD mostra "Segure para quebrar" ou "Toque em Colocar" conforme o alvo.

### Testes

- Unitário: `Inventory` com slot infinito não perde count.
- E2E: abrir criativo, escolher bloco rosa, colocar e confirmar bloco no mundo.
- E2E: aventura com bloco na hotbar, colocar e confirmar consumo.

## Inventário agregado

- `renderInventory()` calcula um `Map<id, total>` com todas as pilhas de `hotbar`, `backpack` e `pouch`.
- O topo da aba Mochila mostra "Resumo por item" com um card por ID e o total.
- Os grids detalhados continuam abaixo para mover, soltar e organizar.
- O resumo é somente leitura; clicar em um card seleciona a primeira pilha encontrada na hotbar, se existir.
- Nada de `.html()`: todos os textos usam `textContent`.

## Pets

- O alvo do pet passa a ser `2.2` unidades à frente e `0.85` ao lado.
- Quando o jogador está parado, o pet olha para o jogador.
- O modelo ganha grupos de pata, cabeça e cauda com animações independentes.
- Estados: `idle`, `walk`, `run`, `hop`, `sit`.
- `prefers-reduced-motion` reduz amplitude, não remove feedback.

## Chat seguro

- Allowlist fixa de frases e emotes. Não existe input de texto livre.
- `sanitizeChat(text)` aceita somente IDs da allowlist.
- Rate limit local de 1,5 s e no host de 2 s por jogador.
- Mensagens usam `textContent` e nome já sanitizado.
- Host reenvia para todos; guest envia ao host.
- Sem links, sem HTML, sem dados pessoais.

## Mundo Minha Casa

- Novo prebuilt `minha-casa`, seed `lumi-minha-casa`, modo `creative`, acento rosa.
- A cidade ocupa uma área fixa de 3×3 chunks ao redor de `(0, 0)`.
- `World.heightAt` e `generateChunk` reconhecem a seed e criam uma plataforma urbana em `CITY_BASE`.
- A casa fica no centro, com paredes de `PINK_PLANKS`, telhado de `PRISM`, janelas de `CRYSTAL`, lampiões e baús.
- A cidade tem ruas de `COBBLE`, casas pequenas, praça com fonte, jardins, árvores e lampiões.
- Baús usam `rollChestLoot` com viés para raros e um item lendário garantido.
- `findSpawn()` retorna a porta da casa para evitar spawn no telhado.

## Personagens

- Novos presets "Lumi Crew" com nomes e combinações autorais.
- Preview CSS com cabeça maior, olhos expressivos, bochechas, sombras e roupas detalhadas.
- Modelo 3D mantém os pivôs atuais, mas ganha pescoço, mãos, detalhes de roupa e expressões.
- Nada de copiar rosto, roupa ou nome de pessoa real.

## Áudio ambiente

- `AudioEngine.setBiome(biome)` ajusta filtro e ganho da ambiência.
- Prado: pássaros curtos e vento suave.
- Floresta: pássaros graves, folhas e insetos.
- Deserto: vento seco e cristais.
- Tundra: vento agudo e sinos de gelo.
- Costa: ondas e gaivotas.
- Noite: grilos e tons baixos.

## Performance

- Resumo de inventário é recalculado só ao abrir/atualizar a mochila.
- Mundo Minha Casa é construído uma vez por chunk e cacheado.
- Chat não cria listeners por mensagem; usa um feed com limite de 6 itens.
- Áudio usa os mesmos buffers e osciladores procedurais, sem assets novos.
- Novos blocos usam o atlas existente, sem texturas externas.

## Segurança

- Nenhum `innerHTML` com dados de jogador.
- Chat por allowlist e rate limit.
- Construção valida coordenadas, colisão com jogador e limites do mundo.
- Novos IDs de bloco não reutilizam IDs antigos.
- Assets do app são servidos apenas em loopback.
