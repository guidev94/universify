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
4. **Seguro por padrão**, sem combate, sem dano, sem chat, sem coleta de dados, sem monetização.
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

- Personagens cosméticos com 9 skins/presets, cores personalizadas e nome.
- 5 armas mágicas sem dano para brincadeiras e buffs.
- 6 mundos prontos, incluindo dois com Kit de Raros.
- Kit de Raros opcional na criação de mundo.
- Multiplayer P2P com convite, até 6 exploradores, sem contas e sem chat.
- Testes unitários, smoke test e teste multiplayer com dois navegadores.

## 6. Fora de escopo

- Servidores dedicados, contas, nuvem, matchmaking público e chat.
- Combate, vida/morte, fome, dano de queda.
- Mobile nativo (App Store/Play Store), contas e nuvem.
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

## 9. Referências de qualidade

- Sensação de descoberta: *A Short Hike*, *Animal Crossing*.
- Construção imediata: *Minecraft Creative*, *Toca Boca*.
- Clareza de UI infantil: *Sago Mini*, *PBS Kids*.
- Polimento visual: Vercel/Linear (hierarquia, respiro, microinterações).
