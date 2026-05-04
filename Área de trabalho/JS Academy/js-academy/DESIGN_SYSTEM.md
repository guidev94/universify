# 🎨 JS Academy Design System

Este documento define os padrões visuais e componentes da plataforma **JS Academy By AquinoDev**. O sistema é focado em alta performance, modernidade e aprendizado técnico.

---

## 🌈 Paleta de Cores (Tokens)

| Token | Cor | Hex | Uso |
| :--- | :--- | :--- | :--- |
| `js-yellow` | ![#F7DF1E](https://via.placeholder.com/15/F7DF1E/000000?text=+) | `#F7DF1E` | Marca principal, destaques de sucesso. |
| `accent-pink` | ![#EC4899](https://via.placeholder.com/15/EC4899/000000?text=+) | `#EC4899` | Chamadas de ação (CTAs), caminhos. |
| `accent-cyan` | ![#22D3EE](https://via.placeholder.com/15/22D3EE/000000?text=+) | `#22D3EE` | Arena de lógica, elementos interativos. |
| `zinc-950` | ![#09090B](https://via.placeholder.com/15/09090B/000000?text=+) | `#09090B` | Fundo principal (OLED). |
| `zinc-900` | ![#18181B](https://via.placeholder.com/15/18181B/000000?text=+) | `#18181B` | Superfícies de cards e inputs. |

---

## 🏗️ Componentes Principais

### 1. Bento Card
O padrão visual de toda a plataforma.
*   **Classe:** `.bento-card`
*   **Propriedades:** Border radius de `2.5rem`, fundo translúcido (`backdrop-blur`), borda sutil.
*   **Hover:** `.bento-card-hover` para elevação e brilho.

### 2. Botões (Buttons)
*   **Primário:** `.btn-primary` (Branco/Amarelo, Texto Preto).
*   **Ação:** `.btn-accent` (Rosa, Texto Branco).
*   **Outline:** `.btn-outline` (Transparente com borda).

### 3. Tipografia (Typography)
*   **Headings Huge:** `.heading-huge` (Extra Bold, Italic, 8xl).
*   **Section Titles:** `.heading-section` (Extra Bold, 3xl).
*   **Code:** JetBrains Mono (Para o editor e snippets).

---

## 🌗 Modos de Cor

A plataforma suporta **Dark Mode (Padrão)** e **Light Mode (White Mode)**.
*   As variáveis CSS são trocadas via classe `.light-mode` no `body`.
*   O Monaco Editor e todos os componentes reagem automaticamente à mudança.

---

## 🛡️ Segurança de UI
*   `user-select: none`: Aplicado em áreas críticas para evitar plágio de código.
*   `backdrop-filter`: Usado para dar profundidade de interface (Glassmorphism).

---
*Desenvolvido com foco em UI/UX Premium por **AquinoDev***
