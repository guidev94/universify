# Lulicraft: Game Design Document

## 1. Fantasia central

O mundo de **Lumi** era colorido até a **Aurora** adormecer. Agora as cores escapam em fragmentos. O jogador é um **Jardineiro de Lumi**: explora, coleta, constrói e acorda cristais para devolver cor e luz aos biomas.

Não há vilão. O "desafio" é a curiosidade: encontrar o próximo item raro, a próxima estrutura, o próximo bioma.

## 2. Loop principal (30 s)

```
Explorar → avistar algo brilhante → quebrar/coletar → celebrar descoberta
   ↑                                                        ↓
   └──────── construir, criar na forja, mostrar no álbum ←──┘
```

### Loop de sessão (15 min)

1. Acordar no mundo (spawn seguro).
2. Seguir a bússola de Lumi até um ponto de interesse.
3. Coletar recursos e 1 a 2 itens raros.
4. Craftar algo que abre novas possibilidades (picareta, botas, lâmpada).
5. Construir ou decorar um cantinho.
6. Conversar com um NPC, alimentar o pet ou completar uma descoberta.
7. Salvar/fotografar/compartilhar.

### Loop de longo prazo

- Completar o álbum (20+ itens).
- Encontrar a Coroa de Lumi (1 por mundo).
- Descobrir os 5 biomas.
- Construir uma base em cada bioma.
- Completar desafios semanais.

## 3. Modos

| Modo | Regras | Para quem |
|---|---|---|
| **Aventura Cozy** | Recursos finitos, forja consome, voo bloqueado | Descoberta e construção com propósito |
| **Criativo** | Blocos infinitos, voo, forja grátis, sem drops | Construção livre e crianças menores |

Sem dano, morte, fome ou queda. Água permite nadar; no Criativo, voar.

## 4. Biomas

| Bioma | Paleta | Sensação | Itens-assinatura |
|---|---|---|---|
| **Prado Musgo** | verde-lima, amarelo | acolhedor, inicial | Flor Rosa, Mel, Fragmento |
| **Bosque Arco-Íris** | pastel multicolor | encantado | Semente Prismática, Cogumelo Estelar |
| **Deserto Coral** | coral, areia quente | vasto, misterioso | Cristal Solar, Coral |
| **Tundra Lumina** | azul-gelo, branco | silencioso, mágico | Gema Aurora, Gelo |
| **Costa de Nuvens** | turquesa, branco | leve, aberto | Pena de Nuvem, Bloco de Nuvem |

Cada bioma tem uma "assinatura sonora" (nota base diferente no ambiente) e partículas próprias.

## 5. Progressão

```
Início: mãos + 4 blocos de terra
  → quebrar árvore → tábuas → baú
  → achar Fragmento de Lumi → lâmpada
  → achar Cristal → picareta de cristal
  → achar Gema Aurora → picareta aurora (3×3)
  → achar Pena → botas de nuvem (salto duplo)
  → juntar 8 fragmentos + gema → Coração de Lumi (radar)
  → encontrar Coroa de Lumi (troféu mítico)
```

Sem árvore de habilidades. A progressão é **horizontal e por descoberta**.

## 6. Inventários

1. **Barra rápida** (9), construção.
2. **Mochila** (27), tudo.
3. **Bolsa de Sementes** (9), sementes e alimentos, auto-roteados.
4. **Baú** (27 por baú), armazenamento no mundo.
5. **Forja**, receitas.
6. **Álbum**, coleção, não armazena.

## 6.1 Mochila expansível

- A mochila começa com 27 slots e cresce em linhas de 9 quando necessário.
- Limite técnico: 512 linhas, suficiente para sessões longas e kits grandes.
- Itens de semente e alimento continuam priorizando a Bolsa de Sementes.
- A expansão é automática ao coletar, craftar ou receber loot; nada é truncado.
- O save preserva o tamanho atual da mochila.

## 7. Raridade e recompensa

