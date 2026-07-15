# WV Todoz — Tema WordPress (ACF Free)

Tema standalone da landing page WV_Todoz. Conteúdo editável no Admin via **ACF free** + **Custom Post Types** (sem recursos Pro).

## Requisitos

- WordPress 6.0+
- PHP 8.0+
- Plugin **[Advanced Custom Fields](https://wordpress.org/plugins/advanced-custom-fields/)** (versão **gratuita** — não precisa do Pro)

## Instalação

1. Copie a pasta `wvtodoz-theme/` para `wp-content/themes/wvtodoz-theme/` (ou zip a pasta e use Aparência → Temas → Adicionar).
2. Ative o tema **WV Todoz**.
3. Instale e ative o plugin **Advanced Custom Fields** (free).
4. Importe o conteúdo (recomendado — ver abaixo) **ou** configure a Home manualmente.
5. (Opcional) Aparência → Personalizar → Identidade do site → Logo.
6. (Opcional) Aparência → Menus → atribua menus a **Menu principal**, **Menu mobile**, **Menu rodapé**.

Os grupos de campos ACF são registrados em PHP (`inc/acf-fields.php`) e aparecem automaticamente na página Home (Front Page).

Thumbnail do tema: `screenshot.png` (1200×900) — aparece em Aparência → Temas.

## Seed de conteúdo (recomendado)

O tema inclui importador idempotente (`inc/seed.php`) que pré-carrega **todo o conteúdo atual do `index.html`** na Home + CPTs.

### Como usar

1. Ative o tema e o ACF free.
2. No Admin, um aviso **“Importar conteúdo WV”** aparece no Dashboard / Aparência → Temas.
3. Clique no botão **Importar conteúdo WV**.
4. Alternativa: **Aparência → Importar WV** (reimportar com “Reimportar / atualizar” se necessário).

### O que o seed faz

- Cria/atualiza a página **Home** e define como Front Page (`Configurações → Leitura`)
- Preenche todos os campos ACF da Home (hero, clientes title, sobre, missão, mobile-bridge, serviços/processo, portfólio, produtos intro, aplicações, blog intro, contato, footer, WhatsApp, strings de modais/CTAs)
- Publica CPTs: **Cases**, **Produtos**, **Clientes**, **Serviços** (títulos, textos, imagens, ordem)
- Publica 3 posts **Insights** com imagens locais do tema
- Usa `update_field` quando ACF está ativo; senão `update_post_meta` com as mesmas keys
- Marca a opção `wv_seed_done` para não repetir o aviso (reimportação forçada disponível na tela Importar WV)

## Onde editar o quê

| Conteúdo | Onde |
|----------|------|
| Hero, Sobre, Missão, Mobile Bridge, intros, Aplicações, Contato, Footer, WhatsApp, modais | Página **Home** → campos ACF (abas) |
| Cases do portfólio | CPT **Cases** |
| Cards de produtos | CPT **Produtos** |
| Logos de clientes | CPT **Clientes** (imagem destacada) |
| Cards de serviços | CPT **Serviços** |
| Insights / blog | Posts nativos do WordPress |
| Logo do site | Personalizar → Identidade |
| Navegação | Aparência → Menus |

## Limites ACF Free (importante)

Este tema **não** usa:

- Options Pages (Pro)
- Repeater / Flexible Content / Gallery / Clone (Pro)

Itens repetíveis usam **CPTs**. Passos do processo (4) e benefícios de aplicações (3) usam **campos numerados fixos** na Home.

Enquanto não houver posts nos CPTs, o tema renderiza **fallbacks** iguais ao HTML estático original.

## Estrutura

```
wvtodoz-theme/
├── style.css
├── screenshot.png
├── functions.php
├── front-page.php
├── header.php / footer.php
├── single.php / page.php / index.php
├── inc/          # setup, enqueue, cpt, acf-fields, helpers, defaults, seed
├── template-parts/sections/
└── assets/       # css, js, img, video (cópia do site estático)
```

## Stack front-end

Mesma do site estático: Tailwind CDN, Phosphor Icons, GSAP + ScrollTrigger, Lenis, AOS, `assets/css/main.css`. Tema claro é o padrão (toggle no header).

## Segurança

Saídas escapadas com `esc_html`, `esc_url`, `esc_attr`, `wp_kses_post` / `wv_kses_title`. Chamadas ACF protegidas com `function_exists('get_field')`.
