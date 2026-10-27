import { B } from './blocks.js';
import { fbm2, fbm3, hash2i, hashString, randomAt } from '../core/rng.js';
import { rollChestLoot } from './items.js';

export const CHUNK_SIZE = 16;
export const WORLD_HEIGHT = 48;
export const SEA_LEVEL = 20;
const CHUNK_VOLUME = CHUNK_SIZE * CHUNK_SIZE * WORLD_HEIGHT;

export const BIOMES = {
  meadow: { id: 'meadow', name: 'Prado Musgo', accent: '#b8ff6a', tree: B.LOG },
  forest: { id: 'forest', name: 'Bosque Arco-Íris', accent: '#ffb3c7', tree: B.RAINBOW_LOG },
  desert: { id: 'desert', name: 'Deserto Coral', accent: '#ff8a6b', tree: B.LOG },
  tundra: { id: 'tundra', name: 'Tundra Lumina', accent: '#b8e7ff', tree: B.LOG },
  coast: { id: 'coast', name: 'Costa de Nuvens', accent: '#7cd6ff', tree: B.LOG }
};

export function chunkKey(cx, cz) {
  return `${cx},${cz}`;
}

export function chestKey(x, y, z) {
  return `${x},${y},${z}`;
}

export class World {
  constructor({ seed, diffs = {}, chests = {}, crownPlaced = false } = {}) {
    this.seed = String(seed || 'lumi');
    this.seedNum = hashString(this.seed);
    this.chunks = new Map();
    this.diffs = diffs && typeof diffs === 'object' ? diffs : {};
    this.chests = new Map(Object.entries(chests || {}));
    this.crownPlaced = !!crownPlaced;
  }

  index(x, y, z) {
    return x + z * CHUNK_SIZE + y * CHUNK_SIZE * CHUNK_SIZE;
  }

  key(cx, cz) {
    return chunkKey(cx, cz);
  }

  getChunk(cx, cz, create = true) {
    const key = this.key(cx, cz);
    let chunk = this.chunks.get(key);
    if (!chunk && create) {
      chunk = this.generateChunk(cx, cz);
      this.chunks.set(key, chunk);
    }
    return chunk || null;
  }

  heightAt(wx, wz) {
    const continental = fbm2(this.seedNum + 11, wx * 0.006, wz * 0.006, 4);
    const detail = fbm2(this.seedNum + 23, wx * 0.035, wz * 0.035, 3);
    let h = SEA_LEVEL - 3 + continental * 22 + detail * 4;
    if (h < 5) h = 5;
    if (h > WORLD_HEIGHT - 5) h = WORLD_HEIGHT - 5;
    return Math.floor(h);
  }

  biomeAt(wx, wz, height = this.heightAt(wx, wz)) {
    if (height <= SEA_LEVEL + 1) return 'coast';
    const temp = fbm2(this.seedNum + 101, wx * 0.004, wz * 0.004, 3);
    const humid = fbm2(this.seedNum + 202, wx * 0.0045, wz * 0.0045, 3);
    if (temp < 0.34) return 'tundra';
    if (temp > 0.66 && humid < 0.42) return 'desert';
    if (humid > 0.6) return 'forest';
    return 'meadow';
  }

  surfaceBlock(biome, height) {
    if (height <= SEA_LEVEL) return B.SAND;
    if (biome === 'tundra') return B.SNOW;
    if (biome === 'desert' || biome === 'coast') return B.SAND;
    return B.GRASS;
  }

