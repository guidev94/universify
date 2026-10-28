# Lulicraft

Sandbox 3D cozy de blocos, luz e descobertas. Explore biomas procedurais, colete itens raros, construa com amigos e compartilhe mundos, tudo no navegador, sem conta e sem build step.

## Rodar localmente

Requisitos: navegador moderno com WebGL e Python 3 (ou qualquer servidor estático).

```bash
npm run serve
# abra http://localhost:8000: o jogo entra direto no último mundo
# para ver o lobby: http://localhost:8000/?menu=1
```

Também funciona com:

```bash
python3 -m http.server 8000
```

Não abra `index.html` via `file://`; os ES Modules e o WebGL precisam de um servidor local.

## Controles

| Ação | Desktop | Toque |
|---|---|---|
| Andar | `WASD` / setas | joystick |
| Olhar | mouse | arrastar |
| Quebrar | clique esquerdo | botão quebrar |
| Colocar / usar arma | clique direito | botão colocar |
| Pular | `Espaço` | botão pular |
| Voar | `V` (modo Criativo) | botão voar |
| Pousar | segurar `Espaço` por 4 s | botão descer |
| Conversar com NPC | `R` | botão interagir |
| Falas rápidas | botão no HUD | botão no HUD |
| Mochila | `E` | botão Mochila |
| Forja | `C` | aba Forja |
| Hotbar | `1` a `9` ou roda do mouse | toque no slot |
| Terceira pessoa | `F5` ou botão de visão no HUD | botão de visão no HUD ou nos controles |
| Mapa local | `Tab` | Pausa → Mapa local |
| Missões | `J` | Pausa → Missões |
| Desfazer construção | `Ctrl/Cmd+Z` | teclado externo |
| Refazer construção | `Ctrl/Cmd+Y` | teclado externo |
| Paleta criativa | `B` | aba Mochila no Criativo |
| Guardar item | `Q` | teclado externo |
| Copiar bloco | clique do meio | toque longo no bloco |
| Trocar hotbar | `Shift+1` a `Shift+9` | arrastar no inventário |
| Tela cheia | `F11` | botão no HUD |
| Coordenadas | `F3` | Configurações |
| Pausa | `Esc` | botão pausa |
| Foto | `F` ou botão de câmera | botão câmera |
| Som | `M` | Configurações |
| Canhoto | Configurações | Configurações |

## Recursos

- Mundo voxel determinístico com 5 biomas, cavernas, árvores, flores e estruturas.
- Modos Aventura e Criativo; voo e blocos infinitos no Criativo.
- Hotbar, mochila, bolsa de sementes, baús, forja e álbum de coleção.
- Busca, ordenação e favoritos no resumo da mochila.
- Desfazer/refazer construção com histórico de blocos e baús.
- Mapa local com biomas, baús e NPCs ao redor.
- Diário de missões com progresso de coleta, construção, biomas, forja e pet.
- Vilas procedurais com casas, praça, lampiões, flores e baús.
- NPCs com rotina de caminhada e pausa perto do jogador.
- Pacotes de conteúdo JSON seguros: paleta e blocos extras na paleta criativa.
- 6 raridades, itens autorais, drops raros, efeitos e conquistas.
- 18 personagens autorais do Lumi Crew, 6 skins de arma, cores personalizadas e nome.
- 4 pets cosméticos com 8 cores, preview e companheiro 3D que anda à frente e reage.
- 3 NPCs cozy com fala contextual e tecla R.
- 5 armas mágicas sem combate: pétalas, bolhas, estrelas, martelo solar e arco aurora.
- Novos alimentos: Frutinha Arco-Íris, Biscoito Estelar e Algodão-doce de Nuvem.
- Terceira pessoa com avatar local, mão com item e animação de quebrar, colocar, usar arma e forjar.
- Atalho de visão 1ª/3ª no HUD, nos controles de toque e em `F5`.
- Viewmodel com poses por categoria, muzzle alinhado, recuo e flash de arma.
- Configurações ampliadas: FOV, inverter Y, sensibilidade de toque, canhoto, balanço, FPS, partículas, nuvens, escala de UI e iniciar em 3ª pessoa.
- UX mobile com safe areas, alvos de 48 px, layout canhoto e dica de paisagem.
- Bridge `window.LulicraftNative` para o app Flutter e overrides por query string.
- Contador de exploradores online sempre visível no HUD.
- Entrada direta no jogo: o lobby só aparece com `?menu=1`.
- 7 mundos prontos, incluindo Minha Casa e três com Kit de Raros.
- Blocos rosa, menta e lavanda na paleta criativa.
- 6 mundos inspirados em cidades brasileiras e 6 mundos da comunidade.
- Ranking local de tempo por mundo.
- Mochila expansível até 512 linhas, sem truncar itens.
- Pacotes gráficos auto, pixel suave e turbo leve.
- Salvar/carregar, autosave, exportar/importar arquivo e código de mundo.
- Seed do dia, modo foto e cartão compartilhável.
- Multiplayer P2P com convite, até 6 exploradores por sala.
- Áudio ambiente por bioma, passos, blocos e efeitos de UI.
- Chat seguro apenas com frases prontas, sem texto livre.
- 15 prints de referência em [`docs/screenshots/`](docs/screenshots/).

