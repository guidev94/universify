# Feature: Geração de Mundos

## Objetivo

Gerar mundos 3D determinísticos a partir de uma seed, com biomas autorais, relevo suave, cavernas, árvores, flores e estruturas de recompensa, rápidos o bastante para rodar em notebook simples.

## Requisitos

| ID | Requisito | Prioridade |
|---|---|---|
| WG-01 | A mesma seed deve gerar exatamente o mesmo mundo | Must |
| WG-02 | Deve existir pelo menos 5 biomas: Prado Musgo, Bosque Arco-Íris, Deserto Coral, Tundra Lumina, Costa de Nuvens | Must |
| WG-03 | Relevo deve ter praias, mar raso e montanhas suaves | Must |
| WG-04 | Cavernas devem existir abaixo da superfície e conter minérios | Must |
| WG-05 | Árvores, flores, grama alta, cogumelos e corais devem povoar os biomas | Must |
| WG-06 | Estruturas de Lumi (santuário com baú) devem aparecer esparsamente | Must |
| WG-07 | Spawn deve ser seguro, no nível do mar, sem bloco sólido na cabeça | Must |
| WG-08 | Chunks devem ser gerados/meshados sem travar a UI | Should |
| WG-09 | Mundo deve ter limite vertical claro e bedrock no fundo | Must |
| WG-10 | Seed do dia deve ser estável por 24 h | Should |
| WG-11 | Bioma deve ser identificável pelo jogador (cor, blocos, nome no HUD) | Should |
| WG-12 | Geração deve ser independente de ordem de carregamento de chunks | Must |

## Critérios de aceite

- Duas sessões com `seed=12345` produzem o mesmo mapa de alturas.
- Andar 2 minutos revela ao menos 2 biomas distintos.
- Nenhum chunk gera blocos acima de `y=47` ou abaixo de `y=0`.
- Santuário contém baú com pelo menos 1 item raro em 70% dos casos.
- Geração de 1 chunk < 30 ms em desktop de teste.

## Fora de escopo

- Biomas infinitos por stream (v1 usa raio finito ao redor do jogador).
- Rios, vulcões, cidades, NPCs.
- Sincronização multiplayer de terreno.
