import { mulberry32 } from '../core/rng.js';

export const B = {
  AIR: 0,
  GRASS: 1,
  DIRT: 2,
  STONE: 3,
  COBBLE: 4,
  SAND: 5,
  CORAL_STONE: 6,
  SNOW: 7,
  ICE: 8,
  LOG: 9,
  LEAVES: 10,
  RAINBOW_LOG: 11,
  RAINBOW_LEAVES: 12,
  PLANKS: 13,
  CRYSTAL: 14,
  LUMI_ORE: 15,
  AURORA_ORE: 16,
  WATER: 17,
  FLOWER_PINK: 18,
  FLOWER_BLUE: 19,
  TALL_GRASS: 20,
  MUSHROOM_STAR: 21,
  CHEST: 22,
  LUMI_LAMP: 23,
  PRISM: 24,
  CLOUD: 25,
  BEDROCK: 26,
  CORAL: 27,
  BERRY_BUSH: 28,
  PINK_PLANKS: 29,
  MINT_PLANKS: 30,
  LAVENDER_PLANKS: 31
};

const TILE_NAMES = [
  'grass_top', 'grass_side', 'dirt', 'stone', 'cobble', 'sand', 'coral_stone', 'snow',
  'ice', 'log_top', 'log_side', 'leaves', 'rainbow_log_top', 'rainbow_log_side', 'rainbow_leaves', 'planks',
  'crystal', 'lumi_ore', 'aurora_ore', 'water', 'flower_pink', 'flower_blue', 'tall_grass', 'mushroom_star',
  'chest_top', 'chest_side', 'lumi_lamp', 'prism', 'cloud', 'bedrock', 'coral', 'berry_bush',
  'pink_planks', 'mint_planks', 'lavender_planks'
];

export const TILE = Object.fromEntries(TILE_NAMES.map((name, i) => [name, i]));
export const ATLAS_TILES = 8;
export const TILE_SIZE = 16;
export const ATLAS_SIZE = ATLAS_TILES * TILE_SIZE;

let atlasCanvas = null;
const blockIconCache = new Map();

function fill(ctx, color) {
  ctx.fillStyle = color;
  ctx.fillRect(0, 0, TILE_SIZE, TILE_SIZE);
}

function speckle(ctx, rand, colors, count, size = 1) {
  for (let i = 0; i < count; i++) {
    ctx.fillStyle = colors[Math.floor(rand() * colors.length)];
    const s = rand() < 0.18 ? size + 1 : size;
    ctx.fillRect(Math.floor(rand() * (TILE_SIZE - s + 1)), Math.floor(rand() * (TILE_SIZE - s + 1)), s, s);
  }
}

function stoneBase(ctx, rand) {
  fill(ctx, '#8d99a4');
  speckle(ctx, rand, ['#9aa7b2', '#7e8a95', '#a6b2bc', '#74808b'], 52);
}

function veins(ctx, rand, colors) {
  for (let v = 0; v < 4; v++) {
    let x = Math.floor(rand() * 14) + 1;
    let y = Math.floor(rand() * 14) + 1;
    for (let i = 0; i < 5; i++) {
      ctx.fillStyle = colors[Math.floor(rand() * colors.length)];
      ctx.fillRect(x, y, rand() < 0.3 ? 2 : 1, rand() < 0.3 ? 2 : 1);
      x += Math.floor(rand() * 3) - 1;
      y += Math.floor(rand() * 3) - 1;
    }
  }
}

