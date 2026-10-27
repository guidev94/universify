# Feature: Mundos Prontos e Kit de Raros

## Objetivo

Permitir que uma criança entre em um mundo bonito em poucos segundos, com curadoria de bioma e uma opção clara de começar com itens raros.

## Mundos

| ID | Nome | Seed | Modo | Kit de Raros |
|---|---|---|---|---|
| `jardim` | Jardim de Lumi | `lumi-jardim` | adventure | não |
| `arcoiris` | Vale Arco-Íris | `lumi-arcoiris` | adventure | não |
| `nuvens` | Ilha das Nuvens | `lumi-nuvens` | creative | não |
| `coral` | Deserto Coral | `lumi-coral` | adventure | não |
| `aurora` | Noite de Aurora | `lumi-aurora` | creative | sim |
| `raros` | Caixa de Raros | `lumi-raros` | adventure | sim |

## Requisitos

| ID | Requisito | Prioridade |
|---|---|---|
| PRE-01 | Exibir 6 cards de mundos prontos no menu | Must |
| PRE-02 | Cada card mostra nome, modo e chip de Kit de Raros | Must |
| PRE-03 | Abrir mundo existente com mesmo nome+seed em vez de duplicar | Must |
| PRE-04 | Criar mundo com seed e modo corretos quando não existir | Must |
| PRE-05 | Kit de Raros adiciona 10 itens raros+ ao inventário inicial | Must |
| PRE-06 | Kit de Raros é opcional e não substitui o início normal | Must |
| PRE-07 | Respeitar o limite de 12 mundos com mensagem clara | Must |
| PRE-08 | Funcionar em grade responsiva no celular | Must |

## Kit de Raros

`aurora_pickaxe`, `cloud_boots`, `lumi_charm`, `heart_lumi`, `prism_seed`, `rainbow_sapling`, `star_soup`, `aurora_gem`, `cloud_feather`, `star_mushroom`.

## Critérios de aceite

- Menu mostra 6 cards; clicar em um abre o mundo correto.
- Clicar duas vezes no mesmo card não cria duplicata.
- Mundo com Kit de Raros contém os 10 itens; mundo normal contém apenas o kit inicial.
- Com 12 mundos, abrir um novo mostra aviso e não quebra o menu.
- Cards não causam overflow em 360 px.

## Fora de escopo

- Editor visual de mundos.
- Download de mundos prontos.
- Mundos sazonais rotativos.
