# Lulicraft: Roadmap

## M0: Fundação (entregue neste repositório)

- [x] Estrutura spec-driven (`.specs/` + `docs/`).
- [x] Engine voxel Three.js sem build step.
- [x] Geração procedural com seed, 5 biomas, cavernas e estruturas.
- [x] Movimento, colisão, voo, quebrar/colocar, raycast.
- [x] Inventários: hotbar, mochila, bolsa, baú, álbum.
- [x] Forja por receitas, raridades, drops raros e efeitos.
- [x] Save/load local, múltiplos mundos, export/import, seed do dia.
- [x] UI, tutorial, conquistas, modo foto, som procedural.
- [x] Testes de fumaça em Chrome headless.

## M0.5: Expansão 1.1 (entregue)

- [x] Personagens cosméticos com presets, cores e nome.
- [x] 6 mundos prontos e Kit de Raros opcional.
- [x] Multiplayer P2P com código/link de convite e até 6 jogadores.
- [x] Avatares remotos, sincronização de blocos, baús e posições.
- [x] Testes unitários, smoke test e E2E multiplayer em dois navegadores.

## M1: Polimento cozy (próximo)

- [ ] Criaturas Lumis passivas (vagalumes, lumis que seguem o jogador).
- [ ] Ciclo climático suave (chuva de pétalas, aurora noturna).
- [ ] Sons ambientes por bioma e trilha adaptativa.
- [ ] Sistema de "jardim": plantar sementes e ver crescimento em tempo real.
- [ ] Balões de dica contextuais para crianças que ficam paradas.

## M2: Construção social assíncrona

- [ ] Código de mundo com *snapshot* compactado (RLE + base64).
- [ ] Galeria local de cartões do mundo.
- [ ] Desafios semanais com seed fixa e medalhas.
- [ ] "Visita surpresa": abrir o mundo de um código e explorar sem editar.

## M3: Acessibilidade e família

- [ ] Modo daltônico para raridades (formas + cores).
- [ ] Controles remapeáveis e sensibilidade separada.
- [ ] Modo leitura fácil (textos curtos, ícones).
- [ ] Perfil infantil com bloqueio de exportação.
- [ ] Localização PT-BR / EN / ES.

## M4: Performance e alcance

- [ ] Web Worker para geração e meshing.
- [ ] Compressão de save (LZ-string) e IndexedDB para mundos grandes.
- [ ] PWA offline instalável.
- [ ] Renderer adaptativo (qualidade automática por FPS).
- [ ] Build minificado opcional.

## M5: Plataformas

- [ ] Empacotamento Capacitor (Android/iOS).
- [ ] Suporte a gamepad.
- [ ] Sincronização opcional por arquivo (sem conta).

## Critérios de saída por milestone

- M1: 30 min de sessão sem erro no console; 3 criaturas e 2 eventos climáticos.
- M2: código de mundo < 2 KB para mundo novo; importar em < 1 s.
- M3: axe-core sem violações críticas; navegação 100% por teclado.
- M4: 60 FPS em notebook integrado; 30 FPS em celular médio; TTI < 2,5 s.
- M5: build assinado e teste em 3 dispositivos.
