# Feature: UX Infantil e Segurança

## Objetivo

Garantir que uma criança de 7 anos consiga jogar sozinha, sem frustração, sem medo e sem riscos digitais.

## Requisitos

| ID | Requisito | Prioridade |
|---|---|---|
| KID-01 | Sem combate, dano, morte, fome ou criaturas hostis | Must |
| KID-02 | Sem chat, comentários, perfis ou contas; single-player não envia dados e multiplayer só troca estado via convite explícito | Must |
| KID-03 | Sem anúncios, compras, loot boxes ou timers de energia | Must |
| KID-04 | Tutorial de 4 passos, pulável e reabrível | Must |
| KID-05 | Dicas contextuais nos primeiros 3 minutos | Must |
| KID-06 | Controles grandes, alto contraste e alvos ≥ 44 px | Must |
| KID-07 | Auto-step de 1 bloco e pulo generoso | Must |
| KID-08 | Sem dano de queda; água não afoga | Must |
| KID-09 | Botão de pausa sempre acessível | Must |
| KID-10 | Textos curtos, linguagem simples, sem jargão | Must |
| KID-11 | Feedback imediato em toda ação (som/visual) | Must |
| KID-12 | Respeitar `prefers-reduced-motion` | Should |
| KID-13 | Modo daltônico para raridades | Should |
| KID-14 | Fechar modais com Esc e botão grande | Must |
| KID-15 | Nenhum texto do usuário renderizado como HTML | Must |

## Critérios de aceite

- Criança entra, move e quebra um bloco em < 30 s sem ajuda.
- Nenhum caminho leva a tela sem saída.
- Ao apertar Esc no jogo, pausa abre em < 100 ms.
- Layout mobile não cobre a hotbar com modais.
- Console sem erros em fluxo de 10 min.

## Anti-metas

- Gamificação agressiva (streaks punitivos, FOMO).
- Notificações push.
- Comparação social ou rankings.
