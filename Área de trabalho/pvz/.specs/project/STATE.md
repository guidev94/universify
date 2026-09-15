# State

## Decisions
- **Fusion Logic:** Decided to use CSS `filter` (tints) for hybrid plants instead of missing external assets to maintain visual consistency and performance.
- **HUD Style:** Adopted a "Glassmorphism" aesthetic with high-contrast typography (Inter 900) for a premium SaaS-like feel.
- **Projectiles:** Implemented `projectile` type in `state.plants` to handle slowing effects and custom bullet styles.

## Blockers
- **Missing Assets:** Several classic plants (Spikeweed, Torchwood, etc.) are missing local assets. Fusions now focus on variations of available high-quality sprites.

## Todos
- [ ] Implement Phase 3 (VFX, Save System, etc.)
- [ ] Add more sound feedback for fusion events

## Lessons
- DOM-based rendering is highly performant for this scale of game, but CSS `filter` can be expensive on mobile if overused.
