# Feature: Forja de Lumis (Crafting)

## Objetivo

Permitir criação de itens sem a barreira da grade 3×3. A criança vê o que pode criar, o que falta e cria com um toque.

## Requisitos

| ID | Requisito | Prioridade |
|---|---|---|
| CR-01 | Forja acessível pela mochila e por atalho (C) | Must |
| CR-02 | Lista mostra receitas disponíveis e bloqueadas | Must |
| CR-03 | Receita exibe ingredientes com "tenho/preciso" | Must |
| CR-04 | Botão "Criar" desabilitado se faltar ingrediente | Must |
| CR-05 | Criar consome ingredientes de todos os inventários | Must |
| CR-06 | Item criado vai para mochila/bolsa correta | Must |
| CR-07 | Receitas raras só aparecem após descobrir 1 ingrediente | Should |
| CR-08 | Feedback sonoro + toast ao criar item raro | Must |
| CR-09 | Receitas são data-driven (`recipes.js`) | Must |
| CR-10 | Modo Criativo não consome ingredientes | Must |

## Receitas iniciais

| Resultado | Ingredientes |
|---|---|
| Tábua de Mel ×4 | Tronco de Mel ×1 |
| Graveto ×4 | Tábua de Mel ×1 |
| Baú | Tábua ×8 |
| Lâmpada de Lumi | Fragmento de Lumi ×2 + Graveto ×1 |
| Bloco de Cristal | Cristal ×4 |
| Picareta de Cristal | Graveto ×2 + Cristal ×3 |
| Picareta Aurora | Graveto ×2 + Gema Aurora ×3 + Fragmento ×2 |
| Botas de Nuvem | Pena de Nuvem ×2 + Tábua ×2 |
| Coração de Lumi | Fragmento ×8 + Gema Aurora ×1 |
| Sopa Estelar | Cogumelo Estelar ×1 + Fragmento ×1 |
| Bloco Prisma | Gema Aurora ×4 + Cristal ×4 |
| Semente Prismática | Gema Aurora ×1 + Cogumelo ×1 |

## Critérios de aceite

- Abrir forja sem ingredientes mostra receitas bloqueadas com dica.
- Criar tábua a partir de tronco reduz tronco em 1 e adiciona 4 tábuas.
- Criar item raro dispara celebração.
- Receita não pode ser criada duas vezes com os mesmos ingredientes.

## Fora de escopo

- Grade manual, estações múltiplas, fila de crafting.
