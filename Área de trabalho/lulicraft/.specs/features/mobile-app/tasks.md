# Tasks: App Mobile (Flutter)

| # | Tarefa | Depende | Feito quando | Status |
|---|---|---|---|---|
| MOB-T01 | Criar projeto Flutter `mobile/` com org `com.guidev94` | não | `flutter analyze` roda | ✅ |
| MOB-T02 | Configurar tema, orientação, edge-to-edge e rotas | T01 | App abre na Home | ✅ |
| MOB-T03 | Implementar servidor HTTP local seguro | T01 | Assets servem em 127.0.0.1 | ✅ |
| MOB-T04 | Copiar assets web para `mobile/assets/web` | T01 | Jogo carrega na WebView | ✅ |
| MOB-T05 | Implementar `GamePage` com WebView e wakelock | T03, T04 | Dá para jogar | ✅ |
| MOB-T06 | Implementar bridge JS e sincronização de settings | T05 | FOV/som/canhoto aplicam | ✅ |
| MOB-T07 | Implementar `SettingsPage` nativa | T06 | Preferências sobrevivem ao restart | ✅ |
| MOB-T08 | Implementar `AboutPage` e copyright | T02 | Link do GitHub abre | ✅ |
| MOB-T09 | Tratar botão voltar, lifecycle e pause | T05 | Sem perda de progresso | ✅ |
| MOB-T10 | Ajustar manifest/Info.plist e ícone/splash | T01 | Launcher e splash corretos | ✅ |
| MOB-T11 | Criar scripts `mobile:sync` e `mobile:build` | T04 | Build por npm | ✅ |
| MOB-T12 | Escrever testes de unidade e smoke manual | T06 | Analyze/test/build passam | ✅ |
| MOB-T13 | Documentar build e execução no README | T11 | Outro dev consegue rodar | ✅ |

## Evidências de validação

- `dart analyze`: sem problemas.
- `flutter test`: 24 testes, todos passando.
- `flutter build apk --debug`: APK gerado (x86_64/arm64/armv7).
- Web: `npm test` (15), `tests/e2e.mjs` (inclui alternância 1ª/3ª pessoa pelo HUD e `LulicraftNative.setSettings`) e `tests/multiplayer.e2e.mjs` passando.
- Sync do jogo web: 2,21 MB em `mobile/assets/web`.

## Pendências conhecidas

- Smoke visual em aparelho físico ainda não executado. O AVD disponível neste host usa imagem Android 37.1 16 KB page size, na qual o engine Flutter falha ao criar o isolate raiz (`Could not prepare isolate`) antes do primeiro frame; o WebView do mesmo emulador também não carrega a biblioteca Chromium. Validar em aparelho real ou AVD com imagem 4 KB antes de publicar.
- Assinatura release usa a chave de debug; configurar keystore de produção.
- Build iOS requer macOS/Xcode e não foi executado neste ambiente.
