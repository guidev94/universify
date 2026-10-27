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
- [x] Armas mágicas: itens, receitas, uso e projéteis sem dano.
- [x] Mundos prontos: 6 entradas únicas, modos e seeds.
- [x] Códigos de sala: 5 caracteres, alfabeto seguro e normalização.
- [x] Mundo determinístico, spawn seguro e diffs/baús.
- [x] Inventário inicial e Kit de Raros.
- [x] Receitas, save/código/import e limite de 12 mundos.
- [x] Menu, personagem, criação com Kit de Raros e HUD.
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
- Mobile sem overflow.
- Documentação e specs atualizadas.
