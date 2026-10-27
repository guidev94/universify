# Feature: Save e Mundos

## Objetivo

Salvar e restaurar mundos completos localmente, com múltiplos slots, autosave, exportação/importação e compartilhamento por código.

## Requisitos

| ID | Requisito | Prioridade |
|---|---|---|
| SV-01 | Criar mundo com nome, seed e modo | Must |
| SV-02 | Listar mundos com nome, seed, modo, data e playtime | Must |
| SV-03 | Autosave a cada 15 s e ao sair | Must |
| SV-04 | Salvar diffs de blocos por chunk (não o mundo inteiro) | Must |
| SV-05 | Salvar inventário, baús, posição, tempo, álbum e conquistas | Must |
| SV-06 | Exportar mundo como arquivo `.lulicraft` | Must |
| SV-07 | Importar arquivo validado e jogar | Must |
| SV-08 | Código de mundo curto (seed + nome + modo) | Must |
| SV-09 | Seed do dia estável e destacada no menu | Should |
| SV-10 | Deletar mundo com confirmação | Must |
| SV-11 | Save corrompido não deve quebrar o menu | Must |
| SV-12 | Migração de versão do save | Should |
| SV-13 | Limite de 12 mundos com aviso claro | Should |
| SV-14 | Single-player não envia dados; multiplayer só troca estado via P2P após convite explícito | Must |

## Critérios de aceite

- Quebrar 3 blocos, recarregar e encontrar os 3 blocos quebrados.
- Exportar, apagar e importar restaura inventário e construções.
- Seed do dia muda à meia-noite local.
- Save inválido exibe mensagem e mantém outros mundos intactos.
- Código de mundo gera o mesmo terreno em outro navegador.

## Fora de escopo

- Contas, nuvem, sync entre dispositivos.
- Mundos infinitos além do raio carregado (diffs ficam no save).
