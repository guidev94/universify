# State

**Last updated:** 2026-07-15  
**Session:** Copy IA-first (agentes / automações / integrações)

## Decisions

- Messaging comercial centra em **Criação de Agentes**, **Automações** e **Integração de Sistemas**; apps/sites como veículos de entrega.
- Copy espelhada em `index.html` + `wvtodoz-theme/` (`defaults.php`, seed, ACF defaults).
- Tema em `wvtodoz-theme/` na raiz do repo (zip-ready), não sob `wordpress/wp-content/`.
- ACF free only: Home page field groups + CPTs para repetíveis (sem Options Page / Repeater).
- Fallbacks em `inc/defaults.php` preservam copy/visual do HTML estático quando Admin ainda vazio.
- Backup em `backups/static-v3-20260715/` (originais não deletados).
- Push `origin/master` (+ `origin/main` se tracking permitir, sem force).

## Blockers

- Nenhum ativo.

## Lessons

- ACF free: tabs + text/image/url/wysiwyg/true_false/select/file OK; Gallery/Repeater/Flexible/Options = Pro.
- `get_field` deve ser guarded com `function_exists` para o tema não fatalar sem o plugin.

## Todos

- [x] Spec-Driven wordpress-theme-acf
- [x] Backup static HTML
- [x] Theme scaffold + ACF/CPT/templates
- [x] Commit + push

## Deferred

- Seed automático de CPTs via WP-CLI
- ACF Pro Options Page (se licença for adquirida)
- Design system ~R$50k

## Preferences

- Commits atômicos; copy PT premium B2B.
