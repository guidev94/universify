# Testing

**Analyzed:** 2026-07-14

## Commands

| Gate | Command | When |
| --- | --- | --- |
| Unit (quick) | `npm run test:unit` | After utils/constants changes |
| E2E (full) | `npm run test:e2e` | After HTML structure / modal / hero |
| All | `npm test` | Pre-commit / pre-push |

## Coverage matrix

| Layer | Unit | E2E | Notes |
| --- | --- | --- | --- |
| `site-utils.mjs` helpers | Required | — | WhatsApp, case data, constants |
| Section IDs / nav hashes | Via constants | Required | `REQUIRED_SECTION_IDS`, `NAV_HASHES` |
| Contact / case modals | — | Required | open/close, no ghost overlay |
| Hero logos row | Pure fn + | Required | single flex row |
| Portfolio cards | — | Count assert | horizontal rail |

## Parallelism

- Unit: Parallel-Safe Yes
- E2E: Parallel-Safe No (shared browser / port) — do not mark e2e tasks `[P]` across workers carelessly

## Gaps

- No visual regression snapshots yet (Milestone B)
- No a11y automated suite yet
