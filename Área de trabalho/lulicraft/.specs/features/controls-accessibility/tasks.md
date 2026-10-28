# Tasks: Controles, Câmera e Acessibilidade

| # | Tarefa | Depende | Feito quando |
|---|---|---|---|
| CTR-T01 | Botão de visão no HUD e no toque | não | Um toque alterna 1ª/3ª |
| CTR-T02 | Estado visual/aria dos botões de visão | T01 | Rótulo e `aria-pressed` corretos |
| CTR-T03 | Poses por categoria no viewmodel | não | Picareta e arma se movem diferente |
| CTR-T04 | Muzzle alinhado e flash | T03 | Flecha nasce na mão |
| CTR-T05 | Recuo e equipar suaves | T03 | Troca de item sem corte |
| CTR-T06 | Modo canhoto no item e nos controles | T03 | Layout espelhado |
| CTR-T07 | Sensibilidade de mouse e toque separadas | não | Sliders independentes |
| CTR-T08 | Inverter Y | T07 | Mouse e toque invertem |
| CTR-T09 | FOV e view bob configuráveis | não | Mudança imediata |
| CTR-T10 | FPS, partículas e nuvens configuráveis | não | Toggle funciona em jogo |
| CTR-T11 | Escala de UI e iniciar em 3ª pessoa | não | Persistem após reload |
| CTR-T12 | Restaurar padrões preservando personagem | T07..T11 | Botão restaura |
| CTR-T13 | Query params e bridge nativa | T07..T11 | Flutter aplica settings |
| CTR-T14 | Safe areas e alvos de toque | não | Sem overflow em 320 px |
| CTR-T15 | Dica de paisagem | não | Retrato mostra, paisagem esconde |
| CTR-T16 | Testes E2E dos novos controles | T01..T13 | Pipeline verde |
