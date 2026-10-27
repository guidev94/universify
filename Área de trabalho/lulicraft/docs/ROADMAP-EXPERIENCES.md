# Lulicraft: Próximas experiências

## 1. Norte

O jogo já entrega o loop base: explorar, coletar, construir, forjar, colecionar e compartilhar. As próximas melhorias devem aumentar **tempo até a primeira descoberta**, **vontade de voltar amanhã** e **orgulho de mostrar o que criou**, sem adicionar combate, contas, chat aberto ou monetização.

Prioridade:

1. Experiências que funcionam offline e sem backend.
2. Conteúdo que reaproveita blocos, itens e receitas existentes.
3. Sistemas que uma criança entende sem tutorial longo.
4. Performance e acessibilidade antes de mais efeitos.

## 2. Prioridade 0: encantar a primeira sessão

### 2.1 Jardim vivo

**Job to be done:** "Quero plantar e ver meu cantinho crescer."

- Semente plantada vira muda e cresce em 3 estágios.
- Crescimento em tempo real dentro da sessão e avanço offline ao voltar.
- Regar com água acelera um estágio.
- Colher devolve sementes, flores e às vezes uma fruta rara.
- Diário do jardim mostra quantas plantas e espécies o jogador cultivou.
- Sem praga, murcha ou punição. O jardim nunca morre.

**Aceite:** plantar, sair, voltar e encontrar a planta no estágio correto. Nenhuma planta some do save.

**Impacto:** alto. **Esforço:** médio. **Reuso:** `prism_seed`, `rainbow_sapling`, `berry`, `flower_pink`, `flower_blue`, `World.setBlock`.

### 2.2 Missões curtas de NPC

**Job to be done:** "Quero saber o que fazer agora sem ler um manual."

- Cada NPC oferece uma missão de 3 a 5 minutos.
- Lia pede 3 flores; Theo pede 8 blocos colocados; Nina pede uma foto da aurora.
- Recompensa: adesivo no cartão, moldura de foto ou cosmético de pet.
- Uma missão ativa por vez, com checklist simples no HUD.
- Sem grind, sem moeda premium, sem ranking global.

**Aceite:** aceitar, concluir e entregar a missão. O NPC muda a fala depois da entrega.

**Impacto:** alto. **Esforço:** médio. **Reuso:** `NPC_DEFS`, `talkToNpc`, conquistas e toasts.

### 2.3 Álbum de fotos e cartões

**Job to be done:** "Quero mostrar o lugar bonito que encontrei."

- Modo foto com pose, filtro suave, moldura e legenda curta.
- Galeria local com as 12 melhores fotos.
- Cartão do mundo com nome, seed, tempo, conquistas e foto.
- Download PNG e cópia de código do mundo.
- Nenhum upload automático. Compartilhar é sempre uma ação explícita.

**Aceite:** tirar foto, salvar na galeria, baixar cartão e abrir o mundo pelo código.

**Impacto:** alto. **Esforço:** médio. **Reuso:** `photoMode`, cartão atual e `save.js`.

### 2.4 Clima e aurora

**Job to be done:** "Quero que o mundo pareça vivo."

- Chuva de pétalas no Prado e no Bosque.
- Garoa morna no Deserto Coral, sem dano.
- Aurora noturna na Tundra e na Costa de Nuvens.
- Transições de 20 a 40 segundos, com som ambiente suave.
- Clima apenas visual e sonoro. Não altera colisão, dano ou colheita.

**Aceite:** 3 eventos visíveis em 10 minutos, sem queda de FPS acima de 10%.

**Impacto:** alto. **Esforço:** médio. **Reuso:** partículas, `timeOfDay` e `audio.js`.

### 2.5 Descoberta diária encadeada

**Job to be done:** "Quero um motivo leve para voltar amanhã."

- O Mundo do Dia ganha 3 pistas curtas.
- Cada pista concluída dá um selo local.
- Completar as 3 libera uma skin de pet ou moldura.
- O ciclo reseta à meia-noite local.
- Sem streak punitivo: perder um dia não apaga medalhas.

**Aceite:** completar as 3 pistas e receber o cosmético no mesmo dia.

