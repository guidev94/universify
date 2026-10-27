import * as THREE from '../vendor/three.module.js';
import { World, BIOMES, CHUNK_SIZE, WORLD_HEIGHT, chestKey, chunkKey } from './world.js';
import { Player } from './player.js';
import { Inventory } from './inventory.js';
import { GameUI } from './ui.js';
import { getWeaponSkin, getPet, normalizeCharacter } from './characters.js';
import { getPrebuilt } from './prebuilt.js';
import { randomSeed, randomWorldName } from './seeds.js';
import { Multiplayer, normalizeRoomCode } from './multiplayer.js';
import { AudioEngine } from './audio.js';
import { B, getAtlasTexture, getBlock, isPlantBlock, isSolidBlock } from './blocks.js';
import { buildChunkGeometries, createMaterials } from './mesher.js';
import { getItem, getItemIcon, getRarity, isRare, rollBlockDrops } from './items.js';
import { getRecipe } from './recipes.js';
import {
  listWorlds,
  createWorld,
  getWorld,
  saveSnapshot,
  deleteWorld as deleteWorldSave,
  exportWorld,
  exportSnapshot,
  importWorldFile,
  importWorldCode,
  getDaily,
  saveDaily,
  findDailyWorld,
  getSettings,
  saveSettings,
  encodeWorldCode,
  isStorageAvailable
} from './save.js';

const $ = (id) => document.getElementById(id);
const canvas = $('game-canvas');
const isTouch = window.matchMedia?.('(pointer: coarse)').matches || (navigator.maxTouchPoints > 0 && 'ontouchstart' in window);
const RENDER_RADIUS = isTouch ? 3 : 4;
const AUTOSAVE_MS = 15000;
const DAY_LENGTH_SECONDS = 600;

const renderer = new THREE.WebGLRenderer({
  canvas,
  antialias: true,
  preserveDrawingBuffer: true,
  powerPreference: 'high-performance'
});
renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
renderer.setSize(window.innerWidth, window.innerHeight, false);
renderer.outputColorSpace = THREE.SRGBColorSpace;

const scene = new THREE.Scene();
scene.background = new THREE.Color('#8ec9e8');
scene.fog = new THREE.Fog('#8ec9e8', 38, 105);

const camera = new THREE.PerspectiveCamera(72, window.innerWidth / window.innerHeight, 0.1, 240);

const hemi = new THREE.HemisphereLight(0xdfefff, 0x4b5b3d, 0.9);
const sun = new THREE.DirectionalLight(0xfff2d0, 1.2);
const ambient = new THREE.AmbientLight(0xffffff, 0.16);
scene.add(hemi, sun, sun.target, ambient);

const SKY_NIGHT = new THREE.Color('#09111d');
const SKY_DAY = new THREE.Color('#8ec9e8');
const SKY_DUSK = new THREE.Color('#f4a261');
const SKY_AURORA = new THREE.Color('#7a4fd0');

const starGeometry = new THREE.BufferGeometry();
const starPositions = new Float32Array(320 * 3);
for (let i = 0; i < 320; i++) {
  const radius = 130 + Math.random() * 45;
  const theta = Math.random() * Math.PI * 2;
  const phi = Math.acos(2 * Math.random() - 1);
  starPositions[i * 3] = Math.sin(phi) * Math.cos(theta) * radius;
  starPositions[i * 3 + 1] = Math.cos(phi) * radius;
  starPositions[i * 3 + 2] = Math.sin(phi) * Math.sin(theta) * radius;
}
starGeometry.setAttribute('position', new THREE.BufferAttribute(starPositions, 3));
const starMaterial = new THREE.PointsMaterial({
  color: 0xffffff,
  size: 0.9,
  transparent: true,
  opacity: 0,
  depthWrite: false,
  sizeAttenuation: true
});
const stars = new THREE.Points(starGeometry, starMaterial);
stars.frustumCulled = false;
scene.add(stars);

const atlasTexture = getAtlasTexture(THREE);
const materials = createMaterials(atlasTexture);

class Particles {
  constructor(targetScene, max = 600) {
    this.max = max;
    this.items = [];
    this.positions = new Float32Array(max * 3);
    this.colors = new Float32Array(max * 3);
    this.geometry = new THREE.BufferGeometry();
    this.positionAttribute = new THREE.BufferAttribute(this.positions, 3);
    this.colorAttribute = new THREE.BufferAttribute(this.colors, 3);
    this.positionAttribute.setUsage(THREE.DynamicDrawUsage);
    this.colorAttribute.setUsage(THREE.DynamicDrawUsage);
    this.geometry.setAttribute('position', this.positionAttribute);
    this.geometry.setAttribute('color', this.colorAttribute);
    this.geometry.setDrawRange(0, 0);
    this.material = new THREE.PointsMaterial({
      size: 0.17,
      vertexColors: true,
      transparent: true,
      opacity: 0.95,
      depthWrite: false,
      blending: THREE.AdditiveBlending,
      sizeAttenuation: true
    });
    this.points = new THREE.Points(this.geometry, this.material);
    this.points.frustumCulled = false;
    targetScene.add(this.points);
  }

  burst(x, y, z, color, count = 10, spread = 0.5, speed = 2.2) {
    const tint = new THREE.Color(color);
    for (let i = 0; i < count; i++) {
      if (this.items.length >= this.max) this.items.shift();
      const angle = Math.random() * Math.PI * 2;
      const life = 0.55 + Math.random() * 0.5;
      this.items.push({
        x: x + (Math.random() - 0.5) * spread,
        y: y + (Math.random() - 0.5) * spread,
        z: z + (Math.random() - 0.5) * spread,
        vx: Math.cos(angle) * Math.random() * speed,
        vy: Math.random() * speed * 0.8,
        vz: Math.sin(angle) * Math.random() * speed,
        life,
        maxLife: life,
        r: tint.r,
        g: tint.g,
        b: tint.b
      });
    }
  }

  update(dt) {
    const alive = [];
    for (const particle of this.items) {
      particle.life -= dt;
      if (particle.life <= 0) continue;
      particle.vy -= 6 * dt;
      particle.x += particle.vx * dt;
      particle.y += particle.vy * dt;
      particle.z += particle.vz * dt;
      particle.vx *= 0.98;
      particle.vz *= 0.98;
      alive.push(particle);
    }
    this.items = alive;
    const count = Math.min(this.items.length, this.max);
    for (let i = 0; i < count; i++) {
      const particle = this.items[i];
      const fade = Math.max(0, particle.life / particle.maxLife);
      this.positions[i * 3] = particle.x;
      this.positions[i * 3 + 1] = particle.y;
      this.positions[i * 3 + 2] = particle.z;
      this.colors[i * 3] = particle.r * fade;
      this.colors[i * 3 + 1] = particle.g * fade;
      this.colors[i * 3 + 2] = particle.b * fade;
    }
    this.positionAttribute.needsUpdate = true;
    this.colorAttribute.needsUpdate = true;
    this.geometry.setDrawRange(0, count);
  }

  reset() {
    this.items = [];
    this.geometry.setDrawRange(0, 0);
  }
}

class Projectiles {
  constructor(targetScene, getWorld, onImpact) {
    this.scene = targetScene;
    this.getWorld = getWorld;
    this.onImpact = onImpact;
    this.items = [];
    this.geometries = {
      bubble: new THREE.SphereGeometry(0.16, 10, 8),
      star: new THREE.OctahedronGeometry(0.18, 0),
      arrow: new THREE.ConeGeometry(0.1, 0.5, 6)
    };
  }

  spawn({ x, y, z, dx, dy, dz, kind = 'star', color = '#ffd76a', speed = 14, life = 1.4, gravity = 0, scale = 1 }) {
    const direction = new THREE.Vector3(dx, dy, dz);
    if (direction.lengthSq() === 0) direction.set(0, 0, -1);
    direction.normalize();
    const material = new THREE.MeshBasicMaterial({ color, transparent: true, opacity: 0.95 });
    const mesh = new THREE.Mesh(this.geometries[kind] || this.geometries.star, material);
    mesh.position.set(x, y, z);
    mesh.scale.setScalar(scale);
    if (kind === 'arrow') mesh.rotation.x = Math.PI / 2;
    this.scene.add(mesh);
    this.items.push({
      mesh,
      material,
      velocity: direction.multiplyScalar(speed),
      life,
      maxLife: life,
      gravity,
      kind,
      color
    });
  }

  update(dt) {
    const world = this.getWorld?.();
    const alive = [];
    for (const item of this.items) {
      item.life -= dt;
      item.velocity.y -= item.gravity * dt;
      item.mesh.position.addScaledVector(item.velocity, dt);
      item.mesh.rotation.x += dt * 5;
      item.mesh.rotation.y += dt * 7;
      const p = item.mesh.position;
      const hitBlock = world?.isSolid(Math.floor(p.x), Math.floor(p.y), Math.floor(p.z));
      if (item.life <= 0 || hitBlock || p.y < -2) {
        this.impact(item);
        continue;
      }
      alive.push(item);
    }
    this.items = alive;
  }

  impact(item) {
    this.scene.remove(item.mesh);
    item.material.dispose();
    this.onImpact?.({
      x: item.mesh.position.x,
      y: item.mesh.position.y,
      z: item.mesh.position.z,
      kind: item.kind,
      color: item.color
    });
  }

  reset() {
    for (const item of this.items) {
      this.scene.remove(item.mesh);
      item.material.dispose();
    }
    this.items = [];
  }
}

const particles = new Particles(scene);
const audio = new AudioEngine();
const projectiles = new Projectiles(scene, () => world, (impact) => {
  const count = impact.kind === 'bubble' ? 6 : impact.kind === 'arrow' ? 14 : 18;
  particles.burst(impact.x, impact.y, impact.z, impact.color, count, 0.35, impact.kind === 'bubble' ? 1.1 : 2.4);
  audio.pop();
});

const EMPTY_INPUT = {
  forward: false,
  back: false,
  left: false,
  right: false,
  jump: false,
  jumpPressed: false,
  sprint: false
};

const DEFAULT_STATS = {
  blocksBroken: 0,
  blocksPlaced: 0,
  crafted: 0,
  lumiCollected: 0,
  biomesVisited: [],
  crownFound: false,
  dailyLumi: 0,
  dailyDone: false
};

let ui = null;
let multiplayer = null;
let world = null;
let player = null;
let inventory = null;
let currentWorld = null;
let settings = getSettings();
settings.character = normalizeCharacter(settings.character);

let running = false;
let ready = false;
let paused = false;
let uiOpen = false;
let photoMode = false;
let starting = false;
let thirdPerson = false;
let localAvatar = null;
let companion = null;
let npcs = [];
let nearestNpc = null;
let lastNpcPromptId = null;
let avatarCraftTimer = 0;
let petBubbleTimer = 0;
let graphicsQuality = settings.quality || 'auto';

let stats = { ...DEFAULT_STATS };
let achievements = {};
let discovered = new Set();
let timeOfDay = 0.3;
let playtimeBase = 0;
let playtimeSession = 0;
let auroraTimer = 0;
let lastBiome = null;
let charmTimer = 0;

const chunkMeshes = new Map();
const keyState = new Set();
const touchMove = { forward: 0, strafe: 0, sprint: false, jump: false };
let jumpQueued = false;
let mouseLeft = false;
let mouseRight = false;
let actionCooldown = 0;
let chunkAccum = 0;
let hudAccum = 0;
let autosaveAccum = 0;
let lastTime = performance.now();
let lookPointerId = null;
let lookLastX = 0;
let lookLastY = 0;

function nextFrame() {
  return new Promise((resolve) => requestAnimationFrame(() => resolve()));
}

function requestLock() {
  if (!running || !ready || uiOpen || photoMode || isTouch) return;
  if (document.pointerLockElement === canvas) return;
  try {
    const request = canvas.requestPointerLock?.();
    if (request && typeof request.catch === 'function') request.catch(() => {});
  } catch {
    return;
  }
}

function clearHeldInputs() {
  keyState.clear();
  mouseLeft = false;
  mouseRight = false;
  jumpQueued = false;
  touchMove.forward = 0;
  touchMove.strafe = 0;
  touchMove.sprint = false;
  touchMove.jump = false;
  const knob = $('joystick-knob');
  if (knob) knob.style.transform = 'translate(0px, 0px)';
}

function desiredChunks(pcx, pcz) {
  const list = [];
  for (let dx = -RENDER_RADIUS; dx <= RENDER_RADIUS; dx++) {
    for (let dz = -RENDER_RADIUS; dz <= RENDER_RADIUS; dz++) {
      const distance = dx * dx + dz * dz;
      if (distance > RENDER_RADIUS * RENDER_RADIUS + RENDER_RADIUS) continue;
      list.push({ cx: pcx + dx, cz: pcz + dz, distance });
    }
  }
  return list.sort((a, b) => a.distance - b.distance);
}

function createChunkMeshes(chunk) {
  const geometries = buildChunkGeometries(world, chunk);
  const group = new THREE.Group();
  group.position.set(chunk.cx * CHUNK_SIZE, 0, chunk.cz * CHUNK_SIZE);
  group.userData.chunkKey = chunk.key;

  const addMesh = (geometry, material, renderOrder = 0) => {
    if (!geometry) return;
    const mesh = new THREE.Mesh(geometry, material);
    mesh.castShadow = false;
    mesh.receiveShadow = false;
    mesh.renderOrder = renderOrder;
    group.add(mesh);
  };

  addMesh(geometries.solid, materials.solid);
  addMesh(geometries.glow, materials.glow);
  addMesh(geometries.plants, materials.plants);
  addMesh(geometries.water, materials.water, 2);

  scene.add(group);
  chunkMeshes.set(chunk.key, { chunk, group, geometries });
  chunk.dirty = false;
}