function drawTile(ctx, index, rand) {
  switch (TILE_NAMES[index]) {
    case 'grass_top':
      fill(ctx, '#6fce4f');
      speckle(ctx, rand, ['#7fdd5a', '#61bd43', '#8ae86a', '#58b23e', '#93ef74'], 64);
      break;
    case 'grass_side': {
      fill(ctx, '#8b5a3c');
      speckle(ctx, rand, ['#966344', '#7a4c31', '#a06b49'], 36);
      for (let x = 0; x < TILE_SIZE; x++) {
        const h = 3 + (rand() < 0.45 ? 1 : 0) + (rand() < 0.15 ? 1 : 0);
        ctx.fillStyle = rand() < 0.3 ? '#61bd43' : '#6fce4f';
        ctx.fillRect(x, 0, 1, h);
        ctx.fillStyle = '#4f9e38';
        ctx.fillRect(x, h, 1, 1);
      }
      break;
    }
    case 'dirt':
      fill(ctx, '#8b5a3c');
      speckle(ctx, rand, ['#966344', '#7a4c31', '#a06b49', '#6f4429'], 64);
      break;
    case 'stone':
      stoneBase(ctx, rand);
      break;
    case 'cobble':
      fill(ctx, '#6f7b87');
      for (let i = 0; i < 9; i++) {
        const w = 3 + Math.floor(rand() * 3);
        const h = 3 + Math.floor(rand() * 3);
        const x = Math.floor(rand() * (TILE_SIZE - w));
        const y = Math.floor(rand() * (TILE_SIZE - h));
        ctx.fillStyle = rand() < 0.5 ? '#8794a0' : '#7c8995';
        ctx.fillRect(x, y, w, h);
        ctx.strokeStyle = '#59646f';
        ctx.strokeRect(x + 0.5, y + 0.5, w - 1, h - 1);
      }
      break;
    case 'sand':
      fill(ctx, '#f0cf8a');
      speckle(ctx, rand, ['#f7dda4', '#e3bd73', '#ffe7b5', '#d9b26a'], 58);
      break;
    case 'coral_stone':
      fill(ctx, '#e58b9e');
      speckle(ctx, rand, ['#f2a7b6', '#d37488', '#f7bcc7', '#c7667b'], 56);
      break;
    case 'snow':
      fill(ctx, '#f6fbff');
      speckle(ctx, rand, ['#dceefb', '#eaf6ff', '#ffffff', '#cfe6f7'], 42);
      break;
    case 'ice':
      fill(ctx, '#b8e7ff');
      speckle(ctx, rand, ['#d8f3ff', '#8fd4f5', '#e8faff', '#a3defa'], 36);
      ctx.fillStyle = '#e8faff';
      ctx.fillRect(2, 3, 1, 5);
      ctx.fillRect(9, 8, 1, 6);
      ctx.fillRect(12, 2, 1, 4);
      break;
    case 'log_top':
      fill(ctx, '#a97745');
      for (let r = 6; r > 0; r -= 2) {
        ctx.strokeStyle = r % 4 === 0 ? '#7a4e2a' : '#8f6136';
        ctx.strokeRect(8 - r + 0.5, 8 - r + 0.5, r * 2 - 1, r * 2 - 1);
      }
      ctx.fillStyle = '#6b4223';
      ctx.fillRect(7, 7, 2, 2);
      break;
    case 'log_side':
      fill(ctx, '#9b6a3f');
      for (let x = 1; x < TILE_SIZE; x += 4) {
        ctx.fillStyle = '#7d5230';
        ctx.fillRect(x, 0, 1, TILE_SIZE);
      }
      speckle(ctx, rand, ['#a97745', '#8a5c36', '#b5824f'], 26);
      ctx.fillStyle = '#6b4223';
      ctx.fillRect(11, 5, 2, 3);
      break;
    case 'leaves':
      fill(ctx, '#3f9e4d');
      speckle(ctx, rand, ['#4fb45e', '#2f7a3b', '#5cc76c', '#286a33', '#69d47a'], 68);
      break;
    case 'rainbow_log_top': {
      fill(ctx, '#ffd7a8');
      const colors = ['#ff8a6b', '#ffc94d', '#b8ff6a', '#7cd6ff', '#b07cff'];
      let c = 0;
      for (let r = 7; r > 0; r -= 2) {
        ctx.strokeStyle = colors[c++ % colors.length];
        ctx.strokeRect(8 - r + 0.5, 8 - r + 0.5, r * 2 - 1, r * 2 - 1);
      }
      break;
    }
    case 'rainbow_log_side': {
      const colors = ['#ff8a6b', '#ffc94d', '#b8ff6a', '#7cd6ff', '#b07cff'];
      for (let x = 0; x < TILE_SIZE; x += 2) {
        ctx.fillStyle = colors[(x / 2) % colors.length];
        ctx.fillRect(x, 0, 2, TILE_SIZE);
      }
      ctx.fillStyle = 'rgba(70,40,70,0.35)';
      for (let x = 1; x < TILE_SIZE; x += 4) ctx.fillRect(x, 0, 1, TILE_SIZE);
      break;
    }
    case 'rainbow_leaves':
      fill(ctx, '#3d7a6a');
      speckle(ctx, rand, ['#ffb3c7', '#ffe28a', '#b8ff9e', '#a8d8ff', '#d4b3ff', '#4f9e8a'], 70);
      break;
    case 'planks':
      fill(ctx, '#c98f52');
      speckle(ctx, rand, ['#d69c5d', '#b97f44', '#e0a868'], 34);
      ctx.fillStyle = '#9c6a38';
      for (let y = 4; y < TILE_SIZE; y += 5) ctx.fillRect(0, y, TILE_SIZE, 1);
      ctx.fillRect(5, 0, 1, 4);
      ctx.fillRect(11, 5, 1, 4);
      ctx.fillRect(3, 10, 1, 4);
      break;
    case 'crystal': {
      fill(ctx, '#4fc9dd');
      ctx.fillStyle = '#8ff0ff';
      ctx.beginPath();
      ctx.moveTo(8, 1);
      ctx.lineTo(14, 8);
      ctx.lineTo(8, 15);
      ctx.lineTo(2, 8);
      ctx.closePath();
      ctx.fill();
      ctx.fillStyle = '#d4fbff';
      ctx.beginPath();
      ctx.moveTo(8, 1);
      ctx.lineTo(11, 8);
      ctx.lineTo(8, 15);
      ctx.closePath();
      ctx.fill();
      ctx.fillStyle = '#2ba8c4';
      ctx.fillRect(4, 6, 2, 1);
      ctx.fillRect(10, 10, 2, 1);
      break;
    }
    case 'lumi_ore':
      stoneBase(ctx, rand);
      veins(ctx, rand, ['#ffd76a', '#ffb347', '#ffe9a3']);
      break;
    case 'aurora_ore':
      stoneBase(ctx, rand);
      veins(ctx, rand, ['#b07cff', '#7cd6ff', '#e0b3ff']);
      break;
    case 'water':
      fill(ctx, '#4fb7e8');
      for (let y = 2; y < TILE_SIZE; y += 4) {
        ctx.fillStyle = y % 8 === 2 ? '#7cd6ff' : '#3aa0d6';
        ctx.fillRect(0, y, TILE_SIZE, 1);
      }
      speckle(ctx, rand, ['#a8e8ff', '#3aa0d6'], 14);
      break;
    case 'flower_pink':
      ctx.fillStyle = '#3f9e4d';
      ctx.fillRect(7, 7, 2, 9);
      ctx.fillRect(5, 11, 2, 1);
      ctx.fillStyle = '#ff9ec7';
      ctx.fillRect(5, 3, 2, 2);
      ctx.fillRect(9, 3, 2, 2);
      ctx.fillRect(4, 5, 3, 2);
      ctx.fillRect(9, 5, 3, 2);
      ctx.fillRect(6, 7, 4, 2);
      ctx.fillStyle = '#ffd76a';
      ctx.fillRect(7, 5, 2, 2);
      break;
    case 'flower_blue':
      ctx.fillStyle = '#3f9e4d';
      ctx.fillRect(7, 7, 2, 9);
      ctx.fillRect(9, 11, 2, 1);
      ctx.fillStyle = '#7cc8ff';
      ctx.fillRect(5, 3, 2, 2);
      ctx.fillRect(9, 3, 2, 2);
      ctx.fillRect(4, 5, 3, 2);
      ctx.fillRect(9, 5, 3, 2);
      ctx.fillRect(6, 7, 4, 2);
      ctx.fillStyle = '#fff1a8';
      ctx.fillRect(7, 5, 2, 2);
      break;
    case 'tall_grass':
      ctx.fillStyle = '#4fae4a';
      for (let i = 0; i < 6; i++) {
        const x = 2 + i * 2;
        const top = 3 + Math.floor(rand() * 6);
        ctx.fillRect(x, top, 1, TILE_SIZE - top);
        if (rand() < 0.5) ctx.fillRect(x + 1, top + 2, 1, 1);
      }
      ctx.fillStyle = '#79d46a';
      ctx.fillRect(6, 6, 1, 9);
      ctx.fillRect(9, 4, 1, 11);
      break;
    case 'mushroom_star':
      ctx.fillStyle = '#f2e6c9';
      ctx.fillRect(7, 9, 2, 7);
      ctx.fillStyle = '#8a5fb8';
      ctx.fillRect(5, 6, 6, 3);
      ctx.fillRect(4, 7, 8, 2);
      ctx.fillRect(6, 4, 4, 2);
      ctx.fillStyle = '#b07cff';
      ctx.fillRect(5, 5, 6, 2);
      ctx.fillStyle = '#ffc94d';
      ctx.fillRect(5, 6, 1, 1);
      ctx.fillRect(9, 5, 1, 1);
      ctx.fillRect(7, 4, 1, 1);
      ctx.fillStyle = '#ffe9a3';
      ctx.fillRect(11, 2, 1, 1);
      ctx.fillRect(2, 4, 1, 1);
      break;
    case 'chest_top':
      fill(ctx, '#b07a43');
      speckle(ctx, rand, ['#c08a4f', '#9c6836'], 22);
      ctx.strokeStyle = '#6d4523';
      ctx.strokeRect(0.5, 0.5, 15, 15);
      ctx.fillStyle = '#d9c27a';
      ctx.fillRect(2, 2, 1, 12);
      ctx.fillRect(13, 2, 1, 12);
      ctx.fillRect(6, 6, 4, 4);
      ctx.fillStyle = '#f4e4a6';
      ctx.fillRect(7, 7, 2, 2);
      break;
    case 'chest_side':
      fill(ctx, '#a9743f');
      speckle(ctx, rand, ['#b8834b', '#966232'], 20);
      ctx.strokeStyle = '#6d4523';
      ctx.strokeRect(0.5, 0.5, 15, 15);
      ctx.fillStyle = '#d9c27a';
      ctx.fillRect(2, 1, 1, 14);
      ctx.fillRect(13, 1, 1, 14);
      ctx.fillRect(1, 6, 14, 1);
      ctx.fillStyle = '#6d4523';
      ctx.fillRect(6, 6, 4, 4);
      ctx.fillStyle = '#ffd76a';
      ctx.fillRect(7, 7, 2, 2);
      break;
    case 'lumi_lamp':
      fill(ctx, '#ffd76a');
      ctx.fillStyle = '#ffe9a3';
      ctx.fillRect(2, 2, 12, 12);
      ctx.fillStyle = '#fffbe0';
      ctx.fillRect(5, 5, 6, 6);
      ctx.fillStyle = '#e0a93a';
      ctx.fillRect(0, 0, 16, 1);
      ctx.fillRect(0, 15, 16, 1);
      ctx.fillRect(0, 0, 1, 16);
      ctx.fillRect(15, 0, 1, 16);
      ctx.fillStyle = '#ffb347';
      ctx.fillRect(7, 1, 2, 1);
      ctx.fillRect(7, 14, 2, 1);
      break;
    case 'prism': {
      const colors = ['#ff8a6b', '#ffc94d', '#b8ff6a', '#7cd6ff', '#b07cff', '#ff7ad9'];
      for (let y = 0; y < TILE_SIZE; y++) {
        for (let x = 0; x < TILE_SIZE; x++) {
          ctx.fillStyle = colors[(x + y) % colors.length];
          ctx.fillRect(x, y, 1, 1);
        }
      }
      ctx.fillStyle = 'rgba(255,255,255,0.55)';
      for (let i = 0; i < 5; i++) ctx.fillRect(3 + i * 2, 2 + i, 2, 1);
      break;
    }
    case 'cloud':
      fill(ctx, '#f4fbff');
      speckle(ctx, rand, ['#dceefb', '#ffffff', '#eaf6ff'], 34);
      ctx.fillStyle = '#dceefb';
      ctx.fillRect(0, 13, 16, 3);
      ctx.fillStyle = '#ffffff';
      ctx.fillRect(2, 3, 5, 2);
      ctx.fillRect(9, 6, 4, 2);
      break;
    case 'bedrock':
      fill(ctx, '#3a3f46');
      speckle(ctx, rand, ['#2a2e33', '#4b5159', '#1f2328'], 48);
      ctx.fillStyle = '#1f2328';
      ctx.fillRect(2, 3, 4, 1);
      ctx.fillRect(10, 9, 3, 1);
      ctx.fillRect(6, 12, 2, 1);
      break;
    case 'coral':
      ctx.fillStyle = '#ff8a6b';
      ctx.fillRect(5, 6, 2, 10);
      ctx.fillRect(9, 4, 2, 12);
      ctx.fillRect(12, 8, 2, 8);
      ctx.fillRect(3, 9, 2, 7);
      ctx.fillStyle = '#ffb3a0';
      ctx.fillRect(5, 6, 1, 3);
      ctx.fillRect(9, 4, 1, 3);
      ctx.fillRect(12, 8, 1, 2);
      ctx.fillStyle = '#ff6b8a';
      ctx.fillRect(7, 10, 2, 1);
      ctx.fillRect(10, 12, 2, 1);
      break;
    case 'berry_bush':
      ctx.fillStyle = '#3f9e4d';
      ctx.fillRect(3, 8, 10, 8);
      ctx.fillRect(4, 6, 8, 3);
      ctx.fillRect(6, 4, 4, 3);
      ctx.fillStyle = '#4fb45e';
      ctx.fillRect(5, 7, 2, 2);
      ctx.fillRect(9, 9, 2, 2);
      ctx.fillStyle = '#ffd76a';
      ctx.fillRect(4, 10, 2, 2);
      ctx.fillRect(9, 7, 2, 2);
      ctx.fillRect(7, 12, 2, 2);
      ctx.fillStyle = '#ffe9a3';
      ctx.fillRect(4, 10, 1, 1);
      ctx.fillRect(9, 7, 1, 1);
      break;
    case 'pink_planks':
      fill(ctx, '#f4a9c4');
      for (let y = 0; y < TILE_SIZE; y += 4) {
        ctx.fillStyle = y % 8 === 0 ? '#e98fb2' : '#f7b8cf';
        ctx.fillRect(0, y, TILE_SIZE, 1);
      }
      speckle(ctx, rand, ['#ffc2d8', '#dc7ea2', '#ffd6e5'], 24);
      ctx.fillStyle = '#b95f82';
      ctx.fillRect(3, 2, 1, 2);
      ctx.fillRect(11, 6, 1, 2);
      ctx.fillRect(7, 10, 1, 2);
      break;
    case 'mint_planks':
      fill(ctx, '#9ee6c1');
      for (let y = 0; y < TILE_SIZE; y += 4) {
        ctx.fillStyle = y % 8 === 0 ? '#7fd2ab' : '#b2f0d0';
        ctx.fillRect(0, y, TILE_SIZE, 1);
      }
      speckle(ctx, rand, ['#c6f7df', '#6bbf98', '#d9fff0'], 24);
      ctx.fillStyle = '#4b9a77';
      ctx.fillRect(3, 2, 1, 2);
      ctx.fillRect(11, 6, 1, 2);
      ctx.fillRect(7, 10, 1, 2);
      break;
    case 'lavender_planks':
      fill(ctx, '#c4b2f0');
      for (let y = 0; y < TILE_SIZE; y += 4) {
        ctx.fillStyle = y % 8 === 0 ? '#ad97df' : '#d4c5fa';
        ctx.fillRect(0, y, TILE_SIZE, 1);
      }
      speckle(ctx, rand, ['#e2d7ff', '#9a80d1', '#eee8ff'], 24);
      ctx.fillStyle = '#7d63b4';
      ctx.fillRect(3, 2, 1, 2);
      ctx.fillRect(11, 6, 1, 2);
      ctx.fillRect(7, 10, 1, 2);
      break;
  }
}

