# Design: App Mobile (Flutter)

## Decisão principal

**Flutter shell + WebView + servidor HTTP local empacotado.**

```text
Flutter
├── HomePage
├── GamePage
│   ├── LocalWebServer (dart:io, 127.0.0.1)
│   ├── WebViewController (webview_flutter)
│   └── JsBridge → window.LulicraftNative
└── SettingsPage / AboutPage
```

### Por que não abrir `file://`

- ES Modules são bloqueados por CORS em `file://`.
- WebGL funciona, mas `localStorage` pode ter origem opaca.
- Texturas e imports relativos ficam frágeis.

### Por que não portar o engine para Dart

- O engine tem milhares de linhas e depende de Three.js.
- O objetivo é distribuição mobile, não reescrita.
- O jogo web continua evoluindo; o app deve acompanhar sem fork.

## Fluxo de inicialização

1. `main()` configura tema, orientação e `SystemChrome` edge-to-edge.
2. `HomePage` mostra logo, botões Jogar/Novo mundo/Configurações/Sobre.
3. Ao jogar:
   - O servidor local inicia em uma porta livre (`bind` em `127.0.0.1`, porta 0).
   - A WebView carrega `http://127.0.0.1:<porta>/index.html?app=1&menu=1` ou `?demo=1`.
   - Aguarda `window.LulicraftNative.isReady()`.
   - Aplica as preferências salvas via `setSettings`.
   - Mantém `wakelock_plus` ativo.
4. Ao sair do jogo:
   - Chama `quitToMenu()`/`saveNow` pela bridge.
   - Libera o servidor e o wakelock.

## Servidor local

- `dart:io` `HttpServer.bind(InternetAddress.loopbackIPv4, 0)`.
- Resolve apenas caminhos relativos dentro de `assets/web`.
- Bloqueia `..`, caminhos absolutos e métodos diferentes de `GET`/`HEAD`.
- MIME types: html, js, mjs, css, json, png, svg, woff2, wasm.
- Cabeçalhos `Cache-Control: no-store` em desenvolvimento e `max-age=3600` em release.
- Porta exposta apenas em loopback.

## Assets

```text
mobile/assets/web/   ← cópia dos assets do jogo
mobile/lib/*.dart
mobile/android/*
mobile/ios/*
```

Script `npm run mobile:sync` copia `index.html`, `logo.png`, `assets/`, `src/` e `vercel.json` (não usado no app) para `mobile/assets/web/`.

## Bridge nativa

O jogo expõe:

```js
window.LulicraftNative = {
  version: '1.3.0',
  getSettings(): object,
  setSettings(patch: object): object,
  toggleThirdPerson(): boolean,
  setThirdPerson(active: boolean): boolean,
  isReady(): boolean,
  getState(): { running, ready, thirdPerson, touch, world, fps },
  openSettings(): void,
  pause(): void,
  quitToMenu(): void
};
```

Flutter chama com `runJavaScript` e usa `JavaScriptChannel` para receber eventos:

```js
LulicraftBridge.postMessage(JSON.stringify({ type: 'ready' }));
```

Eventos: `ready`, `pause`, `settings-changed`, `fps`.

## Configurações

Persistência em `shared_preferences` com a mesma chave `lulicraft:settings` versionada pelo app. O app guarda um espelho e envia o objeto completo na inicialização e em cada mudança.

Campos: `sound`, `sensitivity`, `touchSensitivity`, `invertY`, `fov`, `viewBob`, `leftHand`, `startThird`, `showFps`, `particles`, `clouds`, `showCoords`, `quality`, `uiScale`, `character`.

## Ciclo de vida

- `AppLifecycleState.paused` → `pause()` na bridge + `saveNow`.
- `AppLifecycleState.resumed` → retoma wakelock e reaplica settings.
- Botão voltar (`PopScope`):
  1. Se a WebView tem modal aberto, `handleEscape()`.
  2. Se está em jogo, `quitToMenu()`.
  3. Senão, fecha o app.
- `AndroidManifest.xml`: `configChanges="orientation|screenSize|keyboardHidden|uiMode"` para não recriar a Activity.
- iOS: `UIViewControllerBasedStatusBarAppearance=false`, `UIStatusBarHidden=true`.

## Segurança e privacidade

- Nenhuma permissão além de `INTERNET` (já padrão) e rede local.
- `usesCleartextTraffic` habilitado apenas para loopback; o app não acessa HTTP externo.
- Links do GitHub e do jogo abrem com `url_launcher` no navegador.
- Sem WebView `JavaScriptMode.unrestricted` para domínios externos: a navegação é limitada ao servidor local e a links externos são interceptados.

## Ícone e splash

- Ícone mestre 1024×1024 autoral: bloco de grama estilizado com núcleo de luz Lumi, sem referência a Minecraft.
- Gerar mipmaps Android (`mipmap-*`) e iOS (`AppIcon.appiconset`).
- Splash com fundo `#101a26`, logo centralizado e nome Lulicraft.
- Usar `flutter_launcher_icons` (dev dependency) e `flutter_native_splash` se disponível; caso contrário, copiar os PNGs manualmente.

## Testes

- `flutter analyze`.
- `flutter test` para: normalização de settings, parser de URL, bloqueio de path traversal.
- Teste manual em emulador: jogar, alternar visão, mudar FOV, voltar, girar tela, bloquear/desbloquear.
- Teste de longa duração: 10 min com app em segundo plano e retorno.

## Riscos

| Risco | Mitigação |
|---|---|
| WebView antiga sem WebGL | Exibir mensagem clara e exigir Android 7+/iOS 13+ |
| `localStorage` limpo pelo sistema | Exportar/importar arquivo continua disponível |
| Assets duplicados aumentam o APK | Sync seletivo e compressão |
| Áudio bloqueado sem gesto | O botão Jogar conta como gesto do usuário |
| Loopback bloqueado por OEM | Fallback para `loadFlutterAsset` com `baseUrl` local |