function removeChunkMeshes(key) {
  const entry = chunkMeshes.get(key);
  if (!entry) return;
  scene.remove(entry.group);
  for (const mesh of entry.group.children) {
    if (mesh.geometry) mesh.geometry.dispose();
  }
  chunkMeshes.delete(key);
}

function rebuildChunkByKey(key) {
  const entry = chunkMeshes.get(key);
  if (!entry || !entry.chunk.dirty) return;
  removeChunkMeshes(key);
  createChunkMeshes(entry.chunk);
}

function rebuildChunkAt(wx, wz) {
  rebuildChunkByKey(chunkKey(Math.floor(wx / CHUNK_SIZE), Math.floor(wz / CHUNK_SIZE)));
}

function refreshChunksNearby() {
  if (!world || !player) return;
  const pcx = Math.floor(player.position.x / CHUNK_SIZE);
  const pcz = Math.floor(player.position.z / CHUNK_SIZE);

  for (const [key, entry] of [...chunkMeshes.entries()]) {
    const distance = Math.max(Math.abs(entry.chunk.cx - pcx), Math.abs(entry.chunk.cz - pcz));
    if (distance > RENDER_RADIUS + 1) {
      removeChunkMeshes(key);
      world.chunks.delete(key);
    }
  }

  for (const key of [...world.chunks.keys()]) {
    if (chunkMeshes.has(key)) continue;
    const chunk = world.chunks.get(key);
    if (!chunk) continue;
    const distance = Math.max(Math.abs(chunk.cx - pcx), Math.abs(chunk.cz - pcz));
    if (distance > RENDER_RADIUS + 2) world.chunks.delete(key);
  }

  const list = desiredChunks(pcx, pcz);
  let built = 0;
  for (const { cx, cz } of list) {
    if (built >= 2) break;
    const chunk = world.getChunk(cx, cz);
    if (!chunkMeshes.has(chunk.key)) {
      createChunkMeshes(chunk);
      built += 1;
    }
  }

  let rebuilt = 0;
  for (const [key, entry] of [...chunkMeshes.entries()]) {
    if (rebuilt >= 2) break;
    if (entry.chunk.dirty) {
      rebuildChunkByKey(key);
      rebuilt += 1;
    }
  }
}

async function preloadChunks() {
  const pcx = Math.floor(player.position.x / CHUNK_SIZE);
  const pcz = Math.floor(player.position.z / CHUNK_SIZE);
  const list = desiredChunks(pcx, pcz);
  for (let i = 0; i < list.length; i++) {
    const { cx, cz } = list[i];
    const chunk = world.getChunk(cx, cz);
    if (!chunkMeshes.has(chunk.key)) createChunkMeshes(chunk);
    if (i % 4 === 0) {
      ui.setLoading(10 + (84 * (i + 1)) / list.length, 'Construindo o mundo…');
      await nextFrame();
    }
  }
}

function snapshotFromEntry(entry) {
  return {
    version: 1,
    id: entry.id,
    name: entry.name,
    seed: entry.seed,
    mode: entry.mode,
    createdAt: entry.createdAt,
    updatedAt: entry.updatedAt,
    playtime: entry.playtime || 0,
    timeOfDay: 0.32,
    player: { x: 0, y: 24, z: 0, yaw: 0, pitch: 0, fly: false },
    inventory: {
      hotbar: new Array(9).fill(null),
      backpack: new Array(27).fill(null),
      pouch: new Array(9).fill(null),
      selected: 0
    },
    chests: {},
    diffs: {},
    discovered: [],
    stats: { ...DEFAULT_STATS },
    achievements: {},
    crownPlaced: false,
    biome: 'meadow'
  };
}

function ensureSafePosition() {
  if (!player || !world) return;
  let guard = 0;
  while (player.collidesAt(player.position.x, player.position.y, player.position.z) && guard < 24) {
    player.position.y += 1;
    guard += 1;
  }
  if (guard >= 24) {
    const spawn = world.findSpawn();
    player.setPosition(spawn.x, spawn.y + 0.05, spawn.z);
  }
}

function currentBiomeId() {
  if (!world || !player) return 'meadow';
  return world.biomeAt(Math.floor(player.position.x), Math.floor(player.position.z));
}

function isDailyWorld() {
  return currentWorld?.name === 'Mundo do Dia';
}

function rareDiscoveredCount() {
  let total = 0;
  for (const id of discovered) if (isRare(id)) total += 1;
  return total;
}

function clockInfo() {
  if (timeOfDay < 0.2) return { label: 'Noite', icon: '☾' };
  if (timeOfDay < 0.3) return { label: 'Amanhecer', icon: '◐' };
  if (timeOfDay < 0.7) return { label: 'Dia', icon: '☀' };
  if (timeOfDay < 0.8) return { label: 'Entardecer', icon: '◑' };
  return { label: 'Noite', icon: '☾' };
}

function isNightTime() {
  return Math.max(0, Math.sin((timeOfDay - 0.25) * Math.PI * 2)) < 0.18;
}

function updateDayNight(dt) {
  if (!world || !player) return;
  timeOfDay = (timeOfDay + dt / DAY_LENGTH_SECONDS) % 1;
  const angle = (timeOfDay - 0.25) * Math.PI * 2;
  const height = Math.sin(angle);
  const daylight = Math.max(0, height);

  sun.position.set(
    player.position.x + Math.cos(angle) * 80,
    player.position.y + height * 80,
    player.position.z + 40
  );
  sun.target.position.copy(player.position);
  sun.target.updateMatrixWorld();
  sun.intensity = 0.16 + daylight * 1.18;
  hemi.intensity = 0.3 + daylight * 0.68;
  ambient.intensity = 0.11 + daylight * 0.18;

  const horizon = Math.max(0, 1 - Math.abs(height) * 3.2);
  const sky = SKY_NIGHT.clone().lerp(SKY_DAY, Math.min(1, daylight * 1.4));
  sky.lerp(SKY_DUSK, horizon * 0.55);
  if (auroraTimer > 0) {
    sky.lerp(SKY_AURORA, Math.min(0.7, (auroraTimer / 14) * 0.7));
    auroraTimer = Math.max(0, auroraTimer - dt);
  }
  scene.background.copy(sky);
  scene.fog.color.copy(sky);

  stars.position.copy(camera.position);
  starMaterial.opacity = Math.max(0, 1 - daylight * 2.6);
}

function checkAchievements() {
  if (!ui) return;
  const rare = rareDiscoveredCount();
  const list = [
    { id: 'first_break', name: 'Primeiro Brilho', desc: 'Quebre seu primeiro bloco.', test: () => (stats.blocksBroken || 0) >= 1 },
    { id: 'gardener', name: 'Jardineiro', desc: 'Coloque 20 blocos no mundo.', test: () => (stats.blocksPlaced || 0) >= 20 },
    { id: 'collector', name: 'Colecionador', desc: 'Descubra 10 itens diferentes.', test: () => discovered.size >= 10 },
    { id: 'rare_hunter', name: 'Caçador de Raros', desc: 'Encontre 3 itens raros.', test: () => rare >= 3 },
    { id: 'architect', name: 'Arquiteto', desc: 'Coloque 100 blocos no mundo.', test: () => (stats.blocksPlaced || 0) >= 100 },
    { id: 'explorer', name: 'Explorador', desc: 'Visite os 5 biomas.', test: () => (stats.biomesVisited || []).length >= 5 },
    { id: 'forger', name: 'Forjador', desc: 'Crie 5 itens na forja.', test: () => (stats.crafted || 0) >= 5 },
    { id: 'crowned', name: 'Coroado', desc: 'Encontre a Coroa de Lumi.', test: () => !!stats.crownFound, rarity: 'mitico' }
  ];
  for (const achievement of list) {
    if (achievements[achievement.id]) continue;
    if (!achievement.test()) continue;
    achievements[achievement.id] = true;
    audio.achievement();
    ui.toast({
      title: `Conquista: ${achievement.name}`,
      body: achievement.desc,
      rarity: achievement.rarity || 'lendario'
    });
  }
}

function discoverItem(id, count = 1, quiet = false) {
  const item = getItem(id);
  const key = String(id);
  const isNew = !discovered.has(key);
  discovered.add(key);
  currentWorld.discovered = [...discovered];
  ui.setState({ world: currentWorld });

  if (id === 'lumi_shard') {
    stats.lumiCollected = (stats.lumiCollected || 0) + count;
    if (isDailyWorld()) {
      stats.dailyLumi = (stats.dailyLumi || 0) + count;
      const daily = getDaily();
      const done = stats.dailyDone || stats.dailyLumi >= 5;
      if (done && !stats.dailyDone) {
        stats.dailyDone = true;
        ui.toast({
          title: 'Desafio do dia concluído!',
          body: 'Você coletou 5 Fragmentos de Lumi.',
          rarity: 'lendario'
        });
        audio.achievement();
      }
      saveDaily({ date: daily.date, seed: daily.seed, progress: Math.min(5, stats.dailyLumi), done });
    }
  }

  if (id === 'crown_lumi') {
    stats.crownFound = true;
    auroraTimer = 14;
    if (!quiet) {
      ui.toast({
        title: 'A Coroa de Lumi!',
        body: 'O tesouro mais raro do mundo é seu.',
        rarity: 'mitico',
        icon: getItemIcon(id)
      });
      audio.rare('mitico');
      const eye = player.eyePosition();
      particles.burst(eye.x, eye.y, eye.z, '#ff7ad9', 36, 1.2, 3.2);
    }
  }

  if (isNew) {
    if (isRare(id)) {
      const rarity = getRarity(id);
      audio.rare(rarity.key);
      if (player) {
        const eye = player.eyePosition();
        particles.burst(eye.x, eye.y, eye.z, rarity.color, 22, 1, 2.6);
      }
      if (!quiet) {
        ui.toast({
          title: item.name,
          body: item.desc,
          rarity: item.rarity,
          icon: getItemIcon(id)
        });
      }
    } else if (!quiet && item.category !== 'bloco') {
      ui.toast({
        title: 'Nova descoberta',
        body: item.name,
        rarity: item.rarity,
        icon: getItemIcon(id)
      });
    }
  }

  checkAchievements();
  updateHud(true);
}

function acquireDrops(drops) {
  if (!inventory || !drops.length) return;
  let overflow = 0;
  for (const drop of drops) {
    const left = inventory.addItem(drop.id, drop.count);
    const added = drop.count - left;
    if (added > 0) {
      discoverItem(drop.id, added);
      audio.pickup(getRarity(drop.id).key);
      ui.showPickup(drop.id, added);
    }
    if (left > 0) overflow += left;
  }
  if (overflow > 0) {
    ui.toast({ title: 'Mochila cheia', body: 'Alguns itens ficaram para trás.', rarity: 'comum' });
  }
  player.refreshAbilities(inventory);
  ui.renderHotbar(inventory, currentWorld?.mode === 'creative');
  updateHud(true);
}

function overlapsPlayer(x, y, z) {
  if (!player) return false;
  const half = 0.31;
  const minX = player.position.x - half;
  const maxX = player.position.x + half;
  const minY = player.position.y;
  const maxY = player.position.y + 1.72;
  const minZ = player.position.z - half;
  const maxZ = player.position.z + half;
  return maxX > x && minX < x + 1 && maxY > y && minY < y + 1 && maxZ > z && minZ < z + 1;
}

function openChestAt(x, y, z) {
  if (!world || !inventory) return;
  const key = chestKey(x, y, z);
  const items = world.getChest(x, y, z);
  ui.openChest(items, key, inventory);
  audio.open();
}

function eatItem(id) {
  const effects = {
    star_soup: [['speed', 20], ['jump', 20]],
    berry: [['speed', 12]],
    moon_berry: [['jump', 12]],
    rainbow_berry: [['speed', 20]],
    star_cookie: [['speed', 10], ['jump', 10]],
    cloud_candy: [['jump', 18]],
    star_mushroom: [['jump', 15]]
  };
  const list = effects[id];
  if (!list) return;
  for (const [buff, seconds] of list) player.applyBuff(buff, seconds);
  if (currentWorld.mode !== 'creative') inventory.removeOneSelected();
  player.refreshAbilities(inventory);
  ui.renderHotbar(inventory, currentWorld.mode === 'creative');
  audio.eat();
  ui.swingHand();
  petReact('treat');
  const labels = list.map(([buff]) => (buff === 'speed' ? 'Velocidade' : 'Salto alto'));
  ui.toast({
    title: 'Energia de Lumi!',
    body: `${labels.join(' + ')} ativado.`,
    rarity: getItem(id).rarity,
    icon: getItemIcon(id)
  });
  updateHud(true);
}

