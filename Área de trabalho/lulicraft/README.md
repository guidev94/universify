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
| Mochila | `E` | botão Mochila |
| Forja | `C` | aba Forja |
| Hotbar | `1` a `9` ou roda do mouse | toque no slot |
| Terceira pessoa | `F5` | botão na pausa |
| Tela cheia | `F11` | botão no HUD |
| Coordenadas | `F3` | Configurações |
| Pausa | `Esc` | botão pausa |
| Foto | `F` ou botão de câmera | botão câmera |
| Som | `M` | Configurações |

## Recursos

- Mundo voxel determinístico com 5 biomas, cavernas, árvores, flores e estruturas.
- Modos Aventura e Criativo; voo e blocos infinitos no Criativo.
- Hotbar, mochila, bolsa de sementes, baús, forja e álbum de coleção.
- 6 raridades, itens autorais, drops raros, efeitos e conquistas.
- 9 skins de personagem, 4 skins de arma, cores personalizadas e nome.
- 5 armas mágicas sem combate: pétalas, bolhas, estrelas, martelo solar e arco aurora.
- Novos alimentos: Frutinha Arco-Íris, Biscoito Estelar e Algodão-doce de Nuvem.
- Terceira pessoa com avatar local, mão com item e animação de quebrar, colocar, usar arma e forjar.
- Contador de exploradores online sempre visível no HUD.
- Entrada direta no jogo: o lobby só aparece com `?menu=1`.
- 6 mundos prontos, incluindo dois com Kit de Raros.
- Salvar/carregar, autosave, exportar/importar arquivo e código de mundo.
- Seed do dia, modo foto e cartão compartilhável.
- Multiplayer P2P com convite, até 6 exploradores por sala.
- Áudio ambiente suave de dia e de noite, passos, blocos e efeitos de UI.

## Multiplayer

1. Abra **Multiplayer** no menu ou na pausa.
2. **Criar sala** gera um código de 5 caracteres e um link de convite.
3. O convidado abre o link ou digita o código em **Entrar com convite**.
4. O anfitrião mantém o mundo; convidados recebem uma cópia inicial e sincronizam blocos, baús e posições.

Detalhes importantes:

- Conexão **WebRTC P2P** via PeerJS; o servidor público PeerJS é usado apenas para sinalização.
- O código/link é um convite, não uma senha. Compartilhe apenas com pessoas de confiança.
- Não há chat, contas, matchmaking público, voz ou moderação de sala.
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
    characters.js prebuilt.js multiplayer.js main.js
  vendor/
    three.module.js
    peerjs.min.js
    PEERJS-LICENSE
tests/
  core.test.mjs
  e2e.mjs
  multiplayer.e2e.mjs
.specs/   specs por feature e memória do projeto
docs/     design, arquitetura, arte, conteúdo e plano de testes
```

## Dados e privacidade

- Single-player: mundos e configurações ficam em `localStorage`; nada é enviado.
- Multiplayer: somente o estado necessário do jogo (posição, blocos, baús, personagem e nome) é trocado diretamente entre os navegadores.
- Sem analytics, anúncios, cookies de rastreamento ou contas.
- Exportar/importar e compartilhar são sempre ações explícitas.

## Limitações conhecidas

- Máximo de 12 mundos locais; mundos prontos e o Mundo do Dia ocupam slots.
- Multiplayer depende da disponibilidade do serviço público PeerJS e de WebRTC.
- Sem chat, sincronização de inventário completo ou persistência oficial do mundo no lado do convidado durante a sala.
- Sem build/minificação; os arquivos são servidos como estão.

## Documentação

- [`docs/GAME-DESIGN.md`](docs/GAME-DESIGN.md)
- [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md)
- [`docs/CONTENT-ITEMS.md`](docs/CONTENT-ITEMS.md)
- [`docs/ART-BIBLE.md`](docs/ART-BIBLE.md)
- [`docs/TEST-PLAN.md`](docs/TEST-PLAN.md)
- [`.specs/project/STATE.md`](.specs/project/STATE.md)