export function getAtlasCanvas() {
  if (atlasCanvas) return atlasCanvas;
  const canvas = document.createElement('canvas');
  canvas.width = ATLAS_SIZE;
  canvas.height = ATLAS_SIZE;
  const ctx = canvas.getContext('2d');
  ctx.imageSmoothingEnabled = false;
  TILE_NAMES.forEach((name, index) => {
    const rand = mulberry32(9173 + index * 7919);
    const ox = (index % ATLAS_TILES) * TILE_SIZE;
    const oy = Math.floor(index / ATLAS_TILES) * TILE_SIZE;
    ctx.save();
    ctx.translate(ox, oy);
    ctx.beginPath();
    ctx.rect(0, 0, TILE_SIZE, TILE_SIZE);
    ctx.clip();
    drawTile(ctx, index, rand);
    ctx.restore();
  });
  atlasCanvas = canvas;
  return canvas;
}

export function getAtlasTexture(THREE) {
  const texture = new THREE.CanvasTexture(getAtlasCanvas());
  texture.magFilter = THREE.NearestFilter;
  texture.minFilter = THREE.NearestFilter;
  texture.generateMipmaps = false;
  texture.colorSpace = THREE.SRGBColorSpace;
  return texture;
}

export function getBlockIcon(blockId) {
  if (blockIconCache.has(blockId)) return blockIconCache.get(blockId);
  const block = BLOCKS[blockId];
  if (!block) return '';
  const tileName = typeof block.tiles === 'string' ? block.tiles : (block.tiles.side || block.tiles.top || block.tiles.bottom);
  const tile = TILE[tileName];
  const source = getAtlasCanvas();
  const canvas = document.createElement('canvas');
  canvas.width = 32;
  canvas.height = 32;
  const ctx = canvas.getContext('2d');
  ctx.imageSmoothingEnabled = false;
  const sx = (tile % ATLAS_TILES) * TILE_SIZE;
  const sy = Math.floor(tile / ATLAS_TILES) * TILE_SIZE;
  ctx.drawImage(source, sx, sy, TILE_SIZE, TILE_SIZE, 0, 0, 32, 32);
  const url = canvas.toDataURL();
  blockIconCache.set(blockId, url);
  return url;
}