function useWeapon(item, hit) {
  if (!player) return;
  ui.swingHand();
  const kind = item.weapon?.kind || 'star';
  const skin = getWeaponSkin(settings.character);
  const origin = player.eyePosition();
  const aim = player.direction();
  const start = {
    x: origin.x + aim.x * 0.8,
    y: origin.y + aim.y * 0.8 - 0.12,
    z: origin.z + aim.z * 0.8
  };
  const target = hit ? { x: hit.x + 0.5, y: hit.y + 0.5, z: hit.z + 0.5 } : null;

  if (kind === 'petal') {
    player.applyBuff('speed', 8);
    const p = target || { x: start.x + aim.x * 2, y: start.y + aim.y * 2, z: start.z + aim.z * 2 };
    particles.burst(p.x, p.y, p.z, skin.color, 26, 0.9, 2.6);
    particles.burst(p.x, p.y, p.z, '#ffb3c7', 10, 0.5, 1.8);
    audio.weapon('petal');
    ui.showHint('Lâmina de Pétalas: velocidade ativada por 8 s.', 2200);
    updateHud(true);
    return;
  }

  if (kind === 'hammer') {
    player.applyBuff('jump', 10);
    const p = target || { x: start.x + aim.x * 1.8, y: start.y + aim.y * 1.8, z: start.z + aim.z * 1.8 };
    particles.burst(p.x, p.y, p.z, skin.color, 34, 1.1, 3.2);
    particles.burst(p.x, p.y, p.z, '#fff1b8', 12, 0.4, 1.6);
    audio.weapon('hammer');
    ui.showHint('Martelo Solar: salto alto ativado por 10 s.', 2200);
    updateHud(true);
    return;
  }

  if (kind === 'bubble') {
    for (let i = 0; i < 5; i++) {
      const spread = 0.18;
      projectiles.spawn({
        ...start,
        dx: aim.x + (Math.random() - 0.5) * spread,
        dy: aim.y + Math.random() * 0.16 + 0.05,
        dz: aim.z + (Math.random() - 0.5) * spread,
        kind: 'bubble',
        color: i % 2 ? '#b8e7ff' : skin.color,
        speed: 5.5 + Math.random() * 2,
        life: 2.4,
        gravity: -1.1,
        scale: 1.1 + Math.random() * 0.7
      });
    }
    audio.weapon('bubble');
    return;
  }

  if (kind === 'bow') {
    projectiles.spawn({
      ...start,
      dx: aim.x,
      dy: aim.y,
      dz: aim.z,
      kind: 'arrow',
      color: skin.color,
      speed: 23,
      life: 1.8,
      scale: 1.15
    });
    audio.weapon('bow');
    return;
  }

  projectiles.spawn({
    ...start,
    dx: aim.x,
    dy: aim.y,
    dz: aim.z,
    kind: 'star',
    color: skin.color,
    speed: 16,
    life: 1.5,
    gravity: 8,
    scale: 1.2
  });
  audio.weapon('star');
}

function plantSeed(id, hit) {
  const target = { x: hit.x + hit.normal[0], y: hit.y + hit.normal[1], z: hit.z + hit.normal[2] };
  const below = world.getBlock(target.x, target.y - 1, target.z);
  const soil = below === B.GRASS || below === B.DIRT || below === B.SNOW || below === B.SAND;
  if (!soil || world.getBlock(target.x, target.y, target.z) !== B.AIR) {
    ui.toast({ title: 'Solo mágico', body: 'Plante sobre grama, terra, neve ou areia, com espaço livre.', rarity: 'comum' });
    return;
  }
  if (!world.growRainbowTree(target.x, target.y, target.z)) {
    ui.toast({ title: 'Precisa de mais espaço', body: 'A árvore arco-íris cresce alto. Tente um lugar mais aberto.', rarity: 'comum' });
    return;
  }
  sendTreePatch(target.x, target.z);
  if (currentWorld.mode !== 'creative') inventory.removeOneSelected();
  player.refreshAbilities(inventory);
  ui.renderHotbar(inventory, currentWorld.mode === 'creative');
  audio.craft('raro');
  avatarCraftTimer = 0.55;
  ui.swingHand('craft');
  petReact('plant');
  particles.burst(target.x + 0.5, target.y + 1, target.z + 0.5, '#ffb3c7', 28, 1.4, 3);
  ui.toast({
    title: 'Árvore arco-íris!',
    body: 'Uma árvore de cores nasceu no seu mundo.',
    rarity: 'raro',
    icon: getItemIcon(id)
  });
  checkAchievements();
  updateHud(true);
}

function tryBreak() {
  if (!running || !ready || uiOpen || photoMode || !player || !inventory) return;
  const hit = player.raycast();
  if (!hit) return;

  const targets = [];
  const selected = inventory.selectedSlot();
  if (selected?.id === 'aurora_pickaxe' || selected?.id === 'sun_hammer') {
    for (let dx = -1; dx <= 1; dx++) {
      for (let dy = -1; dy <= 1; dy++) {
        for (let dz = -1; dz <= 1; dz++) {
          targets.push({ x: hit.x + dx, y: hit.y + dy, z: hit.z + dz, normal: [0, 0, 0] });
        }
      }
    }
  } else {
    targets.push(hit);
  }

  const drops = [];
  const touched = new Set();
  let broke = 0;

  for (const target of targets) {
    const id = world.getBlock(target.x, target.y, target.z);
    if (id === B.AIR || id === B.WATER || id === B.BEDROCK) continue;

    if (id === B.CHEST) {
      const key = chestKey(target.x, target.y, target.z);
      const items = (world.chests.get(key) || []).filter(Boolean);
      if (items.length > 0 && !inventory.canAddMany(items)) {
        ui.toast({ title: 'Esvazie o baú primeiro', body: 'Sua mochila precisa de espaço para guardar o conteúdo.', rarity: 'comum' });
        continue;
      }
      if (items.length > 0) {
        inventory.addMany(items);
        for (const item of items) discoverItem(item.id, item.count, true);
      }
      world.deleteChest(target.x, target.y, target.z);
      multiplayer?.sendChest(target.x, target.y, target.z, null);
    }

    const block = getBlock(id);
    world.setBlock(target.x, target.y, target.z, B.AIR);
    multiplayer?.sendBlock(target.x, target.y, target.z, B.AIR);
    if (currentWorld.mode !== 'creative') drops.push(...rollBlockDrops(id));
    particles.burst(target.x + 0.5, target.y + 0.5, target.z + 0.5, block.color || '#ffffff', 8, 0.55, 2.2);
    touched.add(chunkKey(Math.floor(target.x / CHUNK_SIZE), Math.floor(target.z / CHUNK_SIZE)));
    stats.blocksBroken = (stats.blocksBroken || 0) + 1;
    broke += 1;
  }

  if (broke === 0) return;
  ui.swingHand();
  for (const key of touched) rebuildChunkByKey(key);
  if (drops.length > 0) acquireDrops(drops);
  audio.breakBlock(getBlock(hit.block));
  checkAchievements();
  updateHud(true);
}

function tryPlace() {
  if (!running || !ready || uiOpen || photoMode || !player || !inventory) return;
  const slot = inventory.selectedSlot();
  if (!slot) return;
  const item = getItem(slot.id);
  if (item.id === 'pet_treat') {
    feedPet();
    return;
  }
  const hit = player.raycast();
  if (item.category === 'arma') {
    useWeapon(item, hit);
    return;
  }
  if (!hit) return;

  if (hit.block === B.CHEST) {
    openChestAt(hit.x, hit.y, hit.z);
    return;
  }

  if (item.id === 'prism_seed' || item.id === 'rainbow_sapling') {
    plantSeed(item.id, hit);
    return;
  }

  if (item.category === 'alimento' && !item.place) {
    eatItem(item.id);
    return;
  }

  if (!item.place || item.blockId === undefined) {
    ui.toast({ title: item.name, body: item.desc, rarity: item.rarity, icon: getItemIcon(slot.id) });
    return;
  }

  const target = { x: hit.x + hit.normal[0], y: hit.y + hit.normal[1], z: hit.z + hit.normal[2] };
  const existing = world.getBlock(target.x, target.y, target.z);
  if (existing !== B.AIR && existing !== B.WATER && !isPlantBlock(existing)) return;
  if (isSolidBlock(item.blockId) && overlapsPlayer(target.x, target.y, target.z)) {
    ui.toast({ title: 'Espaço ocupado', body: 'Saia da frente para colocar o bloco.', rarity: 'comum' });
    return;
  }

  if (!world.setBlock(target.x, target.y, target.z, item.blockId)) return;
  multiplayer?.sendBlock(target.x, target.y, target.z, item.blockId);
  if (item.blockId === B.CHEST) {
    world.setChest(target.x, target.y, target.z, []);
    multiplayer?.sendChest(target.x, target.y, target.z, []);
  }
  if (currentWorld.mode !== 'creative') inventory.removeOneSelected();
  player.refreshAbilities(inventory);
  ui.renderHotbar(inventory, currentWorld.mode === 'creative');
  audio.placeBlock();
  ui.swingHand();
  particles.burst(target.x + 0.5, target.y + 0.5, target.z + 0.5, getBlock(item.blockId).color || '#ffffff', 6, 0.4, 1.2);
  stats.blocksPlaced = (stats.blocksPlaced || 0) + 1;
  rebuildChunkAt(target.x, target.z);
  checkAchievements();
  updateHud(true);
}

function updateHud(force = false) {
  if (!ui || !player || !inventory || !world || !currentWorld) return;
  const biomeId = currentBiomeId();
  if (biomeId !== lastBiome) {
    lastBiome = biomeId;
    if (!stats.biomesVisited.includes(biomeId)) {
      stats.biomesVisited.push(biomeId);
      checkAchievements();
    }
  }

  const daily = isDailyWorld();
  const questValue = daily ? stats.dailyLumi || 0 : stats.lumiCollected || 0;
  const questDone = daily ? stats.dailyDone || questValue >= 5 : questValue >= 5;
  const clock = clockInfo();
  const buffs = [];
  if (player.buffActive('speed')) buffs.push({ label: 'Velocidade', seconds: player.buffRemaining('speed') });
  if (player.buffActive('jump')) buffs.push({ label: 'Salto', seconds: player.buffRemaining('jump') });

  ui.renderHUD({
    worldName: currentWorld.name,
    biome: BIOMES[biomeId]?.name || 'Prado Musgo',
    lumi: inventory.countItem('lumi_shard'),
    clock: clock.label,
    clockIcon: clock.icon,
    quest: questDone ? 'Desafio concluído! ✦' : 'Colete 5 Fragmentos de Lumi',
    questProgress: `${Math.min(5, questValue)}/5`,
    buffs,
    coords: player.position,
    showCoords: !!settings.showCoords
  });
}

const remoteAvatars = new Map();

function makeNameTag(name) {
  const canvas = document.createElement('canvas');
  canvas.width = 256;
  canvas.height = 64;
  const ctx = canvas.getContext('2d');
  ctx.clearRect(0, 0, canvas.width, canvas.height);
  ctx.fillStyle = 'rgba(11, 20, 31, 0.72)';
  ctx.beginPath();
  ctx.roundRect?.(12, 10, 232, 44, 14);
  if (ctx.roundRect) ctx.fill();
  else ctx.fillRect(12, 10, 232, 44);
  ctx.fillStyle = '#f7f3e8';
  ctx.font = '800 28px "Nunito", sans-serif';
  ctx.textAlign = 'center';
  ctx.textBaseline = 'middle';
  ctx.fillText(String(name || 'Explorador').slice(0, 16), 128, 33);
  const texture = new THREE.CanvasTexture(canvas);
  texture.colorSpace = THREE.SRGBColorSpace;
  texture.minFilter = THREE.LinearFilter;
  const material = new THREE.SpriteMaterial({ map: texture, transparent: true, depthTest: false, depthWrite: false });
  const sprite = new THREE.Sprite(material);
  sprite.scale.set(2.4, 0.6, 1);
  sprite.position.y = 2.35;
  return sprite;
}

function disposeAvatar(avatar) {
  avatar.traverse((child) => {
    if (child.geometry) child.geometry.dispose?.();
    if (child.material) {
      if (child.material.map) child.material.map.dispose?.();
      child.material.dispose?.();
    }
  });
}

function createAvatarModel(characterInput, name, { tag = true } = {}) {
  const group = new THREE.Group();
  const material = (color) => new THREE.MeshStandardMaterial({ color, roughness: 0.86, metalness: 0.02 });
  const shade = (color, amount) => {
    const value = new THREE.Color(color);
    value.offsetHSL(0, 0, amount);
    return `#${value.getHexString()}`;
  };
  const addBox = (parent, width, height, depth, color, x, y, z) => {
    const mesh = new THREE.Mesh(new THREE.BoxGeometry(width, height, depth), material(color));
    mesh.position.set(x, y, z);
    parent.add(mesh);
    return mesh;
  };
  const character = normalizeCharacter(characterInput);

  addBox(group, 0.62, 0.72, 0.34, character.shirt, 0, 1.12, 0);
  addBox(group, 0.64, 0.08, 0.36, shade(character.shirt, -0.18), 0, 0.83, 0);
  addBox(group, 0.34, 0.06, 0.36, shade(character.shirt, 0.16), 0, 1.44, 0);
  addBox(group, 0.1, 0.16, 0.02, shade(character.shirt, -0.1), -0.18, 1.2, 0.181);
  addBox(group, 0.1, 0.16, 0.02, shade(character.shirt, -0.1), 0.18, 1.2, 0.181);

  addBox(group, 0.5, 0.5, 0.5, character.skin, 0, 1.72, 0);
  addBox(group, 0.54, 0.14, 0.54, character.hair, 0, 2.0, 0);
  addBox(group, 0.08, 0.3, 0.5, character.hair, -0.27, 1.76, 0);
  addBox(group, 0.08, 0.3, 0.5, character.hair, 0.27, 1.76, 0);
  addBox(group, 0.5, 0.08, 0.06, character.hair, 0, 1.98, 0.28);
  addBox(group, 0.09, 0.1, 0.02, '#f7f3e8', -0.12, 1.78, 0.256);
  addBox(group, 0.09, 0.1, 0.02, '#f7f3e8', 0.12, 1.78, 0.256);
  addBox(group, 0.045, 0.07, 0.025, '#1b2430', -0.1, 1.78, 0.268);
  addBox(group, 0.045, 0.07, 0.025, '#1b2430', 0.1, 1.78, 0.268);
  addBox(group, 0.14, 0.03, 0.03, '#7a3b4a', 0, 1.61, 0.262);

  const armPivot = (x) => {
    const pivot = new THREE.Group();
    pivot.position.set(x, 1.44, 0);
    group.add(pivot);
    addBox(pivot, 0.16, 0.5, 0.2, character.shirt, 0, -0.25, 0);
    addBox(pivot, 0.15, 0.12, 0.18, character.skin, 0, -0.55, 0);
    return pivot;
  };
  const legPivot = (x) => {
    const pivot = new THREE.Group();
    pivot.position.set(x, 0.74, 0);
    group.add(pivot);
    addBox(pivot, 0.22, 0.55, 0.24, character.pants, 0, -0.3, 0);
    addBox(pivot, 0.24, 0.12, 0.3, '#24344f', 0, -0.62, 0.03);
    return pivot;
  };
  const leftArm = armPivot(-0.4);
  const rightArm = armPivot(0.4);
  const leftLeg = legPivot(-0.16);
  const rightLeg = legPivot(0.16);

  if (character.accessory === 'leaf') addBox(group, 0.26, 0.1, 0.18, '#6fd13c', 0.16, 2.1, 0);
  else if (character.accessory === 'star') addBox(group, 0.18, 0.18, 0.1, '#ffc94d', 0, 2.12, 0.02);
  else if (character.accessory === 'flower') addBox(group, 0.16, 0.16, 0.16, '#ff7ad9', 0.16, 2.1, 0);
  else if (character.accessory === 'goggles') addBox(group, 0.56, 0.1, 0.08, '#7cd6ff', 0, 1.78, 0.27);
  else if (character.accessory === 'bandana') addBox(group, 0.56, 0.1, 0.56, '#ff8a6b', 0, 1.94, 0);

  group.userData.limbs = { leftArm, rightArm, leftLeg, rightLeg };
  group.userData.character = character;
  if (tag) group.add(makeNameTag(name));
  return group;
}

