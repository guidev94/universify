# LeadHunter — Design System

## Paleta de Cores

### Backgrounds
| Token | Hex | Uso |
|-------|-----|-----|
| `--bg-deep` | `#09090b` | Body background |
| `--bg-surface` | `#18181b` | Cards internos, inputs |
| `--bg-elevated` | `#27272a` | Hover states, skeleton |

### Accent (Blue — baseado no logo)
| Token | Hex | Uso |
|-------|-----|-----|
| `--accent` | `#2563eb` | Botões primários, focus rings, links ativos |
| `--accent-hover` | `#3b82f6` | Hover do accent |
| `--accent-light` | `#60a5fa` | Texto accent, badges ativas |
| `--accent-subtle` | `rgba(37,99,235,0.08)` | Background sutil (sidebar active, badges) |
| `--accent-glow` | `rgba(37,99,235,0.12)` | Focus ring glow, hover card |

### Texto
| Token | Hex | Uso |
|-------|-----|-----|
| `--text-primary` | `#fafafa` | Headings, valores, nomes |
| `--text-secondary` | `#a1a1aa` | Body text, descriptions |
| `--text-muted` | `#52525b` | Placeholders, labels inativos |

### Semânticas (badges/status)
| Contexto | Background | Text |
|----------|-----------|------|
| Hot | `rgba(234,179,8,0.12)` | `#fde047` |
| Warm | `rgba(251,146,60,0.12)` | `#fb923c` |
| Cold | `rgba(113,113,122,0.12)` | `#a1a1aa` |
| New | `rgba(37,99,235,0.12)` | `#93c5fd` |
| Contacted | `rgba(6,182,212,0.12)` | `#67e8f9` |
| Negotiating | `rgba(249,115,22,0.12)` | `#fdba74` |
| Closed Won | `rgba(34,197,94,0.12)` | `#86efac` |
| Closed Lost | `rgba(82,82,91,0.15)` | `#71717a` |

---

## Tipografia

**Font:** Inter (Google Fonts)
**Weights:** 300 (light), 400 (regular), 500 (medium), 600 (semibold), 700 (bold), 800 (extrabold)

| Elemento | Size | Weight | Letter-spacing |
|----------|------|--------|---------------|
| Stat numbers | 2.25rem (36px) | 800 | -0.03em |
| Page title | 15px | 600 | -0.01em |
| Section title | 13px | 600 | normal |
| Body text | 13px | 400 | normal |
| Labels | 11-12px | 600 | 0.08em (uppercase) |
| Micro text | 10px | 500 | normal |
| Badges | 11px | 600 | 0.02em |

---

## Glassmorphism

### Receita Base (`.glass-card`)
```css
background: linear-gradient(135deg, rgba(255,255,255,0.03) 0%, rgba(255,255,255,0.01) 100%);
backdrop-filter: blur(32px) saturate(1.3);
border: 1px solid rgba(255,255,255,0.08);
border-radius: 20px;
```

### Hover State
```css
border-color: rgba(255,255,255,0.1);
box-shadow: 0 16px 64px -16px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,255,255,0.05);
transform: translateY(-2px);
```

### Variantes
- `.glass-card` — hover com translateY
- `.glass-card-static` — sem hover animation (strips, filtros)
- `.glass` — minimal (border-radius 16px, sem gradient)

---

## Componentes

### Botões
| Classe | Estilo |
|--------|--------|
| `.btn-accent` | Gradiente azul `#2563eb → #1d4ed8`, texto branco, shadow glow |
| `.btn-ghost` | Transparente, border `glass-border`, texto secondary |

### Inputs
| Classe | Estilo |
|--------|--------|
| `.input-glass` | bg `rgba(24,24,27,0.8)`, border glass, focus ring azul |
| `.select-glass` | Mesmo + custom arrow SVG |

### Badges
| Classe | Uso |
|--------|-----|
| `.badge` | Pill shape (border-radius 100px), 11px semibold |
| `.badge-{status}` | Cor semântica do status |
| `.score-pill` | Score numérico (border-radius 8px, tabular-nums) |

### Cards
| Classe | Uso |
|--------|-----|
| `.stat-card` | Stat principal com radial glow on hover |
| `.icon-box` | 40x40 rounded-xl com ícone central |

---

## Espaçamento

**Grid:** 8pt base
| Context | Value |
|---------|-------|
| Card padding | 24px (p-6) |
| Section gap | 20px (gap-5) |
| Section margin | 32px (mb-8) |
| Inner gap | 12px (gap-3) |
| Sidebar width | 260px |
| Content padding | 32px (p-8) |

---

## Animações

### GSAP (page load)
- Stat cards: `y:30, opacity:0 → stagger 0.08s`
- Glass cards: `y:20, opacity:0 → stagger 0.06s, delay 0.3s`
- Table rows: `x:-10, opacity:0 → stagger 0.03s`
- Buttons: mousedown `scale(0.95)` → mouseup `elastic.out`

### CSS Keyframes
| Name | Duration | Easing | Uso |
|------|----------|--------|-----|
| `fadeUp` | 0.4s | cubic-bezier(0.4,0,0.2,1) | Entrance |
| `glow` | 2s | ease-in-out infinite | Status dots |
| `shimmer` | 1.8s | ease-in-out infinite | Skeleton loading |
| `float` | 3s | ease-in-out infinite | Empty state icons |
| `shake` | 0.4s | ease | Delete button hover |

### Transitions
- Cards: `0.3s ease` (border, shadow, transform)
- Buttons: `0.25s cubic-bezier(0.4,0,0.2,1)`
- Sidebar links: `0.2s cubic-bezier(0.4,0,0.2,1)`
- Inputs: `0.25s cubic-bezier(0.4,0,0.2,1)`

---

## Ícones

**Library:** Lucide Icons (CDN)
**Stroke width:** 1.5 (padrão)
| Context | Size | Stroke |
|---------|------|--------|
| Sidebar nav | 18px | 1.5 |
| Stat card icon-box | 20px | 1.5 |
| Action buttons | 14px (3.5) | 2 |
| Inline badges | 12px (3) | 2 |
| Empty state | 28-32px | 1 |

---

## Layout

### Breakpoints (Tailwind)
| Name | Min-width | Uso |
|------|-----------|-----|
| `sm` | 640px | 2-col grid |
| `lg` | 1024px | Sidebar visible, 4-col stats |

### Estrutura
```
┌──────────┬────────────────────────────────────┐
│          │  Topbar (sticky, blur bg)           │
│ Sidebar  ├────────────────────────────────────┤
│  260px   │                                    │
│          │  Content (scrollable, p-8)          │
│  fixed   │                                    │
│  on lg   │  Stats → Charts → Tables           │
│          │                                    │
└──────────┴────────────────────────────────────┘
```

### Mobile (<1024px)
- Sidebar: hidden (-translate-x-full), toggle via hamburger
- Stats: 1-2 columns
- Table: horizontal scroll

---

## Dark Theme Rules

1. Nunca usar branco puro (`#ffffff`) — máximo é `#fafafa`
2. Backgrounds sempre em gradiente sutil (radial azul 3-4% opacity)
3. Borders são `rgba(255,255,255, 0.06-0.08)` — nunca solid colors
4. Hover: aumentar opacity do border em 0.02-0.04
5. Shadows: preto com alta opacity (50-60%), blur largo (64px)
6. Accent glow: sempre `rgba(37,99,235, 0.12-0.15)` — nunca mais que 0.2
7. Texto em 3 níveis: primary (#fafafa), secondary (#a1a1aa), muted (#52525b)
