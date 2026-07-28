# WV Todoz — Tema WordPress (ACF Free)

Tema standalone da landing page WV_Todoz. Conteúdo editável no Admin via **painel WV Todoz** + **ACF free** + **Custom Post Types** (sem recursos Pro).

Mensagem comercial atual: **agentes de IA**, **automações** e **integrações de sistemas**.

## Requisitos

- WordPress 6.0+
- PHP 8.0+
- Plugin **[Advanced Custom Fields](https://wordpress.org/plugins/advanced-custom-fields/)** (versão **gratuita**)

## Instalação rápida

1. Copie `wvtodoz-theme/` → `wp-content/themes/wvtodoz-theme/` (ou zip a pasta e use Aparência → Temas → Adicionar).
2. Ative o tema **WV Todoz**.
3. Instale e ative o **ACF free**.
4. Vá em **WV Todoz → Importar conteúdo** e clique **Importar conteúdo WV** (recomendado).
5. Confirme em **Configurações → Leitura** que a Front Page é a página **Home**.
6. (Opcional) **WV Todoz → Menus** (atalho para Aparência → Menus) e atribua **Menu principal**, **Menu mobile**, **Menu rodapé**.

Thumbnail: `screenshot.png` (aparece em Aparência → Temas).

## Painel WV Todoz (manutenção)

Menu lateral **WV Todoz** — painel com a linguagem visual da home (lima `#CFFF04`, dark `#0a0a0a`, cream `#fafaf8`).

| Página | Função |
|--------|--------|
| **Painel** | Status (tema, ACF, Front Page, seed) + atalhos + cards de todas as seções |
| **Hero** … **Contato / Footer** | Uma página por seção: prévia, valores atuais, CTA de edição com `?wv_tab=`, Ver no site |
| **Serviços / Portfólio / Produtos / Insights / Clientes** | Intro da Home + lista do CPT relacionado |
| **Menus** | Atalho para menus do WP |
| **Importar conteúdo** | Seed / reimport (com confirmação) |
| **Ajuda** | Fluxo curto para editores |

### Deep link de abas ACF

Os botões **Editar …** abrem a Home com `?wv_tab=hero` (etc.). O JS do admin tenta clicar na aba ACF correspondente e mostra um banner de contexto.

### Seletor de ícones (Phosphor)

No editor de **Serviços** (`wv_servico_icon`) aparece o botão **Escolher ícone**:

- Modal pesquisável com ícones usados no site + set popular
- Preview ao vivo
- Grava a classe no mesmo campo texto ACF (ex.: `ph-robot`)

### Conteúdo HTML / visual

| Campo | Tipo | Onde |
|-------|------|------|
| `wv_servico_title_html` | **Wysiwyg** (visual) | CPT Serviço |
| `wv_clientes_title` | **Wysiwyg** (visual) | Home → aba Portfólio |

As páginas de seção mostram prévia sanitizada — editores não precisam editar HTML cru.

## Importar conteúdo WV

| Onde | O que faz |
|------|-----------|
| **WV Todoz → Importar conteúdo** | Tela oficial de import / reimport |
| Aparência → Importar WV | Redireciona para o painel WV Todoz |
| Aviso no Dashboard | Botão rápido se o seed ainda não rodou |

O seed (`inc/seed.php` v1.4.0):

- Cria/atualiza a página **Home** e define como Front Page
- Preenche **todas as abas ACF** da Home (incl. rótulos da Missão e títulos do footer)
- Publica CPTs: **Cases**, **Produtos**, **Clientes**, **Serviços**
- Publica 3 posts **Insights**
- **Não** rebaixa imagens já anexadas no reimport

## Onde editar cada seção

Use **WV Todoz → [seção]** em vez de caçar páginas soltas.

### Página Home (abas ACF)

| Aba | O que controla |
|-----|----------------|
| **Hero** | Badge, eyebrow, título, lead, CTAs, vídeo, logos |
| **Sobre** | Título, lead, stats, CTAs, imagem |
| **Missão** | Copy, produtos A/B, rótulos dos stats, CTA |
| **Mobile Bridge** | Headline + lead + CTA |
| **Serviços** | Intro + 4 passos do processo |
| **Portfólio** | Intro dos cases + título visual da faixa de clientes |
| **Produtos** | Eyebrow / título / lead |
| **Aplicações** | Copy, chip, benefícios, CTA, imagem |
| **Insights** | Intro + CTA do blog |
| **Contato / Footer** | Contato, WhatsApp, tagline, colunas do rodapé, redes, modais |

### Custom Post Types

| CPT | Seção | Notas |
|-----|-------|-------|
| **Cases** | `#portfolio` | Imagem destacada + meta ACF |
| **Produtos** | `#produtos` + footer | Logo, highlights, CTAs |
| **Clientes** | `#clientes` | Imagem destacada = logo |
| **Serviços** | `#servicos` | Ícone via picker, título visual, bullets |
| **Posts** | `#blog` | Conteúdo nativo do WP |

Ordem dos CPTs: **menu_order** (asc) depois data.

## Limites ACF Free

Este tema **não** usa Options Page, Repeater, Flexible, Gallery ou Clone (Pro).

Itens repetíveis = **CPTs**. O painel custom (`inc/admin/`) substitui Options Page.

## Estrutura

```
wvtodoz-theme/
├── style.css / screenshot.png
├── front-page.php / header.php / footer.php
├── inc/
│   ├── admin/          # Painel WV Todoz (seções, ícones, render)
│   ├── acf-fields.php, cpt.php, seed.php, …
├── template-parts/sections/
└── assets/
    ├── css/admin.css   # UI do painel
    └── js/admin.js     # Abas ACF + icon picker
```

## Stack front-end

Tailwind CDN, Phosphor Icons, GSAP + ScrollTrigger, Lenis, AOS, `assets/css/main.css`.

**Tema claro (light) é o padrão.**

## Segurança

Saídas com `esc_html`, `esc_url`, `esc_attr`, `wp_kses_post` / `wv_kses_title`. ACF guardado com `function_exists('get_field')`.
