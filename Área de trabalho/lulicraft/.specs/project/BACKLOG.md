# Lulicraft: Backlog de Próximas Melhorias

Prioridades: **P0** = próximo bloco, **P1** = depois do app estabilizar, **P2** = exploração.
Esforço: **S** (≤ 1 dia), **M** (2–4 dias), **L** (1–2 semanas).

## UI e UX

| ID | Melhoria | Prioridade | Esforço | Critério de aceite |
|---|---|---|---|---|
| UX-01 | Onboarding contextual: dicas de 1 linha na primeira vez que cada ação aparece | P0 | S | Cada dica aparece no máximo 1 vez por instalação |
| UX-02 | Tooltip rico ao segurar um item na hotbar (nome, raridade, uso, receita) | P0 | S | Toque longo abre; Esc/toque fora fecha |
| UX-03 | Mapa local opcional (minimapa de biomedas descobertas, sem inimigos) | Feito | M | Abre com `Tab` ou Pausa → Mapa local; mostra biomas, baús, NPCs e jogador |
| UX-04 | Diário de descobertas com filtros por raridade e bioma | P1 | M | Itens desconhecidos aparecem como silhueta |
| UX-05 | Ajuste fino de HUD: esconder chips individuais | P1 | S | Preferência persiste |
| UX-06 | Modo leitura fácil: textos curtos e ícones antes de palavras | P1 | M | Alternável em Configurações |
| UX-07 | Feedback tátil no celular em quebrar, colocar e craftar | P1 | S | `HapticFeedback.lightImpact()` no app e `navigator.vibrate` no web |
| UX-08 | Estados vazios mais úteis (mochila, baú, álbum) com ação direta | P2 | S | Cada vazio tem 1 botão de próximo passo |
| UX-09 | Tour de boas-vindas no app Flutter (3 telas, pulável) | P1 | S | Não reaparece após concluir |
| UX-10 | Tema claro/escuro do menu e do app | P2 | M | Contraste AA nos dois temas |

## Jogabilidade

| ID | Melhoria | Prioridade | Esforço | Critério de aceite |
|---|---|---|---|---|
| GP-01 | Jardim vivo: plantar sementes e ver crescimento em tempo real e offline | P0 | M | 3 estágios; persiste no save; notifica ao voltar |
| GP-02 | Missões curtas de NPC (3 por NPC, recompensa cosmética) | Parcial | M | Diário de missões com progresso entregue; ramificações por NPC e recompensa única pendentes |
| GP-03 | Criaturas Lumis selvagens observáveis (sem combate) | P1 | M | 3 criaturas por bioma; reagem ao jogador |
| GP-04 | Clima suave e aurora com efeitos visuais e som | P1 | M | Chuva de pétalas, neve e aurora alternam por horário |
| GP-05 | Descoberta diária: 1 item raro em local fixo por dia | P1 | S | Seed do dia define local; reinicia 00:00 local |
| GP-06 | Construção assistida: plantas de casa/ponte com prévia fantasma | P2 | L | Prévia verde/vermelha; consome blocos reais |
| GP-12 | Desfazer/refazer construção e copiar bloco | Feito | S | `Ctrl/Cmd+Z`, `Ctrl/Cmd+Y`, `B`, `Q`, `Shift+1..9` e clique do meio |
| GP-13 | Vilas procedurais e rotina de NPCs | Feito | M | Casas, praça, lampiões, flores e baús; NPCs caminham e param perto do jogador |
| GP-07 | Emotes e poses para foto (acenar, sentar, dormir) | P2 | S | 4 emotes; aparecem no avatar de 3ª pessoa |
| GP-11 | Falas rápidas contextuais por bioma/NPC | P1 | S | Frases da allowlist mudam com o cenário |
| GP-08 | Pet com truques (buscar item próximo, girar, deitar) | P2 | M | 3 truques desbloqueados por carinho |
| GP-09 | Balões de ajuda quando a criança fica 45 s sem agir | P1 | S | Máximo 1 balão por minuto |
| GP-10 | Modo visita: explorar mundo por código sem editar | P2 | M | Blocos somente leitura; botão "copiar para meu mundo" |

## Itens e conteúdo

