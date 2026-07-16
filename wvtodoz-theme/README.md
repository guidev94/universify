# WV Todoz — Tema WordPress (ACF Free)

Tema standalone da landing page WV_Todoz. Conteúdo editável no Admin via **ACF free** + **Custom Post Types** (sem recursos Pro).

Mensagem comercial atual: **agentes de IA**, **automações** e **integrações de sistemas**.

## Requisitos

- WordPress 6.0+
- PHP 8.0+
- Plugin **[Advanced Custom Fields](https://wordpress.org/plugins/advanced-custom-fields/)** (versão **gratuita**)

## Instalação rápida

1. Copie `wvtodoz-theme/` → `wp-content/themes/wvtodoz-theme/` (ou zip a pasta e use Aparência → Temas → Adicionar).
2. Ative o tema **WV Todoz**.
3. Instale e ative o **ACF free**.
4. Vá em **Aparência → Importar WV** e clique **Importar conteúdo WV** (recomendado).
5. Confirme em **Configurações → Leitura** que a Front Page é a página **Home**.
6. (Opcional) Aparência → Menus → atribua **Menu principal**, **Menu mobile**, **Menu rodapé**.

Thumbnail: `screenshot.png` (aparece em Aparência → Temas).

## Importar conteúdo WV

| Onde | O que faz |
|------|-----------|
| **Aparência → Importar WV** | Tela oficial de import / reimport |
| Aviso no Dashboard / Temas | Botão **Importar conteúdo WV** se o seed ainda não rodou |
| Reimportar / atualizar | Sobrescreve textos ACF + CPTs a partir de `inc/defaults.php` |

O seed (`inc/seed.php` v1.3.0):

- Cria/atualiza a página **Home** e define como Front Page
- Preenche **todas as abas ACF** da Home
- Publica CPTs: **Cases**, **Produtos**, **Clientes**, **Serviços**
- Publica 3 posts **Insights**
- **Não** rebaixa imagens já anexadas (thumbnail / ACF image) no reimport

## Onde editar cada seção

### Página Home (abas ACF)

Abra **Páginas → Home** (a Front Page). Os campos aparecem em abas:

| Aba | O que controla |
|-----|----------------|
| **Hero** | Badge, eyebrow, título, lead, CTAs, vídeo, toggle dos logos de produtos |
| **Sobre** | Título, lead, stats, CTAs, imagem do escritório |
| **Missão** | Eyebrow, título, apoio, produtos A/B, CTA |
| **Mobile Bridge** | Headline (4 linhas + destaque lime), eyebrow, lead, CTA |
| **Serviços** | Intro + 4 passos do processo (campos numerados) |
| **Portfólio** | Intro dos cases + título HTML da faixa de clientes |
| **Produtos** | Eyebrow / título / lead da seção `#produtos` |
| **Aplicações** | Copy completa, chip, 3 benefícios, CTA, imagem |
| **Insights** | Intro + CTA do blog |
| **Contato / Footer** | Contato, WhatsApp, tagline, endereço, copyright, redes, modais |

Cards de **serviços / produtos / cases / clientes** não ficam na Home — use os CPTs abaixo.

### Custom Post Types

| CPT (menu Admin) | Seção no site | Campos principais |
|------------------|---------------|-------------------|
| **Cases** | `#portfolio` | Título, imagem destacada, categoria, meta |
| **Produtos** | `#produtos` + links do rodapé | Logo (imagem), subtítulo, highlights, badge, featured/wide, URLs dos botões |
| **Clientes** | `#clientes` | Título + **imagem destacada** (logo) |
| **Serviços** | `#servicos` | Ícone Phosphor, título HTML, bullets, CTA, variant, link |
| **Posts** | `#blog` / single | Conteúdo nativo do WP |

Ordem dos CPTs: **menu_order** (asc) depois data.

Com CPT vazio, o tema usa fallbacks de `inc/defaults.php` (iguais ao `index.html`).

### Logo e tema claro/escuro

Arquivos no tema:

- `assets/img/logo-wv-light.svg` — `WV_` verde `#CFFF04` + `TODOZ` / tagline **pretos** (header scrolled / páginas claras)
- `assets/img/logo-wv-dark.svg` — `WV_` verde + wordmark **branco** (hero, dark theme, footer)

O CSS troca `.site-logo--light` / `.site-logo--dark` conforme `data-theme` e `#main-header.is-scrolled`. Não use `brightness(0)` no wordmark.

Logo custom (Personalizar → Identidade) ainda é lido por `wv_logo_url()` em contextos legados; o header/footer usam os SVGs dual-theme.

### Menus

| Local | Uso |
|-------|-----|
| Menu principal | Nav desktop |
| Menu mobile | Overlay mobile |
| Menu rodapé — Navegação | Coluna “Navegação” do footer |

Sem menu atribuído, o tema usa links padrão (`#sobre`, `#servicos`, etc.).

A coluna **Produtos** do footer lista automaticamente os CPT **Produtos** (URL primária).

## Limites ACF Free

Este tema **não** usa Options Page, Repeater, Flexible, Gallery ou Clone (Pro).

Itens repetíveis = **CPTs**. Passos do processo (4) e benefícios de aplicações (3) = **campos fixos** na Home.

## Estrutura

```
wvtodoz-theme/
├── style.css / screenshot.png
├── front-page.php / header.php / footer.php
├── inc/          # setup, enqueue, cpt, acf-fields, helpers, defaults, seed
├── template-parts/sections/
└── assets/       # css, js, img, video (espelho do site estático)
```

## Stack front-end

Tailwind CDN, Phosphor Icons, GSAP + ScrollTrigger, Lenis, AOS, `assets/css/main.css`.

**Tema claro (light) é o padrão:** `data-theme="light"` no `<html>`, snippet FOUC em `header.php` (fallback `light` se `localStorage` vazio/inválido), e `theme.js` com `DEFAULT_THEME = "light"` + chave `wv-theme`.

Hero video: `assets/video/hero.mp4` (web-optimized ~2.3MB). Path igual no estático e no tema.

## Segurança

Saídas com `esc_html`, `esc_url`, `esc_attr`, `wp_kses_post` / `wv_kses_title`. ACF guardado com `function_exists('get_field')`.
