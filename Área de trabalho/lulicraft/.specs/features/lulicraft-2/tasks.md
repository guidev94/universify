# Tasks: Lulicraft 2.0

| # | Tarefa | Depende | Feito quando | Status |
|---|---|---|---|---|
| L2-T01 | Criar marca autoral e substituir logo/ícone/splash | não | Logo sem Minecraft/Creeper; app sync | Feito |
| L2-T02 | Corrigir seleção de bloco criativo e feedback de construção | não | E2E coloca bloco rosa | Feito |
| L2-T03 | Adicionar resumo agregado da mochila | não | 3 pilhas mostram total único | Feito |
| L2-T04 | Melhorar pet na frente e animações | não | Pet conduz e anima | Feito |
| L2-T05 | Melhorar animação de mão/arma | não | Quebrar, colocar e usar distintos | Feito |
| L2-T06 | Implementar chat seguro por frases | não | Allowlist e rate limit | Feito |
| L2-T07 | Criar mundo Minha Casa com casa rosa, cidade e baús | não | Mundo abre completo | Feito |
| L2-T08 | Adicionar blocos rosa, menta e lavanda | não | Paleta criativa e atlas | Feito |
| L2-T09 | Adicionar presets autorais e preview expressivo | não | 6 novos presets | Feito |
| L2-T10 | Áudio ambiente por bioma | não | 5 texturas distintas | Feito |
| L2-T11 | Atualizar testes unitários, E2E e multiplayer | T02..T10 | `npm run test:all` | Feito |
| L2-T12 | Atualizar docs, specs e STATE | T11 | Documentação coerente | Feito |
| L2-T13 | Sincronizar mobile e validar Flutter | T01..T12 | Sync + analyze/test | Feito |
| L2-T14 | Undo/redo, pick block e atalhos de inventário | T02 | Histórico de 120 mudanças e atalhos | Feito |
| L2-T15 | Busca, ordenação e favoritos no inventário | T03 | Resumo e slots filtram; favoritos persistem | Feito |
| L2-T16 | Mapa local com biomas, baús e NPCs | T07 | `Tab` e Pausa abrem canvas 2D | Feito |
| L2-T17 | Diário de missões com progresso | T11 | `J` e Pausa abrem 5 missões | Feito |
| L2-T18 | Vilas procedurais e rotina de NPCs | T07 | Casas, praça e baús determinísticos | Feito |
| L2-T19 | Pacotes de conteúdo JSON seguros | T11 | Allowlist, limite e paleta ativa | Feito |
| L2-T20 | Prints reproduzíveis e build release assinado | T14..T19 | 15 prints + APK/AAB/splits | Feito |

## Evidências

- `npm test`: 20 testes unitários, incluindo chat, Minha Casa, presets e pacotes JSON.
- `npm run test:e2e`: smoke test com 7 mundos prontos, 18 presets e 0 erros de console.
- `npm run test:mp`: chat allowlist, rate limit, feed e ausência de HTML injetado.
- `npm run mobile:sync`: 1,94 MB de assets web copiados para `mobile/assets/web`.
- `dart analyze`: sem problemas.
- `flutter test`: 24 testes passando.
- Validação visual de Minha Casa em Chrome headless: spawn na porta, casa rosa, praça, cidade e 68 baús.
- Build de construção criativa validado: paleta seleciona Tábuas Rosas e `tryPlace()` retorna `placed`.
- `npm run test:all`: 20 unitários + smoke + multiplayer passando, 0 exceções e 0 erros de console.
- `npm run screenshots`: 15 PNGs em `docs/screenshots` (menu, personagem, configurações, pacotes, criação, gameplay, 3ª pessoa, inventário, busca, mapa, missões, pausa, multiplayer e mobile).
- `npm run mobile:build`: APK release 1.4.1 (15) assinado com `CN=Lulicraft`, 56,6 MB, SHA-256 `06bcc7d2d3f82b6ee9c34f88cdf629d54be6c895a464e06ec2f85c4fbdb37c49`.
- `flutter build appbundle`: AAB 55,9 MB assinado, SHA-256 `86feb8527ca1e29f98872c9d4d46f5a8e058b5a824e04c2e2e09b12fb8b662ca`; `flutter build apk --split-per-abi`: arm64 24 MB, armv7 21,4 MB, x86_64 25,5 MB.