## Multiplayer

1. Abra **Multiplayer** no menu ou na pausa.
2. **Criar sala** gera um código de 5 caracteres e um link de convite.
3. O convidado abre o link ou digita o código em **Entrar com convite**.
4. O anfitrião mantém o mundo; convidados recebem uma cópia inicial e sincronizam blocos, baús e posições.

Detalhes importantes:

- Conexão **WebRTC P2P** via PeerJS; o servidor público PeerJS é usado apenas para sinalização.
- O código/link é um convite, não uma senha. Compartilhe apenas com pessoas de confiança.
- O chat usa uma allowlist de frases prontas, com rate limit. Não há texto livre, contas, matchmaking público ou voz.
- Se o anfitrião sair, a sala fecha e cada convidado continua no mundo local.
- Firewalls/NAT restritivos podem impedir a conexão; não há servidor relay próprio.

## Publicar na Vercel

O projeto é 100% estático, então funciona na Vercel sem build step.

1. Importe o repositório na Vercel.
2. Framework preset: **Other**.
3. Root directory: `lulicraft` quando o repositório tiver outros projetos na raiz.
4. Build command: vazio. Output directory: `.`.

O `vercel.json` já adiciona cabeçalhos básicos de segurança. Para jogar com outra pessoa, use **Criar sala** e envie o link de convite.

### Mundo compartilhado na Vercel

A Vercel hospeda o jogo, mas cada navegador tem seu próprio `localStorage`. Isso significa que duas pessoas que abrem o mesmo link **não entram automaticamente no mesmo mundo salvo**. Para compartilhar ao vivo, o anfitrião precisa manter uma sala aberta e convidar a outra pessoa. Um mundo persistente e público para todos exigiria backend, banco de dados e autenticação, o que este projeto não inclui por segurança e privacidade.

## Testes

```bash
npm test          # testes unitários (Node)
npm run test:e2e  # smoke test no Chrome headless
npm run test:mp   # dois navegadores: criar sala, entrar, sincronizar e sair
npm run test:all  # tudo em sequência
```

Os testes E2E usam `/usr/bin/google-chrome` e `/usr/bin/python3` por padrão. Para outros caminhos:

```bash
CHROME_BIN=/caminho/chrome PYTHON_BIN=/caminho/python npm run test:e2e
```

## Estrutura

```text
index.html
assets/styles.css
src/
  core/rng.js
  game/
    blocks.js items.js recipes.js world.js mesher.js
    player.js inventory.js save.js audio.js ui.js
    characters.js prebuilt.js community.js brazil.js seeds.js
    build-history.js content-packs.js multiplayer.js main.js
  vendor/
    three.module.js
    peerjs.min.js
    PEERJS-LICENSE
tests/
  core.test.mjs
  e2e.mjs
  multiplayer.e2e.mjs
  screenshots.mjs
mobile/   app Flutter (WebView + servidor local 127.0.0.1)
.specs/   specs por feature e memória do projeto
docs/     design, arquitetura, arte, conteúdo e plano de testes
```

## Configurações

O menu de Configurações (no menu inicial ou na pausa) oferece:

- Som, sensibilidade do mouse e sensibilidade do toque.
- Inverter eixo Y, campo de visão (60°–100°) e balanço de câmera.
- Modo canhoto, iniciar em 3ª pessoa e escala da interface.
- Coordenadas, contador de FPS, partículas ambientais e nuvens.
- Pacote visual (automático, pixel suave, turbo leve) e tutorial.
- Pacotes de conteúdo JSON para paletas e blocos extras.
- Restaurar padrões sem apagar personagem ou mundos.

Tudo é salvo em `lulicraft:settings`. O jogo também aceita overrides por query string (`?fov=82&third=1&lefthand=1`, por exemplo) e expõe `window.LulicraftNative` para o app Flutter.

## App mobile (Flutter)

