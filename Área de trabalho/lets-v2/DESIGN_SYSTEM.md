# 🎯 Design System — Let's Work (Baseado no projeto `lets-v2`)

> Documento gerado **somente com análise das páginas deste projeto** (`index.html` + páginas internas `.html`).

---

## 1) Escopo analisado

Páginas mapeadas no repositório:

- `index.html`
- `blog.html`
- `planos.html`
- `como-funciona.html`
- `contato.html`
- `download-app.html`
- `conheca.html`
- `ia-no-rh.html`
- `rastreamento.html`
- `gestao-de-documentos.html`
- `comunicacao-de-equipe.html`
- `gerenciamento-de-escalas.html`
- `gerenciamento-de-projetos.html`
- `gerenciamento-de-ferias.html`
- `gerenciamento-de-vagas.html`

---

## 2) Identidade visual observada

### Personalidade da marca

- **Corporativo moderno** com pegada SaaS premium.
- Base monocromática (branco/preto) com acento forte em vermelho.
- Mistura de blocos sólidos com efeitos pontuais de glow, blur e microinteração.

### Princípios visuais atuais

1. **Contraste alto** (light e dark consistentes).
2. **Hierarquia tipográfica forte** (display pesado + texto funcional limpo).
3. **Cards como unidade principal** da UI.
4. **Ação sempre destacada em vermelho** (`primary`).
5. **Animação como suporte** (não como conteúdo principal).

---

## 3) Foundations (tokens oficiais do projeto)

## 3.1 Cores

Tokens existentes no `tailwind.config` embutido nas páginas:

| Token | Valor | Uso |
|---|---|---|
| `primary` | `#FF2D2D` | CTA principal, destaque, estados de foco/hover |
| `dark` | `#000000` | Fundo dark base / contraste máximo |
| `surface.light` | `#FFFFFF` | Fundo padrão light |
| `surface.dark` | `#0A0A0A` | Superfícies e cards no dark |
| `subtle.light` | `#475569` | Texto secundário em light |
| `subtle.dark` | `#A1A1AA` | Texto secundário em dark |
| `borderline.light` | `rgba(0,0,0,0.08)` | Bordas suaves no light |
| `borderline.dark` | `rgba(255,255,255,0.08)` | Bordas suaves no dark |

### Cores auxiliares recorrentes

- WhatsApp floating CTA: `#25D366`
- Verdes/âmbar/azul em badges de status contextual.

---

## 3.2 Tipografia

Famílias globais encontradas:

- **Display:** `Plus Jakarta Sans`
- **Texto/UI:** `Inter`

### Escala observada

- Hero grande: `text-5xl` até `xl:text-[4.5rem]`
- Título de seção: `text-4xl md:text-5xl lg:text-[3.5rem]`
- Subtítulo/apoio: `text-lg` / `text-xl`
- Corpo padrão: `text-sm` / `text-base`
- Microcopy/tag: `text-xs` / `text-[10px]` + `tracking-widest` + uppercase

### Peso recorrente

- Display: `font-black` / `font-bold`
- Corpo: `font-medium`
- CTA: `font-bold`

---

## 3.3 Espaçamento e layout

### Container

- Container principal padrão: `max-w-[1400px] mx-auto px-6`
- Variação de conteúdo editorial: `max-w-[1200px]`

### Ritmo vertical observado

- Seções grandes: `py-24 lg:py-32`
- Seções médias: `py-16 lg:py-20`
- Header interno: `pt-32 md:pt-40`

### Grid/layout recorrente

- `grid lg:grid-cols-12` para composições hero e blocos complexos.
- Páginas internas com padrão: **content + sidebar** (`lg:col-span-8` / `lg:col-span-4`).

---

## 3.4 Bordas, raio e sombra

### Radius mais usados

- `rounded-xl` (inputs/menus)
- `rounded-2xl` (cards padrão)
- `rounded-3xl` (cards premium/destaque)
- `rounded-full` (chips e botões pílula)

### Sombras tokenizadas

- `shadow-soft`: `0 20px 40px -15px rgba(0,0,0,0.05)`
- `shadow-glow`: `0 0 30px rgba(255,45,45,0.15)`

### Borda padrão

- Light: `border-borderline-light`
- Dark: `border-borderline-dark`

---

## 3.5 Motion / animação

Biblioteca: **GSAP + ScrollTrigger + ScrollToPlugin**.

Padrões observados:

- Revelação por scroll via `.section-reveal`
- Hero initial via `.hero-reveal`
- Efeito magnético em elementos `.magnetic`
- Marquee contínuo para logos/depoimentos

Diretriz atual do projeto (após ajuste):

- Entrada mais rápida: `start: "top 95%"`, `duration: 0.45`, `once: true`

---

## 4) Componentes-base (catálogo)

## 4.1 Navegação

### Navbar Desktop/Mobile

- Fixa no topo (`fixed`, `z-50`)
- Fundo translúcido com blur (`bg-white/80`, `dark:bg-black/80`, `backdrop-blur-md`)
- Dropdown “Soluções” com reveal por hover
- Toggle de tema em desktop e mobile

### Mobile Menu Overlay

- Tela cheia com `translate-x-full` + transição
- Estrutura de links e CTA replicada no desktop

---

## 4.2 Botões

### `btn-primary` (token de ação)

- Fundo vermelho (`primary`), texto branco, borda vermelha
- Hover invertido (fundo transparente + texto vermelho)
- Focus visível com halo vermelho

### Botões secundários

- Estilo outline neutro (borda sutil)
- Alto contraste no dark/light

### `magnetic`

