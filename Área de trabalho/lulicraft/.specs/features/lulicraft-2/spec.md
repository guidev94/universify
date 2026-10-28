# Feature: Lulicraft 2.0

## Objetivo

Evoluir o Lulicraft para uma experiência mais expressiva, construível e segura: personagens autorais com personalidade, pets que conduzem o jogador, construção imediata, inventário legível, mundo "Minha Casa" com cidade rosa, chat seguro por frases prontas, áudio ambiente por bioma e marca 100% original.

O jogo web continua sendo a fonte única de verdade. O app Flutter existente é o casco de distribuição, com WebView e servidor local. Não há reescrita do engine em Dart.

## Público

| Persona | Necessidade | Como esta versão atende |
|---|---|---|
| Lia, 7 a 10 anos | Construir sem travar e entender o que está selecionado | Bloco escolhido vira seleção ativa, dica clara e feedback imediato |
| Carla, mãe | Conversar sem risco e sem exposição | Chat apenas com frases prontas, sem texto livre |
| Davi, criador | Mostrar um mundo bonito e cheio de detalhes | "Minha Casa" com casa rosa, cidade, baús e itens infinitos |
| Lumi, jogadora | Personagens e pets com alma | Presets expressivos, pet na frente e animações próprias |

## Modelo

```text
Web game (fonte de verdade)
├── Engine Three.js
├── Mundo "Minha Casa" (seed fixa + distrito autoral)
├── Construção e inventário
├── Pets e personagens
├── Chat seguro por allowlist
└── Áudio ambiente procedural

Flutter shell
├── Home, Ajustes e Sobre
├── WebView + servidor local
└── Assets sincronizados por npm run mobile:sync
```

## Requisitos

| ID | Requisito | Prioridade |
|---|---|---|
| L2-01 | Marca autoral sem referência a Minecraft, Creeper ou "MOD" | Must |
| L2-02 | Escolher bloco na paleta criativa seleciona o bloco imediatamente | Must |
| L2-03 | Construir funciona em aventura e criativo, com dica de seleção e erro claro | Must |
| L2-04 | Resumo da mochila mostra cada item uma vez com o total somado | Must |
| L2-05 | Detalhamento por pilhas continua disponível para mover e organizar | Must |
| L2-06 | Pet segue na frente do jogador, olha para trás e tem animações de caminhada, corrida, orelha, cauda e pulo | Must |
| L2-07 | Mão e arma têm animação distinta para quebrar, colocar, usar e acertar | Must |
| L2-08 | Chat multiplayer usa apenas frases e emotes de uma allowlist | Must |
| L2-09 | Chat tem rate limit, nome do jogador e nunca usa HTML dinâmico | Must |
| L2-10 | Mundo "Minha Casa" tem casa rosa, cidade, praça, jardins, lampiões e baús raros | Must |
| L2-11 | "Minha Casa" é criativo por padrão, com blocos infinitos | Must |
| L2-12 | Baús da casa contêm itens raros e lendários determinísticos | Must |
| L2-13 | Personagens ganham presets autorais expressivos, com cabeça maior, olhos, bochechas e roupas detalhadas | Should |
| L2-14 | Áudio ambiente varia por bioma e por dia/noite | Should |
| L2-15 | Novos blocos decorativos rosa, menta e lavanda entram na paleta | Should |
| L2-16 | Tudo funciona em desktop, toque e WebView | Must |
| L2-17 | Preferências e assets do app Flutter continuam sincronizados | Must |
| L2-18 | Nenhum chat livre, dano, combate ou coleta de dados pessoais | Must |

## Critérios de aceite

- Na paleta criativa, tocar em "Tábuas Rosas" seleciona o bloco e o próximo toque em "Colocar" cria o bloco.
- Em aventura, selecionar um bloco na hotbar e usar "Colocar" consome um item e cria o bloco.
- Mochila com 3 pilhas de gravetos mostra "Graveto" uma vez com o total `192` no resumo.
- O pet fica à frente do jogador, vira o rosto para ele quando parado e anima as patas ao andar.
- O chat mostra apenas frases da allowlist; texto desconhecido é descartado.
- "Minha Casa" abre com casa rosa, cidade ao redor e pelo menos 4 baús com itens raros.
- O áudio muda de textura entre prado, floresta, deserto, tundra e costa.
- `npm run test:all` passa.
- `flutter analyze` e `flutter test` passam no diretório `mobile/`.
- `npm run mobile:sync` copia os assets atualizados.

## Fora de escopo

- Chat livre, matchmaking público, contas, nuvem, anúncios ou loja.
- Combate, dano, morte ou PvP.
- Reescrever o engine em Dart.
- Upload de skins ou moderação por servidor dedicado.
- Publicação nas lojas neste bloco.

## Riscos

| Risco | Mitigação |
|---|---|
| "Estilo Cadres" virar cópia de pessoa ou personagem existente | Usar apenas como referência de expressividade; criar silhuetas, roupas e nomes próprios |
| Chat infantil ser vetor de abuso | Allowlist de frases, rate limit, sem links, sem texto livre |
| Mundo autoral quebrar chunks vizinhos | Construir com coordenadas globais e testar bordas de chunk |
| Novos blocos quebrarem saves antigos | IDs novos no fim da enumeração, sem reutilizar IDs |
| WebView sem WebGL | Mensagem clara e fallback existente |