- Comum, Incomum, Raro, Épico, Lendário, Mítico.
- Pistas múltiplas: cor + estrelas + som + texto + partículas.
- Item raro: toast, som cristalino, partículas ao redor do jogador e registro no álbum.
- Mítico: cartão especial e efeito de aurora no céu.

## 8. Conquistas

| Conquista | Condição |
|---|---|
| Primeiro Brilho | Quebrar o primeiro bloco |
| Jardineiro | Colocar 20 blocos |
| Colecionador | Descobrir 10 itens |
| Caçador de Raros | Encontrar 3 itens raros |
| Arquiteto | Colocar 100 blocos |
| Explorador | Visitar 5 biomas |
| Forjador | Criar 5 itens |
| Coroado | Encontrar a Coroa de Lumi |

## 9. Desafio diário/semanal

- **Diário:** "Colete 5 Fragmentos de Lumi" no mundo do dia.
- **Semanal:** seed fixa + "encontre 3 itens raros" ou "construa 50 blocos".
- Recompensa: selo no cartão do mundo, sem moeda premium.

## 10. Áudio

- Notas pentatônicas por bioma; sinos para raros.
- Passos com variação de tom por bloco.
- Ambiente suave (vento, água, grilos) em loop gerado por WebAudio.
- Sem voz, sem letras, sem áudio agressivo.

## 11. Viralidade ética

- Seed do dia, código de mundo, cartão do mundo, modo foto, álbum.
- Zero upload automático, zero conta, zero analytics.
- Compartilhar é sempre uma ação explícita do jogador.
- Convite de sala é opt-in; o chat usa frases prontas e não há lista pública de salas.

## 11.1 Pets e NPCs

### Pets
- 4 tipos: Lumi, Gatinho, Coruja e Coelho.
- 8 cores cosméticas; a cor é aplicada ao modelo 3D e ao preview.
- O pet anda à frente do jogador, balança cauda/orelhas, anima patas e reage a salto, pouso, craft e forja.
- Petisco de Lumi é craftável e alimenta o pet com uma reação alegre.
- Pet é cosmético: não coleta, não bloqueia dano e não altera progressão.

### NPCs
- Lia, jardineira; Theo, construtor; Nina, observadora.
- Ficam perto do spawn, têm nome flutuante e uma fala curta.
- Aproximar mostra um balão com a tecla R.
- Conversar abre uma fala contextual e um som suave.
- Sem chat livre, sem coleta de dados, sem diálogo ramificado complexo.

## 11.2 Vilas e rotina

- Chunks elegíveis geram vilas com duas casas, praça, lampiões, flores e um baú.
- O Mundo Minha Casa não recebe vila procedural para preservar a cidade autoral.
- NPCs caminham em um raio curto, param e olham para o jogador quando ele se aproxima.
- A vila é determinística pela seed; não há NPCs hostis ou invasão de construções.

## 11.3 Mapa e missões

- O mapa local mostra 64 blocos ao redor, com cores por bioma, baús, NPCs e a direção do jogador.
- O diário de missões acompanha coleta de Lumi, blocos colocados, biomas, itens forjados e carinho do pet.
- Missões não criam economia paralela; o progresso vem dos stats já existentes.
- O mapa é consulta, não fast travel.

## 11.4 Cidades brasileiras

- 6 mundos curados: Rio de Lumi, Avenida das Cores, Pelourinho de Blocos, Pontes do Recife, Amazônia Encantada e Ilha da Magia.
- Cada card mostra cidade/UF, ideia, modo, selo e botão Explorar.
- Seeds fixas e determinísticas; nenhum backend necessário.
- O objetivo é representatividade e inspiração para construir, não replicar monumentos com precisão.

## 11.5 Ranking de tempo

- Lista local dos mundos do jogador, ordenada por tempo jogado.
- Mostra nome, seed, modo e tempo formatado.
- Abre o mundo diretamente pelo botão do ranking.
- Sem ranking global, sem contas, sem comparação entre crianças.