function def(id, name, props) {
  BLOCKS[id] = {
    id,
    name,
    solid: true,
    opaque: true,
    plant: false,
    liquid: false,
    glow: false,
    hardness: 1,
    rarity: 'comum',
    category: 'bloco',
    color: '#9aa7a0',
    drop: { id, min: 1, max: 1 },
    ...props
  };
}

export const BLOCKS = [];

def(B.AIR, 'Ar', {
  solid: false,
  opaque: false,
  category: 'ar',
  drop: null,
  color: '#000000'
});

def(B.GRASS, 'Grama Musgo', {
  tiles: { top: 'grass_top', side: 'grass_side', bottom: 'dirt' },
  color: '#6fce4f',
  drop: { id: B.GRASS }
});

def(B.DIRT, 'Terra Fofa', {
  tiles: 'dirt',
  color: '#8b5a3c',
  drop: { id: B.DIRT }
});

def(B.STONE, 'Pedra Sereno', {
  tiles: 'stone',
  color: '#8d99a4',
  hardness: 1.4,
  drop: { id: B.STONE }
});

def(B.COBBLE, 'Pedra Britada', {
  tiles: 'cobble',
  color: '#7b8794',
  hardness: 1.3,
  drop: { id: B.COBBLE }
});

def(B.SAND, 'Areia Coral', {
  tiles: 'sand',
  color: '#f0cf8a',
  drop: { id: B.SAND }
});