| ID | Item/conteúdo | Prioridade | Esforço | Critério de aceite |
|---|---|---|---|---|
| IT-01 | Sementes de flores raras com cores únicas por bioma | P0 | S | 5 sementes, 5 flores, álbum atualizado |
| IT-02 | Lanterna de Lumi (ilumina à noite sem tocha) | P0 | S | Buff de luz 60 s; receita simples |
| IT-03 | Mochila de exploração (+9 slots temporários) | P1 | M | Receita rara; expira ao sair do mundo |
| IT-04 | Kit de jardinagem (regador, tesoura, vaso) | P1 | M | 3 ferramentas cosméticas com uso próprio |
| IT-05 | Móveis cozy: banco, cerca, luminária, tapete | P1 | M | 8 blocos novos; craftáveis |
| IT-06 | Instrumentos musicais (flauta, sino) com notas curtas | P2 | M | 5 notas; sem áudio agressivo |
| IT-07 | Cartas de NPC colecionáveis | P2 | S | 9 cartas; aparecem no álbum |
| IT-08 | Peixes e inseto de lago para o álbum | P2 | M | 4 espécies; captura sem dano |
| IT-09 | Tintas de bloco (recolorir 6 blocos) | P2 | L | 8 cores; persiste no save |
| IT-10 | Conquistas com recompensa cosmética | P1 | S | 12 conquistas; 4 chapéus exclusivos |

## Funcionalidades e plataforma

| ID | Funcionalidade | Prioridade | Esforço | Critério de aceite |
|---|---|---|---|---|
| FN-01 | App Flutter Android/iOS (este bloco) | P0 | L | Build debug e fluxo completo |
| FN-02 | PWA offline instalável | P1 | M | Service worker cacheia shell e assets |
| FN-03 | Build minificado opcional | P1 | S | `npm run build` gera `dist/` |
| FN-04 | Web Worker para geração/meshing | P1 | L | Sem engasgo ao cruzar chunk |
| FN-05 | Save em IndexedDB para mundos grandes | P1 | M | Migração automática do localStorage |
| FN-06 | Exportar/importar mundo no app mobile via share sheet | P1 | M | Arquivo `.lulicraft` compartilhável |
| FN-07 | Galeria local de cartões do mundo | P2 | M | 20 cartões, exclusão individual |
| FN-08 | Código de mundo compactado (RLE + base64) | P2 | M | Mundo novo < 2 KB |
| FN-09 | Suporte a gamepad | P2 | M | Mapeamento padrão; sensibilidade separada |
| FN-10 | Localização EN/ES | P2 | L | Textos externos em JSON |
| FN-11 | Pacotes de conteúdo JSON seguros | Feito | S | Allowlist de paleta/blocos, limite 64 KB, 8 pacotes, sem execução de código |
| FN-12 | Prints reproduzíveis via CDP | Feito | S | `npm run screenshots` gera 15 imagens em `docs/screenshots` |

## Acessibilidade

| ID | Melhoria | Prioridade | Esforço | Critério de aceite |
|---|---|---|---|---|
| AC-01 | Modo daltônico para raridades (forma + cor + texto) | P0 | S | 6 raridades distinguíveis sem cor |
| AC-02 | Navegação completa por teclado nos modais | P1 | M | Foco preso, Esc fecha, ordem lógica |
| AC-03 | Legendas para sons importantes | P1 | S | 8 eventos com legenda opcional |
| AC-04 | Reduzir movimento respeitado em todas as animações novas | P1 | S | `prefers-reduced-motion` cobre HUD e 3D |
| AC-05 | Alto contraste no HUD | P2 | S | Token de contraste alternável |

## Ordem recomendada

1. **Fechar o app Flutter** com teste em aparelho real e publicação.
2. **GP-02 + GP-01 + IT-01 + IT-02**: ramificações de missão, jardim e recompensas.
3. **AC-01 + UX-01 + UX-02**: acessibilidade e clareza para novos jogadores.
4. **FN-02 + FN-03 + FN-04**: alcance e performance.
5. **GP-03 + GP-04 + UX-03**: camada de descoberta e surpresa.

## Não fazer agora

- Combate, dano, morte ou PvP.
- Chat livre ou matchmaking público.
- Chat por voz ou texto livre.
- Loja, anúncios, passe de batalha.
- Servidor dedicado com contas.
- Reescrever o engine em Dart.
