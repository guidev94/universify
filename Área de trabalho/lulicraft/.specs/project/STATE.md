# Lulicraft: State (memória do projeto)

## Decisões

| Data | Decisão | Motivo |
|---|---|---|
| 2026-10-27 | Web 3D com Three.js vendorizado, sem build | Rodar com duplo clique/servidor estático; fácil de compartilhar |
| 2026-10-27 | Mundo 16×48×16 por chunk, raio 4 | Equilíbrio entre beleza e performance em hardware simples |
| 2026-10-27 | Texturas procedurais em atlas 8×8 | Zero assets externos, estilo autoral coerente |
| 2026-10-27 | Crafting por receitas clicáveis | Crianças não precisam decorar grades 3×3 |
| 2026-10-27 | Sem combate/dano | Pilar de segurança e conforto |
| 2026-10-27 | Save em localStorage com diffs por chunk | Mundos pequenos, carregamento instantâneo |
| 2026-10-27 | 5 inventários distintos | Atende "vários tipos de inventário" com propósito |
| 2026-10-27 | Raridade com cor + brilho + som + álbum | Descoberta memorável e compartilhável |
| 2026-10-27 | Código de mundo = seed + nome + modo | Compartilhamento curto e seguro |
| 2026-10-27 | Personagem cosmético com presets e cores | Identidade sem loja nem vantagem de gameplay |
| 2026-10-27 | Mundos prontos + Kit de Raros opcional | Descoberta rápida sem substituir o início normal |
| 2026-10-27 | Multiplayer P2P com PeerJS e convite de 5 caracteres | Sem backend, contas ou chat; host mantém o mundo |
| 2026-10-27 | Convidado continua localmente se o host sair | Evita perder a sessão e mantém o conforto |
| 2026-10-27 | Entrada direta no jogo; lobby em `?menu=1` | Primeira tela deve parecer jogo, não landing page |
| 2026-10-27 | 9 skins de personagem, 4 skins de arma e 5 armas mágicas sem dano | Mais identidade e gameplay sem quebrar o pilar kid-safe |
| 2026-10-27 | Terceira pessoa com avatar local, F5, F11 e roda na hotbar | Aproximar a sensação de jogo de blocos sem perder acessibilidade |
| 2026-10-27 | Limite de 12 mundos locais | Dar espaço para experimentar sem estourar o localStorage |
| 2026-10-27 | Vercel hospeda o cliente; sala ao vivo usa PeerJS | Site estático não compartilha localStorage entre navegadores |

## Bloqueios

- Nenhum bloqueio técnico no MVP.
- Risco: WebGL indisponível em navegadores antigos → fallback com mensagem clara.
- Risco: PeerJS público ou NAT restritivo pode impedir o multiplayer; não há relay próprio.

## Lições

- Gerar e meshificar chunk no mesmo frame causa engasgo; distribuir por frame.
- Atlas com inset de UV evita sangramento de tiles vizinhos.
- Colisão por eixo + auto-step é mais amigável para crianças que física realista.
- Raridade precisa de mais de uma pista (cor, forma, som, texto) para acessibilidade.

## TODOs

- [ ] Adicionar Web Worker no M4.
- [x] Criar testes automatizados de save/load e regras puras.
- [x] Criar smoke test e E2E multiplayer.
- [ ] Medir FPS real em celular médio.
- [x] Revisar textos com linguagem 7+ e remover travessões.
- [x] Definir política de slots para mundos prontos e Mundo do Dia (limite 12).

## Ideias adiadas

- Multiplayer local em split-screen.
- Chat seguro com frases prontas e moderação.
- Servidor relay próprio para NAT restritivo.
- Editor de skins.
- Estações do ano no mundo.
- Mini-jardim persistente com crescimento offline.
- Modo "construtor guiado" com plantas de casas.