function createPetModel(characterInput) {
  const character = normalizeCharacter(characterInput);
  const pet = getPet(character);
  const base = character.petColor || pet.color;
  const group = new THREE.Group();
  const material = (color) => new THREE.MeshStandardMaterial({ color, roughness: 0.9, metalness: 0.02 });
  const shade = (color, amount) => {
    const value = new THREE.Color(color);
    value.offsetHSL(0, 0, amount);
    return `#${value.getHexString()}`;
  };
  const addBox = (parent, width, height, depth, color, x, y, z) => {
    const mesh = new THREE.Mesh(new THREE.BoxGeometry(width, height, depth), material(color));
    mesh.position.set(x, y, z);
    parent.add(mesh);
    return mesh;
  };

  const dark = shade(base, -0.2);
  const light = shade(base, 0.18);
  addBox(group, 0.4, 0.34, 0.5, base, 0, 0.34, 0);
  addBox(group, 0.3, 0.26, 0.28, light, 0, 0.56, 0.22);
  addBox(group, 0.08, 0.08, 0.03, '#17231f', -0.08, 0.59, 0.37);
  addBox(group, 0.08, 0.08, 0.03, '#17231f', 0.08, 0.59, 0.37);
  addBox(group, 0.05, 0.03, 0.02, '#fff7e6', -0.075, 0.61, 0.386);
  addBox(group, 0.05, 0.03, 0.02, '#fff7e6', 0.075, 0.61, 0.386);
  addBox(group, 0.06, 0.04, 0.03, '#7a3b4a', 0, 0.5, 0.38);

  const ears = [];
  if (character.petType === 'coelho') {
    ears.push(addBox(group, 0.09, 0.34, 0.08, base, -0.09, 0.84, 0.18));
    ears.push(addBox(group, 0.09, 0.34, 0.08, base, 0.09, 0.84, 0.18));
    ears.push(addBox(group, 0.05, 0.2, 0.02, '#ffb3c7', -0.09, 0.84, 0.225));
    ears.push(addBox(group, 0.05, 0.2, 0.02, '#ffb3c7', 0.09, 0.84, 0.225));
  } else if (character.petType === 'coruja') {
    ears.push(addBox(group, 0.1, 0.12, 0.08, dark, -0.12, 0.74, 0.18));
    ears.push(addBox(group, 0.1, 0.12, 0.08, dark, 0.12, 0.74, 0.18));
    addBox(group, 0.06, 0.22, 0.3, light, -0.22, 0.38, 0);
    addBox(group, 0.06, 0.22, 0.3, light, 0.22, 0.38, 0);
  } else {
    ears.push(addBox(group, 0.12, 0.13, 0.08, dark, -0.12, 0.73, 0.18));
    ears.push(addBox(group, 0.12, 0.13, 0.08, dark, 0.12, 0.73, 0.18));
    addBox(group, 0.09, 0.08, 0.04, '#ffb3c7', -0.12, 0.72, 0.225);
    addBox(group, 0.09, 0.08, 0.04, '#ffb3c7', 0.12, 0.72, 0.225);
  }

  const tail = new THREE.Group();
  tail.position.set(0, 0.4, -0.26);
  group.add(tail);
  addBox(tail, 0.1, 0.1, 0.3, dark, 0, 0.02, -0.13);
  addBox(tail, 0.12, 0.12, 0.12, light, 0, 0.02, -0.28);

  const legs = [];
  for (const x of [-0.13, 0.13]) {
    for (const z of [-0.16, 0.16]) {
      legs.push(addBox(group, 0.1, 0.2, 0.1, dark, x, 0.1, z));
    }
  }

  group.userData.parts = { tail, ears, legs, petType: character.petType };
  group.userData.pet = pet;
  return group;
}

function removeCompanion() {
  if (!companion) return;
  scene.remove(companion);
  disposeAvatar(companion);
  companion = null;
}

function refreshCompanion() {
  if (!player) return;
  removeCompanion();
  companion = createPetModel(settings.character);
  companion.position.copy(player.position);
  companion.position.y += 0.04;
  scene.add(companion);
}

function updateCompanion(dt) {
  if (!companion || !player) return;
  const yaw = player.yaw;
  const behindX = Math.sin(yaw) * 1.55;
  const behindZ = Math.cos(yaw) * 1.55;
  const sideX = Math.cos(yaw) * 0.72;
  const sideZ = -Math.sin(yaw) * 0.72;
  const target = new THREE.Vector3(
    player.position.x + behindX + sideX,
    player.position.y + 0.04,
    player.position.z + behindZ + sideZ
  );
  const distance = companion.position.distanceTo(target);
  if (distance > 14) companion.position.copy(target);
  else companion.position.lerp(target, Math.min(1, dt * 4.2));
  if (distance > 1.2) companion.rotation.y = Math.atan2(player.position.x - companion.position.x, player.position.z - companion.position.z);
  const parts = companion.userData.parts;
  if (parts) {
    const time = performance.now() * 0.006;
    companion.position.y = target.y + Math.sin(time) * 0.025;
    parts.tail.rotation.y = Math.sin(time * 1.7) * 0.45;
    parts.ears.forEach((ear, index) => {
      ear.rotation.z = Math.sin(time * 2.1 + index) * 0.12;
    });
    const moving = distance > 0.25;
    parts.legs.forEach((leg, index) => {
      leg.position.y = 0.1 + (moving ? Math.max(0, Math.sin(time * 2.6 + index * 1.6)) * 0.035 : 0);
    });
  }
  petBubbleTimer -= dt;
  if (distance < 2.4 && petBubbleTimer <= 0) {
    petBubbleTimer = 8 + Math.random() * 5;
    petReact('idle');
  }
}

function petReact(kind = 'happy') {
  if (!companion) return;
  const pet = companion.userData.pet || { color: '#b8ff6a', name: 'Lumi' };
  const color = kind === 'treat' ? '#ffb3c7' : kind === 'craft' ? '#ffc94d' : pet.color;
  particles.burst(companion.position.x, companion.position.y + 0.55, companion.position.z, color, kind === 'craft' ? 18 : 10, 0.55, 1.7);
  if (kind === 'treat' || kind === 'craft' || kind === 'land') audio.pet(kind);
}

function feedPet() {
  if (!inventory || !player) return;
  if (!companion) refreshCompanion();
  if (currentWorld.mode !== 'creative') inventory.removeOneSelected();
  player.refreshAbilities(inventory);
  ui.renderHotbar(inventory, currentWorld.mode === 'creative');
  audio.eat();
  ui.swingHand('craft');
  petReact('treat');
  ui.toast({
    title: 'Petisco de Lumi!',
    body: `${companion?.userData.pet?.name || 'Seu companheiro'} adorou o agrado.`,
    rarity: 'incomum',
    icon: getItemIcon('pet_treat')
  });
  updateHud(true);
}

const NPC_DEFS = [
  {
    id: 'lia',
    name: 'Lia, jardineira',
    shirt: '#ffb3c7',
    pants: '#4d6b45',
    hair: '#5a3b2e',
    accent: '#b8ff6a',
    offset: { x: 4.5, z: 3.5 },
    lines: [
      'As flores gostam quando você planta perto da água.',
      'Já viu uma árvore arco-íris? Ela nasce de uma semente prismática.',
      'Seu companheiro fica mais feliz com petiscos de Lumi.'
    ]
  },
  {
    id: 'theo',
    name: 'Theo, construtor',
    shirt: '#7cd6ff',
    pants: '#2f4f6f',
    hair: '#24344f',
    accent: '#ffc94d',
    offset: { x: -5, z: 4 },
    lines: [
      'Uma casa pequena com telhado colorido já parece um lar.',
      'Tábuas e gravetos abrem a porta para quase toda ferramenta.',
      'No modo Criativo, aperte V e segure Espaço por 4 segundos para pousar.'
    ]
  },
  {
    id: 'nina',
    name: 'Nina, cartógrafa',
    shirt: '#b07cff',
    pants: '#3f3a63',
    hair: '#6f4630',
    accent: '#ff7ad9',
    offset: { x: 0.5, z: -5.5 },
    lines: [
      'Cada bioma guarda uma cor diferente de Lumi.',
      'O mundo do dia é o mesmo para todo mundo. Compare descobertas!',
      'Se encontrar um baú de santuário, olhe bem antes de quebrar.'
    ]
  }
];

function npcGroundY(x, z, startY) {
  const bx = Math.floor(x);
  const bz = Math.floor(z);
  for (let y = Math.min(WORLD_HEIGHT - 2, Math.max(1, Math.floor(startY) + 6)); y > 0; y--) {
    if (world?.isSolid(bx, y - 1, bz) && !world.isSolid(bx, y, bz)) return y;
  }
  return startY;
}

function createNpcModel(def) {
  const group = new THREE.Group();
  const material = (color) => new THREE.MeshStandardMaterial({ color, roughness: 0.88, metalness: 0.02 });
  const addBox = (width, height, depth, color, x, y, z) => {
    const mesh = new THREE.Mesh(new THREE.BoxGeometry(width, height, depth), material(color));
    mesh.position.set(x, y, z);
    group.add(mesh);
    return mesh;
  };
  addBox(0.56, 0.68, 0.32, def.shirt, 0, 1.08, 0);
  addBox(0.44, 0.44, 0.44, '#f2c9a0', 0, 1.6, 0);
  addBox(0.48, 0.13, 0.48, def.hair, 0, 1.85, 0);
  addBox(0.08, 0.08, 0.03, '#17231f', -0.1, 1.65, 0.23);
  addBox(0.08, 0.08, 0.03, '#17231f', 0.1, 1.65, 0.23);
  addBox(0.14, 0.035, 0.03, '#7a3b4a', 0, 1.49, 0.23);
  addBox(0.16, 0.48, 0.18, def.shirt, -0.36, 1.08, 0);
  addBox(0.16, 0.48, 0.18, def.shirt, 0.36, 1.08, 0);
  addBox(0.2, 0.52, 0.22, def.pants, -0.15, 0.5, 0);
  addBox(0.2, 0.52, 0.22, def.pants, 0.15, 0.5, 0);
  addBox(0.18, 0.12, 0.05, def.accent, 0, 1.24, 0.18);
  group.add(makeNameTag(def.name));
  group.userData.def = def;
  return group;
}

function clearNpcs() {
  for (const npc of npcs) {
    scene.remove(npc.group);
    disposeAvatar(npc.group);
  }
  npcs = [];
  nearestNpc = null;
  lastNpcPromptId = null;
  if (ui) ui.setNpcPrompt(null);
}

function spawnNpcs(spawn) {
  clearNpcs();
  if (!world || !spawn) return;
  for (const def of NPC_DEFS) {
    const x = spawn.x + def.offset.x;
    const z = spawn.z + def.offset.z;
    const y = npcGroundY(x, z, spawn.y);
    const group = createNpcModel(def);
    group.position.set(x, y, z);
    group.rotation.y = Math.atan2(spawn.x - x, spawn.z - z);
    scene.add(group);
    npcs.push({ group, def, baseY: y, index: 0 });
  }
}

function updateNpcs(dt) {
  if (!player || !npcs.length) return;
  let found = null;
  let foundDistance = Infinity;
  for (const npc of npcs) {
    const dx = player.position.x - npc.group.position.x;
    const dz = player.position.z - npc.group.position.z;
    const distance = Math.hypot(dx, dz);
    npc.group.position.y = npc.baseY + Math.sin(performance.now() * 0.0025 + npc.group.position.x) * 0.035;
    if (distance < 6) npc.group.rotation.y = Math.atan2(dx, dz);
    if (distance < 4.5 && distance < foundDistance) {
      found = npc;
      foundDistance = distance;
    }
  }
  nearestNpc = found;
  if (found) {
    const id = `${found.def.id}:${found.index}`;
    if (id !== lastNpcPromptId) {
      lastNpcPromptId = id;
      ui.setNpcPrompt({ name: found.def.name, line: found.def.lines[found.index] });
    }
  } else if (lastNpcPromptId !== null) {
    lastNpcPromptId = null;
    ui.setNpcPrompt(null);
  }
}

