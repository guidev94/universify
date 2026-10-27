# Lulicraft: Plano de Testes

## 1. Testes automatizados

```bash
npm test          # unitários
npm run test:e2e  # smoke no Chrome headless
npm run test:mp   # host + convidado em dois Chrome headless
npm run test:all  # todos
```

Cobertura atual:

- [x] Personagens: 9 skins/presets, normalização, cores e nome.
- [x] Pets: 4 tipos, 8 cores, normalização e persistência no personagem.
- [x] Armas mágicas: itens, receitas, uso e projéteis sem dano.
- [x] Mundos prontos: 6 entradas únicas, modos e seeds.
- [x] Mundos da comunidade e 6 cidades brasileiras únicas.
- [x] Códigos de sala: 5 caracteres, alfabeto seguro e normalização.
- [x] Mundo determinístico, spawn seguro e diffs/baús.
- [x] Inventário inicial, Kit de Raros e mochila expansível sem truncar.
- [x] Receitas, incluindo petisco de pet, save/código/import e limite de 12 mundos.
- [x] Menu, cidades, personagem/pet, pacotes gráficos, criação com Kit de Raros e HUD.
- [x] NPCs: 3 no mundo, prompt por proximidade, fala com R e hint visível.
- [x] Companheiro 3D segue o jogador e reage a ações.
- [x] Pouso seguro: segurar Espaço por 4 s desliga o voo e esconde o hint.
- [x] Multiplayer: convite, link, sync de bloco, saída e host-offline.

## 2. Roteiro manual: primeira sessão (criança)

| Passo | Ação | Esperado |
|---|---|---|
| 1 | Abrir jogo | Menu com "Criar mundo" e "Mundo do Dia" |
| 2 | Criar mundo "Teste" | Loading < 3 s, spawn seguro |
| 3 | Mover WASD | Câmera e passos suaves |
| 4 | Olhar e clique esquerdo | Bloco quebra com partículas |
| 5 | Clique direito | Bloco colocado |
| 6 | Tecla E | Mochila com abas |
| 7 | Forja: criar tábuas | Consome tronco, adiciona tábuas |
| 8 | Coletar minério | Item raro/toast/álbum |
| 9 | Esc | Pausa com salvar/exportar |
| 10 | Recarregar | Mundo e inventário restaurados |
| 11 | Abrir Personagem | Pet preview muda de tipo e cor ao clicar |
| 12 | Entrar no mundo | Pet 3D aparece e segue o jogador |
| 13 | Aproximar de NPC e apertar R | Balão some e fala curta aparece |
| 14 | Voar e segurar Espaço por 4 s | Jogador desce e pousa sem queda brusca |
| 15 | Coletar muitos itens | Mochila cresce e nada é perdido |
| 16 | Alternar pacote gráfico | Preferência salva e visual muda |

## 3. Matriz de dispositivos

| Dispositivo | Largura | Verificar |
|---|---|---|
| Desktop | 1440×900 | mouse, teclado, pointer lock |
| Notebook | 1280×720 | FPS ≥ 45 |
| Tablet | 820×1180 | toque, joystick |
| Celular | 360×800 | sem overflow, alvos ≥ 44 px |

## 4. Casos de borda

- Seed vazia, com emoji, com 200 caracteres.
- Save corrompido no localStorage.
- Importar arquivo que não é JSON.
- Fechar inventário com item em mãos.
- Quebrar bloco sob os pés (deve reposicionar sem cair no vazio).
- Colocar bloco dentro do próprio corpo (deve ser bloqueado).
- Abrir baú e deletar mundo em outra aba.
- localStorage cheio (quota).
- WebGL desativado (mensagem amigável).
- Pet com tipo/cor inválidos (normalizar para Lumi).
- NPC próximo e jogador em voo (prompt não deve capturar teclado).
- Pouso cancelado ao soltar Espaço antes de 4 s.
- Mochila no limite técnico de 512 linhas.
- Pacote gráfico `turbo` com `prefers-reduced-motion`.

## 5. Acessibilidade

- Navegar menu e modais só com Tab/Shift+Tab/Enter/Esc.
- Foco visível em todos os controles.
- Contraste mínimo 4.5:1 em textos.
- `prefers-reduced-motion` desliga animações.
- Raridade legível sem cor (estrelas + rótulo).

## 6. Performance

- 81 chunks carregados: ≥ 45 FPS em desktop integrado.
- Geração de chunk < 30 ms.
- Autosave < 20 ms.
- Sem crescimento de memória após 5 min andando.

## 7. Segurança

- Single-player não faz requisições de rede.
- Multiplayer só conecta após ação explícita; PeerJS Cloud é usado para signaling.
- Nenhum `innerHTML` com nome de mundo/seed/personagem.
- Importação rejeita campos desconhecidos perigosos.
- Nenhum dado pessoal solicitado; sem chat ou contas.
- Nome, personagem, coordenadas, blocos, baús e patches são sanitizados.

## 8. Critérios de release

- 0 erros no console em 10 min de jogo.
- Todos os itens do roteiro manual aprovados.
- Save/load/export/import aprovados.
- Multiplayer host/convidado aprovado, incluindo host-offline.
- Mobile sem overflow, header legível e cards de cidade em uma coluna.
- Pet e NPC não causam erros no console em 10 min.
- Documentação e specs atualizadas.