  generateChunk(cx, cz) {
    const blocks = new Uint8Array(CHUNK_VOLUME);
    const heights = new Int16Array(CHUNK_SIZE * CHUNK_SIZE);
    const biomes = new Array(CHUNK_SIZE * CHUNK_SIZE);
    const set = (x, y, z, id) => {
      if (x < 0 || x >= CHUNK_SIZE || z < 0 || z >= CHUNK_SIZE || y < 0 || y >= WORLD_HEIGHT) return;
      blocks[this.index(x, y, z)] = id;
    };
    const get = (x, y, z) => {
      if (x < 0 || x >= CHUNK_SIZE || z < 0 || z >= CHUNK_SIZE || y < 0 || y >= WORLD_HEIGHT) return B.AIR;
      return blocks[this.index(x, y, z)];
    };

    for (let z = 0; z < CHUNK_SIZE; z++) {
      for (let x = 0; x < CHUNK_SIZE; x++) {
        const wx = cx * CHUNK_SIZE + x;
        const wz = cz * CHUNK_SIZE + z;
        const h = this.heightAt(wx, wz);
        const biome = this.biomeAt(wx, wz, h);
        heights[z * CHUNK_SIZE + x] = h;
        biomes[z * CHUNK_SIZE + x] = biome;
        const surface = this.surfaceBlock(biome, h);
        const sub = biome === 'desert' || biome === 'coast' ? B.SAND : biome === 'tundra' ? B.DIRT : B.DIRT;
        for (let y = 0; y < h; y++) {
          if (y === 0) set(x, y, z, B.BEDROCK);
          else if (y === h - 1) set(x, y, z, surface);
          else if (y >= h - 4) set(x, y, z, sub);
          else set(x, y, z, B.STONE);
        }
        for (let y = 2; y < h - 2; y++) {
          const cave = fbm3(this.seedNum + 303, wx * 0.055, y * 0.075, wz * 0.055, 3);
          if (cave > 0.635) set(x, y, z, B.AIR);
        }
        for (let y = 2; y < h - 3; y++) {
          if (get(x, y, z) !== B.STONE) continue;
          const r = randomAt(this.seedNum + 404, wx, y, wz);
          if (y < 17 && r < 0.0012) set(x, y, z, B.AURORA_ORE);
          else if (r < 0.0075) set(x, y, z, B.LUMI_ORE);
          else if (r < 0.0105) set(x, y, z, B.CRYSTAL);
        }
        if (h <= SEA_LEVEL) {
          for (let y = h; y <= SEA_LEVEL; y++) {
            if (get(x, y, z) === B.AIR) set(x, y, z, B.WATER);
          }
          if (biome === 'tundra' && get(x, SEA_LEVEL, z) === B.WATER) set(x, SEA_LEVEL, z, B.ICE);
        }
      }
    }

    for (let z = 2; z < CHUNK_SIZE - 2; z++) {
      for (let x = 2; x < CHUNK_SIZE - 2; x++) {
        const wx = cx * CHUNK_SIZE + x;
        const wz = cz * CHUNK_SIZE + z;
        const h = heights[z * CHUNK_SIZE + x];
        const biome = biomes[z * CHUNK_SIZE + x];
        if (h <= SEA_LEVEL + 1) continue;
        const top = get(x, h - 1, z);
        if (top !== B.GRASS && top !== B.SNOW && top !== B.SAND) continue;
        const r = randomAt(this.seedNum + 505, wx, 0, wz);
        const treeChance = biome === 'forest' ? 0.055 : biome === 'meadow' ? 0.016 : biome === 'coast' ? 0.01 : 0;
        if (treeChance > 0 && r < treeChance && x >= 3 && x <= CHUNK_SIZE - 4 && z >= 3 && z <= CHUNK_SIZE - 4) {
          this.plantTree(set, get, x, h, z, biome === 'forest');
          continue;
        }
        if (get(x, h, z) !== B.AIR) continue;
        if (biome === 'meadow') {
          if (r < 0.1) set(x, h, z, r < 0.05 ? B.FLOWER_PINK : B.FLOWER_BLUE);
          else if (r < 0.18) set(x, h, z, B.TALL_GRASS);
          else if (r < 0.205) set(x, h, z, B.BERRY_BUSH);
        } else if (biome === 'forest') {
          if (r < 0.07) set(x, h, z, B.MUSHROOM_STAR);
          else if (r < 0.15) set(x, h, z, B.TALL_GRASS);
          else if (r < 0.17) set(x, h, z, B.FLOWER_BLUE);
        } else if (biome === 'desert') {
          if (r < 0.05) set(x, h, z, B.CORAL);
          else if (r < 0.075) set(x, h, z, B.CORAL_STONE);
        } else if (biome === 'tundra') {
          if (r < 0.02) set(x, h, z, B.CRYSTAL);
          else if (r < 0.035) set(x, h, z, B.FLOWER_BLUE);
        } else if (biome === 'coast') {
          if (r < 0.04) set(x, h, z, B.TALL_GRASS);
        }
      }
    }

    if (hash2i(cx, cz, this.seedNum + 77) % 7 === 0) {
      this.buildShrine(cx, cz, set, get, heights, biomes);
    }

    if (hash2i(cx, cz, this.seedNum + 91) % 11 === 0) {
      this.buildCloudIsland(cx, cz, set, get, heights);
    }

    const diff = this.diffs[this.key(cx, cz)];
    if (diff) {
      for (const [idx, id] of Object.entries(diff)) {
        const i = Number(idx);
        if (i >= 0 && i < blocks.length) blocks[i] = id;
      }
    }

    return {
      cx,
      cz,
      key: this.key(cx, cz),
      blocks,
      mesh: null,
      glowMesh: null,
      plantMesh: null,
      waterMesh: null,
      dirty: false
    };
  }

