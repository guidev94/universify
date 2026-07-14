# Tech Stack

**Analyzed:** 2026-07-14

## Core

- Framework: Static site (no SPA framework)
- Language: HTML5, CSS3, JavaScript (ES5/ES2015+ in classic scripts)
- Runtime: Browser + Node 20+ (tests)
- Package manager: npm (`package.json` private)

## Frontend

- UI: Semantic HTML + Tailwind CDN (`cdn.tailwindcss.com`)
- Styling: `assets/css/main.css` (custom) + Tailwind utility classes in HTML
- Theme tokens: `primary` `#0a0a0a`, `todoz` `#CFFF04`
- Motion: GSAP 3.12.5 + ScrollTrigger, AOS 2.3.1, Lenis 1.1.20
- Icons: Phosphor Icons (`@phosphor-icons/web`)

## Testing

- Unit: Node.js built-in test runner (`node --test`)
- E2E: Playwright `@playwright/test` ^1.49
- Commands: `npm run test:unit`, `npm run test:e2e`, `npm test`

## External Services

- Hosting: Vercel (static)
- CDN assets: wvtodoz.com.br WordPress uploads (images)
- Contact: WhatsApp `wa.me/551155311779`

## Development Tools

- Local serve: `npx serve`
- GitHub: `guidev94/wvtodoz-v2`
