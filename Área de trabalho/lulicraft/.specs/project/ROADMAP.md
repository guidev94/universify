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

## M1.2: Expansão 1.2 (entregue)

- [x] Pets cosméticos com 4 tipos, 8 cores, preview e companheiro 3D que segue o jogador.
- [x] Interações de pet: reação a salto, pouso, craft, forja e petisco craftável.
- [x] NPCs cozy (Lia, Theo e Nina) com nome, fala contextual e tecla R.
- [x] 6 mundos inspirados em cidades brasileiras no lobby.
- [x] Ranking local de tempo por mundo, com abertura direta pelo card.
- [x] Inventário expansível até 512 linhas de mochila, sem truncar itens.
- [x] Pouso seguro segurando Espaço por 4 s durante o voo criativo.
- [x] Animações de caminhada, salto, voo, craft e picareta.
- [x] Pacotes gráficos leves: automático, pixel suave e turbo leve.
- [x] Testes unitários e E2E cobrindo os novos sistemas.

## M1.3: Mobile e controles (em andamento)

- [x] Atalho de visão 1ª/3ª pessoa no HUD, no toque e F5.
- [x] Poses por categoria, muzzle alinhado, recuo e flash.
- [x] Configurações ampliadas (FOV, inverter Y, toque, canhoto, FPS, partículas, nuvens, escala).
- [x] Safe areas, alvos de toque, layout canhoto e dica de paisagem.
- [x] Bridge `window.LulicraftNative` e overrides por query string.
- [x] App Flutter `mobile/` com servidor local e WebView.
- [x] Home, Ajustes e Sobre nativos com settings persistidos e sincronizados.
- [x] Ícone adaptativo, splash e build debug do app.
- [x] Back do sistema em camadas (overlay → pausa → menu) e pause no lifecycle.
- [ ] Validação em aparelho real (AVD 16 KB atual falha ao criar o isolate do engine).

## M1.4: Lulicraft 2.0 (entregue)

- [x] Marca autoral e favicon.
- [x] Construção criativa com seleção de bloco e feedback.
- [x] Resumo agregado da mochila.
- [x] Pet à frente com animações de marcha e olhar.
- [x] Mão e arma com animações distintas.
- [x] Chat seguro por frases prontas com rate limit.
- [x] Mundo Minha Casa com casa rosa, cidade e baús raros.
- [x] Blocos rosa, menta e lavanda.
- [x] Lumi Crew com 18 presets e blink no avatar.
- [x] Áudio ambiente por bioma.
- [x] Testes unitários, E2E e multiplayer atualizados.
- [x] Sync mobile e assets de marca.
- [x] Undo/redo de construção, pick block e atalhos de inventário.
- [x] Busca, ordenação e favoritos no resumo da mochila.
- [x] Mapa local com biomas, baús e NPCs.
- [x] Diário de missões com progresso.
- [x] Vilas procedurais e rotina de NPCs.
- [x] Pacotes de conteúdo JSON com paleta e blocos extras.
- [x] 15 prints reproduzíveis em `docs/screenshots`.
- [x] APK/AAB assinados com keystore de upload.

## M1: Polimento cozy (próximo)

- [ ] Criaturas Lumis selvagens (vagalumes, lumis que aparecem nos biomas).
- [ ] Ciclo climático suave (chuva de pétalas, aurora noturna).
- [x] Sons ambientes por bioma e trilha adaptativa.
- [ ] Trilha musical adaptativa.
- [ ] Sistema de "jardim": plantar sementes e ver crescimento em tempo real e offline.
- [ ] Balões de dica contextuais para crianças que ficam paradas.
- [x] Diário de missões curtas com progresso.
- [ ] Ramificações de missão por NPC com recompensas cosméticas.
- [ ] Álbum de fotos com molduras e legendas.

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

- [x] App Flutter shell (Android/iOS) com WebView e servidor local.
- [x] Assinatura de release com keystore de upload.
- [ ] Publicação nas lojas.
- [ ] Suporte a gamepad.
- [ ] Sincronização opcional por arquivo (sem conta).

## Critérios de saída por milestone

- M1.2: 30 min de sessão sem erro no console; pets, NPCs, cidades e ranking funcionando em desktop e mobile.
- M1: 30 min de sessão sem erro no console; 3 criaturas e 2 eventos climáticos.
- M2: código de mundo < 2 KB para mundo novo; importar em < 1 s.
- M3: axe-core sem violações críticas; navegação 100% por teclado.
- M4: 60 FPS em notebook integrado; 30 FPS em celular médio; TTI < 2,5 s.
- M5: build assinado e teste em 3 dispositivos.
