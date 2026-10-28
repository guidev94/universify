# Feature: Itens Autorais e Raridades

## Objetivo

Criar um catálogo de itens originais com identidade visual e sonora, onde a raridade é compreensível por crianças e recompensa a exploração.

## Raridades

| Raridade | Cor | Peso em loot | Pistas |
|---|---|---|---|
| Comum | `#9aa7a0` | 55% | som suave, sem brilho |
| Incomum | `#5fd38a` | 25% | brilho leve |
| Raro | `#5aa9ff` | 15% | brilho + som cristalino |
| Épico | `#b07cff` | 4,5% | aura + toast roxo |
| Lendário | `#ffc94d` | 0,5% | raios + som de sino |
| Mítico | `#ff7ad9` | evento | cartão especial no álbum |

## Catálogo mínimo

| Item | Tipo | Raridade | Efeito |
|---|---|---|---|
| Fragmento de Lumi | material | Incomum | craft e moeda |
| Gema Aurora | material | Raro | craft lendário |
| Cogumelo Estelar | alimento | Raro | velocidade 20 s |
| Semente Prismática | semente | Raro | árvore arco-íris |
| Pena de Nuvem | material | Épico | craft botas |
| Coração de Lumi | charme | Épico | revela minérios próximos |
| Picareta Aurora | ferramenta | Lendário | quebra 3×3 |
| Coroa de Lumi | troféu | Mítico | 1 por mundo, no santuário raro |
| Botas de Nuvem | calçado | Épico | salto duplo |
| Sopa Estelar | alimento | Raro | velocidade + salto |
| Bloco Prisma | bloco | Lendário | decoração brilhante |
| Lâmpada de Lumi | bloco | Incomum | iluminação |

## Requisitos

| ID | Requisito | Prioridade |
|---|---|---|
| IT-01 | Todo item tem id, nome, descrição, raridade, ícone e categoria | Must |
| IT-02 | Drops de blocos seguem tabela determinística + chance | Must |
| IT-03 | Item raro dispara toast, som, partículas e álbum | Must |
| IT-04 | Raridade não depende só de cor (texto + estrelas) | Must |
| IT-05 | Ferramentas alteram velocidade/alcance de quebra | Must |
| IT-06 | Picareta Aurora quebra área 3×3 | Should |
| IT-07 | Botas de Nuvem concedem salto duplo | Should |
| IT-08 | Coração de Lumi pulsa minérios próximos | Should |
| IT-09 | Comida aplica buff temporário com ícone no HUD | Should |
| IT-10 | Coroa de Lumi só existe 1 por mundo | Should |
| IT-11 | Ícones são gerados sem assets externos | Must |
| IT-12 | Itens não empilháveis têm limite 1 | Must |

## Critérios de aceite

- Minerar 100 blocos de pedra rende ao menos 1 Fragmento de Lumi.
- Encontrar Gema Aurora exibe toast azul e entrada no álbum.
- Picareta Aurora quebra 9 blocos ao clicar em área válida.
- Botas de Nuvem permitem segundo salto no ar.
- Coroa aparece no máximo uma vez por seed.

## Fora de escopo

- Encantamentos, durabilidade, forja de equipamentos.
- Economia com NPCs.
