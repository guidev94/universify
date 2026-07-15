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
4. Crie uma Página chamada **Home** (conteúdo vazio).
5. Em **Configurações → Leitura**:
   - Marque “Uma página estática”
   - Página inicial: **Home**
6. (Opcional) Aparência → Personalizar → Identidade do site → Logo.
7. (Opcional) Aparência → Menus → atribua menus a **Menu principal**, **Menu mobile**, **Menu rodapé**.

Os grupos de campos ACF são registrados em PHP (`inc/acf-fields.php`) e aparecem automaticamente na página Home (Front Page).

## Onde editar o quê

| Conteúdo | Onde |
|----------|------|
| Hero, Sobre, Missão, Mobile Bridge, intros, Aplicações, Contato, Footer, WhatsApp | Página **Home** → campos ACF (abas) |
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

## Seed rápido (recomendado)

1. Publique 5 **Cases** com imagem destacada + campos categoria/meta (ordem via Atributos da página → Ordem).
2. Publique 4 **Produtos** com logo na imagem destacada.
3. Publique logos em **Clientes**.
4. Publique 5 **Serviços** com variante de layout (featured/tall/mobile/ia/produtos).
5. Publique 3+ **Posts** com imagem destacada para a seção Insights.

## Estrutura

```
wvtodoz-theme/
├── style.css
├── functions.php
├── front-page.php
├── header.php / footer.php
├── single.php / page.php / index.php
├── inc/          # setup, enqueue, cpt, acf-fields, helpers, defaults
├── template-parts/sections/
└── assets/       # css, js, img, video (cópia do site estático)
```

## Stack front-end

Mesma do site estático: Tailwind CDN, Phosphor Icons, GSAP + ScrollTrigger, Lenis, AOS, `assets/css/main.css`.

## Segurança

Saídas escapadas com `esc_html`, `esc_url`, `esc_attr`, `wp_kses_post` / `wv_kses_title`. Chamadas ACF protegidas com `function_exists('get_field')`.
