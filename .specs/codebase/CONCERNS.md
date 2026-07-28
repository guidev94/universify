# Concerns

**Analyzed:** 2026-07-14

## Fragile areas

| Area | Risk | Mitigation |
| --- | --- | --- |
| Portfolio GSAP pin | Breaks if rail/track IDs or overflow change | Touch CSS carefully; keep `#portfolio-rail` / `#portfolio-horizontal` |
| Section glow `::after` | Hard cuts if `overflow` clipped | Keep `overflow: visible` on canvases |
| Classic script boot | ES modules break GSAP globals | Do not switch index to `type=module` casually |
| Tailwind CDN | FOUC / version drift | Accept for now; Milestone B may self-host |
| Remote images | Hotlink failures | Prefer existing CDN URLs already in use |

## Tech debt

- Inter as default font (generic) — deferred to ~R$50k
- Duplicate helpers `site-utils.js` vs `.mjs`
- Blog still labels category "Marketing" (taxonomy, not section)
- Footer copy typo "technology" (fix in polish task)

## Security

- No secrets in repo expected; contact is public WhatsApp
- External script CDNs — supply-chain trust on CDNs

## Performance

- Hero video must stay optimized under `assets/video/`
- Multiple CDN scripts — Milestone B performance budget
