# Feature: Controles, Câmera e Acessibilidade

## Objetivo

Deixar a primeira pessoa confortável, a terceira pessoa a um toque e o jogo configurável para diferentes mãos, telas e sensibilidades, sem perder a simplicidade para crianças.

## Requisitos

| ID | Requisito | Prioridade |
|---|---|---|
| CTR-01 | Botão de visão 1ª/3ª pessoa visível no HUD | Must |
| CTR-02 | Botão de visão específico para toque, com alvo de 48 px | Must |
| CTR-03 | `F5` continua alternando a visão | Must |
| CTR-04 | Estado do botão reflete a visão atual (`aria-pressed`, rótulo e cor) | Must |
| CTR-05 | Picareta e armas têm poses e arcos de ataque distintos | Must |
| CTR-06 | Armas disparam a partir do cano/mão (muzzle), não do centro dos olhos | Must |
| CTR-07 | Ataque tem recuo curto e flash de muzzle | Should |
| CTR-08 | Item equipado desce e sobe ao trocar de slot | Must |
| CTR-09 | Modo canhoto espelha o item, o muzzle e os controles de toque | Should |
| CTR-10 | Sensibilidade separada para mouse e toque | Must |
| CTR-11 | Opção de inverter eixo Y | Should |
| CTR-12 | FOV ajustável de 60° a 100° | Should |
| CTR-13 | Balanço de câmera e item pode ser desligado | Should |
| CTR-14 | Contador de FPS opcional com cor de alerta | Should |
| CTR-15 | Partículas e nuvens podem ser desligadas | Should |
| CTR-16 | Escala de interface compacta/padrão/ampliada | Should |
| CTR-17 | Iniciar em 3ª pessoa é opcional | Should |
| CTR-18 | Safe areas, notch e barra de gestos respeitados | Must |
| CTR-19 | Dica de paisagem em retrato, sem bloquear toques | Should |
| CTR-20 | Restaurar padrões sem apagar personagem/mundos | Must |
| CTR-21 | Overrides por query string para suporte e app nativo | Should |
| CTR-22 | Bridge `window.LulicraftNative` para settings e visão | Must |

## Critérios de aceite

- Em desktop e toque, um toque/clique no botão de visão alterna a câmera e o avatar aparece/some.
- Em terceira pessoa, o viewmodel some e a câmera não volta para a primeira pessoa sozinha.
- Com arco, a flecha nasce à direita da tela (ou esquerda no modo canhoto) e segue a mira.
- Picareta faz arco de mineração; arma faz estocada/slash; arco puxa antes de soltar.
- FOV 60 e 100 alteram a câmera imediatamente e persistem após recarregar.
- Mouse e toque usam sensibilidades independentes.
- Em 375×667 retrato, nenhum botão cobre a hotbar e a dica de paisagem aparece.
- Sem overflow horizontal em 320 px de largura.
- `npm test` e `node tests/e2e.mjs` passam.

## Fora de escopo

- Remapeamento arbitrário de teclas nesta versão.
- Suporte a gamepad.
- Legendas e leitor de tela completos.
- Modo daltônico para raridades (backlog).

## Modelo de settings

```js
{
  sound: true,
  sensitivity: 1,          // 0.4 .. 2.2
  touchSensitivity: 1.6,   // 0.6 .. 2.6
  invertY: false,
  fov: 74,                 // 60 .. 100
  viewBob: true,
  leftHand: false,
  startThird: false,
  showFps: false,
  particles: true,
  clouds: true,
  showCoords: false,
  quality: 'auto',
  uiScale: 'normal',       // compact | normal | large
  character: { ... }
}
```
