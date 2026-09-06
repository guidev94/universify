# Spec: 100% Faithful PVZ Fusion

## Requirements

### R1: The Menu
- **ID:** UI-MENU-001
- **Description:** The main menu must be a 1:1 recreation of the wooden panel shown in the user's image.
- **Details:** No standard browser scrollbars or HTML buttons. Custom cursors. Wood textures.

### R2: The Seed Tray (HUD)
- **ID:** UI-HUD-001
- **Description:** The top seed bank must be rendered within the Canvas.
- **Details:** Slot highlight, recharge shadow animation, sun cost display, and "not enough sun" darkening.

### R3: Animations (The "Skill")
- **ID:** MEC-ANIM-001
- **Description:** Plants must have idle "breathing" animations. Zombies must have multi-frame walking/eating animations.
- **Details:** Use spritesheets or `PIXI.AnimatedSprite`.

### R4: Fusion Logic
- **ID:** MEC-FUSION-001
- **Description:** Planting on top of another plant triggers fusion.
- **Details:** Must show a "Fusion Preview" glow when hovering over a compatible plant.

### R5: Scoring and Progress
- **ID:** MEC-PROGRESS-001
- **Description:** Wave progress bar at bottom right. Sun counter at top left.
- **Details:** Sun points must be clickable and move toward the counter with a curve (Bezier).