function talkToNpc() {
  if (!nearestNpc || !running || !ready || uiOpen || paused || photoMode) return;
  const npc = nearestNpc;
  npc.index = (npc.index + 1) % npc.def.lines.length;
  lastNpcPromptId = `${npc.def.id}:${npc.index}`;
  ui.setNpcPrompt({ name: npc.def.name, line: npc.def.lines[npc.index] });
  ui.toast({ title: npc.def.name, body: npc.def.lines[npc.index], rarity: 'comum' });
  audio.npc();
  petReact('idle');
}

function createRemoteAvatar(data) {
  const character = normalizeCharacter(data.character);
  const group = createAvatarModel(character, data.name, { tag: true });
  group.position.set(finite(data.x), finite(data.y, 24, -8, 80), finite(data.z));
  group.userData.signature = `${data.name}|${character.skin}|${character.shirt}|${character.pants}|${character.hair}|${character.accessory}|${character.petType}|${character.petColor}`;
  scene.add(group);
  return group;
}

function removeLocalAvatar() {
  if (!localAvatar) return;
  scene.remove(localAvatar);
  disposeAvatar(localAvatar);
  localAvatar = null;
}

function refreshLocalAvatar() {
  if (!player) return;
  removeLocalAvatar();
  localAvatar = createAvatarModel(settings.character, settings.character.name, { tag: false });
  localAvatar.visible = thirdPerson && !photoMode;
  scene.add(localAvatar);
}

function updateLocalAvatar(dt = 0) {
  if (!localAvatar || !player) return;
  localAvatar.visible = thirdPerson && !photoMode;
  if (!localAvatar.visible) return;
  localAvatar.position.copy(player.position);
  localAvatar.rotation.y = player.yaw;
  const moving = player.onGround && Math.hypot(player.velocity.x, player.velocity.z) > 0.35;
  const swing = moving ? Math.sin(performance.now() * 0.012) * 0.42 : 0;
  const limbs = localAvatar.userData.limbs;
  if (!limbs) return;
  if (avatarCraftTimer > 0) {
    avatarCraftTimer = Math.max(0, avatarCraftTimer - dt);
    const craftSwing = Math.sin((0.55 - avatarCraftTimer) * 18) * 0.35;
    limbs.leftArm.rotation.x = -1.25 + craftSwing;
    limbs.rightArm.rotation.x = -1.05 - craftSwing;
    limbs.leftArm.rotation.z = -0.18;
    limbs.rightArm.rotation.z = 0.18;
    limbs.leftLeg.rotation.x = 0;
    limbs.rightLeg.rotation.x = 0;
    return;
  }
  limbs.leftArm.rotation.z = 0;
  limbs.rightArm.rotation.z = 0;
  if (player.fly) {
    limbs.leftArm.rotation.x = -0.9 + Math.sin(performance.now() * 0.006) * 0.08;
    limbs.rightArm.rotation.x = -0.9 - Math.sin(performance.now() * 0.006) * 0.08;
    limbs.leftLeg.rotation.x = 0.18;
    limbs.rightLeg.rotation.x = -0.18;
    return;
  }
  if (!player.onGround) {
    limbs.leftArm.rotation.x = -1.1;
    limbs.rightArm.rotation.x = -1.1;
    limbs.leftLeg.rotation.x = 0.35;
    limbs.rightLeg.rotation.x = -0.2;
    return;
  }
  limbs.leftArm.rotation.x = swing;
  limbs.rightArm.rotation.x = -swing;
  limbs.leftLeg.rotation.x = -swing * 0.8;
  limbs.rightLeg.rotation.x = swing * 0.8;
}

function cameraClearDistance(target, forward, maxDistance) {
  if (!world) return maxDistance;
  for (let distance = 0.8; distance < maxDistance; distance += 0.25) {
    const x = Math.floor(target.x - forward.x * distance);
    const y = Math.floor(target.y - forward.y * distance);
    const z = Math.floor(target.z - forward.z * distance);
    if (isSolidBlock(world.getBlock(x, y, z))) return Math.max(1.1, distance - 0.35);
  }
  return maxDistance;
}

function updateThirdPersonCamera(dt) {
  if (!thirdPerson || !player || photoMode) return;
  const target = new THREE.Vector3(player.position.x, player.position.y + 1.35, player.position.z);
  const forward = new THREE.Vector3(-Math.sin(player.yaw), 0, -Math.cos(player.yaw));
  const distance = cameraClearDistance(target, forward, 4.4);
  const desired = target.clone().addScaledVector(forward, -distance);
  desired.y += 1.15;
  camera.position.lerp(desired, Math.min(1, dt * 14));
  const look = target.clone().add(new THREE.Vector3(0, Math.sin(player.pitch) * 1.8, 0));
  camera.lookAt(look);
}

function toggleThirdPerson() {
  if (!running || !ready) return;
  thirdPerson = !thirdPerson;
  ui.setThirdPerson(thirdPerson);
  if (localAvatar) localAvatar.visible = thirdPerson && !photoMode;
  if (!thirdPerson) {
    camera.position.set(player.position.x, player.position.y + 1.62, player.position.z);
    camera.rotation.set(player.pitch, player.yaw, 0, 'YXZ');
  }
  audio.ui();
  ui.showHint(thirdPerson ? 'Visão de terceira pessoa · F5 para voltar' : 'Visão de primeira pessoa', 2800);
}

function removeRemoteAvatar(id) {
  const avatar = remoteAvatars.get(id);
  if (!avatar) return;
  scene.remove(avatar);
  disposeAvatar(avatar);
  remoteAvatars.delete(id);
}

function clearRemoteAvatars() {
  for (const id of [...remoteAvatars.keys()]) removeRemoteAvatar(id);
}

function updateRemoteAvatars(dt) {
  if (!multiplayer) return;
  if (!running || !world) {
    clearRemoteAvatars();
    return;
  }
  const seen = new Set();
  for (const data of multiplayer.players.values()) {
    if (!data || !data.id || data.id === multiplayer.selfId) continue;
    seen.add(data.id);
    const signature = `${data.name}|${data.character?.skin}|${data.character?.shirt}|${data.character?.pants}|${data.character?.hair}|${data.character?.accessory}|${data.character?.petType}|${data.character?.petColor}`;
    let avatar = remoteAvatars.get(data.id);
    if (!avatar || avatar.userData.signature !== signature) {
      if (avatar) removeRemoteAvatar(data.id);
      avatar = createRemoteAvatar(data);
      remoteAvatars.set(data.id, avatar);
    }
    const target = new THREE.Vector3(finite(data.x), finite(data.y, 24, -8, 80), finite(data.z));
    avatar.position.lerp(target, Math.min(1, dt * 10));
    avatar.rotation.y = finite(data.yaw, 0);
    avatar.visible = camera.position.distanceToSquared(target) < 140 * 140;
  }
  for (const id of [...remoteAvatars.keys()]) {
    if (!seen.has(id)) removeRemoteAvatar(id);
  }
}

function finite(value, fallback = 0, min = -1000000, max = 1000000) {
  const number = Number(value);
  if (!Number.isFinite(number)) return fallback;
  return Math.max(min, Math.min(max, number));
}

function sendTreePatch(x, z) {
  if (!multiplayer?.connected || !world) return;
  const cx = Math.floor(x / CHUNK_SIZE);
  const cz = Math.floor(z / CHUNK_SIZE);
  const patch = {};
  for (let dx = -1; dx <= 1; dx++) {
    for (let dz = -1; dz <= 1; dz++) {
      const key = chunkKey(cx + dx, cz + dz);
      const diff = world.diffs[key];
      if (diff && Object.keys(diff).length) patch[key] = { ...diff };
    }
  }
  multiplayer.sendPatch(patch);
}

function applyRemoteBlock({ x, y, z, id }) {
  if (!world || !running) return;
  if (!world.setBlock(x, y, z, id, true)) return;
  rebuildChunkAt(x, z);
  updateHud(true);
}

function applyRemoteChest({ x, y, z, items }) {
  if (!world) return;
  if (items == null) world.deleteChest(x, y, z);
  else world.setChest(x, y, z, items.filter(Boolean));
}

function applyRemotePatch(patch) {
  if (!world || !running || !patch) return;
  const touched = new Set();
  for (const [key, diff] of Object.entries(patch)) {
    const [cx, cz] = key.split(',').map(Number);
    if (!Number.isFinite(cx) || !Number.isFinite(cz) || !diff) continue;
    touched.add(key);
    for (const [rawIndex, id] of Object.entries(diff)) {
      const index = Number(rawIndex);
      if (!Number.isFinite(index) || index < 0 || index >= CHUNK_SIZE * CHUNK_SIZE * WORLD_HEIGHT) continue;
      const lx = index % CHUNK_SIZE;
      const lz = Math.floor(index / CHUNK_SIZE) % CHUNK_SIZE;
      const y = Math.floor(index / (CHUNK_SIZE * CHUNK_SIZE));
      world.setBlock(cx * CHUNK_SIZE + lx, y, cz * CHUNK_SIZE + lz, id, true);
    }
  }
  for (const key of touched) rebuildChunkByKey(key);
  updateHud(true);
}

function buildSnapshot() {
  const totalPlaytime = playtimeBase + playtimeSession;
  return {
    version: 1,
    id: currentWorld.id,
    name: currentWorld.name,
    seed: currentWorld.seed,
    mode: currentWorld.mode,
    createdAt: currentWorld.createdAt,
    updatedAt: Date.now(),
    playtime: totalPlaytime,
    timeOfDay,
    player: player.serialize(),
    inventory: inventory.serialize(),
    chests: world.serializeChests(),
    diffs: world.serializeDiffs(),
    discovered: [...discovered],
    stats,
    achievements,
    crownPlaced: world.crownPlaced,
    biome: currentBiomeId()
  };
}

function saveNow({ silent = false } = {}) {
  if (!running || !ready || !world || !inventory || !currentWorld) return false;
  if (multiplayer?.isGuest) return false;
  const snapshot = buildSnapshot();
  const result = saveSnapshot(snapshot);
  if (result.ok) {
    currentWorld.updatedAt = snapshot.updatedAt;
    currentWorld.playtime = snapshot.playtime;
    playtimeBase = snapshot.playtime;
    playtimeSession = 0;
    if (!silent) {
      ui.toast({ title: 'Mundo salvo!', body: 'Tudo pronto para continuar depois.', rarity: 'incomum' });
    }
    return true;
  }
  if (!silent) {
    ui.toast({ title: 'Não foi possível salvar', body: result.error || 'Tente novamente.', rarity: 'comum' });
  }
  return false;
}

function resetWorld() {
  clearRemoteAvatars();
  removeLocalAvatar();
  removeCompanion();
  clearNpcs();
  thirdPerson = false;
  for (const key of [...chunkMeshes.keys()]) removeChunkMeshes(key);
  chunkMeshes.clear();
  if (world) world.chunks.clear();
  world = null;
  player = null;
  inventory = null;
  currentWorld = null;
  particles.reset();
  projectiles.reset();
  auroraTimer = 0;
  lastBiome = null;
  charmTimer = 0;
  chunkAccum = 0;
  hudAccum = 0;
  autosaveAccum = 0;
}

function quitToMenu() {
  if (running && ready) saveNow({ silent: true });
  if (multiplayer?.connected || multiplayer?.role) {
    multiplayer.leave();
    clearRemoteAvatars();
    ui.setMultiplayerState({ status: 'left', message: 'Você saiu da sala.', code: '', invite: '', players: [], selfId: '' });
  }
  if (!running) {
    ui.showMenu();
    refreshMenu();
    return;
  }
  running = false;
  ready = false;
  paused = false;
  uiOpen = false;
  photoMode = false;
  clearHeldInputs();
  document.exitPointerLock?.();
  resetWorld();
  ui.setState({ inventory: null, world: null, worldName: 'Mundo', seed: '', playerName: '' });
  ui.showMenu();
  refreshMenu();
  audio.close();
  audio.stopAmbience();
}

function enterPhoto() {
  if (!running || !ready || photoMode) return;
  photoMode = true;
  paused = false;
  if (ui.modalOpen) ui.hideAllModals();
  saveNow({ silent: true });
  clearHeldInputs();
  document.exitPointerLock?.();
  ui.openPhoto();
}

function exitPhoto() {
  if (!photoMode) return;
  photoMode = false;
  requestLock();
}

function photoInfo() {
  return {
    worldName: currentWorld?.name || 'Mundo',
    seed: currentWorld?.seed || 'lumi',
    mode: currentWorld?.mode || 'adventure',
    rare: rareDiscoveredCount(),
    discovered: discovered.size,
    blocks: stats.blocksPlaced || 0,
    biome: BIOMES[currentBiomeId()]?.name || 'Prado Musgo',
    characterName: settings.character?.name || 'Lumi'
  };
}

