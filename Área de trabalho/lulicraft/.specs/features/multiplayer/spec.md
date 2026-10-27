# Feature: Multiplayer P2P

## Objetivo

Permitir que até 6 exploradores visitem o mesmo mundo por convite, sem contas, servidor de jogo, chat ou matchmaking público.

## Arquitetura

- PeerJS 1.5.4 vendorizado (`src/vendor/peerjs.min.js`).
- WebRTC P2P; PeerJS Cloud é usado apenas como signaling.
- Anfitrião mantém o mundo e retransmite mensagens.
- Convidado recebe um snapshot inicial e sincroniza blocos, baús e posições.
- Sem backend próprio; sem persistência oficial do convidado durante a sala.

## Protocolo

| Mensagem | Direção | Conteúdo |
|---|---|---|
| `welcome` | host → guest | snapshot, code, hostId, players |
| `hello` | guest → host | id, nome, personagem |
| `join` | host → todos | jogador entrou |
| `player` | ambos | posição, yaw, pitch, personagem |
| `block` | ambos | x, y, z, bloco |
| `chest` | ambos | chave, itens |
| `patch` | host → guest | diffs de chunks |
| `players` | host → todos | lista de jogadores |
| `leave` | ambos | id |
| `left` | host → todos | jogador saiu |

## Requisitos

| ID | Requisito | Prioridade |
|---|---|---|
| MP-01 | Criar sala com código de 5 caracteres e link `?join=CODE` | Must |
| MP-02 | Entrar por código ou link, com código pré-preenchido | Must |
| MP-03 | Limite de 6 jogadores por sala | Must |
| MP-04 | Sincronizar posição, bloco, baú e patch de chunk | Must |
| MP-05 | Mostrar avatares remotos com nome e personagem | Should |
| MP-06 | Sanitizar nome, personagem, coordenadas, blocos e itens | Must |
| MP-07 | Host retransmite mensagens sem confiar cegamente no cliente | Must |
| MP-08 | Convidado não sobrescreve o save local durante a sala | Must |
| MP-09 | Se o host sair, o convidado continua no mundo local | Must |
| MP-10 | Sair da sala encerra conexões e limpa avatares | Must |
| MP-11 | Não haver chat, voz, contas ou anúncios | Must |
| MP-12 | Funcionar em desktop e toque | Must |

## Segurança e privacidade

- Código/link é convite, não senha; sala não é autenticada.
- Nome limitado a 16 caracteres e sem `<>`.
- Coordenadas limitadas a ±1.000.000; patches limitados a 64 chunks e 8192 entradas.
- Blocos inválidos e itens desconhecidos são descartados.
- Sem dados pessoais; somente estado do jogo é transmitido.
- Signaling público PeerJS pode registrar metadados de conexão.

## Critérios de aceite

- Dois navegadores: host cria, convidado entra, seeds iguais e 2 jogadores.
- Bloco colocado no host aparece no convidado.
- Convidado sai e host volta a 1 jogador.
- Host sai e convidado continua rodando, sozinho, no mesmo mundo.
- Link de convite abre o modal com código preenchido.
- Nenhuma exceção/erro de console no teste E2E.

## Fora de escopo

- Chat, voz, emotes, amigos, contas.
- Servidor dedicado, relay próprio, matchmaking público.
- Sincronizar inventário completo, forja ou conquistas.
- Moderação de sala.
