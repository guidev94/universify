# Feature: App Mobile (Flutter)

## Objetivo

Levar o Lulicraft para Android e iOS com um app Flutter chamado **Lulicraft Pocket**, mantendo o jogo web como fonte única de verdade. O app é um casco nativo confiável: tela inicial, jogo em tela cheia, configurações nativas, tela Sobre e ciclo de vida correto.

O engine voxel **não** é reescrito em Dart neste escopo. O app empacota os assets web e os serve em `127.0.0.1` para uma WebView, preservando ES Modules, `localStorage` e WebGL.

## Público

| Persona | Necessidade | Como o app atende |
|---|---|---|
| Lia, 7 a 10 anos | Abrir e jogar rápido no celular | Botão grande "Jogar", tutorial curto e controles de toque |
| Carla, mãe | Segurança e previsibilidade | Sem contas, sem anúncios, sem permissões além de rede local |
| Davi, criador | Mostrar o jogo para amigos | Link e código de mundo continuam funcionando dentro do app |

## Modelo do app

```text
LulicraftApp
├── HomePage          → jogar, configurações, sobre, link do GitHub
├── GamePage          → WebView + servidor HTTP local + HUD nativo mínimo
├── SettingsPage      → preferências persistidas e espelhadas no jogo
└── AboutPage         → versão, créditos, copyright guidev94
```

## Requisitos

| ID | Requisito | Prioridade |
|---|---|---|
| MOB-01 | App Flutter com bundle id `com.guidev94.lulicraft` | Must |
| MOB-02 | Servir os assets web em `http://127.0.0.1:<porta>` dentro do app | Must |
| MOB-03 | WebView em tela cheia, sem barra de endereço, com WebGL habilitado | Must |
| MOB-04 | Botão "Jogar" abre o último mundo; "Novo mundo" abre o menu web | Must |
| MOB-05 | Tela de configurações nativa com som, sensibilidade, FOV, canhoto, 3ª pessoa, FPS, partículas e nuvens | Must |
| MOB-06 | Preferências persistidas em `shared_preferences` e aplicadas via bridge | Must |
| MOB-07 | Bridge `window.LulicraftNative` documentada e versionada | Must |
| MOB-08 | Botão voltar do Android: fecha modal → sai do jogo → fecha app | Must |
| MOB-09 | Manter tela acordada durante o jogo (`wakelock_plus`) | Should |
| MOB-10 | Orientação livre em paisagem; retrato mostra dica de girar | Should |
| MOB-11 | Safe areas e notch respeitados no HUD e nos controles | Must |
| MOB-12 | Alvos de toque com pelo menos 48×48 dp | Must |
| MOB-13 | Layout canhoto espelhado quando ativado | Should |
| MOB-14 | Ícone nativo 1024×1024 e splash coerentes com a marca Lulicraft | Must |
| MOB-15 | Tela Sobre com versão, copyright © 2026 guidev94 e link do GitHub | Must |
| MOB-16 | Funcionar sem internet para single-player | Must |
| MOB-17 | Não pedir permissão de câmera, contatos, localização ou armazenamento | Must |
| MOB-18 | Abrir links externos apenas no navegador, com confirmação | Should |
| MOB-19 | Pausar o jogo ao perder foco e salvar antes de sair | Must |
| MOB-20 | Build de debug e release geráveis por script (`npm run mobile:build`) | Should |

## Critérios de aceite

- `flutter analyze` sem erros e `flutter test` passando.
- `flutter build apk --debug` conclui e o APK abre em emulador Android 31+.
- Sem internet, o botão Jogar carrega o mundo e permite quebrar/colocar blocos.
- O botão de visão no HUD alterna 1ª/3ª pessoa e o estado sobrevive ao voltar para o menu.
- Alterar FOV em Configurações muda a câmera do jogo em menos de 1 segundo.
- Rotacionar o aparelho não recria a WebView nem perde o progresso.
- Nenhum erro de console no WebView na primeira sessão.
- O ícone aparece corretamente no launcher, na gaveta de apps e na splash.

## Fora de escopo

- Reescrever o engine voxel em Dart/Flutter nativo.
- Contas, nuvem, sincronização entre aparelhos, analytics e push.
- Loja integrada, assinaturas, anúncios ou compras.
- Multiplayer com servidor relay próprio.
- Publicação automática na Play Store/App Store neste bloco.

## Métricas locais

| Métrica | Meta |
|---|---|
| Tempo do toque em "Jogar" até o HUD | < 4 s |
| Sessão média no app | > 10 min |
| Erros de console por sessão | 0 |
| Tamanho do APK debug | < 45 MB |
| Toques para alternar 3ª pessoa | 1 |
