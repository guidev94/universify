# Tasks: Geração de Mundos

| # | Tarefa | Depende | Feito quando |
|---|---|---|---|
| WG-T1 | PRNG + noise 2D/3D determinísticos | não | Teste com seed fixa repete sequência |
| WG-T2 | Atlas procedural de texturas 8×8 | não | 30+ tiles desenhados sem asset externo |
| WG-T3 | Registro de blocos com faces e tiles | WG-T2 | Cada bloco tem topo/lado/base |
| WG-T4 | `generateChunk` com altura, bioma, água e cavernas | WG-T1 | Chunk visualizável |
| WG-T5 | Árvores, flores, corais, cogumelos | WG-T4 | 5 biomas reconhecíveis |
| WG-T6 | Santuários e ilhas de nuvem | WG-T4 | Baú encontrado em teste |
| WG-T7 | Mesher com face culling, AO e cross-plants | WG-T3,T4 | Chunk renderiza sem faces internas |
| WG-T8 | Chunk manager com raio e dispose | WG-T7 | Andar não vaza memória |
| WG-T9 | Spawn seguro + seed do dia | WG-T4 | Spawn nunca dentro de bloco |
| WG-T10 | Diffs de save aplicados ao chunk | WG-T4 | Bloco quebrado persiste após reload |
