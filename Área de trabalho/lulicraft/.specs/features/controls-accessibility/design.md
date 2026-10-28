# Design: Controles, Câmera e Acessibilidade

## Visão 1ª/3ª pessoa

- Estado único `thirdPerson` no `main.js`.
- `player.cameraLocked = thirdPerson` impede que `player.update` sobrescreva a câmera.
- `updateThirdPersonCamera(dt)` faz lerp atrás do jogador e olha para a mira.
- Entradas: `F5`, botão da pausa, `#btn-view` no HUD e `#btn-view-touch`.
- `ui.setThirdPerson` centraliza `aria-pressed`, título, rótulo `1ª/3ª` e visibilidade do avatar/viewmodel.

## Viewmodel

### Poses base por categoria

```js
VIEW_POSES = {
  tool:   { x: 0.43, y: -0.50, z: -0.94, rx: 0.14, ry: -0.36, rz: 0.22 },
  weapon: { x: 0.36, y: -0.46, z: -0.90, rx: 0.04, ry: -0.26, rz: 0.12 },
  bow:    { x: 0.38, y: -0.44, z: -0.92, rx: -0.02, ry: -0.46, rz: 0.02 },
  block:  { x: 0.40, y: -0.52, z: -0.96, rx: 0.18, ry: -0.50, rz: 0.14 },
  item:   { x: 0.42, y: -0.52, z: -0.96, rx: 0.16, ry: -0.44, rz: 0.16 },
  hand:   { x: 0.50, y: -0.55, z: -1.00, rx: 0.20, ry: -0.50, rz: 0.16 }
}
```

### Animações

| Ação | Pose |
|---|---|
| Minerar | arco clássico: `rx -1.05`, `rz +0.50`, sobe e avança |
| Arma | estocada/slash: `rx -0.38`, `ry +0.42`, `rz -0.42`, avança no eixo Z |
| Arco | puxa para trás e sobe: `pz +0.12`, `ry +0.55` |
| Colocar | empurrão curto para frente |
| Equipar | item sobe de baixo com rotação suave |
| Recuo | `weaponKick` decai e empurra o item para trás/rotação |

### Canhoto

`handSign = leftHand ? -1 : 1` multiplica X, rotação Y e Z. O muzzle também espelha. Os controles de toque trocam de lado via `body[data-left-hand="true"]`.

## Muzzle e projéteis

```js
right = (cos(yaw), 0, -sin(yaw))
up    = right × aim
start = eye + right * 0.23 * handSign + up * 0.12 + aim * 0.62
```

- Projéteis de bolha, flecha e estrela nascem em `start`.
- `flashMuzzle` desenha um sprite aditivo por 120 ms.
- Efeitos de pétala e martelo usam o mesmo ponto para partículas.

## Configurações

- Defaults em `save.js`; normalização em `normalizeSettings` (main).
- Aplicação centralizada em `applyAllSettings()`:
  - `ui.applySettings`
  - `applyGraphics`
  - `applyCameraSettings` (FOV e view bob)
  - `applyEffectSettings` (partículas, nuvens, FPS)
  - áudio
- `onResetSettings` preserva `character` e `tutorialDone`.

## Query string

| Param | Efeito |
|---|---|
| `sound=0/1` | som |
| `sens=1.2` | sensibilidade do mouse |
| `touchsens=1.8` | sensibilidade do toque |
| `fov=80` | campo de visão |
| `invert=1` | inverter Y |
| `lefthand=1` | canhoto |
| `viewbob=0` | sem balanço |
| `third=1` | começar em 3ª pessoa |
| `fps=1` | contador de FPS |
| `particles=0` | sem partículas |
| `clouds=0` | sem nuvens |
| `quality=pixel` | pacote visual |
| `coords=1` | coordenadas |
| `ui=large` | escala da interface |

## CSS mobile

- Safe areas: `env(safe-area-inset-*)` em HUD, hotbar, joystick e ações.
- Alvos de toque: 48–56 px; botão de visão 50 px.
- Joystick 118 px, knob 48 px.
- `body[data-ui-scale]` ajusta chips e ícones do HUD via `--hud-scale`.
- `@media (orientation: portrait) and (pointer: coarse)` mostra a dica de girar.
- `prefers-reduced-motion` já desliga animações.

## Testes

- `tests/core.test.mjs`: normalização de settings puros, se extraído.
- `tests/e2e.mjs`:
  - clique em `#btn-view` alterna visão;
  - `setSettings({fov: 90})` muda `camera.fov`;
  - `leftHand` espelha `viewModel.position.x`;
  - muzzle de arco fica à direita da câmera.
- Checklist manual em 320, 375, 768 e 1440 px.
