# Feature: Inventários Múltiplos

## Objetivo

Oferecer inventários com propósitos claros, navegação infantil e transferência de itens sem fricção.

## Tipos

| Inventário | Slots | Acesso | Propósito |
|---|---|---|---|
| Barra rápida | 9 | sempre visível | Construção imediata |
| Mochila | 27 | tecla E / botão | Armazenamento principal |
| Bolsa de Sementes | 9 | aba da mochila | Sementes, frutas e alimentos |
| Baú | 27 | interagir com baú | Armazenamento no mundo |
| Forja | não | aba da mochila | Receitas (não armazena) |
| Álbum de Coleção | não | aba da mochila | Registro de descobertas |

## Requisitos

| ID | Requisito | Prioridade |
|---|---|---|
| INV-01 | Itens iguais empilham até o limite do item (padrão 64) | Must |
| INV-02 | Clique pega/coloca a pilha; clique com pilha na mão deposita | Must |
| INV-03 | Shift+clique move a pilha entre mochila e baú/bolsa | Must |
| INV-04 | Arrastar e soltar deve funcionar com mouse e toque | Must |
| INV-05 | Itens raros exibem cor, brilho e tooltip com descrição | Must |
| INV-06 | Sementes e alimentos são roteados para a bolsa quando houver espaço | Should |
| INV-07 | Baús guardam conteúdo por coordenada e persistem no save | Must |
| INV-08 | Álbum registra item na primeira coleta e mostra silhueta antes | Must |
| INV-09 | Fechar painel devolve item em mãos ao inventário | Must |
| INV-10 | Modo Criativo oferece paleta infinita de blocos | Must |
| INV-11 | Hotbar selecionável por 1 a 9 e roda do mouse | Must |
| INV-12 | Nenhum item pode ser duplicado ou perdido em transferência | Must |
| INV-13 | Layout funciona em 360 px de largura | Must |
| INV-14 | Navegação por teclado (Tab, setas, Enter) no painel | Should |

## Critérios de aceite

- Quebrar 70 blocos de terra preenche 2 slots (64+6) corretamente.
- Mover item da mochila para baú e recarregar mantém conteúdo.
- Coletar item raro dispara toast, som e entrada no álbum uma única vez.
- Abrir baú no celular não cobre a hotbar.
- Nenhum erro de console ao fechar inventário com item em mãos.

## Fora de escopo

- Sort automático, filtros, favoritos.
- Compartilhar baú entre jogadores.
- Autocrafting.
