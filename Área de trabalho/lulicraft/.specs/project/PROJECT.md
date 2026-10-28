# Lulicraft: Project Spec

## 1. Visão

**Lulicraft** é um sandbox de blocos 3D, cozy e familiar, inspirado na liberdade de Minecraft, mas com **mundo, blocos, itens, criaturas e progressão 100% autorais**. O jogador é um **Jardineiro de Lumi**: explora biomas, coleta fragmentos de luz, acorda cristais adormecidos e devolve cor ao mundo.

> **Tagline:** Um mundo de blocos, luz e descobertas.

### Elevator pitch
Um jogo de mundo aberto em blocos onde crianças e famílias constroem, exploram e colecionam itens raros, sem combate, sem morte, sem pressão. Cada mundo nasce de uma semente compartilhável, pode ser salvo no navegador e vira um cartão pronto para compartilhar.

## 2. Público

| Persona | Idade | Necessidade | Como o jogo atende |
|---|---|---|---|
| **Lia, a exploradora** | 7 a 10 | Descobrir coisas bonitas e raras | Biomas coloridos, itens raros com brilho, álbum de coleção |
| **Beto, o construtor** | 8 a 12 | Criar sem limites | Modo Criativo, voo, blocos infinitos, colocação fácil |
| **Carla, a mãe** | 30 a 45 | Jogo seguro e sem compras | Sem chat, sem violência, sem anúncios, offline-first |
| **Davi, o criador de conteúdo** | 12 a 18 | Compartilhar descobertas | Código de mundo, seed do dia, modo foto e cartão compartilhável |

## 3. Pilares (não negociáveis)

1. **Fácil em 30 segundos**, entrar, mover, quebrar e colocar sem tutorial obrigatório.
2. **Autoral de verdade**, nenhum bloco, item, bioma ou nome copiado; estética "Lumi".
3. **Descoberta recompensadora**, raridade visível, brilho, som e registro no álbum.
4. **Seguro por padrão**, sem combate, sem dano, sem chat livre, sem coleta de dados, sem monetização.
5. **Compartilhável**, seed, código de mundo e cartão de foto em um clique.
6. **Leve**, roda em notebook simples, Chromebook e celular; sem build step.

## 4. Escopo do MVP (v1.0 jogável)

- Mundo 3D voxel procedural com 5 biomas, cavernas, árvores, flores e estruturas.
- 5 tipos de inventário: barra rápida, mochila, bolsa de sementes, baú e álbum de coleção.
- Forja de receitas (crafting por clique, sem grade complexa).
- 6 raridades, 20+ itens autorais, drops raros e efeitos especiais.
- Salvar/carregar múltiplos mundos, autosave, exportar/importar, seed do dia.
- Modos Aventura (cozy survival) e Criativo (voar + blocos infinitos).
- UI infantil, tutorial de 4 passos, conquistas, modo foto e cartão de compartilhamento.
- Desktop + toque (celular/tablet).

## 5. Expansão v1.1 (entregue)

- Personagens cosméticos com 18 presets do Lumi Crew, cores personalizadas e nome.
- 5 armas mágicas sem dano para brincadeiras e buffs.
- 7 mundos prontos, incluindo Minha Casa e três com Kit de Raros.
- Kit de Raros opcional na criação de mundo.
- Multiplayer P2P com convite, até 6 exploradores, sem contas e com chat por frases prontas.
- Testes unitários, smoke test e teste multiplayer com dois navegadores.

## 5.1 Expansão v1.2 (entregue)

- Pets cosméticos com 4 tipos, 8 cores e companheiro 3D que segue e reage ao jogador.
- Petisco craftável e interação de alimentar pet.
- 3 NPCs cozy com fala contextual e tecla R.
- 6 mundos inspirados em cidades brasileiras.
- Ranking local de tempo por mundo.
- Mochila expansível até 512 linhas, sem truncar itens.
- Pouso seguro segurando Espaço por 4 s no voo criativo.
- Pacotes gráficos auto, pixel suave e turbo leve.
- Animações de caminhada, salto, voo, craft e picareta.

