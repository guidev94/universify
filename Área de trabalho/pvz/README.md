# Plants vs. Zombies — Web Remake

Project Duration: Dec 2023 – Jan 2024

A browser-based remake of the classic *Plants vs. Zombies* game, implemented using HTML, CSS, and vanilla JavaScript.  
The game reproduces core mechanics such as planting defensive units, generating sun, firing bullets, and fending off waves of zombies.

This project demonstrates frontend game development techniques including sprite management, animation loops, collision detection, state updates, and DOM-based rendering.

##  Live Demo
Simply open **index.html** in any modern browser to play.

##  Game Features

### **1. Selectable Plant Units**
Players can choose from a set of plants, including:
- **Peashooter**
- **Gatling Pea**
- **Tall-nut**
- **Cherry Bomb**
- **Twin Sunflower**
- **Sunflower**

Each plant has unique stats (health, damage, attack speed, or special effects).

### **2. Enemy Types**
The game includes multiple zombie types such as:
- **Basic Zombie**
- **Conehead Zombie**
- **Buckethead Zombie**
- **Flag Zombie**

Zombies gradually spawn from the right and march toward the player’s base.

### **3. Game Mechanics**
- **Sun collection system**  
- **Drag-and-drop plant placement**  
- **Bullets & projectile animation**  
- **Area-damage explosion effects**  
- **Zombie attack & plant health**  
- **Collision detection**  
- **Wave-based enemy spawning**  
- **Win & Lose conditions**

##  Technical Overview

### **Technologies**
- **HTML5** — game board, UI layout  
- **CSS3** — styling, visual positioning  
- **JavaScript (ES6)** — all game logic  
- **Canvas-free implementation** using DOM elements

### **Main Components**
| Component | Description |
|----------|-------------|
| **Plant Classes** | Handles health, firing interval, sprite rendering |
| **Zombie Classes** | Movement logic, attack behavior, animations |
| **Bullet System** | Projectile creation, movement, hit detection |
| **Sun Generator** | Sun production, falling animation, collection |
| **Grid System** | Determines valid plant placement |
| **Game Loop** | Timed updates (movement, spawn, collision checks) |


##  Gameplay Preview
<img width="1050" height="513" alt="image" src="https://github.com/user-attachments/assets/4ea3ad59-9f28-4942-9a8c-2b434fe0ba1f" />

##  How to Run

1. Clone the repository:
```bash
git clone https://github.com/your-username/plants-vs-zombies-web.git
```

2.Open the project folder.

3.Double-click index.html to launch the game in your browser.

No build tools or servers are required.
   