async function startWorld(snapshot, { fresh = false, rareKit = false, joined = false } = {}) {
  if (starting) return;
  starting = true;
  running = false;
  ready = false;
  paused = false;
  photoMode = false;
  clearHeldInputs();
  document.exitPointerLock?.();
  try {
    ui.hideAllModals();
    resetWorld();
    ui.hideMenu();
    ui.showLoading(
      fresh ? 'Criando um mundo novo' : 'Carregando seu mundo',
      fresh ? 'Procurando um lugar seguro…' : 'Reacendendo as cores…'
    );
    await nextFrame();

    currentWorld = { ...snapshot };
    world = new World({
      seed: snapshot.seed,
      diffs: snapshot.diffs,
      chests: snapshot.chests,
      crownPlaced: snapshot.crownPlaced
    });
    inventory = new Inventory(snapshot.inventory);
    player = new Player(camera, world, fresh ? { x: 0, y: 24, z: 0 } : snapshot.player);
    player.setCallbacks({
      step: () => audio.step(),
      jump: () => {
        audio.jump();
        petReact('jump');
      },
      doubleJump: () => {
        audio.jump();
        petReact('jump');
      },
      land: () => {
        audio.land();
        petReact('land');
        ui.showHint('Pouso suave concluído.', 2200);
      },
      respawn: () => ui.toast({ title: 'De volta ao início', body: 'Você voltou para um lugar seguro.', rarity: 'comum' })
    });
    player.fly = snapshot.mode === 'creative' ? !!snapshot.player?.fly : false;
    player.refreshAbilities(inventory);
    refreshLocalAvatar();

    timeOfDay = Number(snapshot.timeOfDay) || 0.32;
    playtimeBase = Number(snapshot.playtime) || 0;
    playtimeSession = 0;
    stats = { ...DEFAULT_STATS, ...(snapshot.stats || {}) };
    if (!Array.isArray(stats.biomesVisited)) stats.biomesVisited = [];
    achievements = { ...(snapshot.achievements || {}) };
    discovered = new Set((snapshot.discovered || []).map((id) => String(id)));
    auroraTimer = 0;
    lastBiome = null;
    charmTimer = 0;
    chunkAccum = 0;
    hudAccum = 0;
    autosaveAccum = 0;

    let spawnYaw = 0;
    if (fresh) {
      const spawn = world.findSpawn();
      player.setPosition(spawn.x, spawn.y, spawn.z);
      spawnYaw = spawn.yaw || 0;
      if (rareKit) inventory.giveRareKit();
      else if (snapshot.mode !== 'creative') inventory.giveStarterKit();
      if (isDailyWorld()) {
        stats.dailyLumi = 0;
        stats.dailyDone = false;
      }
      currentWorld.createdAt = Date.now();
      currentWorld.updatedAt = Date.now();
    } else {
      player.setPosition(snapshot.player.x, snapshot.player.y, snapshot.player.z);
    }
    if (joined && snapshot.mode !== 'creative') inventory.giveStarterKit();

    ensureSafePosition();
    spawnNpcs(world.findSpawn());
    refreshCompanion();
    player.yaw = fresh ? spawnYaw : Number(snapshot.player?.yaw) || 0;
    player.pitch = fresh ? 0 : Number(snapshot.player?.pitch) || 0;
    player.update(0, EMPTY_INPUT);

    currentWorld.discovered = [...discovered];
    ui.setState({
      inventory,
      mode: snapshot.mode,
      world: currentWorld,
      worldName: snapshot.name,
      seed: snapshot.seed
    });
    ui.applySettings(settings);
    ui.setTouchMode(isTouch);
    ui.setThirdPerson(false);
    ui.renderHotbar(inventory, snapshot.mode === 'creative');

    if (fresh) {
      for (const arr of inventory.arrays()) {
        for (const slot of arr) {
          if (slot) discoverItem(slot.id, slot.count, true);
        }
      }
    }

    ui.setLoading(10, 'Plantando árvores arco-íris…');
    await preloadChunks();
    ui.setLoading(99, 'Acendendo as luzes…');
    await nextFrame();

    ui.hideLoading();
    ui.showHUD();
    updateDayNight(0);
    updateHud(true);

    running = true;
    ready = true;
    paused = false;
    uiOpen = false;
    photoMode = false;
    lastTime = performance.now();
    if (!multiplayer?.isGuest) saveNow({ silent: true });

    if (!settings.tutorialDone) {
      setTimeout(() => {
        if (running && ready) ui.openTutorial(false);
      }, 400);
    } else {
      ui.showHint(isTouch ? 'Use o joystick para andar' : 'Clique para jogar · WASD para andar', 5200);
    }
  } catch (error) {
    console.error(error);
    running = false;
    ready = false;
    ui.hideLoading();
    ui.showMenu();
    refreshMenu();
    ui.toast({
      title: 'Não foi possível abrir o mundo',
      body: 'Tente novamente ou crie outro mundo.',
      rarity: 'comum'
    });
  } finally {
    starting = false;
  }
}

function refreshMenu() {
  if (!ui) return;
  ui.renderMenu(listWorlds(), getDaily());
}

function onPlayWorld(id) {
  const snapshot = getWorld(id);
  if (!snapshot) {
    ui.toast({ title: 'Mundo não encontrado', body: 'Ele pode ter sido apagado do navegador.', rarity: 'comum' });
    refreshMenu();
    return;
  }
  startWorld(snapshot);
}

function onCreateWorld(data) {
  const result = createWorld(data);
  if (!result.ok) {
    ui.toast({ title: 'Não foi possível criar', body: result.error || 'Tente outro nome ou seed.', rarity: 'comum' });
    return;
  }
  ui.closeCreate();
  startWorld(snapshotFromEntry(result.entry), { fresh: true, rareKit: !!data.rareKit });
}

function onPrebuiltWorld(entry) {
  if (!entry) return;
  const existing = listWorlds().find((world) => world.seed === entry.seed && world.name === entry.name);
  if (existing) {
    const snapshot = getWorld(existing.id);
    if (snapshot) {
      startWorld(snapshot);
      return;
    }
    deleteWorldSave(existing.id);
  }
  const result = createWorld({ name: entry.name, seed: entry.seed, mode: entry.mode });
  if (!result.ok) {
    ui.toast({ title: 'Não foi possível abrir', body: result.error || 'Apague um mundo antigo e tente de novo.', rarity: 'comum' });
    return;
  }
  startWorld(snapshotFromEntry(result.entry), { fresh: true, rareKit: !!entry.rareKit });
}

function onRandomWorld() {
  const seed = randomSeed();
  const result = createWorld({
    name: randomWorldName(seed),
    seed,
    mode: Math.random() < 0.22 ? 'creative' : 'adventure'
  });
  if (!result.ok) {
    ui.toast({ title: 'Não foi possível criar', body: result.error || 'Tente outro nome ou seed.', rarity: 'comum' });
    return;
  }
  startWorld(snapshotFromEntry(result.entry), { fresh: true });
}

function onCharacterSave(character) {
  settings.character = normalizeCharacter(character);
  saveSettings({ character: settings.character });
  ui.applySettings(settings);
  refreshLocalAvatar();
  refreshCompanion();
  ui.toast({ title: 'Personagem salvo!', body: `${settings.character.name} e seu companheiro estão prontos para explorar.`, rarity: 'incomum' });
}

async function onHostRoom() {
  if (!multiplayer) return;
  if (multiplayer.connected) {
    ui.toast({ title: 'Sala já aberta', body: 'Compartilhe o convite com seus amigos.', rarity: 'incomum' });
    ui.openMultiplayer();
    return;
  }
  let snapshot = running && ready ? buildSnapshot() : null;
  let existing = null;
  if (!snapshot) {
    existing = listWorlds().find((entry) => entry.name === 'Sala de Lumi');
    if (existing) snapshot = getWorld(existing.id);
    if (existing && !snapshot) {
      deleteWorldSave(existing.id);
      existing = null;
    }
    if (!snapshot) {
      const result = createWorld({ name: 'Sala de Lumi', seed: `sala-${Date.now().toString(36)}`, mode: 'adventure' });
      if (!result.ok) {
        ui.toast({ title: 'Não foi possível criar a sala', body: result.error || 'Apague um mundo antigo e tente de novo.', rarity: 'comum' });
        return;
      }
      snapshot = snapshotFromEntry(result.entry);
    }
    ui.closeMultiplayer(true);
    await startWorld(snapshot, { fresh: !existing });
    if (!running || !ready) return;
  }
  try {
    const result = await multiplayer.host({
      getSnapshot: buildSnapshot,
      character: settings.character,
      name: settings.character.name
    });
    multiplayer.attach(world, { character: settings.character, name: settings.character.name });
    ui.setMultiplayerState({
      status: 'hosting',
      message: 'Sala pronta. Compartilhe o convite!',
      code: result.code,
      invite: result.invite,
      players: multiplayer.playerList(),
      selfId: multiplayer.selfId
    });
    if (!settings.tutorialDone) await new Promise((resolve) => setTimeout(resolve, 500));
    ui.openMultiplayer();
    ui.toast({ title: 'Sala criada!', body: `Código ${result.code}. Convide até 5 amigos.`, rarity: 'raro' });
  } catch (error) {
    ui.toast({ title: 'Multiplayer indisponível', body: error?.message || 'Tente novamente em instantes.', rarity: 'comum' });
  }
}

async function onJoinRoom(rawCode) {
  if (!multiplayer) return;
  if (multiplayer.connected) {
    ui.toast({ title: 'Você já está em uma sala', body: 'Saia da sala atual para entrar em outra.', rarity: 'comum' });
    return;
  }
  const code = normalizeRoomCode(rawCode);
  if (code.length !== 5) {
    ui.toast({ title: 'Código incompleto', body: 'Digite as 5 letras do convite.', rarity: 'comum' });
    return;
  }
  if (running && ready) saveNow({ silent: true });
  ui.setMultiplayerState({ status: 'joining', message: 'Entrando na sala…', code, players: [], selfId: '' });
  try {
    await multiplayer.join({ code, character: settings.character, name: settings.character.name });
  } catch (error) {
    ui.toast({ title: 'Não foi possível entrar', body: error?.message || 'Confira o código e tente novamente.', rarity: 'comum' });
  }
}

async function onMultiplayerWelcome(welcome) {
  if (!welcome?.snapshot) {
    ui.toast({ title: 'Sala sem mundo', body: 'Peça um novo convite ao anfitrião.', rarity: 'comum' });
    multiplayer?.leave();
    return;
  }
  const hostSnapshot = welcome.snapshot;
  const spawn = hostSnapshot.player || {};
  const guestSnapshot = {
    ...hostSnapshot,
    id: `mp_${welcome.code}`,
    name: hostSnapshot.name || 'Sala de Lumi',
    player: {
      ...spawn,
      x: finite(spawn.x, 0) + 1.5,
      y: finite(spawn.y, 24, -8, 80),
      z: finite(spawn.z, 0) + 1.5,
      yaw: finite(spawn.yaw, 0),
      pitch: 0
    },
    inventory: null
  };
  ui.closeMultiplayer(true);
  await startWorld(guestSnapshot, { joined: true });
  if (!running || !ready) {
    multiplayer?.leave();
    return;
  }
  multiplayer.attach(world, { character: settings.character, name: settings.character.name });
  ui.setMultiplayerState({
    status: 'connected',
    message: `Sala ${welcome.code} conectada.`,
    code: welcome.code,
    invite: multiplayer.invite,
    players: multiplayer.playerList(),
    selfId: multiplayer.selfId
  });
  ui.toast({ title: 'Você entrou na sala!', body: `Código ${welcome.code}. Explore junto!`, rarity: 'raro' });
}

function onLeaveRoom() {
  if (!multiplayer?.role) return;
  const wasGuest = multiplayer.isGuest;
  multiplayer.leave();
  clearRemoteAvatars();
  ui.setMultiplayerState({ status: 'left', message: 'Você saiu da sala.', code: '', invite: '', players: [], selfId: '' });
  ui.closeMultiplayer(true);
  if (running && ready) onPause();
  ui.toast({
    title: wasGuest ? 'Você saiu da sala' : 'Sala encerrada',
    body: wasGuest ? 'Seu mundo local continua aqui.' : 'Seus amigos foram avisados.',
    rarity: 'comum'
  });
}

function onHostLeft() {
  clearRemoteAvatars();
  ui.setMultiplayerState({ status: 'left', message: 'O anfitrião saiu. Você continua sozinho.', code: '', invite: '', players: [], selfId: '' });
  ui.toast({ title: 'Sala encerrada', body: 'O anfitrião saiu. Você continua explorando por aqui.', rarity: 'comum' });
}

function quickPlay() {
  if (!isStorageAvailable()) {
    ui.showMenu();
    ui.toast({ title: 'Armazenamento indisponível', body: 'O jogo precisa de localStorage para salvar. Abra em um navegador normal.', rarity: 'comum' });
    return;
  }
  for (const entry of listWorlds()) {
    const snapshot = getWorld(entry.id);
    if (snapshot) {
      startWorld(snapshot);
      return;
    }
    deleteWorldSave(entry.id);
  }
  const starter = getPrebuilt('jardim');
  const result = createWorld({
    name: starter?.name || 'Jardim de Lumi',
    seed: starter?.seed || 'lumi-jardim',
    mode: starter?.mode || 'adventure'
  });
  if (!result.ok) {
    ui.showMenu();
    ui.toast({ title: 'Não foi possível abrir o jogo', body: result.error || 'Tente novamente.', rarity: 'comum' });
    return;
  }
  startWorld(snapshotFromEntry(result.entry), { fresh: true });
}

function onDaily() {
  const daily = getDaily();
  for (const entry of listWorlds()) {
    if (entry.name === 'Mundo do Dia' && entry.seed !== daily.seed) deleteWorldSave(entry.id);
  }
  let existing = findDailyWorld();
  if (existing) {
    const snapshot = getWorld(existing.id);
    if (snapshot) {
      startWorld(snapshot);
      return;
    }
    deleteWorldSave(existing.id);
    existing = null;
  }
  const result = createWorld({ name: 'Mundo do Dia', seed: daily.seed, mode: 'adventure' });
  if (!result.ok) {
    ui.toast({ title: 'Não foi possível abrir o mundo do dia', body: result.error || 'Apague um mundo antigo e tente de novo.', rarity: 'comum' });
    return;
  }
  startWorld(snapshotFromEntry(result.entry), { fresh: true });
}

