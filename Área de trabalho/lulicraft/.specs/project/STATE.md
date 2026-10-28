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
| 2026-10-27 | Multiplayer P2P com PeerJS e convite de 5 caracteres | Sem backend, contas ou chat livre; host mantém o mundo |
| 2026-10-27 | Convidado continua localmente se o host sair | Evita perder a sessão e mantém o conforto |
| 2026-10-27 | Entrada direta no jogo; lobby em `?menu=1` | Primeira tela deve parecer jogo, não landing page |
| 2026-10-27 | Personagens cosméticos, 6 skins de arma e 5 armas mágicas sem dano | Mais identidade e gameplay sem quebrar o pilar kid-safe |
| 2026-10-27 | Terceira pessoa com avatar local, F5, F11 e roda na hotbar | Aproximar a sensação de jogo de blocos sem perder acessibilidade |
| 2026-10-27 | Limite de 12 mundos locais | Dar espaço para experimentar sem estourar o localStorage |
| 2026-10-27 | Vercel hospeda o cliente; sala ao vivo usa PeerJS | Site estático não compartilha localStorage entre navegadores |
| 2026-10-27 | Pets cosméticos com 4 tipos e 8 cores | Identidade afetiva sem vantagem de gameplay |
| 2026-10-27 | NPCs fixos perto do spawn com fala contextual | Dar vida ao mundo sem chat livre ou servidor |
| 2026-10-27 | 6 mundos de cidades brasileiras curados | Representatividade e rejogabilidade sem custo de backend |
| 2026-10-27 | Ranking local de tempo por mundo | Metas leves sem competição global nem contas |
| 2026-10-27 | Mochila expansível até 512 linhas | Evitar perda de itens em sessões longas e kits grandes |
| 2026-10-27 | Pouso por hold de Espaço (4 s) | Evitar queda brusca no voo criativo infantil |
| 2026-10-27 | Pacotes gráficos auto/pixel/turbo | Ajustar legibilidade e performance sem build |
| 2026-10-27 | App mobile = Flutter shell + WebView + servidor local | Distribuir sem reescrever o engine e sem perder ES Modules |
| 2026-10-27 | Visão 1ª/3ª com botão no HUD, no toque e F5 | Atalho visível e consistente em qualquer plataforma |
| 2026-10-27 | Poses por categoria e projéteis no muzzle | Armas precisam parecer empunhadas e disparar da mão |
| 2026-10-27 | Settings ampliados + bridge `LulicraftNative` | Acessibilidade e paridade entre web e app |
| 2026-10-27 | Canhoto espelha item, muzzle e controles | Atender mãos diferentes sem dois layouts manuais |
| 2026-10-27 | Copyright © 2026 guidev94, sem licença open-source escolhida | Preservar autoria sem decisão unilateral de licença |
| 2026-10-28 | Ícone adaptativo e splash autorais gerados de SVG/HTML | Marca coerente sem depender de assets externos |
| 2026-10-28 | Servidor local só em loopback, allowlist de caminhos e MIME | Servir assets sem abrir superfície de rede ou path traversal |
| 2026-10-28 | Settings nativos em SharedPreferences (schema 2) e espelho no jogo | Preferências sobrevivem ao restart e valem desde o primeiro mundo |
| 2026-10-28 | Back do sistema: overlay → pausa → menu | Evitar saída acidental e perda de progresso no Android |
| 2026-10-28 | Chat por allowlist de frases prontas, 1,5 s local e 2 s no host | Conversa infantil sem texto livre, links ou HTML |
| 2026-10-28 | Minha Casa como prebuilt criativo com cidade 3×3 chunks | Casa rosa, praça, vizinhos e baús raros determinísticos |
| 2026-10-28 | Blocos rosa, menta e lavanda no atlas e na paleta | Construção autoral sem assets externos |
| 2026-10-28 | Resumo da mochila agrega total por item | Evitar leitura de pilhas repetidas e melhorar a legibilidade |
| 2026-10-28 | Pet anda à frente, com marcha, pulinho e olhar para o jogador | Companheiro mais vivo sem bloquear o raycast |
| 2026-10-28 | Áudio ambiente procedural por bioma | Cinco texturas distintas sem novos assets |
| 2026-10-28 | 18 presets do Lumi Crew e preview com blink | Identidade autoral e expressão sem copiar pessoas reais |
| 2026-10-28 | Sync mobile inclui logo, logo-mark e assets web atuais | App e web com a mesma marca e favicon |
| 2026-10-28 | Histórico de construção limitado a 120 mudanças | Desfazer/refazer sem crescer memória indefinidamente |
| 2026-10-28 | Mapa local desenhado em canvas 2D, 64 blocos ao redor | Consulta rápida sem custo de render 3D extra |
| 2026-10-28 | Vilas procedurais em chunks elegíveis, fora do Mundo Minha Casa | Mais descoberta sem conflitar com a cidade autoral |
| 2026-10-28 | Desfazer baú exige loot intacto; desfazer a colocação exige baú vazio | Evita duplicar ou perder itens no histórico de construção |
| 2026-10-28 | Missões em diário com progresso derivado de stats | Sem nova economia ou estado de save complexo |
| 2026-10-28 | Pacotes JSON com allowlist de paleta e blocos | Conteúdo de comunidade sem executar código |
| 2026-10-28 | Screenshots reproduzíveis via CDP em `docs/screenshots` | Evidência visual e regressão de layout |
| 2026-10-28 | APK/AAB assinados com keystore de upload local | Release instalável e pronto para loja |