O diretório `mobile/` contém o **Lulicraft Pocket**, um casco Flutter que serve os assets web em `127.0.0.1` e abre o jogo em uma WebView, sem reescrever o engine. O app traz Home, Ajustes e Sobre nativos, ícone/splash próprios, wakelock, tela imersiva e sincronização bidirecional de preferências com o jogo.

```bash
npm run mobile:sync     # copia o jogo web para mobile/assets/web
npm run mobile:analyze  # dart analyze
npm run mobile:test     # testes de unidade
npm run mobile:build    # sync + APK release assinado
cd mobile && flutter run   # Android/iOS conectado
```

- Requisitos: Flutter 3.47+, Android SDK para Android e macOS/Xcode para iOS. O single-player funciona offline.
- Android: minSdk 24; cleartext liberado apenas para `127.0.0.1`/`localhost` via `network_security_config.xml`.
- iOS: `NSAllowsLocalNetworking` em `Info.plist`.
- Ícone adaptativo e splash são gerados por `flutter_launcher_icons`/`flutter_native_splash` a partir de `mobile/assets/branding/`.
- A assinatura release usa `mobile/android/upload-keystore.jks` e `mobile/android/key.properties`, ambos fora do git. Faça backup seguro da keystore.
- Builds gerados: `app-release.apk`, `app-release.aab` e APKs split por ABI em `mobile/build/app/outputs/`.
- APK universal: 56,6 MB, SHA-256 `06bcc7d2d3f82b6ee9c34f88cdf629d54be6c895a464e06ec2f85c4fbdb37c49`.
- AAB: 55,9 MB, SHA-256 `86feb8527ca1e29f98872c9d4d46f5a8e058b5a824e04c2e2e09b12fb8b662ca`.
- O botão voltar do sistema fecha overlays → pausa → menu, e "Voltar ao início do app" aparece na pausa no modo nativo.

Veja [`.specs/features/mobile-app/design.md`](.specs/features/mobile-app/design.md).

## Dados e privacidade

- Single-player: mundos e configurações ficam em `localStorage`; nada é enviado.
- Multiplayer: somente o estado necessário do jogo (posição, blocos, baús, personagem e nome) é trocado diretamente entre os navegadores.
- Sem analytics, anúncios, cookies de rastreamento ou contas.
- Exportar/importar e compartilhar são sempre ações explícitas.

## Limitações conhecidas

- Máximo de 12 mundos locais; mundos prontos e o Mundo do Dia ocupam slots.
- Multiplayer depende da disponibilidade do serviço público PeerJS e de WebRTC.
- Sem chat livre, sincronização de inventário completo ou persistência oficial do mundo no lado do convidado durante a sala.
- Sem build/minificação; os arquivos são servidos como estão.
- Pacotes de conteúdo mudam apenas paleta e blocos da paleta criativa; não executam código.
- Missões ainda são um diário global, não três missões ramificadas por NPC.
- O app mobile depende do WebView do sistema (Android 7+); o build iOS não foi validado neste ambiente Linux (requer macOS/Xcode).
- A marca é autoral (Lumi, folha e spark), sem referência a Minecraft.

## Prints

Galeria em [`docs/screenshots/`](docs/screenshots/):

- `01-menu.png` e `14-mobile-menu.png`
- `02-personagem.png`, `03-configuracoes.png`, `04-pacotes.png` e `05-criar-mundo.png`
- `06-gameplay.png`, `07-terceira-pessoa.png`, `08-inventario.png` e `09-inventario-busca.png`
- `10-mapa.png`, `11-missoes.png`, `12-pausa.png`, `13-multiplayer.png` e `15-mobile-gameplay.png`

Gerar novamente:

```bash
npm run screenshots
```

## Documentação

- [`docs/GAME-DESIGN.md`](docs/GAME-DESIGN.md)
- [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md)
- [`docs/CONTENT-ITEMS.md`](docs/CONTENT-ITEMS.md)
- [`docs/ART-BIBLE.md`](docs/ART-BIBLE.md)
- [`docs/TEST-PLAN.md`](docs/TEST-PLAN.md)
- [`docs/ROADMAP-EXPERIENCES.md`](docs/ROADMAP-EXPERIENCES.md)
- [`.specs/project/STATE.md`](.specs/project/STATE.md)
- [`.specs/project/BACKLOG.md`](.specs/project/BACKLOG.md)
- [`.specs/features/mobile-app/spec.md`](.specs/features/mobile-app/spec.md)

## Copyright

Copyright © 2026 [guidev94](https://github.com/guidev94). Todos os direitos reservados.

O projeto não concede licença open-source por padrão. Consulte [`COPYRIGHT`](COPYRIGHT) antes de reutilizar, distribuir ou publicar o jogo. Bibliotecas de terceiros mantêm suas próprias licenças.
