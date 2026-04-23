# Let's Work | Design System & UI Guidelines

Este documento descreve o padrão de design e componentes (UI/UX) definidos para a landing page do **Let's Work**.

## 1. Tipografia (Typography)
A escolha tipográfica busca um balanço entre a clareza técnica e um design moderno de software.

- **Display Font (Títulos):** `Plus Jakarta Sans`, sans-serif. 
  - Pesos suportados: 400, 500, 600, 700, 800, 900.
  - Uso: H1, H2, H3 e destaques numéricos.
  - Característica: Espaçamento de letras levemente apertado (`tracking-tight` a `tracking-tighter`) para visual moderno de tech startup.

- **Base Font (Corpos de texto e UI):** `Inter`, sans-serif.
  - Pesos suportados: 400, 500, 600.
  - Uso: Parágrafos, textos descritivos, dados de dashboard, botões e labels menores.
  - Característica: Alta legibilidade e precisão técnica.

## 2. Paleta de Cores (Color Palette)

O sistema suporta nativamente os modos Light e Dark, transitando de branco clean para o Preto Absoluto (estilo OLED).

### Cores Principais
- **Primary (Brand):** `#FF2D2D` (Vermelho Let's Work)
- **Primary Hover/Active:** Escurecimento natural nativo e `ring-primary/50` para focos.

### Superfícies e Backgrounds
- **Background Light:** `#FFFFFF`
- **Background Dark:** `#000000` (Preto OLED para máximo contraste)
- **Surface Light:** `#F8FAFC` (Slate 50 para quebras de seções)
- **Surface Dark:** `#0A0A0A` (Gera sutil elevação em relação ao bg total black)

### Textos
- **Text Padrão (Light):** `#0F172A` (Slate 900)
- **Text Padrão (Dark):** `#FFFFFF`
- **Text Subtle (Light):** `#475569` (Slate 600 - para descrições longas)
- **Text Subtle (Dark):** `#A1A1AA` (Zinc 400 - cinza sofisticado e legível no escuro)

### Bordas (Borders)
- **Border Light:** `rgba(0,0,0,0.08)`
- **Border Dark:** `rgba(255,255,255,0.08)`

## 3. Espaçamento Vertical (Spacing Standards)
Para criar consistência de ritmo visual na leitura (Vertical Rhythm):

- **Hero Section:** `pt-32 lg:pt-40 pb-24 lg:pb-32` (Espaço extra no topo por causa do fixed navbar).
- **Major Sections (Padrão de blocos):** `py-24 lg:py-32`. Esta medida de 96px a 128px de padding Y garante que a interface respire.
- **Scroll Storytelling Sections:** `pt-24 lg:pt-32 pb-12 lg:pb-16` internamente, devido à ancoragem de rolagem.

## 4. Estilos de Componentes (Component Styles)

### Raios de Borda (Border Radius)
Padronizados para não gerar conflito visual:
- **`rounded-full` (1000px):** Usado em **Botões Principais**, Pílulas informativas (badges) e Navbar/floating toggles. Traz abordagem fluida.
- **`rounded-[2rem]` (32px):** Usado nos maiores contêineres e cards de grandes destaques (ex: Blocos de Funcionalidades principais e mockups grandiosos).
- **`rounded-2xl` (16px):** Padrão estrito para **Cards Comuns** (Planos de Preços, Depoimentos, Cards de features menores).

### Botões (Buttons) e Interatividade
Todos os botões interativos devem respeitar três estados cruciais (Hover, Focus, Active):
- **Botão Primário:** 
  - Fundo `#FF2D2D`, texto branco.
  - Hover: `scale(1.02)` ou `hover:-translate-y-1` para botões maiores.
  - Active: `scale(0.98)` (feedback mecânico de clique).
  - Focus: Utiliza anéis para acessibilidade `focus:ring-2 focus:ring-primary/50 focus:ring-offset-2`.
- **Botões Secundários (Ghost/Outline):** 
  - Usa bordas da cor `borderline`.
  - Hover de leve preenchimento ou revelação sutil da cor primária nas bordas.

### Sombras (Shadows)
As sombras não são carregadas (blur pesado preto). Elas são voltadas para estéticas minimalistas modernas:
- **`shadow-soft`**: `0 20px 40px -15px rgba(0,0,0,0.05)` (Usado em modais e grandes painéis light mode).
- **`shadow-glow`**: `0 0 30px rgba(255,45,45,0.15)` (Usado com o botão Primary para dar efeito neon/glow focado).
- No **Dark Mode**, sombras convencionais são raras ou usam `rgba(0,0,0,0.8)` para elevação óptica severa de mockups.

## 5. Elementos Avançados de UI
- **Grid de Fundo (Tech-Grid):** Máscara gradiente esmaecida (radial) baseada em linhas verticais/horizontais translúcidas.
- **Glassmorphism Limitado:** Usado majoritariamente na Navbar (`backdrop-blur-md` + fundo 80% opacity) para manter legibilidade sem poluir o background. Nas features comuns optou-se pelo "Solid Card" para modernidade SaaS.