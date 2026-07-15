# State

**Last updated:** 2026-07-15 (theme readiness wrap-up)  
**Session:** WP theme 100% + light logos + produtos/responsive

## Decisions

- Dual logos: `logo-wv-light.svg` (green WV_ + black TODOZ) / `logo-wv-dark.svg` (green + white); CSS toggle by theme + scrolled header.
- Light `#produtos`: white cards, ink type, olive subtitles, dark chip for Let's Work logo.
- Messaging: agentes / automações / integrações; seed v1.2.0 from `defaults.php`.
- Tema em `wvtodoz-theme/` na raiz (zip-ready). ACF free + CPTs.
- Admin: **Aparência → Importar WV**; footer produtos via CPT.
- Push `origin/master` e `origin/main` (sem force).

## Blockers

- Nenhum ativo.

## Lessons

- ACF free: tabs + text/image/url/wysiwyg/true_false/select/file OK; Gallery/Repeater/Flexible/Options = Pro.
- `get_field` / `wv_field` guarded; `wv_field` aceita `$post_id` opcional para CPT.

## Todos

- [x] Spec-Driven wordpress-theme-acf
- [x] Backup static HTML
- [x] Theme scaffold + ACF/CPT/templates
- [x] Light logos + produtos/responsive
- [x] Seed/README readiness
- [x] Commit + push

## Deferred

- Seed automático de CPTs via WP-CLI
- ACF Pro Options Page (se licença for adquirida)
- Design system ~R$50k

## Preferences

- Commits atômicos; copy PT premium B2B.