  plantTree(set, get, x, y, z, rainbow) {
    const height = 4 + Math.floor(randomAt(this.seedNum + 606, x, y, z) * 3) + (rainbow ? 1 : 0);
    const log = rainbow ? B.RAINBOW_LOG : B.LOG;
    const leaves = rainbow ? B.RAINBOW_LEAVES : B.LEAVES;
    for (let i = 0; i < height; i++) set(x, y + i, z, log);
    const topY = y + height - 1;
    for (let dy = -1; dy <= 2; dy++) {
      for (let dx = -2; dx <= 2; dx++) {
        for (let dz = -2; dz <= 2; dz++) {
          const dist = Math.abs(dx) + Math.abs(dz) + Math.abs(dy);
          if (dist > 3) continue;
          if (dist === 3 && randomAt(this.seedNum + 707, x + dx, topY + dy, z + dz) < 0.45) continue;
          if (get(x + dx, topY + dy, z + dz) === B.AIR) set(x + dx, topY + dy, z + dz, leaves);
        }
      }
    }
    set(x, topY + 3, z, leaves);
  }

  buildShrine(cx, cz, set, get, heights, biomes) {
    const x = 8;
    const z = 8;
    const h = heights[z * CHUNK_SIZE + x];
    const biome = biomes[z * CHUNK_SIZE + x];
    if (!h || h <= SEA_LEVEL || h > WORLD_HEIGHT - 10) return;
    if (biome === 'coast') return;
    const baseY = h - 1;
    for (let dy = 0; dy <= 5; dy++) {
      for (let dx = -2; dx <= 2; dx++) {
        for (let dz = -2; dz <= 2; dz++) {
          set(x + dx, baseY + dy, z + dz, B.AIR);
        }
      }
    }
    for (let dx = -2; dx <= 2; dx++) {
      for (let dz = -2; dz <= 2; dz++) {
        set(x + dx, baseY, z + dz, B.COBBLE);
      }
    }
    const corners = [[-2, -2], [2, -2], [-2, 2], [2, 2]];
    for (const [dx, dz] of corners) {
      for (let dy = 1; dy <= 3; dy++) set(x + dx, baseY + dy, z + dz, B.COBBLE);
      set(x + dx, baseY + 4, z + dz, B.LUMI_LAMP);
    }
    set(x, baseY + 1, z, B.CRYSTAL);
    set(x, baseY + 2, z, B.CRYSTAL);
    set(x, baseY + 1, z + 1, B.CHEST);

    const wx = cx * CHUNK_SIZE + x;
    const wy = baseY + 1;
    const wz = cz * CHUNK_SIZE + z + 1;
    const key = chestKey(wx, wy, wz);
    if (!this.chests.has(key)) {
      const canCrown = !this.crownPlaced;
      const hasCrown = canCrown && randomAt(this.seedNum, cx, 321, cz) < 0.22;
      if (hasCrown) this.crownPlaced = true;
      this.chests.set(key, rollChestLoot(this.seedNum, wx, wy, wz, hasCrown));
    }
  }

