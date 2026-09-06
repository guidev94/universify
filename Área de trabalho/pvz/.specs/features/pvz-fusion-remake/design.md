# Design: Modular Game Architecture

## 1. Component Model
Instead of a monolithic `game.js`, we will use:
- `Entity`: Base class for position and sprite management.
- `Plant`: Extends Entity. Handling HP, shooting, sun production.
- `Zombie`: Extends Entity. Handling AI, movement, eating.
- `GridManager`: Manages the 9x5 tiles and collision.
- `GameUI`: Manages the Seed Tray and Sun HUD in PixiJS.

## 2. Rendering Strategy
- Use `PIXI.Container` for layers: `Background` -> `Plants` -> `Zombies` -> `Projectiles` -> `UI`.
- Implement `TexturePool` to load original assets efficiently.

## 3. Input Handling
- Implement a `MouseManager` to track "held" seeds and highlight grid cells.
- Drag-and-drop feedback (seed follows mouse).