function onDeleteWorld(id) {
  const entry = listWorlds().find((item) => item.id === id);
  const label = entry?.name || 'este mundo';
  if (!window.confirm(`Apagar "${label}"? Essa ação não pode ser desfeita.`)) return;
  deleteWorldSave(id);
  refreshMenu();
  ui.toast({ title: 'Mundo apagado', body: 'O espaço está livre para uma nova aventura.', rarity: 'comum' });
}

function onExportWorld(id) {
  if (exportWorld(id)) {
    ui.toast({ title: 'Mundo exportado!', body: 'Guarde o arquivo para voltar quando quiser.', rarity: 'incomum' });
  } else {
    ui.toast({ title: 'Não foi possível exportar', body: 'Tente abrir o mundo primeiro.', rarity: 'comum' });
  }
}

async function onImportFile(file) {
  const result = await importWorldFile(file);
  if (!result.ok) {
    ui.toast({ title: 'Importação não concluída', body: result.error || 'Arquivo inválido.', rarity: 'comum' });
    return;
  }
  const snapshot = getWorld(result.id);
  if (!snapshot) {
    ui.toast({ title: 'Mundo importado incompleto', body: 'Tente outro arquivo.', rarity: 'comum' });
    return;
  }
  startWorld(snapshot);
}

function onImportCode(code) {
  const result = importWorldCode(code);
  if (!result.ok) {
    ui.toast({ title: 'Código inválido', body: result.error || 'Confira o código e tente novamente.', rarity: 'comum' });
    return;
  }
  if (running && ready) saveNow({ silent: true });
  ui.closeCode(true);
  startWorld(snapshotFromEntry(result.entry), { fresh: true });
}

function onCraft(recipeId) {
  if (!running || !inventory) return;
  const recipe = getRecipe(recipeId);
  if (!recipe) return;
  const creative = currentWorld?.mode === 'creative';
  if (!creative && !inventory.canCraft(recipe.ingredients)) {
    ui.toast({ title: 'Faltam ingredientes', body: 'Colete os itens indicados na receita.', rarity: 'comum' });
    return;
  }
  if (!inventory.canAdd(recipe.out.id, recipe.out.count)) {
    ui.toast({ title: 'Mochila cheia', body: 'Crie espaço antes de forjar.', rarity: 'comum' });
    return;
  }
  if (!creative) inventory.consume(recipe.ingredients);
  inventory.addItem(recipe.out.id, recipe.out.count);
  stats.crafted = (stats.crafted || 0) + 1;
  discoverItem(recipe.out.id, recipe.out.count, true);
  player.refreshAbilities(inventory);
  ui.renderHotbar(inventory, creative);
  ui.renderInventory();
  audio.craft(getRarity(recipe.out.id).key);
  avatarCraftTimer = 0.55;
  ui.swingHand('craft');
  petReact('craft');
  const item = getItem(recipe.out.id);
  ui.toast({
    title: 'Forjado!',
    body: `${item.name} ×${recipe.out.count}`,
    rarity: item.rarity,
    icon: getItemIcon(recipe.out.id)
  });
  checkAchievements();
  updateHud(true);
}

function onCreativePick(id) {
  if (!running || !inventory || currentWorld?.mode !== 'creative') return;
  const item = getItem(id);
  const left = inventory.addItem(id, Math.min(64, item.stack || 64));
  discoverItem(id, 1, true);
  player.refreshAbilities(inventory);
  ui.renderHotbar(inventory, true);
  ui.renderInventory();
  audio.ui();
  if (left > 0) ui.toast({ title: 'Mochila cheia', body: 'Crie espaço para mais blocos.', rarity: 'comum' });
}

function onSelectHotbar(index) {
  if (!inventory) return;
  inventory.select(index);
  player.refreshAbilities(inventory);
  ui.renderHotbar(inventory, currentWorld?.mode === 'creative');
  audio.ui();
  updateHud(true);
}

function onInventoryChanged() {
  if (!inventory || !player) return;
  player.refreshAbilities(inventory);
  ui.renderHotbar(inventory, currentWorld?.mode === 'creative');
  updateHud(true);
}

function onChestClose(key, items) {
  if (!world || !key) return;
  const parts = String(key).split(',').map(Number);
  if (parts.length === 3 && parts.every((value) => Number.isFinite(value)) && Array.isArray(items)) {
    world.setChest(parts[0], parts[1], parts[2], items);
    multiplayer?.sendChest(parts[0], parts[1], parts[2], items);
  }
  saveNow({ silent: true });
}

function onPause() {
  if (!running || !ready || uiOpen || photoMode) return;
  paused = true;
  saveNow({ silent: true });
  clearHeldInputs();
  document.exitPointerLock?.();
  ui.setNpcPrompt(null);
  ui.setLandingProgress(0);
  ui.openPause({
    name: currentWorld?.name || 'Mundo',
    seed: currentWorld?.seed || 'lumi',
    playtime: playtimeBase + playtimeSession
  });
  audio.open();
}

function onResume() {
  paused = false;
  uiOpen = false;
  audio.close();
  requestLock();
}

function onSave() {
  saveNow();
}

function onExport() {
  if (!running || !ready) return;
  if (exportSnapshot(buildSnapshot())) {
    ui.toast({ title: 'Mundo exportado!', body: 'Guarde o arquivo para voltar quando quiser.', rarity: 'incomum' });
  } else {
    ui.toast({ title: 'Não foi possível exportar', body: 'Tente novamente.', rarity: 'comum' });
  }
}

function onWorldCode() {
  if (!running || !currentWorld) {
    ui.openCode('', true);
    return;
  }
  ui.openCode(encodeWorldCode({
    seed: currentWorld.seed,
    name: currentWorld.name,
    mode: currentWorld.mode
  }), false);
}

function onQuit() {
  quitToMenu();
}

function onPhoto() {
  enterPhoto();
}

function onPhotoExit() {
  if (!photoMode) return;
  photoMode = false;
  requestLock();
}

function onPhotoDownload() {
  if (!running || !ready) return;
  renderer.render(scene, camera);
  ui.downloadPhoto(renderer.domElement, photoInfo());
}

function onCardDownload() {
  if (!running || !ready) return;
  renderer.render(scene, camera);
  ui.downloadCard(renderer.domElement, photoInfo());
}

function onSoundToggle(enabled) {
  const next = typeof enabled === 'boolean' ? enabled : !settings.sound;
  settings.sound = next;
  saveSettings({ sound: next });
  audio.setEnabled(next);
  ui.applySettings(settings);
}

function onSensitivity(value) {
  const next = Math.max(0.4, Math.min(2.2, Number(value) || 1));
  settings.sensitivity = next;
  saveSettings({ sensitivity: next });
}

function onCoordsToggle(show) {
  settings.showCoords = !!show;
  saveSettings({ showCoords: settings.showCoords });
  updateHud(true);
}

function applyGraphics(quality) {
  graphicsQuality = ['auto', 'pixel', 'turbo'].includes(quality) ? quality : 'auto';
  document.body.dataset.graphics = graphicsQuality;
  renderer.setPixelRatio(graphicsQuality === 'turbo' ? 1 : Math.min(window.devicePixelRatio || 1, 2));
  renderer.setSize(window.innerWidth, window.innerHeight, false);
}

function onQuality(value) {
  settings.quality = ['auto', 'pixel', 'turbo'].includes(value) ? value : 'auto';
  saveSettings({ quality: settings.quality });
  applyGraphics(settings.quality);
  ui.applySettings(settings);
  const labels = { auto: 'Cozy automático', pixel: 'Pixel suave', turbo: 'Turbo leve' };
  ui.showHint(`Pacote visual: ${labels[settings.quality]}.`, 2600);
}

function onOpenWorld(world) {
  if (world?.id) onPlayWorld(world.id);
}

function isFullscreen() {
  return !!document.fullscreenElement;
}

async function onFullscreen() {
  try {
    if (isFullscreen()) await document.exitFullscreen?.();
    else await document.documentElement.requestFullscreen?.();
  } catch {
    ui.showHint('A tela cheia não está disponível neste navegador.', 3200);
  }
  ui.setFullscreen(isFullscreen());
}

function onThirdPerson() {
  toggleThirdPerson();
}

function onModalState(open) {
  uiOpen = !!open;
  if (open) {
    clearHeldInputs();
    document.exitPointerLock?.();
  } else if (running && ready && !photoMode) {
    requestLock();
  }
}

function onSettingsClose(fromPause = false) {
  if (fromPause && running && ready) {
    paused = true;
    uiOpen = true;
    clearHeldInputs();
    document.exitPointerLock?.();
    return;
  }
  paused = false;
  uiOpen = false;
  requestLock();
}

function onTutorialDone() {
  settings.tutorialDone = true;
  saveSettings({ tutorialDone: true });
  paused = false;
  uiOpen = false;
  requestLock();
  if (running && ready) {
    ui.showHint(isTouch ? 'Use o joystick para andar' : 'Clique para jogar · WASD para andar', 4200);
  }
}

function readInput() {
  const active = running && ready && !uiOpen && !paused && !photoMode;
  if (!active) {
    jumpQueued = false;
    return { ...EMPTY_INPUT };
  }
  const input = {
    forward: keyState.has('KeyW') || keyState.has('ArrowUp') || touchMove.forward > 0.25,
    back: keyState.has('KeyS') || keyState.has('ArrowDown') || touchMove.forward < -0.25,
    left: keyState.has('KeyA') || keyState.has('ArrowLeft') || touchMove.strafe < -0.25,
    right: keyState.has('KeyD') || keyState.has('ArrowRight') || touchMove.strafe > 0.25,
    jump: keyState.has('Space') || touchMove.jump,
    jumpPressed: jumpQueued,
    sprint: keyState.has('ShiftLeft') || keyState.has('ShiftRight') || touchMove.sprint
  };
  jumpQueued = false;
  return input;
}

function toggleFly() {
  if (!running || !ready || uiOpen || paused || photoMode) return;
  if (currentWorld?.mode !== 'creative') {
    ui.showHint('O voo está disponível no modo Criativo.', 3200);
    return;
  }
  player.fly = !player.fly;
  player.velocity.y = 0;
  player.cancelLanding();
  ui.setLandingProgress(0);
  ui.showHint(player.fly ? 'Voo ativado · Espaço sobe · Shift desce · Segure Espaço 4 s para pousar' : 'Voo desativado', 3600);
  audio.ui();
}

function openCraftTab() {
  if (!running || !ready) return;
  if (ui.inventoryOpen) {
    ui.currentTab = 'craft';
    document.querySelectorAll('#inventory-tabs .tab').forEach((button) => {
      button.classList.toggle('active', button.dataset.tab === 'craft');
    });
    ui.renderInventory();
    return;
  }
  if (!uiOpen) {
    ui.openInventory('craft');
    audio.open();
  }
}

function handleEscape() {
  if (photoMode) {
    ui.closePhoto();
    return;
  }
  if (ui.pauseOpen) {
    ui.closePause();
    return;
  }
  if (ui.inventoryOpen) {
    ui.closeInventory();
    return;
  }
  if (ui.chestOpen) {
    ui.closeChest();
    return;
  }
  if (!$('settings-modal')?.classList.contains('hidden')) {
    ui.closeSettings();
    return;
  }
  if (!$('tutorial')?.classList.contains('hidden')) {
    ui.closeTutorial();
    return;
  }
  if (!$('create-modal')?.classList.contains('hidden')) {
    ui.closeCreate();
    return;
  }
  if (!$('code-modal')?.classList.contains('hidden')) {
    ui.closeCode();
    return;
  }
  if (ui.modalOpen) {
    ui.hideAllModals();
    return;
  }
  if (running && ready) onPause();
}

ui = new GameUI({
  onPlayWorld,
  onQuickPlay: quickPlay,
  onCreateWorld,
  onPrebuiltWorld,
  onCommunityWorld: onPrebuiltWorld,
  onRandomWorld,
  onCharacterSave,
  onHostRoom,
  onJoinRoom,
  onLeaveRoom,
  onDaily,
  onDeleteWorld,
  onExportWorld,
  onImportFile,
  onImportCode,
  onCraft,
  onCreativePick,
  onSelectHotbar,
  onInventoryChanged,
  onChestClose,
  onPause,
  onResume,
  onSave,
  onExport,
  onWorldCode,
  onQuit,
  onPhoto,
  onPhotoExit,
  onPhotoDownload,
  onCardDownload,
  onSoundToggle,
  onSensitivity,
  onCoordsToggle,
  onQuality,
  onCityWorld: onPrebuiltWorld,
  onOpenWorld,
  onFullscreen,
  onThirdPerson,
  onModalState,
  onSettingsClose,
  onTutorialDone
});

multiplayer = new Multiplayer({
  onStatus: (status, detail = {}) => {
    const clearRoom = ['left', 'error', 'idle'].includes(status);
    ui?.setMultiplayerState({
      status,
      message: detail.message,
      ...(clearRoom ? { code: '', invite: '' } : {}),
      ...(detail.code ? { code: detail.code } : {}),
      ...(detail.invite ? { invite: detail.invite } : {}),
      selfId: multiplayer?.selfId || ''
    });
  },
  onPlayers: (players) => ui?.setMultiplayerState({ players, selfId: multiplayer?.selfId || '' }),
  onBlock: applyRemoteBlock,
  onChest: applyRemoteChest,
  onPatch: applyRemotePatch,
  onWelcome: onMultiplayerWelcome,
  onError: (message) => ui?.toast({ title: 'Multiplayer', body: message, rarity: 'comum' }),
  onLeavePlayer: () => ui?.toast({ title: 'Um amigo saiu', body: 'A sala continua com os outros exploradores.', rarity: 'comum' }),
  onHostLeft
});

const params = new URLSearchParams(window.location.search);
const demoMode = params.get('demo') === '1';
if (demoMode && params.get('tutorial') !== '1') settings.tutorialDone = true;