  buildCloudIsland(cx, cz, set, get, heights) {
    const x = 8;
    const z = 8;
    const h = heights[z * CHUNK_SIZE + x];
    const y = 33;
    if (h > y - 4) return;
    for (let dx = -1; dx <= 1; dx++) {
      for (let dz = -1; dz <= 1; dz++) {
        if (Math.abs(dx) + Math.abs(dz) === 2 && randomAt(this.seedNum + 808, cx + dx, y, cz + dz) < 0.5) continue;
        set(x + dx, y, z + dz, B.CLOUD);
      }
    }
    set(x, y + 1, z, B.CRYSTAL);
  }

  getBlock(wx, wy, wz) {
    wx = Math.floor(wx);
    wy = Math.floor(wy);
    wz = Math.floor(wz);
    if (wy < 0) return B.BEDROCK;
    if (wy >= WORLD_HEIGHT) return B.AIR;
    const cx = Math.floor(wx / CHUNK_SIZE);
    const cz = Math.floor(wz / CHUNK_SIZE);
    const chunk = this.getChunk(cx, cz);
    const lx = wx - cx * CHUNK_SIZE;
    const lz = wz - cz * CHUNK_SIZE;
    return chunk.blocks[this.index(lx, wy, lz)];
  }

  isSolid(wx, wy, wz) {
    const block = this.getBlock(wx, wy, wz);
    return !!this.constructor.blockSolid(block);
  }

  static blockSolid(id) {
    return id !== B.AIR && id !== B.WATER && !isPlant(id);
  }

  isWater(wx, wy, wz) {
    return this.getBlock(wx, wy, wz) === B.WATER;
  }

  setBlock(wx, wy, wz, id, record = true) {
    wx = Math.floor(wx);
    wy = Math.floor(wy);
    wz = Math.floor(wz);
    if (wy < 0 || wy >= WORLD_HEIGHT) return false;
    const cx = Math.floor(wx / CHUNK_SIZE);
    const cz = Math.floor(wz / CHUNK_SIZE);
    const chunk = this.getChunk(cx, cz);
    const lx = wx - cx * CHUNK_SIZE;
    const lz = wz - cz * CHUNK_SIZE;
    const idx = this.index(lx, wy, lz);
    if (chunk.blocks[idx] === id) return false;
    chunk.blocks[idx] = id;
    chunk.dirty = true;
    if (record) {
      const key = this.key(cx, cz);
      if (!this.diffs[key]) this.diffs[key] = {};
      this.diffs[key][idx] = id;
    }
    if (lx === 0) this.markDirty(cx - 1, cz);
    if (lx === CHUNK_SIZE - 1) this.markDirty(cx + 1, cz);
    if (lz === 0) this.markDirty(cx, cz - 1);
    if (lz === CHUNK_SIZE - 1) this.markDirty(cx, cz + 1);
    return true;
  }

  markDirty(cx, cz) {
    const chunk = this.chunks.get(this.key(cx, cz));
    if (chunk) chunk.dirty = true;
  }

  getChest(wx, wy, wz) {
    const key = chestKey(wx, wy, wz);
    if (!this.chests.has(key)) this.chests.set(key, []);
    return this.chests.get(key);
  }

  setChest(wx, wy, wz, items) {
    this.chests.set(chestKey(wx, wy, wz), items);
  }

  deleteChest(wx, wy, wz) {
    this.chests.delete(chestKey(wx, wy, wz));
  }