## 12. Personagens

- 18 presets do Lumi Crew: Lumi, Nino, Bia, Tico, Dara, Sol, Maya, Kai, Luna, Nori, Iris, Zoe, Pipo, Nara, Teo, Yumi, Cacau e Vivi.
- 6 skins de arma: Aurora, Pétala, Solar, Bolha, Menta e Doce.
- 5 armas mágicas sem dano: Lâmina de Pétalas, Varinha de Bolhas, Estilingue Estelar, Martelo Solar e Arco Aurora.
- Nome + 4 cores + acessório; tudo cosmético.
- Aparência aparece no cartão do mundo e nos avatares multiplayer.
- Criança pode começar como Lumi e personalizar depois, sem loja.

## 13. Mundos prontos e Kit de Raros

- 7 cards no menu: Jardim de Lumi, Vale Arco-Íris, Ilha das Nuvens, Deserto Coral, Noite de Aurora, Caixa de Raros e Minha Casa.
- Três mundos usam Kit de Raros; o checkbox também permite ativá-lo em qualquer mundo novo.
- Minha Casa traz uma casa rosa, cidade ao redor, praça e baús raros determinísticos.
- Kit de Raros: 10 itens raros+ para uma primeira sessão cheia de descobertas.
- Kit é opcional; o início normal continua com o kit inicial cozy.

## 14. Multiplayer cozy

- Até 6 exploradores por sala, por convite de 5 caracteres ou link.
- Host mantém o mundo e retransmite blocos, baús, posições e patches.
- Chat apenas com frases prontas; sem texto livre, voz, contas, matchmaking público ou combate.
- Convidado entra com personagem próprio e starter kit.
- Se o host sair, a sala fecha e o convidado continua no mundo local.
- Segurança: convite não é senha; compartilhe só com pessoas de confiança.

## 14.1 Controles e conforto

- `WASD` anda, mouse olha, `Espaço` pula e `Shift` corre.
- No Criativo, `V` alterna voo; segurar `Espaço` por 4 s desce e pousa com segurança.
- `E` mochila, `C` forja, `M` som, `F` foto, `F5` terceira pessoa, `F11` tela cheia, `F3` coordenadas.
- `R` conversa com o NPC próximo.
- `1` a `9` selecionam a hotbar; a roda do mouse também troca de slot.
- `Shift+1` a `Shift+9` trocam o slot selecionado com o alvo.
- `Tab` abre o mapa local; `J` abre o diário de missões.
- `Ctrl/Cmd+Z` desfaz e `Ctrl/Cmd+Y` refaz a última construção.
- `B` abre a paleta criativa; `Q` guarda o item selecionado.
- Clique do meio copia o bloco apontado.
- `Esc` fecha o painel atual ou abre a pausa.

## 14.2 Pacotes de conteúdo

- Pacotes JSON instalam paletas e blocos extras na paleta criativa.
- Validação por allowlist: versão, id, nome, autor, descrição, cores e blocos conhecidos.
- Limite de 64 KB e 8 pacotes; nada de JavaScript, HTML ou caminhos de arquivo.
- O pacote ativo muda variáveis CSS e a lista de blocos criativos; não altera save nem regras.

## 14.3 Pacotes gráficos

| Pacote | Efeito | Para quem |
|---|---|---|
| **Cozy automático** | Qualidade padrão e animações completas | Maioria dos dispositivos |
| **Pixel suave** | `image-rendering: pixelated`, visual retrô | Quem gosta de pixel art |
| **Turbo leve** | Reduz animações decorativas e custo visual | Notebooks simples e celulares |

A preferência fica salva e é aplicada no menu e no jogo.

## 15. Métricas de diversão

- "Uau" por minuto: ≥ 1 descoberta relevante a cada 3 min.
- Fricção zero: nenhuma ação essencial exige menu profundo.
- Rejogabilidade: seeds diferentes mudam biomas e estruturas.