## Bloqueios

- Nenhum bloqueio técnico no MVP.
- Risco: WebGL indisponível em navegadores antigos → fallback com mensagem clara.
- Risco: PeerJS público ou NAT restritivo pode impedir o multiplayer; não há relay próprio.
- iOS: build e assinatura exigem macOS com Xcode; o projeto fica pronto, mas a validação fica pendente neste ambiente Linux.
- AVD Android 37.1 deste host usa imagem 16 KB page size: o engine Flutter falha em `Could not prepare isolate` antes do primeiro frame e o WebView não carrega a lib Chromium. Validar o app em aparelho real ou AVD 4 KB.

## Lições

- Gerar e meshificar chunk no mesmo frame causa engasgo; distribuir por frame.
- Atlas com inset de UV evita sangramento de tiles vizinhos.
- Colisão por eixo + auto-step é mais amigável para crianças que física realista.
- Raridade precisa de mais de uma pista (cor, forma, som, texto) para acessibilidade.
- IDs numéricos legados de itens exigem `String(id)` antes de métodos de string na UI.
- Testes E2E de multiplayer são ótimos para pegar regressões de inicialização fora do single-player.

## TODOs

- [ ] Adicionar Web Worker no M4.
- [x] Criar testes automatizados de save/load e regras puras.
- [x] Criar smoke test e E2E multiplayer.
- [x] Atalho de terceira pessoa visível no HUD e no toque.
- [x] App Flutter com Home, Ajustes, Sobre, ícone e splash.
- [x] Bridge nativa de settings e botão voltar.
- [x] E2E cobrindo alternância 1ª/3ª e `LulicraftNative.setSettings`.
- [ ] Medir FPS real em celular médio.
- [x] Gerar build release assinado do app Flutter (APK, AAB e splits por ABI).
- [ ] Validar app em aparelho Android real e iOS via TestFlight (AVD 16 KB atual não inicia o engine).
- [x] Substituir a logo derivativa por marca autoral (Lumi, folha e spark).
- [ ] Adicionar crescimento offline do jardim.
- [x] Criar diário de missões curtas com progresso no diário.
- [ ] Evoluir missões para ramificações por NPC.
- [x] Revisar header mobile e sobreposição do sol decorativo.
- [x] Revisar textos com linguagem 7+ e remover travessões.
- [x] Definir política de slots para mundos prontos e Mundo do Dia (limite 12).
- [x] Chat seguro com frases prontas e rate limit (sem moderação de texto livre).
- [x] Gerar 15 prints de referência em `docs/screenshots`.
- [x] Mapa local, desfazer/refazer, busca/favoritos e pacotes JSON.

## Ideias adiadas

- Multiplayer local em split-screen.
- Servidor relay próprio para NAT restritivo.
- Editor de skins.
- Estações do ano no mundo.
- Mini-jardim persistente com crescimento offline.
- Modo "construtor guiado" com plantas de casas.