  findSpawn() {
    const directions = [
      [0, -1, 0],
      [-1, 0, Math.PI / 2],
      [1, 0, -Math.PI / 2],
      [0, 1, Math.PI]
    ];
    let fallback = null;

    for (let r = 0; r <= 48; r++) {
      for (let dx = -r; dx <= r; dx++) {
        for (let dz = -r; dz <= r; dz++) {
          if (r > 0 && Math.abs(dx) !== r && Math.abs(dz) !== r) continue;
          const wx = dx;
          const wz = dz;
          const h = this.heightAt(wx, wz);
          if (h <= SEA_LEVEL + 2 || h > WORLD_HEIGHT - 8) continue;
          const biome = this.biomeAt(wx, wz, h);
          if (biome === 'coast') continue;
          if (!this.isSolid(wx, h - 1, wz)) continue;
          if (this.getBlock(wx, h, wz) !== B.AIR || this.getBlock(wx, h + 1, wz) !== B.AIR) continue;

          let bestClearance = 0;
          let bestYaw = 0;
          for (const [ddx, ddz, yaw] of directions) {
            let clearance = 0;
            for (let step = 1; step <= 6; step++) {
              const bx = wx + ddx * step;
              const bz = wz + ddz * step;
              if (this.getBlock(bx, h, bz) !== B.AIR || this.getBlock(bx, h + 1, bz) !== B.AIR) break;
              if (this.isWater(bx, h - 1, bz)) break;
              clearance = step;
            }
            if (clearance > bestClearance) {
              bestClearance = clearance;
              bestYaw = yaw;
            }
          }

          const candidate = {
            x: wx + 0.5,
            y: h + 0.05,
            z: wz + 0.5,
            biome,
            height: h,
            yaw: bestYaw,
            clearance: bestClearance
          };
          if (!fallback || bestClearance > fallback.clearance) fallback = candidate;
          if (bestClearance >= 4) return candidate;
        }
      }
    }

    if (fallback) return fallback;
    const h = this.heightAt(0, 0);
    return { x: 0.5, y: h + 0.05, z: 0.5, biome: 'meadow', height: h, yaw: 0, clearance: 0 };
  }

  growRainbowTree(wx, wy, wz) {
    for (let i = 0; i < 4; i++) {
      if (this.getBlock(wx, wy + i, wz) !== B.AIR) return false;
    }
    for (let i = 0; i < 5; i++) this.setBlock(wx, wy + i, wz, B.RAINBOW_LOG);
    const topY = wy + 4;
    for (let dy = -1; dy <= 2; dy++) {
      for (let dx = -2; dx <= 2; dx++) {
        for (let dz = -2; dz <= 2; dz++) {
          const dist = Math.abs(dx) + Math.abs(dz) + Math.abs(dy);
          if (dist > 3) continue;
          if (this.getBlock(wx + dx, topY + dy, wz + dz) === B.AIR) {
            this.setBlock(wx + dx, topY + dy, wz + dz, B.RAINBOW_LEAVES);
          }
        }
      }
    }
    this.setBlock(wx, topY + 3, wz, B.RAINBOW_LEAVES);
    return true;
  }

  findNearbyOres(px, py, pz, radius = 8) {
    const found = [];
    const x0 = Math.floor(px) - radius;
    const x1 = Math.floor(px) + radius;
    const y0 = Math.max(1, Math.floor(py) - radius);
    const y1 = Math.min(WORLD_HEIGHT - 1, Math.floor(py) + radius);
    const z0 = Math.floor(pz) - radius;
    const z1 = Math.floor(pz) + radius;
    for (let y = y0; y <= y1 && found.length < 12; y++) {
      for (let z = z0; z <= z1 && found.length < 12; z++) {
        for (let x = x0; x <= x1 && found.length < 12; x++) {
          const block = this.getBlock(x, y, z);
          if (block === B.LUMI_ORE || block === B.AURORA_ORE || block === B.CRYSTAL) {
            found.push({ x: x + 0.5, y: y + 0.5, z: z + 0.5, block });
          }
        }
      }
    }
    return found;
  }

  serializeDiffs() {
    return this.diffs;
  }

  serializeChests() {
    return Object.fromEntries(this.chests.entries());
  }
}

function isPlant(id) {
  return id === B.FLOWER_PINK || id === B.FLOWER_BLUE || id === B.TALL_GRASS || id === B.MUSHROOM_STAR || id === B.CORAL || id === B.BERRY_BUSH;
}