**Impacto:** alto. **Esforço:** baixo. **Reuso:** `getDaily`, conquistas e `save.js`.

## 3. Prioridade 1: criar e compartilhar melhor

### 3.1 Construção guiada

- Plantas de casa pequena, ponte, mirante e jardim.
- Prévia fantasma antes de confirmar.
- Undo de 10 segundos para erros de colocação.
- Modo Criativo apenas, para não gastar recursos por engano.

**Aceite:** colocar uma planta de 5×5 sem bloquear o jogador e desfazer em um clique.

### 3.2 Código de mundo por snapshot

- Snapshot compactado com RLE e base64.
- Orçamento: menos de 2 KB para um mundo novo.
- Importação com validação de tamanho, seed, modo e diffs.
- Link curto apenas se houver backend; sem backend, usar código copiável.

**Aceite:** exportar, colar em outro navegador e abrir o mesmo terreno em menos de 1 segundo.

### 3.3 Ranking semanal local

- Medalhas de bronze, prata e ouro por tempo e descobertas.
- Reset semanal local, sem comparação global.
- Histórico dos 4 melhores mundos.
- Sem notificação agressiva e sem perda de progresso.

### 3.4 Acessibilidade e família

- Modo daltônico com formas além de cores.
- Remapeamento de teclas e sensibilidade separada para mouse e toque.
- Modo leitura fácil com textos curtos e ícones.
- Perfil infantil com exportação bloqueada e limite de tempo opcional.
- Localização PT-BR, EN e ES.

### 3.5 Controles mobile

- Joystick com zona morta ajustável.
- Botões de pulo, voo, pouso, interagir e foto com alvos de 48 px.
- Arrastar para olhar com sensibilidade independente.
- Teste em 360×800 e 820×1180 sem overflow.

## 4. Prioridade 2: escala e performance

### 4.1 Web Worker para geração e meshing

- Mover geração e meshing para worker.
- Fila com prioridade por distância e direção da câmera.
- Meta: nenhum frame acima de 16 ms ao cruzar chunk.

### 4.2 IndexedDB e save comprimido

- Migrar mundos grandes para IndexedDB.
- Compressão LZ-string no snapshot.
- Migração transparente do localStorage existente.

### 4.3 PWA offline

- Instalação, cache do app shell e funcionamento offline.
- Atualização com aviso suave, sem quebrar sessão.
- Sem service worker agressivo em desenvolvimento.

### 4.4 Qualidade adaptativa

- Medir FPS por 10 segundos e ajustar raio, sombras e partículas.
- Pacote `turbo` automático em dispositivos fracos.
- Manter a opção manual para quem quiser controle.

## 5. O que não fazer

- Combate, vida, morte, fome ou dano de queda.
- Chat livre, voz, matchmaking público ou perfis públicos.
- Contas obrigatórias, nuvem obrigatória ou coleta de dados.
- Loot boxes, moeda premium, anúncios ou streaks punitivos.
- Mundos que dependem de servidor para funcionar.
- Efeitos visuais que derrubam FPS em notebook simples.

## 6. Ordem sugerida de sprints

| Sprint | Entrega | Critério |
|---|---|---|
| 1 | Jardim vivo + save offline | Planta cresce e sobrevive ao reload |
| 2 | Missões de NPC + recompensas cosméticas | 3 missões completáveis |
| 3 | Álbum de fotos + cartão | Foto salva e cartão baixado |
| 4 | Clima + aurora + áudio | 3 eventos em 10 min |
| 5 | Descoberta diária + selos | 3 pistas e cosmético |
| 6 | Acessibilidade + mobile | Teclado, daltônico e 360 px |
| 7 | Snapshot de mundo | Código < 2 KB e importação < 1 s |
| 8 | Worker + IndexedDB + PWA | 60 FPS e offline |

## 7. Métricas de sucesso

- Primeira descoberta relevante em menos de 3 minutos.
- Sessão média acima de 12 minutos.
- 30% dos jogadores voltam em 7 dias.
- 20% compartilham foto ou código.
- 0 erros no console em 30 minutos.
- 60 FPS em notebook integrado e 30 FPS em celular médio.
- Nenhuma perda de item ou mundo em testes de reload.
