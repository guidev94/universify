# Codebase Mapping

## Stack
- **Engine:** PixiJS (v7)
- **Animation:** GSAP
- **UI:** Mixed (Tailwind DOM + PixiJS Text) -> **CONCERN:** Must move 100% to PixiJS for game feel.

## Structure
- `index.html`: Contains UI overlays. Needs to be minimized.
- `game.js`: Monolithic logic. Needs modularization (Plant class, Zombie class, GameEngine class).

## Concerns
- **Performance:** High-density waves might lag with current `gsap` on every single frame. Need pooling.
- **Visuals:** Current emojis/images are static. Need frame-based sprites.
- **Input:** Grid clicking is simplified. Needs "Drag and Drop" feel like original.
