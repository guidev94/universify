# Structure

**Analyzed:** 2026-07-14

```
wv-v2/
├── index.html              # Homepage
├── blog-post.html          # Sample insight page
├── package.json            # Scripts + Playwright
├── playwright.config.js
├── assets/
│   ├── css/main.css        # Brand + layout + responsive
│   ├── js/
│   │   ├── site-utils.js   # Browser helpers
│   │   ├── site-utils.mjs  # Testable exports
│   │   ├── main.js
│   │   ├── animations.js   # GSAP / Lenis / AOS / portfolio
│   │   ├── components.js   # Modals, menu, forms
│   │   └── app.js          # Optional module boot (unused by index)
│   ├── img/produtos/       # Product logos
│   └── video/hero.mp4      # Optimized hero
├── tests/
│   ├── unit/site-utils.test.js
│   └── e2e/homepage.spec.js
└── .specs/                 # Spec-Driven artifacts
```

## Section IDs (homepage)

`hero`, `clientes`, `sobre`, `missao`, `servicos`, `portfolio`, `produtos`, ~~`marketing`~~ → `aplicacoes`, `blog`, `contato`