def(B.CORAL_STONE, 'Pedra Coral', {
  tiles: 'coral_stone',
  color: '#e58b9e',
  hardness: 1.2,
  rarity: 'incomum',
  drop: { id: B.CORAL_STONE }
});

def(B.SNOW, 'Neve Lumina', {
  tiles: 'snow',
  color: '#f6fbff',
  drop: { id: B.SNOW }
});

def(B.ICE, 'Gelo Azul', {
  tiles: 'ice',
  color: '#b8e7ff',
  hardness: 1.1,
  rarity: 'incomum',
  drop: { id: B.ICE }
});

def(B.LOG, 'Tronco de Mel', {
  tiles: { top: 'log_top', side: 'log_side', bottom: 'log_top' },
  color: '#9b6a3f',
  drop: { id: B.LOG }
});

def(B.LEAVES, 'Folhas de Mel', {
  tiles: 'leaves',
  color: '#3f9e4d',
  drop: { id: B.LEAVES }
});

def(B.RAINBOW_LOG, 'Tronco Arco-Íris', {
  tiles: { top: 'rainbow_log_top', side: 'rainbow_log_side', bottom: 'rainbow_log_top' },
  color: '#ffb3c7',
  rarity: 'raro',
  drop: { id: B.RAINBOW_LOG }
});