- Microinteração de deslocamento por mouse + retorno elástico

---

## 4.3 Cards

### `solid-card`

- Base clara no light (`#F8FAFC`), escura no dark (`#0A0A0A`)
- Borda sutil + hover em `primary`

### Cards de destaque

- Versões com `rounded-3xl`, glow e borda reforçada em vermelho

---

## 4.4 Seções e wrappers

### Header interno (páginas de solução)

- Padrão: `TITLE BAR + LEAD CAPTURE`
- Esquerda com headline + direita com formulário/CTA

### Bloco principal de conteúdo

- Padrão: `MAIN CONTENT + SIDEBAR LAYOUT`
- Conteúdo técnico + widgets de CTA/IA/prova social

---

## 4.5 Modais

### Modal de captação

- `#lead-modal`, overlay com blur, escala + fade
- Estrutura: topo visual + formulário + CTA

### Modal legal (home)

- Conteúdo dinâmico injetado por ID
- Scroll interno dedicado

---

## 4.6 Elementos utilitários recorrentes

- Floating WhatsApp Button (fixo canto inferior)
- FAQ com `<details>` estilizados
- Chips/Badges (`uppercase`, `tracking-widest`, `text-xs`)

---

## 5) Padrões de conteúdo e copy UI

### Headings

- Curtos, diretos, orientados a resultado.
- Uso frequente de destaque em vermelho para palavra-chave.

### Subhead

- Mensagem de valor + redução de fricção (compliance, implantação, suporte).

### CTA copy

- Verbos de ação claros: “Falar com Vendas”, “Solicitar proposta”, “Ver todos os módulos”.

---

## 6) Acessibilidade (baseline do sistema)

Pontos positivos observados:

- Contraste consistente em temas claro/escuro.
- Uso de `focus:ring` em CTAs principais.
- Ícones com suporte semântico visual e labels via contexto.

Regras obrigatórias do DS:

1. Todo botão/ação precisa de estado `hover`, `focus-visible`, `active`.
2. Não usar cor sozinha para informar estado crítico.
3. Garantir legibilidade mínima em `text-subtle-*` sobre fundo real.
4. Inputs com feedback de foco sempre visível.

---

## 7) Segurança de UI (frontend)

Diretrizes para novas telas:

1. Evitar inserir dados dinâmicos com `.innerHTML` sem sanitização.
2. Preferir `.textContent` para conteúdo textual dinâmico.
3. Em links externos com `target="_blank"`, usar `rel="noopener noreferrer"`.
4. Não expor dados sensíveis em mensagens de erro de interface.

---

## 8) Performance visual

Padrão do projeto:

- Motion suave com GSAP, mas com início antecipado e duração curta.
- Evitar excesso de animações simultâneas em mobile.
- Prefira animações em `transform/opacity`.

Recomendações do DS:

- Imagens de listas/cards com `loading="lazy"`.
- Manter `once: true` em revelações não essenciais de replay.
- Reutilizar classes utilitárias para reduzir CSS repetido inline.

---

## 9) Blueprint de página (template oficial)

Ordem recomendada para novas páginas internas:

1. **Head padrão** (metas + tailwind config + fontes + scripts base)
2. **Navbar global**
3. **Header da página** (`TITLE BAR` ou `TITLE BAR + LEAD CAPTURE`)
4. **Main** com grid `8/4` (conteúdo + sidebar)
5. **Footer global**
6. **Lead modal**
7. **Scripts core** (theme, mobile menu, modal, scroll/motion)
8. **Floating WhatsApp CTA**

---

## 10) Snippets de referência (do próprio padrão)

### 10.1 Container padrão

```html
<div class="max-w-[1400px] mx-auto px-6">...</div>
```

### 10.2 Badge de seção

```html
<div class="inline-flex px-3 py-1.5 rounded-sm border border-borderline-light dark:border-borderline-dark bg-white dark:bg-[#111] text-xs font-bold uppercase tracking-widest text-subtle-light dark:text-subtle-dark items-center gap-2">
  <span class="w-2 h-2 rounded-full bg-primary"></span>
  Label da seção
</div>
```

### 10.3 Card base

```html
<article class="solid-card rounded-2xl border border-borderline-light dark:border-borderline-dark p-6 transition-all hover:border-primary/30">
  ...
</article>
```

### 10.4 CTA primário

```html
<button class="btn-primary rounded-full px-6 py-3 font-bold focus:outline-none focus:ring-2 focus:ring-primary/50">
  Ação principal
</button>
```

---

## 11) Governança do Design System

### Regra de consistência

- Qualquer nova página deve manter: tokens, navbar/footer, modal padrão, estados de botão e estrutura de spacing base.

### Regra de evolução

- Nova variação de componente só entra no DS se usada em **2+ contextos reais**.

### Nomeação recomendada

- Componentes: `kebab-case` (`solid-card`, `btn-primary`)
- Seções: IDs semânticos (`#planos`, `#faq`, `#solucoes`)

---

## 12) Débitos identificados no projeto (para backlog)

1. **Config/CSS duplicados em múltiplos HTMLs** → centralizar base para reduzir manutenção.
2. Classe `solid-card` usa `var(--border-color)` sem token CSS explícito global definido.
3. Classe `glass` usada em FAQ da home sem definição dedicada encontrada no CSS comum.
4. Padronizar `rel="noopener noreferrer"` em todos links externos com `target="_blank"`.

---

## 13) Versão

- **Versão:** 1.0.0
- **Origem:** auditoria real do `lets-v2` (home + internas)
- **Data:** 2026-04-30