## 5.2 Expansão v1.4 e Lulicraft 2.0 (entregue)

- App Flutter **Lulicraft Pocket** com shell nativo, WebView e servidor HTTP local empacotado.
- Atalho de visão 1ª/3ª pessoa no HUD e nos controles de toque.
- Viewmodel por categoria (picareta, arma, arco, bloco) com muzzle alinhado, recuo e flash.
- Configurações ampliadas: FOV, inverter Y, sensibilidade de toque, canhoto, view bob, FPS, partículas, nuvens, escala de UI e iniciar em 3ª pessoa.
- UX mobile: safe areas, alvos de 48 px, layout canhoto e dica de paisagem.
- Bridge `window.LulicraftNative` para o app Flutter.
- Copyright © 2026 guidev94 em menu, README, arquivo `COPYRIGHT` e tela Sobre.
- Backlog priorizado de UI/UX, jogabilidade, itens e funcionalidades em `.specs/project/BACKLOG.md`.
- Marca autoral com favicon, ícone adaptativo e splash.
- Construção criativa com seleção de bloco, feedback e resumo agregado da mochila.
- Mundo Minha Casa com casa rosa, cidade, praça e baús raros.
- Blocos rosa, menta e lavanda; Lumi Crew com 18 presets.
- Pet à frente do jogador, mão e arma com animações distintas.
- Chat seguro por frases prontas com rate limit.
- Áudio ambiente procedural por bioma.

## 6. Fora de escopo

- Servidores dedicados, contas, nuvem, matchmaking público e chat livre.
- Combate, vida/morte, fome, dano de queda.
- Port nativo completo do engine voxel em Dart/Flutter.
- Publicação automática nas lojas ou contratação de contas de desenvolvedor.
- Microtransações, anúncios, loot boxes.
- Mods/plugins de terceiros.

## 7. Métricas de sucesso (v1.0)

| Métrica | Meta | Instrumentação local |
|---|---|---|
| Tempo até o primeiro bloco quebrado | < 30 s | evento local no save |
| Sessão média | > 12 min | playtime no save |
| Mundos criados por jogador | > 1,5 | contador local |
| Itens raros encontrados na 1ª sessão | > 1 | álbum |
| Compartilhamentos (código/foto) | > 20% dos jogadores | botão clicado |
| Crash-free | 100% | console limpo em teste |

## 8. Glossário

- **Lumi**: fragmento de luz; moeda de progresso e nome do mundo.
- **Jardineiro de Lumi**: papel do jogador.
- **Forja**: painel de receitas simplificado.
- **Bolsa de Sementes**: inventário dedicado a sementes e alimentos.
- **Álbum**: coleção de itens descobertos, com raridade.
- **Seed**: código que gera um mundo determinístico.
- **Código de mundo**: string curta compartilhável com seed + nome + modo.
- **Cartão do Mundo**: imagem gerada com foto, nome, seed e conquistas.
- **Sala**: sessão multiplayer P2P identificada por um código de 5 caracteres.
- **Kit de Raros**: conjunto inicial opcional com 10 itens de raridade alta.
- **Pet**: companheiro cosmético que segue o jogador e reage a ações.
- **Petisco**: item craftável que alimenta o pet sem alterar vantagem de gameplay.
- **NPC cozy**: personagem fixo com fala curta e contextual, sem chat livre.
- **Cidade para explorar**: mundo curado inspirado em um cartão-postal brasileiro.
- **Ranking de tempo**: lista local de tempo jogado por mundo, sem servidor.
- **Lulicraft Pocket**: app Flutter que empacota o jogo web em uma WebView com servidor local.
- **Bridge nativa**: objeto JavaScript `window.LulicraftNative` que troca configurações e ações com o app.
- **Muzzle**: ponto de saída visual do projétil, alinhado à mão do personagem.

## 9. Referências de qualidade

- Sensação de descoberta: *A Short Hike*, *Animal Crossing*.
- Construção imediata: *Minecraft Creative*, *Toca Boca*.
- Clareza de UI infantil: *Sago Mini*, *PBS Kids*.
- Polimento visual: Vercel/Linear (hierarquia, respiro, microinterações).
