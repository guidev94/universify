# Codebase Mapping

## Stack
- Frontend: HTML5, Tailwind CSS v3, Vanilla JavaScript (ES6+), GSAP (Animations).
- Backend: PHP 8.3 (Proxy), JSON-based cache.
- Integration: Stricker Europe REST API.

## Structure
- `web/index.html`: Main landing page with featured items and categories.
- `web/catalogo.html`: Full catalog with sidebar filters.
- `web/produto.html`: Single product detail page with gallery.
- `web/api/proxy.php`: Data orchestrator & file cache server.
- `web/api/cache_data/`: Directory containing +1200 product JSON files.

## Conventions
- UI: Premium, minimal, heavy use of border-radius (2.5rem - 3rem).
- Data: Keys normalized to lowercase in frontend; proxy handles casing.
- Communication: Quote request via WhatsApp URL encoding.