def(B.RAINBOW_LEAVES, 'Folhas Prisma', {
  tiles: 'rainbow_leaves',
  color: '#b8ff9e',
  rarity: 'raro',
  drop: { id: B.RAINBOW_LEAVES }
});

def(B.PLANKS, 'Tábua de Mel', {
  tiles: 'planks',
  color: '#c98f52',
  drop: { id: B.PLANKS }
});

def(B.CRYSTAL, 'Cristal de Lumi', {
  tiles: 'crystal',
  color: '#4fc9dd',
  glow: true,
  hardness: 1.6,
  rarity: 'incomum',
  drop: { id: B.CRYSTAL }
});

def(B.LUMI_ORE, 'Minério de Lumi', {
  tiles: 'lumi_ore',
  color: '#ffd76a',
  hardness: 2,
  rarity: 'raro',
  drop: { id: 'lumi_shard', min: 1, max: 3, extra: { id: 'aurora_gem', chance: 0.1 } }
});

def(B.AURORA_ORE, 'Minério Aurora', {
  tiles: 'aurora_ore',
  color: '#b07cff',
  hardness: 2.4,
  rarity: 'epico',
  drop: { id: 'aurora_gem', min: 1, max: 2, extra: { id: 'cloud_feather', chance: 0.06 } }
});