if (params.get('debug') === '1') {
  window.__lulicraft = {
    get player() { return player; },
    get world() { return world; },
    get inventory() { return inventory; },
    get ui() { return ui; },
    get multiplayer() { return multiplayer; },
    get running() { return running; },
    get ready() { return ready; },
    get thirdPerson() { return thirdPerson; },
    get localAvatar() { return localAvatar; },
    get companion() { return companion; },
    get npcs() { return npcs; },
    toggleThirdPerson,
    talkToNpc,
    feedPet,
    tryBreak,
    tryPlace,
    useWeapon,
    getItem,
    get projectiles() { return projectiles; },
    openChestAt,
    saveNow,
    buildSnapshot
  };
}

ui.applySettings(settings);
applyGraphics(settings.quality);
ui.setFullscreen(isFullscreen());
audio.setEnabled(settings.sound);
ui.setTouchMode(isTouch);
refreshMenu();
ui.showMenu();

const inviteCode = normalizeRoomCode(params.get('join') || '');
if (inviteCode) {
  ui.setJoinCode(inviteCode);
  ui.setMultiplayerState({ message: `Convite ${inviteCode} recebido. Clique em entrar.`, code: '', players: [], selfId: '' });
  ui.openMultiplayer();
} else if (params.get('menu') !== '1') {
  quickPlay();
}

if (!isStorageAvailable()) {
  setTimeout(() => {
    ui.toast({ title: 'Modo visitante', body: 'Seu navegador não permite salvar. Jogue sem sair da aba.', rarity: 'comum' });
  }, 700);
}

window.addEventListener('resize', () => {
  renderer.setPixelRatio(graphicsQuality === 'turbo' ? 1 : Math.min(window.devicePixelRatio || 1, 2));
  renderer.setSize(window.innerWidth, window.innerHeight, false);
  camera.aspect = window.innerWidth / window.innerHeight;
  camera.updateProjectionMatrix();
});

document.addEventListener('fullscreenchange', () => ui?.setFullscreen(isFullscreen()));

window.addEventListener('keydown', (event) => {
  const target = event.target;
  if (target && (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.isContentEditable)) return;
  if (event.code === 'Escape') {
    event.preventDefault();
    handleEscape();
    return;
  }
  if (!running || !ready) return;

  if (event.code === 'KeyE') {
    if (ui.inventoryOpen) ui.closeInventory();
    else if (!uiOpen) {
      ui.openInventory('backpack');
      audio.open();
    }
    return;
  }
  if (event.code === 'KeyC') {
    openCraftTab();
    return;
  }
  if (uiOpen) return;

  if (event.code === 'Space') {
    event.preventDefault();
    if (!event.repeat) jumpQueued = true;
    keyState.add('Space');
    return;
  }
  if (event.code === 'KeyV') {
    event.preventDefault();
    toggleFly();
    return;
  }
  if (event.code === 'KeyR') {
    event.preventDefault();
    talkToNpc();
    return;
  }
  if (event.code === 'KeyF') {
    event.preventDefault();
    if (photoMode) ui.closePhoto();
    else enterPhoto();
    return;
  }
  if (event.code === 'KeyM') {
    event.preventDefault();
    onSoundToggle();
    return;
  }
  if (event.code === 'F5') {
    event.preventDefault();
    toggleThirdPerson();
    return;
  }
  if (event.code === 'F11') {
    event.preventDefault();
    onFullscreen();
    return;
  }
  if (event.code === 'F3') {
    event.preventDefault();
    onCoordsToggle(!settings.showCoords);
    ui.applySettings(settings);
    return;
  }
  if (/^Digit[1-9]$/.test(event.code)) {
    onSelectHotbar(Number(event.code.slice(5)) - 1);
    return;
  }
  if (['KeyW', 'KeyA', 'KeyS', 'KeyD', 'ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight', 'ShiftLeft', 'ShiftRight'].includes(event.code)) {
    keyState.add(event.code);
    if (event.code.startsWith('Arrow')) event.preventDefault();
  }
});

window.addEventListener('keyup', (event) => {
  keyState.delete(event.code);
});

window.addEventListener('blur', () => {
  clearHeldInputs();
});

document.addEventListener('visibilitychange', () => {
  if (document.hidden) {
    clearHeldInputs();
    if (running && ready) saveNow({ silent: true });
  }
});

window.addEventListener('beforeunload', () => {
  if (running && ready) saveNow({ silent: true });
});

document.addEventListener('pointerlockchange', () => {
  if (document.pointerLockElement === canvas) {
    if (running && ready && !uiOpen && !photoMode) {
      ui.showHint('WASD andar · Espaço pular · E mochila · F5 visão', 2800);
    }
  } else if (running && ready && !uiOpen && !photoMode) {
    ui.showHint('Clique para capturar o mouse', 3000);
  }
});

document.addEventListener('pointerlockerror', () => {
  if (running && ready) ui.showHint('Clique novamente para capturar o mouse', 3000);
});

canvas.addEventListener('contextmenu', (event) => event.preventDefault());

canvas.addEventListener('wheel', (event) => {
  if (!running || !ready || uiOpen || photoMode || !inventory) return;
  event.preventDefault();
  const step = event.deltaY > 0 ? 1 : -1;
  onSelectHotbar((inventory.selected + step + 9) % 9);
}, { passive: false });

canvas.addEventListener('mousedown', (event) => {
  if (!running || !ready || uiOpen || photoMode || isTouch) return;
  if (document.pointerLockElement !== canvas) {
    requestLock();
    return;
  }
  if (event.button === 0) {
    mouseLeft = true;
    actionCooldown = 0;
    tryBreak();
    actionCooldown = 0.18;
  } else if (event.button === 2) {
    mouseRight = true;
    actionCooldown = 0;
    tryPlace();
    actionCooldown = 0.22;
  }
});

document.addEventListener('mouseup', (event) => {
  if (event.button === 0) mouseLeft = false;
  if (event.button === 2) mouseRight = false;
});

document.addEventListener('mousemove', (event) => {
  if (!player || document.pointerLockElement !== canvas) return;
  const sensitivity = settings.sensitivity || 1;
  player.yaw -= event.movementX * 0.0022 * sensitivity;
  player.pitch -= event.movementY * 0.0022 * sensitivity;
  player.pitch = Math.max(-1.45, Math.min(1.45, player.pitch));
  if (photoMode) {
    camera.position.set(player.position.x, player.position.y + 1.62, player.position.z);
    camera.rotation.set(player.pitch, player.yaw, 0, 'YXZ');
  }
});

canvas.addEventListener('pointerdown', (event) => {
  if (!running || !ready || uiOpen) return;
  if (event.pointerType === 'touch' || photoMode) {
    lookPointerId = event.pointerId;
    lookLastX = event.clientX;
    lookLastY = event.clientY;
    canvas.setPointerCapture?.(event.pointerId);
  }
});

canvas.addEventListener('pointermove', (event) => {
  if (lookPointerId !== event.pointerId || !player) return;
  const dx = event.clientX - lookLastX;
  const dy = event.clientY - lookLastY;
  lookLastX = event.clientX;
  lookLastY = event.clientY;
  const sensitivity = settings.sensitivity || 1;
  player.yaw -= dx * 0.004 * sensitivity;
  player.pitch -= dy * 0.004 * sensitivity;
  player.pitch = Math.max(-1.45, Math.min(1.45, player.pitch));
  if (photoMode) {
    camera.position.set(player.position.x, player.position.y + 1.62, player.position.z);
    camera.rotation.set(player.pitch, player.yaw, 0, 'YXZ');
  }
});

canvas.addEventListener('pointerup', (event) => {
  if (lookPointerId === event.pointerId) lookPointerId = null;
});

canvas.addEventListener('pointercancel', (event) => {
  if (lookPointerId === event.pointerId) lookPointerId = null;
});

const joystick = $('joystick');
const joystickKnob = $('joystick-knob');
let joystickPointerId = null;

function updateJoystick(clientX, clientY) {
  if (!joystick || !joystickKnob) return;
  const rect = joystick.getBoundingClientRect();
  const centerX = rect.left + rect.width / 2;
  const centerY = rect.top + rect.height / 2;
  const max = rect.width * 0.36;
  let dx = clientX - centerX;
  let dy = clientY - centerY;
  const length = Math.hypot(dx, dy);
  if (length > max && length > 0) {
    dx = (dx / length) * max;
    dy = (dy / length) * max;
  }
  joystickKnob.style.transform = `translate(${dx}px, ${dy}px)`;
  const nx = dx / max;
  const ny = dy / max;
  touchMove.strafe = nx;
  touchMove.forward = -ny;
  touchMove.sprint = Math.hypot(nx, ny) > 0.82;
}

function releaseJoystick() {
  joystickPointerId = null;
  touchMove.forward = 0;
  touchMove.strafe = 0;
  touchMove.sprint = false;
  if (joystickKnob) joystickKnob.style.transform = 'translate(0px, 0px)';
}

joystick?.addEventListener('pointerdown', (event) => {
  if (!running || !ready) return;
  event.preventDefault();
  joystickPointerId = event.pointerId;
  joystick.setPointerCapture?.(event.pointerId);
  updateJoystick(event.clientX, event.clientY);
});

joystick?.addEventListener('pointermove', (event) => {
  if (event.pointerId !== joystickPointerId) return;
  event.preventDefault();
  updateJoystick(event.clientX, event.clientY);
});

joystick?.addEventListener('pointerup', (event) => {
  if (event.pointerId !== joystickPointerId) return;
  releaseJoystick();
});

joystick?.addEventListener('pointercancel', (event) => {
  if (event.pointerId !== joystickPointerId) return;
  releaseJoystick();
});

function bindTouchButton(id, onDown, onUp) {
  const button = $(id);
  if (!button) return;
  button.addEventListener('pointerdown', (event) => {
    if (!running || !ready) return;
    event.preventDefault();
    onDown();
  });
  const release = (event) => {
    event.preventDefault();
    onUp();
  };
  button.addEventListener('pointerup', release);
  button.addEventListener('pointercancel', release);
  button.addEventListener('pointerleave', release);
}

bindTouchButton('btn-jump', () => {
  jumpQueued = true;
  touchMove.jump = true;
}, () => {
  touchMove.jump = false;
});

bindTouchButton('btn-break', () => {
  if (uiOpen || photoMode) return;
  mouseLeft = true;
  actionCooldown = 0;
  tryBreak();
  actionCooldown = 0.18;
}, () => {
  mouseLeft = false;
});

bindTouchButton('btn-place', () => {
  if (uiOpen || photoMode) return;
  mouseRight = true;
  actionCooldown = 0;
  tryPlace();
  actionCooldown = 0.22;
}, () => {
  mouseRight = false;
});

$('btn-fly')?.addEventListener('click', () => toggleFly());

function loop(now) {
  const dt = Math.min(0.05, Math.max(0, (now - lastTime) / 1000));
  lastTime = now;

  if (running && ready) {
    const active = !uiOpen && !paused && !photoMode;
    if (active) {
      playtimeSession += dt * 1000;
      player.update(dt, readInput());
      updateLocalAvatar(dt);
      updateCompanion(dt);
      updateNpcs(dt);
      ui.setLandingProgress(player.fly ? player.landingHold / player.holdToLandSeconds : 0);
      updateThirdPersonCamera(dt);
      audio.ambientTick(dt, isNightTime());

      actionCooldown -= dt;
      if (mouseLeft && actionCooldown <= 0) {
        tryBreak();
        actionCooldown = 0.18;
      }
      if (mouseRight && actionCooldown <= 0) {
        tryPlace();
        actionCooldown = 0.22;
      }

      chunkAccum += dt;
      if (chunkAccum > 0.3) {
        chunkAccum = 0;
        refreshChunksNearby();
      }

      updateDayNight(dt);
      particles.update(dt);
      projectiles.update(dt);

      charmTimer -= dt;
      if (charmTimer <= 0) {
        charmTimer = 1.4;
        if (player.hasCharm) {
          const ores = world.findNearbyOres(player.position.x, player.position.y, player.position.z, 6);
          for (const ore of ores.slice(0, 2)) {
            particles.burst(ore.x, ore.y, ore.z, '#ffd76a', 4, 0.2, 0.5);
          }
        }
      }

      autosaveAccum += dt * 1000;
      if (autosaveAccum > AUTOSAVE_MS) {
        autosaveAccum = 0;
        saveNow({ silent: true });
      }
    } else if (photoMode) {
      if (localAvatar) localAvatar.visible = false;
      camera.position.set(player.position.x, player.position.y + 1.62, player.position.z);
      camera.rotation.set(player.pitch, player.yaw, 0, 'YXZ');
      updateDayNight(dt * 0.15);
      particles.update(dt);
      projectiles.update(dt);
    }

    if (player) {
      if (multiplayer?.connected) multiplayer.update(dt, player);
      updateRemoteAvatars(dt);
    }

    hudAccum += dt;
    if (hudAccum > 0.15) {
      hudAccum = 0;
      updateHud();
    }
    renderer.render(scene, camera);
  }

  requestAnimationFrame(loop);
}

requestAnimationFrame(loop);

if (demoMode) {
  const demoSeed = params.get('seed') || 'lumi-demo';
  const demoWorldMode = params.get('mode') === 'creative' ? 'creative' : 'adventure';
  setTimeout(() => {
    const existing = listWorlds().find((entry) => entry.name === 'Mundo Demo' && entry.mode === demoWorldMode);
    if (existing) {
      const snapshot = getWorld(existing.id);
      if (snapshot) {
        startWorld(snapshot);
        return;
      }
      deleteWorldSave(existing.id);
    }
    onCreateWorld({ name: 'Mundo Demo', seed: demoSeed, mode: demoWorldMode });
  }, 350);
}


