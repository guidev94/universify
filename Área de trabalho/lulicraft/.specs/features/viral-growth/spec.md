# Feature: Crescimento Viral (ético)

## Objetivo

Criar momentos naturalmente compartilháveis sem violar privacidade infantil nem usar dark patterns.

## Mecânicas

| Mecânica | Como funciona | Requisito |
|---|---|---|
| **Seed do Dia** | Todo dia um mundo igual para todos; botão "Jogar o mundo de hoje" | VIR-01 |
| **Código de Mundo** | `LC1.…` com seed + nome + modo, copiável | VIR-02 |
| **Cartão do Mundo** | Imagem 1080×1350 com foto, nome, seed e itens raros | VIR-03 |
| **Modo Foto** | Esconde HUD, aplica vinheta, baixa PNG | VIR-04 |
| **Álbum de Raridades** | Progresso "x/20 descobertos" compartilhável | VIR-05 |
| **Desafio Semanal** | Seed fixa + objetivo (ex.: 5 itens raros) | VIR-06 |
| **Mundo Surpresa** | Código aleatório de um bioma | VIR-07 |
| **Convite de Sala** | Link `?join=CODE` com código de 5 caracteres | VIR-11 |

## Requisitos

| ID | Requisito | Prioridade |
|---|---|---|
| VIR-01 | Seed do dia baseada em data local, exibida no menu | Must |
| VIR-02 | Copiar código com feedback "Copiado!" | Must |
| VIR-03 | Gerar cartão sem upload (canvas local) | Must |
| VIR-04 | Foto usa o frame atual e mantém proporção | Must |
| VIR-05 | Álbum mostra silhuetas e contador | Must |
| VIR-06 | Desafio semanal com progresso local | Should |
| VIR-07 | Botão "Mundo Surpresa" | Should |
| VIR-08 | Nenhum compartilhamento automático | Must |
| VIR-09 | Sem marca d'água infantil ou coleta de nome real | Must |
| VIR-10 | Cartão inclui "feito com Lulicraft" e seed legível | Must |
| VIR-11 | Link de convite abre o modal multiplayer com código preenchido | Must |

## Critérios de aceite

- Copiar código em desktop e mobile funciona com fallback.
- Cartão gerado em < 500 ms e baixado como PNG.
- Foto não inclui HUD nem modais.
- Seed do dia idêntica em duas abas no mesmo dia.
- Single-player não envia dados; convite multiplayer é uma ação explícita.
- Link de convite abre o modal com o código preenchido.

## Fora de escopo

- Integração com redes sociais via SDK.
- Upload para servidor, links públicos, analytics.