def(B.WATER, 'Água de Nuvem', {
  tiles: 'water',
  solid: false,
  opaque: false,
  liquid: true,
  category: 'liquido',
  drop: null,
  color: '#4fb7e8'
});

def(B.FLOWER_PINK, 'Flor Rosa', {
  tiles: 'flower_pink',
  solid: false,
  opaque: false,
  plant: true,
  category: 'planta',
  color: '#ff9ec7',
  drop: { id: B.FLOWER_PINK }
});

def(B.FLOWER_BLUE, 'Flor Azul', {
  tiles: 'flower_blue',
  solid: false,
  opaque: false,
  plant: true,
  category: 'planta',
  color: '#7cc8ff',
  drop: { id: B.FLOWER_BLUE }
});

def(B.TALL_GRASS, 'Capim Lumi', {
  tiles: 'tall_grass',
  solid: false,
  opaque: false,
  plant: true,
  category: 'planta',
  color: '#79d46a',
  drop: null
});

def(B.MUSHROOM_STAR, 'Cogumelo Estelar', {
  tiles: 'mushroom_star',
  solid: false,
  opaque: false,
  plant: true,
  category: 'planta',
  color: '#b07cff',
  rarity: 'raro',
  drop: { id: 'star_mushroom' }
});

def(B.CHEST, 'Baú de Lumi', {
  tiles: { top: 'chest_top', side: 'chest_side', bottom: 'chest_top' },
  color: '#d9c27a',
  drop: { id: B.CHEST }
});

