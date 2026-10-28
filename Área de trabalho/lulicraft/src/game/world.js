import { B } from './blocks.js';
import { fbm2, fbm3, hash2i, hashString, randomAt } from '../core/rng.js';
import { isRare, rollChestLoot } from './items.js';

export const CHUNK_SIZE = 16;
export const WORLD_HEIGHT = 48;
export const SEA_LEVEL = 20;
export const HOME_SEED = 'lumi-minha-casa';
export const HOME_BASE = 28;
export const HOME_CITY_MIN = -40;
export const HOME_CITY_MAX = 40;
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
    if (this.seed === HOME_SEED && wx >= HOME_CITY_MIN - 4 && wx <= HOME_CITY_MAX + 4 && wz >= HOME_CITY_MIN - 4 && wz <= HOME_CITY_MAX + 4) {
      return HOME_BASE;
    }
    const continental = fbm2(this.seedNum + 11, wx * 0.006, wz * 0.006, 4);
    const detail = fbm2(this.seedNum + 23, wx * 0.035, wz * 0.035, 3);
    const ridge = Math.abs(fbm2(this.seedNum + 37, wx * 0.011, wz * 0.011, 3) - 0.5) * 2;
    const shelf = fbm2(this.seedNum + 47, wx * 0.02, wz * 0.02, 2);
    let h = SEA_LEVEL - 3 + continental * 20 + detail * 4.5 + ridge * 5;
    if (shelf > 0.64) h += (shelf - 0.64) * 26;
    if (h < 5) h = 5;
    if (h > WORLD_HEIGHT - 5) h = WORLD_HEIGHT - 5;
    return Math.floor(h);
  }

  biomeAt(wx, wz, height = this.heightAt(wx, wz)) {
    if (this.seed === HOME_SEED && wx >= HOME_CITY_MIN && wx <= HOME_CITY_MAX && wz >= HOME_CITY_MIN && wz <= HOME_CITY_MAX) return 'meadow';
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
        const treeChance = biome === 'forest' ? 0.06 : biome === 'meadow' ? 0.018 : biome === 'coast' ? 0.012 : biome === 'tundra' ? 0.012 : 0;
        if (treeChance > 0 && r < treeChance && x >= 3 && x <= CHUNK_SIZE - 4 && z >= 3 && z <= CHUNK_SIZE - 4) {
          let variant = 'oak';
          if (biome === 'forest') variant = r < treeChance * 0.32 ? 'giant' : 'rainbow';
          else if (biome === 'meadow') variant = r < treeChance * 0.45 ? 'shrub' : 'oak';
          else if (biome === 'tundra') variant = 'spruce';
          else if (biome === 'coast') variant = 'palm';
          this.plantTree(set, get, x, h, z, variant);
          continue;
        }
        if (get(x, h, z) !== B.AIR) continue;
        if (biome === 'meadow') {
          if (r < 0.11) set(x, h, z, r < 0.055 ? B.FLOWER_PINK : B.FLOWER_BLUE);
          else if (r < 0.2) set(x, h, z, B.TALL_GRASS);
          else if (r < 0.23) set(x, h, z, B.BERRY_BUSH);
        } else if (biome === 'forest') {
          if (r < 0.08) set(x, h, z, B.MUSHROOM_STAR);
          else if (r < 0.18) set(x, h, z, B.TALL_GRASS);
          else if (r < 0.205) set(x, h, z, B.FLOWER_BLUE);
          else if (r < 0.215) set(x, h, z, B.FLOWER_PINK);
        } else if (biome === 'desert') {
          if (r < 0.06) set(x, h, z, B.CORAL);
          else if (r < 0.08) set(x, h, z, B.CORAL_STONE);
        } else if (biome === 'tundra') {
          if (r < 0.025) set(x, h, z, B.CRYSTAL);
          else if (r < 0.045) set(x, h, z, B.FLOWER_BLUE);
        } else if (biome === 'coast') {
          if (r < 0.055) set(x, h, z, B.TALL_GRASS);
          else if (r < 0.072) set(x, h, z, B.CORAL);
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
        const surface = get(x, h - 1, z);
        if (surface !== B.GRASS && surface !== B.SNOW && surface !== B.SAND) continue;
        if (get(x, h, z) !== B.AIR) continue;
        const r = randomAt(this.seedNum + 919, wx, 2, wz);
        if (biome === 'desert') {
          if (r < 0.013) this.buildCoralSpire(set, get, x, h, z);
          else if (r < 0.021) this.buildBoulder(set, get, x, h, z, biome);
        } else if (biome === 'tundra') {
          if (r < 0.014) this.buildCrystalCluster(set, get, x, h, z);
          else if (r < 0.02) this.buildBoulder(set, get, x, h, z, biome);
        } else if (biome === 'coast') {
          if (r < 0.01) this.buildDriftwood(set, get, x, h, z);
          else if (r < 0.016) this.buildBoulder(set, get, x, h, z, biome);
        } else if (biome === 'forest') {
          if (r < 0.008) this.buildFallenLog(set, get, x, h, z, true);
          else if (r < 0.013) this.buildBoulder(set, get, x, h, z, biome);
        } else if (biome === 'meadow') {
          if (r < 0.007) this.buildFallenLog(set, get, x, h, z, false);
          else if (r < 0.013) this.buildBoulder(set, get, x, h, z, biome);
        }
      }
    }

    for (let z = 0; z < CHUNK_SIZE; z++) {
      for (let x = 0; x < CHUNK_SIZE; x++) {
        if (x >= 2 && x < CHUNK_SIZE - 2 && z >= 2 && z < CHUNK_SIZE - 2) continue;
        const wx = cx * CHUNK_SIZE + x;
        const wz = cz * CHUNK_SIZE + z;
        const h = heights[z * CHUNK_SIZE + x];
        const biome = biomes[z * CHUNK_SIZE + x];
        if (h <= SEA_LEVEL + 1) continue;
        const surface = get(x, h - 1, z);
        if (surface !== B.GRASS && surface !== B.SNOW && surface !== B.SAND) continue;
        if (get(x, h, z) !== B.AIR) continue;
        const r = randomAt(this.seedNum + 515, wx, 1, wz);
        if (biome === 'meadow') {
          if (r < 0.06) set(x, h, z, r < 0.03 ? B.FLOWER_PINK : B.FLOWER_BLUE);
          else if (r < 0.11) set(x, h, z, B.TALL_GRASS);
        } else if (biome === 'forest') {
          if (r < 0.05) set(x, h, z, B.TALL_GRASS);
          else if (r < 0.07) set(x, h, z, B.MUSHROOM_STAR);
        } else if (biome === 'desert') {
          if (r < 0.04) set(x, h, z, B.CORAL);
        } else if (biome === 'tundra') {
          if (r < 0.03) set(x, h, z, B.FLOWER_BLUE);
        } else if (biome === 'coast') {
          if (r < 0.045) set(x, h, z, B.TALL_GRASS);
        }
      }
    }

    const hasShrine = hash2i(cx, cz, this.seedNum + 77) % 7 === 0;
    if (hasShrine) {
      this.buildShrine(cx, cz, set, get, heights, biomes);
    } else if (this.seed !== HOME_SEED && hash2i(cx, cz, this.seedNum + 131) % 9 === 0) {
      this.buildVillage(cx, cz, set, get, heights, biomes);
    }

    if (hash2i(cx, cz, this.seedNum + 91) % 11 === 0) {
      this.buildCloudIsland(cx, cz, set, get, heights);
    }

    if (this.seed === HOME_SEED) {
      this.buildHomeCityChunk(cx, cz, set, get);
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

  plantTree(set, get, x, y, z, variant = 'oak') {
    const rnd = (dx, dy, dz) => randomAt(this.seedNum + 606, x + dx, y + dy, z + dz);
    const put = (dx, dy, dz, id) => {
      if (get(x + dx, y + dy, z + dz) === B.AIR) set(x + dx, y + dy, z + dz, id);
    };
    const trunk = (height, log) => {
      for (let i = 0; i < height; i++) set(x, y + i, z, log);
      return y + height - 1;
    };
    const canopy = (topY, log, leaves, radius, density = 0.45) => {
      const offset = topY - y;
      for (let dy = -1; dy <= 2; dy++) {
        for (let dx = -radius; dx <= radius; dx++) {
          for (let dz = -radius; dz <= radius; dz++) {
            const dist = Math.abs(dx) + Math.abs(dz) + Math.abs(dy);
            if (dist > radius + 1) continue;
            if (dist === radius + 1 && rnd(dx, topY + dy, dz) < density) continue;
            put(dx, offset + dy, dz, leaves);
          }
        }
      }
      put(0, offset + 3, 0, leaves);
      return topY;
    };

    if (variant === 'shrub') {
      const height = 2 + (rnd(0, 0, 0) < 0.5 ? 0 : 1);
      const topY = trunk(height, B.LOG);
      for (let dy = -1; dy <= 1; dy++) {
        for (let dx = -1; dx <= 1; dx++) {
          for (let dz = -1; dz <= 1; dz++) {
            if (Math.abs(dx) + Math.abs(dz) + Math.abs(dy) > 2) continue;
            put(dx, dy, dz, B.LEAVES);
          }
        }
      }
      put(0, 2, 0, B.LEAVES);
      return;
    }

    if (variant === 'palm') {
      const height = 5 + Math.floor(rnd(0, 1, 0) * 3);
      const lean = rnd(0, 2, 0) < 0.5 ? -1 : 1;
      for (let i = 0; i < height; i++) {
        const ox = i >= height - 3 ? lean : 0;
        set(x + ox, y + i, z, B.LOG);
      }
      const topY = y + height - 1;
      const cx = x + lean;
      for (const [dx, dz] of [[0, 0], [1, 0], [-1, 0], [0, 1], [0, -1], [2, 0], [-2, 0], [0, 2], [0, -2]]) {
        if (get(cx + dx, topY + 1, z + dz) === B.AIR) set(cx + dx, topY + 1, z + dz, B.LEAVES);
      }
      set(cx, topY + 2, z, B.LEAVES);
      set(cx + 1, topY, z, B.CORAL);
      return;
    }

    if (variant === 'spruce') {
      const height = 6 + Math.floor(rnd(0, 3, 0) * 3);
      const topY = trunk(height, B.LOG);
      let radius = 2;
      for (let dy = 0; dy >= -5; dy--) {
        radius = Math.max(1, 2 - Math.floor((-dy + 1) / 2));
        for (let dx = -radius; dx <= radius; dx++) {
          for (let dz = -radius; dz <= radius; dz++) {
            if (Math.abs(dx) + Math.abs(dz) > radius + 1) continue;
            const id = dy === 0 || (dy === -4 && rnd(dx, dy, dz) < 0.5) ? B.SNOW : B.LEAVES;
            put(dx, dy, dz, id);
          }
        }
      }
      put(0, height, 0, B.SNOW);
      return;
    }

    if (variant === 'giant') {
      const height = 7 + Math.floor(rnd(0, 4, 0) * 3);
      const topY = trunk(height, B.RAINBOW_LOG);
      canopy(topY, B.RAINBOW_LOG, B.RAINBOW_LEAVES, 3, 0.4);
      canopy(topY - 3, B.RAINBOW_LOG, B.RAINBOW_LEAVES, 2, 0.5);
      return;
    }

    if (variant === 'rainbow') {
      const height = 5 + Math.floor(rnd(0, 5, 0) * 3);
      const topY = trunk(height, B.RAINBOW_LOG);
      canopy(topY, B.RAINBOW_LOG, B.RAINBOW_LEAVES, 2, 0.45);
      return;
    }

    const height = 4 + Math.floor(rnd(0, 6, 0) * 3);
    const topY = trunk(height, B.LOG);
    canopy(topY, B.LOG, B.LEAVES, 2, 0.45);
  }

  buildBoulder(set, get, x, y, z, biome) {
    const tall = randomAt(this.seedNum + 919, x, 3, z) > 0.62;
    for (let dx = -1; dx <= 1; dx++) {
      for (let dz = -1; dz <= 1; dz++) {
        if (Math.abs(dx) + Math.abs(dz) > 1) continue;
        const block = randomAt(this.seedNum + 929, x + dx, y, z + dz) < 0.5 ? B.COBBLE : B.STONE;
        if (get(x + dx, y, z + dz) === B.AIR) set(x + dx, y, z + dz, block);
        if (tall && get(x + dx, y + 1, z + dz) === B.AIR) set(x + dx, y + 1, z + dz, B.COBBLE);
      }
    }
    const cap = randomAt(this.seedNum + 939, x, y, z);
    const capY = y + (tall ? 2 : 1);
    if (cap < 0.4) set(x, capY, z, biome === 'tundra' ? B.SNOW : B.LEAVES);
    else if (cap < 0.55) set(x, capY, z, B.CRYSTAL);
  }

  buildFallenLog(set, get, x, y, z, rainbow) {
    const log = rainbow ? B.RAINBOW_LOG : B.LOG;
    const along = randomAt(this.seedNum + 949, x, 4, z) < 0.5 ? 'x' : 'z';
    const length = 3;
    for (let i = 0; i < length; i++) {
      const bx = x + (along === 'x' ? i : 0);
      const bz = z + (along === 'z' ? i : 0);
      if (get(bx, y, bz) === B.AIR) set(bx, y, bz, log);
    }
    if (randomAt(this.seedNum + 959, x, y, z) < 0.55) {
      const bx = x + (along === 'x' ? length : 0);
      const bz = z + (along === 'z' ? length : 0);
      if (get(bx, y, bz) === B.AIR) set(bx, y, bz, randomAt(this.seedNum + 969, x, y, z) < 0.5 ? B.MUSHROOM_STAR : B.TALL_GRASS);
    }
  }

  buildDriftwood(set, get, x, y, z) {
    const along = randomAt(this.seedNum + 979, x, 5, z) < 0.5 ? 'x' : 'z';
    for (let i = 0; i < 3; i++) {
      const bx = x + (along === 'x' ? i : 0);
      const bz = z + (along === 'z' ? i : 0);
      if (get(bx, y, bz) === B.AIR) set(bx, y, bz, B.LOG);
    }
    if (get(x, y + 1, z) === B.AIR) set(x, y + 1, z, B.CORAL);
  }

  buildCoralSpire(set, get, x, y, z) {
    const height = 2 + Math.floor(randomAt(this.seedNum + 989, x, y, z) * 3);
    for (let i = 0; i < height; i++) {
      if (get(x, y + i, z) === B.AIR) set(x, y + i, z, B.CORAL_STONE);
    }
    if (get(x, y + height, z) === B.AIR) set(x, y + height, z, B.CORAL);
    const arm = randomAt(this.seedNum + 999, x, y, z);
    if (arm < 0.45 && get(x + 1, y + height - 1, z) === B.AIR) set(x + 1, y + height - 1, z, B.CORAL);
    else if (arm < 0.7 && get(x - 1, y + height - 1, z) === B.AIR) set(x - 1, y + height - 1, z, B.CORAL);
  }

  buildCrystalCluster(set, get, x, y, z) {
    const spots = [[0, 0], [1, 0], [-1, 0], [0, 1], [0, -1]];
    for (let i = 0; i < spots.length; i++) {
      const [dx, dz] = spots[i];
      if (randomAt(this.seedNum + 1009, x + dx, y + i, z + dz) > 0.5) continue;
      if (get(x + dx, y, z + dz) !== B.AIR) continue;
      set(x + dx, y, z + dz, i === 0 ? B.CRYSTAL : B.ICE);
    }
    if (get(x, y + 1, z) === B.AIR) set(x, y + 1, z, B.CRYSTAL);
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
    for (let dx = -4; dx <= 4; dx++) {
      for (let dz = -4; dz <= 4; dz++) {
        const ring = Math.max(Math.abs(dx), Math.abs(dz));
        if (ring < 3 || ring > 4) continue;
        const r = randomAt(this.seedNum + 1717, cx * CHUNK_SIZE + x + dx, baseY, cz * CHUNK_SIZE + z + dz);
        if (r > 0.55) continue;
        if (get(x + dx, baseY + 1, z + dz) !== B.AIR) continue;
        set(x + dx, baseY + 1, z + dz, r < 0.3 ? B.FLOWER_PINK : r < 0.45 ? B.FLOWER_BLUE : B.TALL_GRASS);
      }
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

  buildVillage(cx, cz, set, get, heights, biomes) {
    const centerX = 8;
    const centerZ = 8;
    const baseY = heights[centerZ * CHUNK_SIZE + centerX];
    const biome = biomes[centerZ * CHUNK_SIZE + centerX];
    if (!baseY || baseY <= SEA_LEVEL || baseY > WORLD_HEIGHT - 12) return;
    const walls = {
      meadow: B.MINT_PLANKS,
      forest: B.PINK_PLANKS,
      desert: B.CORAL_STONE,
      tundra: B.SNOW,
      coast: B.PLANKS
    };
    const wall = walls[biome] || B.PLANKS;
    const roof = biome === 'desert' ? B.CORAL_STONE : biome === 'tundra' ? B.ICE : B.PRISM;

    for (let x = centerX - 6; x <= centerX + 6; x++) {
      for (let z = centerZ - 6; z <= centerZ + 6; z++) {
        const edge = Math.max(Math.abs(x - centerX), Math.abs(z - centerZ));
        if (edge > 6) continue;
        for (let y = baseY; y <= baseY + 5; y++) set(x, y, z, B.AIR);
        const path = Math.abs(x - centerX) <= 1 || Math.abs(z - centerZ) <= 1;
        set(x, baseY - 1, z, path ? B.COBBLE : biome === 'desert' ? B.SAND : B.GRASS);
      }
    }

    this.buildVillageHouse(set, get, centerX - 3, centerZ, baseY, wall, roof);
    this.buildVillageHouse(set, get, centerX + 3, centerZ, baseY, wall, roof);

    for (const [dx, dz] of [[0, 0], [-1, 1], [1, -1], [-1, -1], [1, 1]]) {
      const x = centerX + dx;
      const z = centerZ + dz;
      if (dx === 0 && dz === 0) {
        set(x, baseY, z, B.LUMI_LAMP);
        set(x, baseY + 1, z, B.CRYSTAL);
      } else if (get(x, baseY, z) === B.AIR) {
        set(x, baseY, z, (dx + dz) % 2 === 0 ? B.FLOWER_PINK : B.FLOWER_BLUE);
      }
    }

    const chestX = cx * CHUNK_SIZE + centerX - 3;
    const chestY = baseY;
    const chestZ = cz * CHUNK_SIZE + centerZ - 1;
    set(centerX - 3, baseY, centerZ - 1, B.CHEST);
    const key = chestKey(chestX, chestY, chestZ);
    if (!this.chests.has(key)) {
      this.chests.set(key, rollChestLoot(this.seedNum, chestX, chestY, chestZ, false));
    }
  }

  buildVillageHouse(set, get, cx, cz, baseY, wall, roof) {
    for (let x = cx - 2; x <= cx + 2; x++) {
      for (let z = cz - 2; z <= cz + 2; z++) {
        set(x, baseY - 1, z, B.COBBLE);
      }
    }
    for (let y = baseY; y <= baseY + 3; y++) {
      for (let x = cx - 2; x <= cx + 2; x++) {
        for (let z = cz - 2; z <= cz + 2; z++) {
          const edge = Math.abs(x - cx) === 2 || Math.abs(z - cz) === 2;
          if (!edge) continue;
          const door = x === cx && z === cz + 2 && y <= baseY + 1;
          const window = y === baseY + 2 && (
            (Math.abs(x - cx) === 1 && z === cz + 2) ||
            (Math.abs(z - cz) === 1 && Math.abs(x - cx) === 2)
          );
          set(x, y, z, door ? B.AIR : window ? B.CRYSTAL : wall);
        }
      }
    }
    for (let x = cx - 2; x <= cx + 2; x++) {
      for (let z = cz - 2; z <= cz + 2; z++) set(x, baseY + 4, z, roof);
    }
    set(cx, baseY, cz, B.LUMI_LAMP);
    set(cx + 1, baseY, cz + 1, B.PINK_PLANKS);
    set(cx - 1, baseY, cz + 1, B.FLOWER_PINK);
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
    if (this.seed === HOME_SEED) {
      return { x: 0.5, y: HOME_BASE + 0.05, z: 6.5, biome: 'meadow', height: HOME_BASE, yaw: 0, clearance: 6 };
    }
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

  buildHomeCityChunk(cx, cz, set, get) {
    const minX = cx * CHUNK_SIZE;
    const minZ = cz * CHUNK_SIZE;
    const maxX = minX + CHUNK_SIZE - 1;
    const maxZ = minZ + CHUNK_SIZE - 1;
    if (maxX < HOME_CITY_MIN || minX > HOME_CITY_MAX || maxZ < HOME_CITY_MIN || minZ > HOME_CITY_MAX) return;

    const put = (gx, gy, gz, id) => {
      if (gx < minX || gx > maxX || gz < minZ || gz > maxZ || gy < 0 || gy >= WORLD_HEIGHT) return;
      set(gx - minX, gy, gz - minZ, id);
    };
    const chest = (gx, gy, gz, index) => {
      put(gx, gy, gz, B.CHEST);
      const key = chestKey(gx, gy, gz);
      if (!this.chests.has(key)) this.setChest(gx, gy, gz, this.homeChestLoot(gx, gy, gz, index));
    };
    const insideHouse = (x, z) => x >= -5 && x <= 5 && z >= -5 && z <= 5;

    for (let gx = Math.max(minX, HOME_CITY_MIN); gx <= Math.min(maxX, HOME_CITY_MAX); gx++) {
      for (let gz = Math.max(minZ, HOME_CITY_MIN); gz <= Math.min(maxZ, HOME_CITY_MAX); gz++) {
        for (let y = HOME_BASE; y < WORLD_HEIGHT; y++) put(gx, y, gz, B.AIR);
        const road = (gx % 8 === 0 || gz % 8 === 0) && !insideHouse(gx, gz);
        if (road) put(gx, HOME_BASE - 1, gz, B.COBBLE);
        else if (Math.abs(gx) % 8 === 1 || Math.abs(gz) % 8 === 1) put(gx, HOME_BASE - 1, gz, B.PINK_PLANKS);
        else put(gx, HOME_BASE - 1, gz, B.GRASS);
      }
    }

    this.buildHomeHouse(put, chest);
    this.buildHomePlaza(put);

    for (let cellX = HOME_CITY_MIN + 4; cellX <= HOME_CITY_MAX - 4; cellX += 8) {
      for (let cellZ = HOME_CITY_MIN + 4; cellZ <= HOME_CITY_MAX - 4; cellZ += 8) {
        if (Math.abs(cellX) <= 9 && Math.abs(cellZ) <= 9) continue;
        const r = randomAt(this.seedNum + 4242, cellX, 1, cellZ);
        if (r < 0.26) this.buildHomePark(put, cellX, cellZ, r);
        else this.buildHomeNeighbor(put, chest, cellX, cellZ, r);
      }
    }
  }

  homeChestLoot(gx, gy, gz, index = 0) {
    const loot = rollChestLoot(this.seedNum, gx, gy, gz, index === 0);
    const guaranteed = ['crown_lumi', 'aurora_pickaxe', 'cloud_boots', 'heart_lumi', 'sun_hammer', 'aurora_bow'];
    const pick = guaranteed[Math.abs(index) % guaranteed.length];
    if (!loot.some((slot) => slot.id === pick)) loot.push({ id: pick, count: 1 });
    if (!loot.some((slot) => isRare(slot.id))) loot.push({ id: 'aurora_gem', count: 1 });
    return loot.slice(0, 12);
  }

  buildHomeHouse(put, chest) {
    for (let x = -5; x <= 5; x++) {
      for (let z = -5; z <= 5; z++) {
        put(x, HOME_BASE - 1, z, B.PLANKS);
      }
    }
    for (let y = HOME_BASE; y <= HOME_BASE + 5; y++) {
      for (let x = -5; x <= 5; x++) {
        for (let z = -5; z <= 5; z++) {
          const edge = Math.abs(x) === 5 || Math.abs(z) === 5;
          if (!edge) continue;
          const door = x === 0 && z === 5 && y <= HOME_BASE + 2;
          const window = y === HOME_BASE + 2 && ((Math.abs(x) === 3 && z === 5) || (Math.abs(z) === 3 && Math.abs(x) === 5));
          put(x, y, z, door ? B.AIR : window ? B.CRYSTAL : B.PINK_PLANKS);
        }
      }
    }
    for (let layer = 0; layer <= 2; layer++) {
      const radius = 5 - layer;
      for (let x = -radius; x <= radius; x++) {
        for (let z = -radius; z <= radius; z++) {
          if (layer === 2 && Math.abs(x) + Math.abs(z) > 2) continue;
          put(x, HOME_BASE + 6 + layer, z, B.PRISM);
        }
      }
    }
    for (const [x, z] of [[-3, -3], [3, -3], [-3, 3], [3, 3]]) {
      put(x, HOME_BASE, z, B.LUMI_LAMP);
    }
    chest(-3, HOME_BASE, -3, 0);
    chest(3, HOME_BASE, -3, 1);
    chest(-3, HOME_BASE, 3, 2);
    chest(3, HOME_BASE, 3, 3);
    for (let x = -1; x <= 1; x++) {
      put(x, HOME_BASE, 0, B.LAVENDER_PLANKS);
      put(x, HOME_BASE, 1, B.LAVENDER_PLANKS);
    }
    put(2, HOME_BASE, 0, B.PINK_PLANKS);
    put(-2, HOME_BASE, 0, B.MINT_PLANKS);
    for (let x = -4; x <= 4; x++) {
      put(x, HOME_BASE - 1, 5, B.PINK_PLANKS);
    }
    for (const x of [-4, 4]) {
      for (const z of [-4, 4]) {
        put(x, HOME_BASE + 1, z, B.LUMI_LAMP);
      }
    }
    for (let x = -8; x <= 8; x++) {
      for (let z = -8; z <= 8; z++) {
        const edge = Math.max(Math.abs(x), Math.abs(z));
        if (edge !== 7 && edge !== 8) continue;
        if ((x + z) % 3 !== 0) continue;
        put(x, HOME_BASE, z, (x + z) % 2 === 0 ? B.FLOWER_PINK : B.FLOWER_BLUE);
      }
    }
    for (const [x, z] of [[-6, 6], [6, 6], [-6, -6], [6, -6]]) {
      put(x, HOME_BASE, z, B.LUMI_LAMP);
    }
  }

  buildHomePlaza(put) {
    const cx = 16;
    const cz = 16;
    for (let x = cx - 3; x <= cx + 3; x++) {
      for (let z = cz - 3; z <= cz + 3; z++) {
        const edge = Math.max(Math.abs(x - cx), Math.abs(z - cz));
        put(x, HOME_BASE - 1, z, edge === 3 ? B.PINK_PLANKS : B.COBBLE);
      }
    }
    put(cx, HOME_BASE - 1, cz, B.WATER);
    put(cx, HOME_BASE, cz, B.PRISM);
    for (const [dx, dz] of [[-2, 0], [2, 0], [0, -2], [0, 2]]) {
      put(cx + dx, HOME_BASE, cz + dz, B.FLOWER_PINK);
    }
    for (const [dx, dz] of [[-4, -4], [4, -4], [-4, 4], [4, 4]]) {
      put(cx + dx, HOME_BASE, cz + dz, B.LUMI_LAMP);
    }
  }

  buildHomeNeighbor(put, chest, cx, cz, r) {
    const walls = [B.PINK_PLANKS, B.MINT_PLANKS, B.LAVENDER_PLANKS, B.PLANKS, B.CORAL_STONE];
    const wall = walls[Math.floor(r * walls.length) % walls.length];
    const roof = r > 0.5 ? B.PRISM : B.CORAL_STONE;
    for (let x = cx - 2; x <= cx + 2; x++) {
      for (let z = cz - 2; z <= cz + 2; z++) {
        put(x, HOME_BASE - 1, z, B.PLANKS);
      }
    }
    for (let y = HOME_BASE; y <= HOME_BASE + 3; y++) {
      for (let x = cx - 2; x <= cx + 2; x++) {
        for (let z = cz - 2; z <= cz + 2; z++) {
          const edge = Math.abs(x - cx) === 2 || Math.abs(z - cz) === 2;
          if (!edge) continue;
          const door = x === cx && z === cz + 2 && y <= HOME_BASE + 1;
          const window = y === HOME_BASE + 2 && ((Math.abs(x - cx) === 1 && z === cz + 2) || (Math.abs(z - cz) === 1 && Math.abs(x - cx) === 2));
          put(x, y, z, door ? B.AIR : window ? B.CRYSTAL : wall);
        }
      }
    }
    for (let x = cx - 2; x <= cx + 2; x++) {
      for (let z = cz - 2; z <= cz + 2; z++) {
        put(x, HOME_BASE + 4, z, roof);
      }
    }
    put(cx, HOME_BASE, cz, B.LUMI_LAMP);
    if (r > 0.58) chest(cx - 1, HOME_BASE, cz - 1, Math.floor(r * 10));
    put(cx + 1, HOME_BASE, cz + 1, B.PINK_PLANKS);
  }

  buildHomePark(put, cx, cz, r) {
    for (let x = cx - 2; x <= cx + 2; x++) {
      for (let z = cz - 2; z <= cz + 2; z++) {
        put(x, HOME_BASE - 1, z, r > 0.5 ? B.MINT_PLANKS : B.GRASS);
      }
    }
    const trunk = r > 0.5 ? B.RAINBOW_LOG : B.LOG;
    for (let y = HOME_BASE; y <= HOME_BASE + 3; y++) put(cx, y, cz, trunk);
    for (let dx = -2; dx <= 2; dx++) {
      for (let dz = -2; dz <= 2; dz++) {
        if (Math.abs(dx) + Math.abs(dz) > 2) continue;
        put(cx + dx, HOME_BASE + 4, cz + dz, B.RAINBOW_LEAVES);
      }
    }
    put(cx, HOME_BASE + 5, cz, B.RAINBOW_LEAVES);
    for (const [dx, dz] of [[-2, 2], [2, 2], [-2, -2], [2, -2]]) {
      put(cx + dx, HOME_BASE, cz + dz, (dx + dz) % 2 === 0 ? B.FLOWER_PINK : B.FLOWER_BLUE);
    }
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
