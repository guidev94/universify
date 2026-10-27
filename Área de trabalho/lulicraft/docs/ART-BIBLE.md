# Lulicraft: Art Bible

## Princípios

1. **Cozy, não infantilizado**, formas arredondadas, cores saturadas, mas com hierarquia e respiro.
2. **Luz como protagonista**, cristais, lâmpadas, aurora e partículas guiam o olhar.
3. **Texturas legíveis**, pixel art 16×16 com contraste alto; nada de ruído visual.
4. **Uma paleta por bioma**, o jogador reconhece onde está pela cor.
5. **Movimento com propósito**, toda animação comunica estado ou recompensa.

## Paleta mestra

| Token | Hex | Uso |
|---|---|---|
| `--lumi` | `#b8ff6a` | cor primária, Lumi, CTAs |
| `--lumi-deep` | `#6fd13c` | hover, bordas |
| `--coral` | `#ff8a6b` | destaque quente, alertas gentis |
| `--sky` | `#7cd6ff` | água, raro, links |
| `--violet` | `#b07cff` | épico, magia |
| `--gold` | `#ffc94d` | lendário, recompensa |
| `--pink` | `#ff7ad9` | mítico, aurora |
| `--night` | `#101a26` | fundo noturno |
| `--ink` | `#14231c` | texto sobre claro |
| `--cream` | `#f7f3e8` | superfícies claras |

## Tipografia

- Títulos: **Baloo 2** (rounded, amigável) ou `ui-rounded`.
- Corpo: **Nunito** / `system-ui`.
- Escala: 12 / 14 / 16 / 20 / 28 / 40 / 56 px.
- Nunca usar caixa alta em textos longos.

## Formas

- Raio de canto: 14 a 20 px em cartões; 999 px em chips.
- Sombras em 3 níveis: 2 px (repouso), 8 px (hover), 20 px (modal).
- Bordas: 1 px translúcida; 2 px em foco.

## Texturas de bloco

- 16×16, paleta de 4 a 6 tons.
- Contorno interno sutil, sem preto puro.
- Grama: topo verde-lima com tufos; lateral com transição irregular.
- Cristais: facetas claras + núcleo saturado.
- Minérios: pedra base + veios brilhantes.
- Água: azul translúcido com faixas claras.

## UI

- HUD mínimo: hotbar, contador de Lumi, relógio, botões.
- Cartões com vidro fosco sobre o mundo (`backdrop-filter`).
- Raridade sempre com cor + estrelas + texto.
- Toasts entram em 300 ms (fade + slide) e saem em 200 ms.
- Ícones de itens em 32×32 com contorno suave.

## Personagens

- Avatar blocky, proporções fofas, sem detalhes agressivos.
- 4 skins de arma (Aurora, Pétala, Solar, Bolha) mudam a cor dos efeitos.
- 9 skins com silhueta reconhecível: Lumi (folha), Nino (estrela), Bia (flor), Tico (óculos), Dara (faixa), Sol (estrela), Maya (flor), Kai (óculos), Luna (faixa).
- Cores em pares de contraste suave; acessório sempre legível a 48 px.
- O mesmo avatar aparece no cartão do mundo e na sala multiplayer.
- Pets: silhuetas pequenas e arredondadas, 4 tipos e 8 cores; nunca parecem inimigos.
- NPCs: cores quentes, nome flutuante legível e fala curta em balão.
- Sem roupas pagas, armas de combate, dano ou expressões assustadoras. As armas são ferramentas mágicas de brincadeira.

## Lobby e cidades

- Sol, nuvens e colinas em camadas dão profundidade sem competir com o conteúdo.
- Cards de cidade usam selo de UF, gradiente próprio e botão Explorar.
- O sol decorativo fica atrás do conteúdo; em telas pequenas, reduzir opacidade para não atrapalhar a leitura.
- A logo atual tem moldura verde de selo e referência a "MINECRAFT MOD"; revisar para uma marca 100% autoral antes de publicação comercial.

## Movimento

- Entrada de modais: `scale(0.96) + opacity` 220 ms ease-out.
- Hover de cartões: `translateY(-4px)` 200 ms.
- Botões: `scale(0.97)` ao toque.
- Partículas de raro: 12 faíscas subindo, 900 ms.
- Respeitar `prefers-reduced-motion`.

## Som

- Escala pentatônica (Dó maior sem quarta/fá).
- Raro: sino cristalino ascendente.
- Quebrar: ruído curto com filtro por material.
- Colocar: thump suave.
- Craft: arpejo de 3 notas.
- Sem música agressiva; ambiente opcional.

## Referências

- *A Short Hike*, leveza e descoberta.
- *Animal Crossing*, aconchego e coleção.
- *Toca Boca*, clareza para crianças.
- *Monument Valley*, paletas e geometria.