def(B.LUMI_LAMP, 'Lâmpada de Lumi', {
  tiles: 'lumi_lamp',
  color: '#ffd76a',
  glow: true,
  rarity: 'incomum',
  drop: { id: B.LUMI_LAMP }
});

def(B.PRISM, 'Bloco Prisma', {
  tiles: 'prism',
  color: '#ff7ad9',
  glow: true,
  rarity: 'lendario',
  drop: { id: B.PRISM }
});

def(B.CLOUD, 'Bloco de Nuvem', {
  tiles: 'cloud',
  color: '#f4fbff',
  rarity: 'epico',
  drop: { id: B.CLOUD }
});

def(B.BEDROCK, 'Raiz do Mundo', {
  tiles: 'bedrock',
  color: '#3a3f46',
  hardness: 99,
  drop: null
});

def(B.CORAL, 'Coral Solar', {
  tiles: 'coral',
  solid: false,
  opaque: false,
  plant: true,
  category: 'planta',
  color: '#ff8a6b',
  drop: { id: B.CORAL }
});

def(B.BERRY_BUSH, 'Arbusto de Mel', {
  tiles: 'berry_bush',
  solid: false,
  opaque: false,
  plant: true,
  category: 'planta',
  color: '#ffd76a',
  drop: { id: 'berry', min: 1, max: 2, extra: { id: B.BERRY_BUSH, chance: 0.5 } }
});

def(B.PINK_PLANKS, 'Tábuas Rosas', {
  tiles: 'pink_planks',
  color: '#f4a9c4',
  hardness: 0.8,
  rarity: 'incomum',
  drop: { id: B.PINK_PLANKS }
});

def(B.MINT_PLANKS, 'Tábuas Menta', {
  tiles: 'mint_planks',
  color: '#9ee6c1',
  hardness: 0.8,
  rarity: 'incomum',
  drop: { id: B.MINT_PLANKS }
});

def(B.LAVENDER_PLANKS, 'Tábuas Lavanda', {
  tiles: 'lavender_planks',
  color: '#c4b2f0',
  hardness: 0.8,
  rarity: 'incomum',
  drop: { id: B.LAVENDER_PLANKS }
});

export function getBlock(id) {
  return BLOCKS[id] || BLOCKS[B.AIR];
}

export function isSolidBlock(id) {
  return !!BLOCKS[id]?.solid;
}

export function isOpaqueBlock(id) {
  return !!BLOCKS[id]?.opaque;
}

export function isPlantBlock(id) {
  return !!BLOCKS[id]?.plant;
}

export function isLiquidBlock(id) {
  return !!BLOCKS[id]?.liquid;
}

export const PLACEABLE_BLOCKS = BLOCKS.filter((b) => b && b.id !== B.AIR && b.id !== B.WATER && b.id !== B.BEDROCK);
