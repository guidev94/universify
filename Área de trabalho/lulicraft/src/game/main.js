import * as THREE from '../vendor/three.module.js';
import { World, BIOMES, CHUNK_SIZE, WORLD_HEIGHT, SEA_LEVEL, chestKey, chunkKey } from './world.js';
import { Player } from './player.js';
import { Inventory } from './inventory.js';
import { GameUI } from './ui.js';
import { getWeaponSkin, getPet, normalizeCharacter, characterSignature } from './characters.js';
import { getPrebuilt } from './prebuilt.js';
import { randomSeed, randomWorldName } from './seeds.js';
import { Multiplayer, normalizeRoomCode } from './multiplayer.js';
import { AudioEngine } from './audio.js';
import { B, getAtlasTexture, getBlock, getBlockIcon, isPlantBlock, isSolidBlock } from './blocks.js';
import { buildChunkGeometries, createMaterials } from './mesher.js';
import { BuildHistory, makeChange } from './build-history.js';
import { getActivePack, applyContentPack } from './content-packs.js';
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
applyContentPack(getActivePack());
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
renderer.autoClear = false;

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

/* ---------------- First-person viewmodel ---------------- */

const viewScene = new THREE.Scene();
const viewCamera = new THREE.PerspectiveCamera(64, window.innerWidth / window.innerHeight, 0.01, 6);
viewScene.add(new THREE.AmbientLight(0xffffff, 1.5));
const viewKeyLight = new THREE.DirectionalLight(0xfff6e0, 1.45);
viewKeyLight.position.set(0.6, 1.2, 1);
viewScene.add(viewKeyLight);
const viewRimLight = new THREE.DirectionalLight(0xb8ffe0, 0.5);
viewRimLight.position.set(-0.9, 0.35, 0.7);
viewScene.add(viewRimLight);
const viewModel = new THREE.Group();
const VIEW_BASE = { x: 0.5, y: -0.55, z: -1, rx: 0.2, ry: -0.5, rz: 0.16 };
const VIEW_POSES = {
  tool: { x: 0.43, y: -0.5, z: -0.94, rx: 0.14, ry: -0.36, rz: 0.22 },
  weapon: { x: 0.36, y: -0.46, z: -0.9, rx: 0.04, ry: -0.26, rz: 0.12 },
  bow: { x: 0.38, y: -0.44, z: -0.92, rx: -0.02, ry: -0.46, rz: 0.02 },
  block: { x: 0.4, y: -0.52, z: -0.96, rx: 0.18, ry: -0.5, rz: 0.14 },
  item: { x: 0.42, y: -0.52, z: -0.96, rx: 0.16, ry: -0.44, rz: 0.16 },
  hand: { x: 0.5, y: -0.55, z: -1, rx: 0.2, ry: -0.5, rz: 0.16 }
};
viewModel.position.set(VIEW_BASE.x, VIEW_BASE.y, VIEW_BASE.z);
viewModel.rotation.set(VIEW_BASE.rx, VIEW_BASE.ry, VIEW_BASE.rz);
viewModel.scale.setScalar(0.66);
viewScene.add(viewModel);

const muzzleCanvas = document.createElement('canvas');
muzzleCanvas.width = 64;
muzzleCanvas.height = 64;
const muzzleContext = muzzleCanvas.getContext('2d');
const muzzleGradient = muzzleContext.createRadialGradient(32, 32, 2, 32, 32, 30);
muzzleGradient.addColorStop(0, 'rgba(255, 255, 255, 1)');
muzzleGradient.addColorStop(0.35, 'rgba(255, 244, 190, 0.85)');
muzzleGradient.addColorStop(1, 'rgba(255, 244, 190, 0)');
muzzleContext.fillStyle = muzzleGradient;
muzzleContext.fillRect(0, 0, 64, 64);
const muzzleTexture = new THREE.CanvasTexture(muzzleCanvas);
const muzzleFlash = new THREE.Sprite(new THREE.SpriteMaterial({
  map: muzzleTexture,
  color: '#fff1b8',
  transparent: true,
  opacity: 0,
  blending: THREE.AdditiveBlending,
  depthWrite: false,
  depthTest: false
}));
muzzleFlash.position.set(0.33, -0.3, -0.82);
muzzleFlash.scale.set(0.34, 0.34, 1);
muzzleFlash.visible = false;
muzzleFlash.renderOrder = 4;
viewScene.add(muzzleFlash);
let muzzleTimer = 0;
const MUZZLE_DURATION = 0.12;

function flashMuzzle(color = '#fff1b8') {
  muzzleTimer = MUZZLE_DURATION;
  muzzleFlash.material.color.set(color);
}

const iconTextureCache = new Map();
function itemIconTexture(id) {
  if (!iconTextureCache.has(id)) {
    const texture = new THREE.TextureLoader().load(getItemIcon(id));
    texture.colorSpace = THREE.SRGBColorSpace;
    texture.magFilter = THREE.NearestFilter;
    texture.minFilter = THREE.LinearFilter;
    iconTextureCache.set(id, texture);
  }
  return iconTextureCache.get(id);
}

function blockIconTexture(blockId) {
  const key = `block:${blockId}`;
  if (!iconTextureCache.has(key)) {
    const texture = new THREE.TextureLoader().load(getBlockIcon(blockId));
    texture.colorSpace = THREE.SRGBColorSpace;
    texture.magFilter = THREE.NearestFilter;
    texture.minFilter = THREE.NearestFilter;
    texture.generateMipmaps = false;
    iconTextureCache.set(key, texture);
  }
  return iconTextureCache.get(key);
}

function disposeNode(node) {
  node.traverse((child) => {
    if (child.geometry) child.geometry.dispose?.();
    if (!child.material) return;
    const list = Array.isArray(child.material) ? child.material : [child.material];
    for (const mat of list) mat.dispose?.();
  });
}

const PICKAXE_TIERS = {
  wood_pickaxe: { head: '#b5824f', shine: '#d9a86a' },
  stone_pickaxe: { head: '#9aa7b2', shine: '#d4dde4' },
  crystal_pickaxe: { head: '#4fc9dd', shine: '#c9fbff' },
  aurora_pickaxe: { head: '#ffc94d', shine: '#fff1b8' }
};

function createHeldModel(id) {
  const item = getItem(id);
  if (!id || !item) return null;
  const group = new THREE.Group();
  const material = (color, extra = {}) => new THREE.MeshStandardMaterial({ color, roughness: 0.7, metalness: 0.08, ...extra });
  const box = (width, height, depth, color, x, y, z, rotation) => {
    const mesh = new THREE.Mesh(new THREE.BoxGeometry(width, height, depth), material(color));
    mesh.position.set(x, y, z);
    if (rotation) mesh.rotation.set(rotation[0] || 0, rotation[1] || 0, rotation[2] || 0);
    group.add(mesh);
    return mesh;
  };
  const shade = (color, amount) => {
    const value = new THREE.Color(color);
    value.offsetHSL(0, 0, amount);
    return `#${value.getHexString()}`;
  };
  const character = settings.character || {};
  const addGrip = () => {
    box(0.1, 0.24, 0.1, character.shirt || '#7cd6ff', 0, -0.14, -0.012);
    box(0.11, 0.1, 0.11, character.skin || '#f2c9a0', 0, -0.01, 0);
  };

  if (PICKAXE_TIERS[id]) {
    const tier = PICKAXE_TIERS[id];
    box(0.055, 0.66, 0.055, '#8b5a3c', 0, 0.27, 0);
    box(0.075, 0.1, 0.075, shade(tier.head, -0.12), 0, 0.0, 0);
    box(0.36, 0.085, 0.085, tier.head, 0, 0.58, 0);
    box(0.1, 0.19, 0.08, tier.head, -0.19, 0.5, 0, [0, 0, 0.55]);
    box(0.1, 0.19, 0.08, tier.head, 0.19, 0.5, 0, [0, 0, -0.55]);
    box(0.2, 0.03, 0.02, tier.shine, 0, 0.61, 0.046);
    addGrip();
    return group;
  }

  if (id === 'sun_hammer' || id === 'star_sling') {
    box(0.06, 0.52, 0.06, '#8b5a3c', 0, 0.22, 0);
    if (id === 'sun_hammer') {
      box(0.34, 0.2, 0.2, '#ffc94d', 0, 0.5, 0);
      box(0.38, 0.08, 0.22, '#e0a93a', 0, 0.5, 0);
      box(0.3, 0.04, 0.02, '#fff1b8', 0, 0.53, 0.11);
      box(0.09, 0.09, 0.09, '#ff8a6b', 0, 0.5, 0.12);
    } else {
      box(0.05, 0.2, 0.05, '#b5824f', -0.11, 0.55, 0, [0, 0, 0.35]);
      box(0.05, 0.2, 0.05, '#b5824f', 0.11, 0.55, 0, [0, 0, -0.35]);
      box(0.27, 0.02, 0.02, '#f7f3e8', 0, 0.65, 0);
      const star = new THREE.Mesh(new THREE.OctahedronGeometry(0.09), material('#ffd76a', { emissive: '#ffc94d', emissiveIntensity: 0.5 }));
      star.position.set(0, 0.65, 0);
      group.add(star);
    }
    addGrip();
    return group;
  }

  if (item.category === 'arma') {
    const skin = getWeaponSkin(settings.character);
    const color = skin.color;
    if (item.weapon?.kind === 'petal') {
      box(0.05, 0.3, 0.05, '#8b5a3c', 0, 0.14, 0);
      box(0.16, 0.36, 0.045, color, 0, 0.45, 0);
      box(0.1, 0.16, 0.05, shade(color, 0.12), 0, 0.66, 0);
      box(0.05, 0.23, 0.02, '#fff1f6', 0.005, 0.46, 0.03);
      box(0.22, 0.05, 0.07, '#ffc94d', 0, 0.28, 0);
    } else if (item.weapon?.kind === 'bubble') {
      box(0.045, 0.48, 0.045, '#8b5a3c', 0, 0.22, 0);
      const ring = new THREE.Mesh(new THREE.TorusGeometry(0.13, 0.025, 8, 20), material(color));
      ring.position.set(0, 0.57, 0);
      group.add(ring);
      box(0.1, 0.02, 0.02, '#f4fbff', 0, 0.57, 0.02);
    } else if (item.weapon?.kind === 'bow') {
      const bow = new THREE.Mesh(new THREE.TorusGeometry(0.3, 0.028, 6, 20, Math.PI * 1.15), material(color));
      bow.position.set(0, 0.33, 0);
      bow.rotation.set(0, 0, Math.PI * 0.42);
      group.add(bow);
      box(0.012, 0.58, 0.012, '#f7f3e8', 0.19, 0.34, 0);
      box(0.26, 0.02, 0.02, '#ffc94d', 0.08, 0.34, 0);
      box(0.06, 0.06, 0.02, color, 0.25, 0.34, 0);
    } else {
      box(0.05, 0.5, 0.05, '#8b5a3c', 0, 0.22, 0);
      box(0.2, 0.12, 0.06, color, 0, 0.48, 0);
      box(0.08, 0.08, 0.08, shade(color, 0.15), 0, 0.59, 0);
    }
    addGrip();
    return group;
  }

  if (item.place && item.blockId !== undefined) {
    const mesh = new THREE.Mesh(
      new THREE.BoxGeometry(0.27, 0.27, 0.27),
      new THREE.MeshStandardMaterial({ map: blockIconTexture(item.blockId), roughness: 0.9, metalness: 0 })
    );
    mesh.position.set(0, 0.2, 0);
    mesh.rotation.set(0.18, 0.55, 0.08);
    group.add(mesh);
    const edges = new THREE.LineSegments(
      new THREE.EdgesGeometry(mesh.geometry),
      new THREE.LineBasicMaterial({ color: '#14231c', transparent: true, opacity: 0.35 })
    );
    edges.position.copy(mesh.position);
    edges.rotation.copy(mesh.rotation);
    group.add(edges);
    return group;
  }

  const plane = new THREE.Mesh(
    new THREE.PlaneGeometry(0.34, 0.34),
    new THREE.MeshBasicMaterial({ map: itemIconTexture(id), transparent: true, depthWrite: false, side: THREE.DoubleSide })
  );
  plane.position.set(0, 0.24, 0);
  plane.rotation.set(-0.15, 0.25, 0.12);
  group.add(plane);
  return group;
}

let viewmodelItemId = null;
let equipTimer = 0;

function setViewmodelItem(id) {
  if (id === viewmodelItemId) return;
  viewmodelItemId = id || null;
  while (viewModel.children.length) {
    const child = viewModel.children.pop();
    disposeNode(child);
  }
  if (id) {
    const model = createHeldModel(id);
    if (model) viewModel.add(model);
    equipTimer = 0.32;
  }
}

let viewBobPhase = 0;
let viewSway = 0;
let lastViewYaw = null;

function heldItemKind(item) {
  if (!item) return 'hand';
  if (PICKAXE_TIERS[item.id] || item.id === 'sun_hammer') return 'tool';
  if (item.category === 'arma') return item.weapon?.kind === 'bow' ? 'bow' : 'weapon';
  if (item.place && item.blockId !== undefined) return 'block';
  return 'item';
}

function updateViewmodel(dt) {
  const active = running && ready && !thirdPerson && !photoMode && !uiOpen && !paused;
  viewModel.visible = active;
  muzzleTimer = Math.max(0, muzzleTimer - dt);
  muzzleFlash.visible = active && muzzleTimer > 0;
  muzzleFlash.material.opacity = muzzleTimer / MUZZLE_DURATION;
  if (!active || !player) return;

  const slot = inventory?.selectedSlot() || null;
  const item = slot ? getItem(slot.id) : null;
  setViewmodelItem(slot?.id || null);

  const kind = heldItemKind(item);
  const pose = VIEW_POSES[kind] || VIEW_POSES.hand;
  const handSign = settings.leftHand ? -1 : 1;
  const bobEnabled = settings.viewBob !== false;

  const speed = Math.hypot(player.velocity.x, player.velocity.z);
  const walking = player.onGround && speed > 0.35;
  viewBobPhase += dt * (walking ? 7.4 + speed * 0.9 : 1.9);
  const bobAmount = bobEnabled ? (walking ? 0.014 : 0.0035) : 0;
  const bobX = Math.sin(viewBobPhase * 0.9) * bobAmount;
  const bobY = Math.abs(Math.sin(viewBobPhase)) * bobAmount * 1.3;

  let yawDelta = 0;
  if (lastViewYaw !== null) {
    yawDelta = player.yaw - lastViewYaw;
    if (Math.abs(yawDelta) > 1.2) yawDelta = 0;
  }
  lastViewYaw = player.yaw;
  const swayTarget = bobEnabled ? THREE.MathUtils.clamp(yawDelta * 5, -0.05, 0.05) : 0;
  viewSway += (swayTarget - viewSway) * Math.min(1, dt * 9);
  const sway = bobEnabled ? viewSway : 0;

  equipTimer = Math.max(0, equipTimer - dt);
  const equip = equipTimer / 0.32;
  weaponKick = Math.max(0, weaponKick - dt * 4.8);
  const kick = weaponKick * weaponKick;

  const s = swingPose;
  const attack = swingType === 'attack';
  let px = 0;
  let py = 0;
  let pz = 0;
  let rx = 0;
  let ry = 0;
  let rz = 0;
  if (s > 0.001) {
    if (attack) {
      if (kind === 'bow') {
        px = -0.04 * s;
        py = 0.03 * s;
        pz = 0.12 * s;
        rx = 0.18 * s;
        ry = 0.55 * s;
        rz = -0.08 * s;
      } else if (kind === 'tool') {
        px = -0.08 * s;
        py = 0.1 * s;
        pz = -0.16 * s;
        rx = -0.85 * s;
        ry = 0.16 * s;
        rz = 0.34 * s;
      } else {
        px = -0.07 * s;
        py = 0.05 * s;
        pz = -0.26 * s;
        rx = -0.38 * s;
        ry = 0.42 * s;
        rz = -0.42 * s;
      }
    } else if (swingType === 'place') {
      pz = -0.14 * s;
      py = 0.03 * s;
      rx = -0.2 * s;
      rz = 0.1 * s;
    } else {
      px = -0.12 * s;
      py = 0.12 * s;
      pz = 0.08 * s;
      rx = -1.05 * s;
      ry = 0.18 * s;
      rz = 0.5 * s;
    }
  }

  if (kind === 'weapon' && s < 0.02) rz += 0.035 * Math.sin(viewBobPhase * 0.6);
  if (kind === 'bow' && s < 0.02) rx += 0.028 * Math.sin(viewBobPhase * 0.5);

  pz += 0.11 * kick;
  rx += 0.22 * kick;
  py -= 0.02 * kick;

  const narrow = THREE.MathUtils.clamp(viewCamera.aspect / 1.5, 0.55, 1);
  const breathe = bobEnabled ? Math.sin(viewBobPhase * 0.5) * 0.004 : 0;
  viewModel.position.set(
    (pose.x + bobX + sway * 0.5 + px) * narrow * handSign,
    pose.y - bobY - equip * 0.32 + py + breathe,
    pose.z + pz
  );
  viewModel.scale.setScalar(0.66 * (0.72 + 0.28 * narrow));
  viewModel.rotation.set(
    pose.rx + rx + equip * 0.35,
    (pose.ry + ry + sway * 4) * handSign,
    (pose.rz + rz - equip * 0.55) * handSign
  );

  muzzleFlash.position.x = 0.33 * handSign;
  muzzleFlash.position.y = -0.3 + py * 0.4;
  muzzleFlash.visible = muzzleFlash.visible && muzzleTimer > 0;
}

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

class AmbientField {
  constructor(targetScene, max = isTouch ? 70 : 150) {
    this.max = max;
    this.items = [];
    this.key = '';
    this.time = 0;
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
      size: 0.16,
      vertexColors: true,
      transparent: true,
      opacity: 0.75,
      depthWrite: false,
      blending: THREE.AdditiveBlending,
      sizeAttenuation: true
    });
    this.points = new THREE.Points(this.geometry, this.material);
    this.points.frustumCulled = false;
    targetScene.add(this.points);
  }

  paletteFor(biomeId, night) {
    const palettes = {
      meadow: { mode: 'float', day: ['#fff1a8', '#e8ffb0', '#ffffff'], night: ['#d8ff8a', '#ffe28a', '#c8ffb0'] },
      forest: { mode: 'drift', day: ['#f2ffc4', '#fff1a8', '#dcffd6'], night: ['#ffd76a', '#c8ff9e', '#ffb3c7'] },
      desert: { mode: 'sand', day: ['#ffd9a0', '#ffe9c4', '#ffbf7a'], night: ['#ffb46b', '#ffd9a0'] },
      tundra: { mode: 'snow', day: ['#ffffff', '#dff2ff', '#cfe9ff'], night: ['#cfe9ff', '#ffffff', '#b8e7ff'] },
      coast: { mode: 'float', day: ['#d8f6ff', '#ffffff', '#b8f0ff'], night: ['#9fe8ff', '#d8f6ff', '#ffffff'] }
    };
    return palettes[biomeId] || palettes.meadow;
  }

  spawn(item, player, palette) {
    const angle = Math.random() * Math.PI * 2;
    const radius = 3 + Math.random() * 16;
    item.x = player.position.x + Math.cos(angle) * radius;
    item.z = player.position.z + Math.sin(angle) * radius;
    item.y = player.position.y + 0.4 + Math.random() * 6;
    item.phase = Math.random() * Math.PI * 2;
    item.speed = 0.25 + Math.random() * 0.6;
    item.mode = palette.mode;
    const color = new THREE.Color(palette.colors[Math.floor(Math.random() * palette.colors.length)]);
    item.r = color.r;
    item.g = color.g;
    item.b = color.b;
    if (palette.mode === 'firefly') item.y = player.position.y + 0.4 + Math.random() * 2.4;
  }

  reset() {
    this.items = [];
    this.key = '';
    this.geometry.setDrawRange(0, 0);
  }

  update(dt, player, biomeId, night) {
    if (!player) return;
    const palette = this.paletteFor(biomeId, night);
    const mode = night && palette.night ? (palette.mode === 'snow' ? 'snow' : 'firefly') : palette.mode;
    const colors = night ? palette.night : palette.day;
    const key = `${biomeId}:${night ? 'night' : 'day'}`;
    if (key !== this.key) {
      this.key = key;
      this.items = [];
      for (let i = 0; i < this.max; i++) {
        const item = {};
        this.spawn(item, player, { mode, colors });
        this.items.push(item);
      }
      this.material.opacity = night ? 0.85 : 0.42;
      this.material.size = night ? 0.19 : 0.14;
    }

    this.time += dt;
    const time = this.time;
    const count = Math.min(this.items.length, this.max);
    for (let i = 0; i < count; i++) {
      const item = this.items[i];
      if (mode === 'snow') {
        item.y -= (0.7 + item.speed) * dt;
        item.x += Math.sin(time * 0.6 + item.phase) * 0.35 * dt;
        item.z += Math.cos(time * 0.45 + item.phase) * 0.25 * dt;
        if (item.y < player.position.y - 3) {
          item.y = player.position.y + 7;
          item.x = player.position.x + (Math.random() - 0.5) * 22;
          item.z = player.position.z + (Math.random() - 0.5) * 22;
        }
      } else if (mode === 'sand') {
        item.x += (0.8 + item.speed) * dt;
        item.z += Math.sin(time * 0.7 + item.phase) * 0.4 * dt;
        item.y += Math.sin(time * 1.4 + item.phase) * 0.08 * dt;
      } else if (mode === 'firefly') {
        item.x += Math.cos(time * 0.5 + item.phase) * 0.35 * dt;
        item.z += Math.sin(time * 0.4 + item.phase) * 0.35 * dt;
        item.y += Math.sin(time * 1.8 + item.phase) * 0.2 * dt;
      } else if (mode === 'drift') {
        item.x += Math.sin(time * 0.5 + item.phase) * 0.5 * dt;
        item.z += Math.cos(time * 0.37 + item.phase) * 0.35 * dt;
        item.y -= 0.12 * dt;
      } else {
        item.y += Math.sin(time * 0.8 + item.phase) * 0.22 * dt;
        item.x += Math.cos(time * 0.4 + item.phase) * 0.2 * dt;
        item.z += Math.sin(time * 0.33 + item.phase) * 0.2 * dt;
      }

      const dx = item.x - player.position.x;
      const dz = item.z - player.position.z;
      if (dx * dx + dz * dz > 22 * 22 || item.y < player.position.y - 6 || item.y > player.position.y + 12) {
        this.spawn(item, player, { mode, colors });
        continue;
      }
      const twinkle = mode === 'firefly' ? 0.4 + Math.abs(Math.sin(time * 2 + item.phase)) * 0.6 : 1;
      this.positions[i * 3] = item.x;
      this.positions[i * 3 + 1] = item.y;
      this.positions[i * 3 + 2] = item.z;
      this.colors[i * 3] = item.r * twinkle;
      this.colors[i * 3 + 1] = item.g * twinkle;
      this.colors[i * 3 + 2] = item.b * twinkle;
    }
    this.positionAttribute.needsUpdate = true;
    this.colorAttribute.needsUpdate = true;
    this.geometry.setDrawRange(0, count);
  }
}

class CloudLayer {
  constructor(targetScene) {
    this.group = new THREE.Group();
    this.material = new THREE.MeshStandardMaterial({
      color: '#ffffff',
      roughness: 1,
      metalness: 0,
      transparent: true,
      opacity: 0.9,
      depthWrite: false
    });
    this.box = new THREE.BoxGeometry(1, 1, 1);
    this.clouds = [];
    for (let i = 0; i < (isTouch ? 7 : 12); i++) {
      const cloud = new THREE.Group();
      const puffs = 2 + Math.floor(Math.random() * 3);
      for (let p = 0; p < puffs; p++) {
        const mesh = new THREE.Mesh(this.box, this.material);
        mesh.scale.set(4 + Math.random() * 7, 1.2 + Math.random() * 1.8, 3.5 + Math.random() * 5);
        mesh.position.set((p - puffs / 2) * 3.4 + (Math.random() - 0.5) * 2, (Math.random() - 0.5) * 1.2, (Math.random() - 0.5) * 3);
        cloud.add(mesh);
      }
      cloud.userData.speed = 0.35 + Math.random() * 0.65;
      cloud.userData.offset = Math.random() * Math.PI * 2;
      cloud.userData.height = 28 + Math.random() * 10;
      this.clouds.push(cloud);
      this.group.add(cloud);
    }
    this.seeded = false;
    targetScene.add(this.group);
  }

  seed(player) {
    if (!player) return;
    this.seeded = true;
    for (const cloud of this.clouds) {
      cloud.position.set(
        player.position.x + (Math.random() - 0.5) * 150,
        player.position.y + cloud.userData.height,
        player.position.z + (Math.random() - 0.5) * 150
      );
    }
  }

  reset() {
    this.seeded = false;
  }

  update(dt, player, daylight = 1) {
    if (!player) return;
    if (!this.seeded) this.seed(player);
    this.time = (this.time || 0) + dt;
    this.material.opacity = 0.18 + daylight * 0.62;
    for (const cloud of this.clouds) {
      cloud.position.x += cloud.userData.speed * dt;
      cloud.position.y += (player.position.y + cloud.userData.height - cloud.position.y) * Math.min(1, dt * 0.6);
      if (cloud.position.x > player.position.x + 85) cloud.position.x -= 170;
      if (cloud.position.x < player.position.x - 85) cloud.position.x += 170;
      if (cloud.position.z > player.position.z + 85) cloud.position.z -= 170;
      if (cloud.position.z < player.position.z - 85) cloud.position.z += 170;
    }
  }
}

const particles = new Particles(scene);
const ambientField = new AmbientField(scene);
const cloudLayer = new CloudLayer(scene);
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
  dailyDone: false,
  petHappiness: 3,
  petMode: 'follow'
};

let ui = null;
let multiplayer = null;
let world = null;
let player = null;
let inventory = null;
let currentWorld = null;
function clampSetting(value, min, max, fallback) {
  const number = Number(value);
  if (!Number.isFinite(number)) return fallback;
  return Math.min(max, Math.max(min, number));
}

function normalizeSettings(raw) {
  const source = raw && typeof raw === 'object' ? raw : {};
  return {
    ...source,
    sound: source.sound !== false,
    tutorialDone: !!source.tutorialDone,
    sensitivity: clampSetting(source.sensitivity, 0.4, 2.2, 1),
    touchSensitivity: clampSetting(source.touchSensitivity, 0.6, 2.6, 1.6),
    invertY: !!source.invertY,
    fov: clampSetting(source.fov, 60, 100, 74),
    viewBob: source.viewBob !== false,
    leftHand: !!source.leftHand,
    startThird: !!source.startThird,
    showFps: !!source.showFps,
    particles: source.particles !== false,
    clouds: source.clouds !== false,
    showCoords: !!source.showCoords,
    quality: ['auto', 'pixel', 'turbo'].includes(source.quality) ? source.quality : 'auto',
    uiScale: ['compact', 'normal', 'large'].includes(source.uiScale) ? source.uiScale : 'normal',
    character: normalizeCharacter(source.character)
  };
}

let settings = normalizeSettings(getSettings());
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
let petSpeechCooldown = 0;
let petBubble = null;
let petStayAnchor = null;
let petPettingCooldown = 0;
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
let swingTimer = 0;
let swingDuration = 0.3;
let swingType = 'mine';
let swingPose = 0;
let weaponKick = 0;
let fpsAccum = 0;
let fpsFrames = 0;
let fpsNotifyAccum = 0;
let lastFps = 60;
const mining = { key: '', progress: 0, swingAccum: 0, area: false };
const buildHistory = new BuildHistory();
let mapOpen = false;
let mapAccum = 0;
let targetOutline = new THREE.LineSegments(
  new THREE.EdgesGeometry(new THREE.BoxGeometry(1.008, 1.008, 1.008)),
  new THREE.LineBasicMaterial({ color: 0xf7f3e8, transparent: true, opacity: 0.6 })
);
targetOutline.visible = false;
targetOutline.renderOrder = 2;
scene.add(targetOutline);
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
  resetMining();
  if (targetOutline) targetOutline.visible = false;
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
  ui.swingHand('use');
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
  ui.swingHand('weapon');
  triggerSwing('attack', item.weapon?.kind === 'hammer' ? 0.52 : 0.4);
  weaponKick = 1;
  const kind = item.weapon?.kind || 'star';
  const skin = getWeaponSkin(settings.character);
  const origin = player.eyePosition();
  const aim = player.direction();
  const right = new THREE.Vector3(Math.cos(player.yaw), 0, -Math.sin(player.yaw));
  const up = right.clone().cross(aim).normalize();
  const hand = settings.leftHand ? -1 : 1;
  const start = origin.clone()
    .addScaledVector(right, 0.23 * hand)
    .addScaledVector(up, 0.12)
    .addScaledVector(aim, 0.62);
  flashMuzzle(kind === 'hammer' ? '#ffd76a' : skin.color);
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

function triggerSwing(type = 'mine', duration = 0.3) {
  swingType = type;
  swingDuration = Math.max(0.08, duration);
  swingTimer = swingDuration;
  swingPose = 0;
}

function updateSwing(dt) {
  if (swingTimer > 0) {
    swingTimer = Math.max(0, swingTimer - dt);
    const progress = 1 - swingTimer / swingDuration;
    swingPose = Math.sin(progress * Math.PI);
  } else {
    swingPose = 0;
  }
}

function miningSpeed(selected, block) {
  const hardness = Math.max(0.25, Number(block?.hardness) || 1);
  let power = 1;
  if (selected?.id === 'wood_pickaxe') power = 2.2;
  else if (selected?.id === 'stone_pickaxe') power = 3.1;
  else if (selected?.id === 'crystal_pickaxe') power = 4.6;
  else if (selected?.id === 'aurora_pickaxe') power = 8;
  else if (selected?.id === 'sun_hammer') power = 5;
  else if (selected?.category === 'arma') power = 1.6;
  return (power / hardness) * 1.15;
}

function breakBlocks(hit, { area = false } = {}) {
  if (!running || !ready || uiOpen || photoMode || !player || !inventory || !hit) return 0;

  const targets = [];
  if (area) {
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
  const changes = [];
  let broke = 0;

  for (const target of targets) {
    const id = world.getBlock(target.x, target.y, target.z);
    if (id === B.AIR || id === B.WATER || id === B.BEDROCK) continue;

    let chestBefore;
    let chestLoot;
    if (id === B.CHEST) {
      const key = chestKey(target.x, target.y, target.z);
      const rawChest = world.chests.get(key) || [];
      chestBefore = rawChest.map((slot) => (slot ? { ...slot } : null));
      const items = rawChest.filter(Boolean);
      if (items.length > 0 && !inventory.canAddMany(items)) {
        ui.toast({ title: 'Esvazie o baú primeiro', body: 'Sua mochila precisa de espaço para guardar o conteúdo.', rarity: 'comum' });
        continue;
      }
      if (items.length > 0) {
        inventory.addMany(items);
        chestLoot = items.map((item) => ({ ...item }));
        for (const item of items) discoverItem(item.id, item.count, true);
      }
      world.deleteChest(target.x, target.y, target.z);
      multiplayer?.sendChest(target.x, target.y, target.z, null);
    }

    const block = getBlock(id);
    world.setBlock(target.x, target.y, target.z, B.AIR);
    multiplayer?.sendBlock(target.x, target.y, target.z, B.AIR);
    changes.push(makeChange({
      x: target.x,
      y: target.y,
      z: target.z,
      before: id,
      after: B.AIR,
      chestBefore,
      chestAfter: chestBefore === undefined ? undefined : null,
      loot: chestLoot
    }));
    if (currentWorld.mode !== 'creative') drops.push(...rollBlockDrops(id));
    particles.burst(target.x + 0.5, target.y + 0.5, target.z + 0.5, block.color || '#ffffff', 8, 0.55, 2.2);
    touched.add(chunkKey(Math.floor(target.x / CHUNK_SIZE), Math.floor(target.z / CHUNK_SIZE)));
    stats.blocksBroken = (stats.blocksBroken || 0) + 1;
    broke += 1;
  }

  if (broke === 0) return 0;
  if (changes.length) buildHistory.record(area ? 'Quebrar área' : 'Quebrar bloco', changes);
  ui.swingHand('break');
  for (const key of touched) rebuildChunkByKey(key);
  if (drops.length > 0) acquireDrops(drops);
  audio.breakBlock(getBlock(hit.block));
  petReact('mine');
  checkAchievements();
  updateHud(true);
  return broke;
}

function tryBreak() {
  if (!running || !ready || uiOpen || photoMode || !player || !inventory) return 0;
  const hit = player.raycast();
  if (!hit) return 0;
  const selected = inventory.selectedSlot();
  const area = selected?.id === 'aurora_pickaxe' || selected?.id === 'sun_hammer';
  triggerSwing('mine', area ? 0.24 : 0.32);
  return breakBlocks(hit, { area });
}

function updateMining(dt, hit) {
  if (!hit) {
    resetMining();
    return;
  }
  const block = getBlock(hit.block);
  if (!block || hit.block === B.BEDROCK) {
    resetMining();
    return;
  }
  const selected = inventory?.selectedSlot();
  const area = selected?.id === 'aurora_pickaxe' || selected?.id === 'sun_hammer';
  const key = `${hit.x},${hit.y},${hit.z}`;
  if (mining.key !== key || mining.area !== area) {
    mining.key = key;
    mining.progress = 0;
    mining.swingAccum = 0;
    mining.area = area;
    triggerSwing('mine', area ? 0.24 : 0.32);
  }

  mining.swingAccum += dt;
  const swingInterval = area ? 0.18 : 0.34;
  if (mining.swingAccum >= swingInterval) {
    mining.swingAccum = 0;
    triggerSwing('mine', area ? 0.24 : 0.32);
    particles.burst(hit.x + 0.5, hit.y + 0.5, hit.z + 0.5, block.color || '#ffffff', 2, 0.28, 1);
  }

  const speed = area ? 7 / Math.max(0.25, block.hardness || 1) : miningSpeed(selected, block);
  mining.progress += dt * speed;
  ui.setMiningProgress(mining.progress);

  if (mining.progress >= 1) {
    mining.progress = 0;
    mining.key = '';
    mining.swingAccum = 0;
    ui.setMiningProgress(0);
    triggerSwing('mine', area ? 0.18 : 0.26);
    breakBlocks(hit, { area });
  }
}

function resetMining() {
  mining.key = '';
  mining.progress = 0;
  mining.swingAccum = 0;
  mining.area = false;
  ui?.setMiningProgress(0);
}

function updateTargetOutline(hit) {
  if (!targetOutline) return;
  const active = running && ready && !uiOpen && !photoMode && !!player;
  if (!active || !hit) {
    targetOutline.visible = false;
    ui?.setTargetInfo(null);
    return;
  }
  targetOutline.visible = true;
  targetOutline.position.set(hit.x + 0.5, hit.y + 0.5, hit.z + 0.5);
  const block = getBlock(hit.block);
  const action = hit.block === B.CHEST ? 'Clique direito para abrir' : 'Segure para quebrar';
  ui?.setTargetInfo({ name: block?.name || 'Bloco', action, rarity: block?.rarity });
}

function tryPlace() {
  if (!running || !ready || uiOpen || photoMode || !player || !inventory) return 'inactive';
  const slot = inventory.selectedSlot();
  if (!slot) {
    ui.showHint('Escolha um bloco na hotbar para construir.', 2600);
    return 'missing';
  }
  const item = getItem(slot.id);
  if (item.id === 'pet_treat') {
    feedPet();
    return 'used';
  }
  const hit = player.raycast();
  if (item.category === 'arma') {
    useWeapon(item, hit);
    return 'used';
  }
  if (!hit) {
    ui.showHint('Mire em um bloco para colocar ou quebrar.', 2200);
    return 'missing-target';
  }

  if (hit.block === B.CHEST) {
    openChestAt(hit.x, hit.y, hit.z);
    return 'used';
  }

  if (item.id === 'prism_seed' || item.id === 'rainbow_sapling') {
    plantSeed(item.id, hit);
    return 'used';
  }

  if (item.category === 'alimento' && !item.place) {
    eatItem(item.id);
    return 'used';
  }

  if (!item.place || item.blockId === undefined) {
    ui.toast({ title: item.name, body: item.desc, rarity: item.rarity, icon: getItemIcon(slot.id) });
    ui.showHint('Este item não coloca blocos. Escolha um bloco.', 2600);
    return 'not-placeable';
  }

  const target = { x: hit.x + hit.normal[0], y: hit.y + hit.normal[1], z: hit.z + hit.normal[2] };
  const existing = world.getBlock(target.x, target.y, target.z);
  if (existing !== B.AIR && existing !== B.WATER && !isPlantBlock(existing)) {
    ui.showHint('Este espaço está ocupado. Mire em outro bloco.', 2200);
    return 'blocked';
  }
  if (isSolidBlock(item.blockId) && overlapsPlayer(target.x, target.y, target.z)) {
    ui.toast({ title: 'Espaço ocupado', body: 'Saia da frente para colocar o bloco.', rarity: 'comum' });
    return 'blocked-player';
  }

  if (!world.setBlock(target.x, target.y, target.z, item.blockId)) {
    ui.showHint('Não foi possível colocar aqui.', 2200);
    return 'failed';
  }
  multiplayer?.sendBlock(target.x, target.y, target.z, item.blockId);
  buildHistory.record('Colocar bloco', [makeChange({
    x: target.x,
    y: target.y,
    z: target.z,
    before: existing,
    after: item.blockId,
    chestBefore: undefined,
    chestAfter: item.blockId === B.CHEST ? [] : undefined
  })]);
  if (item.blockId === B.CHEST) {
    world.setChest(target.x, target.y, target.z, []);
    multiplayer?.sendChest(target.x, target.y, target.z, []);
  }
  if (currentWorld.mode !== 'creative') inventory.removeOneSelected();
  player.refreshAbilities(inventory);
  ui.renderHotbar(inventory, currentWorld.mode === 'creative');
  audio.placeBlock();
  ui.swingHand('place');
  triggerSwing('place', 0.26);
  petReact('place');
  particles.burst(target.x + 0.5, target.y + 0.5, target.z + 0.5, getBlock(item.blockId).color || '#ffffff', 6, 0.4, 1.2);
  stats.blocksPlaced = (stats.blocksPlaced || 0) + 1;
  rebuildChunkAt(target.x, target.z);
  checkAchievements();
  updateHud(true);
  return 'placed';
}

function chestHasItems(change) {
  const chest = world.chests.get(chestKey(change.x, change.y, change.z)) || [];
  return chest.some(Boolean);
}

function historyLootCounts(entry) {
  const counts = new Map();
  for (const change of entry.changes) {
    for (const item of change.loot || []) {
      counts.set(item.id, (counts.get(item.id) || 0) + item.count);
    }
  }
  return counts;
}

function canUndoEntry(entry) {
  if (!inventory) return { ok: false, reason: 'Inventário indisponível.' };
  for (const change of entry.changes) {
    if (change.chestAfter !== undefined && change.chestBefore === undefined && chestHasItems(change)) {
      return { ok: false, reason: 'Esvazie o baú antes de desfazer.' };
    }
  }
  for (const [id, count] of historyLootCounts(entry)) {
    if (inventory.countItem(id) < count) {
      return { ok: false, reason: 'Os itens desse baú já foram usados. Não dá para desfazer.' };
    }
  }
  return { ok: true };
}

function canRedoEntry(entry) {
  if (!inventory) return { ok: false, reason: 'Inventário indisponível.' };
  const loot = [];
  for (const change of entry.changes) loot.push(...(change.loot || []));
  if (loot.length && !inventory.canAddMany(loot)) {
    return { ok: false, reason: 'Libere espaço na mochila para refazer.' };
  }
  return { ok: true };
}

function applyHistoryChange(change, direction) {
  if (!change || !world) return;
  const undo = direction === 'undo';
  const block = undo ? change.before : change.after;
  const chest = undo ? change.chestBefore : change.chestAfter;
  if (block === undefined || block === null) return;
  if (undo && change.loot?.length) {
    for (const item of change.loot) inventory.removeItem(item.id, item.count);
  }
  world.setBlock(change.x, change.y, change.z, block);
  multiplayer?.sendBlock(change.x, change.y, change.z, block);
  if (chest !== undefined) {
    if (chest === null) {
      world.deleteChest(change.x, change.y, change.z);
      multiplayer?.sendChest(change.x, change.y, change.z, null);
    } else {
      const copy = chest.map((slot) => (slot ? { ...slot } : null));
      world.setChest(change.x, change.y, change.z, copy);
      multiplayer?.sendChest(change.x, change.y, change.z, copy);
    }
  }
  if (!undo && change.loot?.length) {
    inventory.addMany(change.loot);
    for (const item of change.loot) discoverItem(item.id, item.count, true);
  }
  rebuildChunkAt(change.x, change.z);
}

function undoBuild() {
  if (!running || !ready || !world || uiOpen || photoMode) return false;
  const pending = buildHistory.peekUndo();
  if (!pending) {
    ui.showHint('Nada para desfazer ainda.', 2000);
    return false;
  }
  const check = canUndoEntry(pending);
  if (!check.ok) {
    ui.showHint(check.reason, 2600);
    return false;
  }
  const entry = buildHistory.undo();
  for (let i = entry.changes.length - 1; i >= 0; i--) applyHistoryChange(entry.changes[i], 'undo');
  ui.showHint(`Desfeito: ${entry.label}.`, 2200);
  audio.ui();
  petReact('idle');
  updateHud(true);
  return true;
}

function redoBuild() {
  if (!running || !ready || !world || uiOpen || photoMode) return false;
  const pending = buildHistory.peekRedo();
  if (!pending) {
    ui.showHint('Nada para refazer ainda.', 2000);
    return false;
  }
  const check = canRedoEntry(pending);
  if (!check.ok) {
    ui.showHint(check.reason, 2600);
    return false;
  }
  const entry = buildHistory.redo();
  for (const change of entry.changes) applyHistoryChange(change, 'redo');
  ui.showHint(`Refeito: ${entry.label}.`, 2200);
  audio.ui();
  petReact('idle');
  updateHud(true);
  return true;
}

function pickBlock() {
  if (!running || !ready || !world || !player || !inventory || uiOpen || photoMode) return false;
  const hit = player.raycast();
  if (!hit || hit.block === B.AIR || hit.block === B.WATER) {
    ui.showHint('Mire em um bloco para copiar.', 2000);
    return false;
  }
  if (currentWorld.mode === 'creative') {
    onCreativePick(hit.block);
    return true;
  }
  const found = inventory.findSlot(hit.block);
  if (!found) {
    ui.showHint('Esse bloco ainda não está na sua mochila.', 2400);
    return false;
  }
  if (found.arr === inventory.hotbar) {
    inventory.select(found.index);
    onInventoryChanged();
    return true;
  }
  moveItemToHotbar(hit.block);
  return true;
}

function moveItemToHotbar(id) {
  if (!inventory) return false;
  const found = inventory.findSlot(id);
  if (!found) return false;
  if (found.arr === inventory.hotbar) {
    inventory.select(found.index);
    onInventoryChanged();
    return true;
  }
  let target = inventory.hotbar.findIndex((slot) => !slot);
  if (target < 0) target = inventory.selected;
  const incoming = inventory.hotbar[target];
  inventory.hotbar[target] = found.slot;
  found.arr[found.index] = incoming;
  inventory.select(target);
  onInventoryChanged();
  ui.showHint(`${getItem(id).name} foi para a hotbar.`, 2200);
  return true;
}

function quickStoreSelected() {
  if (!running || !ready || !inventory || uiOpen || photoMode) return false;
  const slot = inventory.selectedSlot();
  if (!slot) {
    ui.showHint('Selecione um item na hotbar para guardar.', 2000);
    return false;
  }
  const target = inventory.backpack.findIndex((entry) => !entry);
  if (target < 0) {
    ui.showHint('Sua mochila está cheia.', 2200);
    return false;
  }
  inventory.backpack[target] = slot;
  inventory.hotbar[inventory.selected] = null;
  onInventoryChanged();
  ui.showHint(`${getItem(slot.id).name} guardado na mochila.`, 2200);
  return true;
}

function drawMap() {
  if (!mapOpen || !world || !player) return;
  const canvas = $('map-canvas');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  if (!ctx) return;
  const blocks = 64;
  const cell = canvas.width / blocks;
  const originX = Math.floor(player.position.x) - blocks / 2;
  const originZ = Math.floor(player.position.z) - blocks / 2;
  const biomeColors = {
    meadow: ['#5b9e45', '#79c45b', '#8ed86b'],
    forest: ['#2f6f4e', '#3f8a5d', '#58a86e'],
    desert: ['#c99a52', '#e0b56a', '#f0cf8a'],
    tundra: ['#a8c6d8', '#cfe6f2', '#eef9ff'],
    coast: ['#3b8fae', '#4fb7e8', '#8bd9f5']
  };
  ctx.clearRect(0, 0, canvas.width, canvas.height);
  for (let z = 0; z < blocks; z++) {
    for (let x = 0; x < blocks; x++) {
      const wx = originX + x;
      const wz = originZ + z;
      const h = world.heightAt(wx, wz);
      const biome = world.biomeAt(wx, wz, h);
      const palette = biomeColors[biome] || biomeColors.meadow;
      const shade = h <= SEA_LEVEL ? palette[0] : h > 30 ? palette[2] : palette[1];
      ctx.fillStyle = shade;
      ctx.fillRect(x * cell, z * cell, Math.ceil(cell), Math.ceil(cell));
      if (h > 34) {
        ctx.fillStyle = 'rgba(255,255,255,0.28)';
        ctx.fillRect(x * cell, z * cell, Math.ceil(cell), Math.ceil(cell));
      }
    }
  }
  for (const [key, items] of world.chests.entries()) {
    if (!Array.isArray(items) || !items.length) continue;
    const [cx, , cz] = key.split(',').map(Number);
    const px = (cx - originX) * cell;
    const pz = (cz - originZ) * cell;
    if (px < 0 || pz < 0 || px > canvas.width || pz > canvas.height) continue;
    ctx.fillStyle = '#ffc94d';
    ctx.fillRect(px + cell * 0.2, pz + cell * 0.2, Math.max(2, cell * 0.6), Math.max(2, cell * 0.6));
  }
  for (const npc of npcs) {
    const px = (npc.group.position.x - originX) * cell;
    const pz = (npc.group.position.z - originZ) * cell;
    ctx.fillStyle = '#ff7ad9';
    ctx.beginPath();
    ctx.arc(px, pz, Math.max(2, cell * 0.4), 0, Math.PI * 2);
    ctx.fill();
  }
  const px = (player.position.x - originX) * cell;
  const pz = (player.position.z - originZ) * cell;
  ctx.save();
  ctx.translate(px, pz);
  ctx.rotate(-player.yaw);
  ctx.fillStyle = '#ffffff';
  ctx.beginPath();
  ctx.moveTo(0, -cell * 0.9);
  ctx.lineTo(cell * 0.7, cell * 0.7);
  ctx.lineTo(0, cell * 0.35);
  ctx.lineTo(-cell * 0.7, cell * 0.7);
  ctx.closePath();
  ctx.fill();
  ctx.restore();
  const legend = $('map-legend');
  if (legend && !legend.childElementCount) {
    const entries = [
      ['#79c45b', 'Prado'],
      ['#58a86e', 'Bosque'],
      ['#f0cf8a', 'Deserto'],
      ['#eef9ff', 'Tundra'],
      ['#8bd9f5', 'Costa'],
      ['#ffc94d', 'Baú'],
      ['#ff7ad9', 'NPC']
    ];
    for (const [color, label] of entries) {
      const chip = document.createElement('span');
      const dot = document.createElement('i');
      dot.style.setProperty('--swatch', color);
      chip.appendChild(dot);
      chip.appendChild(document.createTextNode(label));
      legend.appendChild(chip);
    }
  }
}

function toggleMap(force) {
  const open = typeof force === 'boolean' ? force : !mapOpen;
  if (open && (!running || !ready || !world || !player)) return;
  mapOpen = open;
  $('map-overlay')?.classList.toggle('hidden', !open);
  if (open) drawMap();
}

function currentMissions() {
  const biomes = Array.isArray(stats.biomesVisited) ? stats.biomesVisited.length : 0;
  return [
    { id: 'lumi', icon: '✦', title: 'Colete Fragmentos de Lumi', desc: 'Os cristais brilham perto de baús e cavernas.', value: stats.lumiCollected || 0, target: 10 },
    { id: 'builder', icon: '▦', title: 'Construa um cantinho', desc: 'Coloque blocos para criar seu espaço.', value: stats.blocksPlaced || 0, target: 30 },
    { id: 'explorer', icon: '⌖', title: 'Conheça novos biomas', desc: 'Cada bioma tem cores e sons próprios.', value: biomes, target: 4 },
    { id: 'forger', icon: '⚒', title: 'Crie itens na forja', desc: 'Combine materiais e descubra receitas.', value: stats.crafted || 0, target: 5 },
    { id: 'friend', icon: '♥', title: 'Cuide do seu pet', desc: 'Brincar e alimentar aumenta o carinho.', value: Math.max(0, (stats.petHappiness ?? 3) - 3), target: 2 }
  ].map((mission) => ({ ...mission, done: mission.value >= mission.target }));
}

function updateHud(force = false) {
  if (!ui || !player || !inventory || !world || !currentWorld) return;
  const biomeId = currentBiomeId();
  if (biomeId !== lastBiome) {
    lastBiome = biomeId;
    audio.setBiome(biomeId);
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
    showCoords: !!settings.showCoords,
    petName: companion?.userData?.pet?.name || settings.character.petName || 'Lumi',
    petHearts: Math.round(stats.petHappiness ?? 3),
    petMode: stats.petMode === 'stay' ? 'stay' : 'follow',
    hasPet: !!companion
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

function makeSpeechBubble(text) {
  const canvas = document.createElement('canvas');
  canvas.width = 512;
  canvas.height = 160;
  const ctx = canvas.getContext('2d');
  ctx.clearRect(0, 0, canvas.width, canvas.height);
  ctx.fillStyle = 'rgba(247, 243, 232, 0.94)';
  ctx.beginPath();
  ctx.roundRect?.(10, 10, 492, 108, 22);
  if (ctx.roundRect) ctx.fill();
  else ctx.fillRect(10, 10, 492, 108);
  ctx.beginPath();
  ctx.moveTo(230, 118);
  ctx.lineTo(256, 148);
  ctx.lineTo(282, 118);
  ctx.closePath();
  ctx.fill();
  ctx.fillStyle = '#24313d';
  ctx.font = '700 30px "Nunito", sans-serif';
  ctx.textAlign = 'center';
  ctx.textBaseline = 'middle';
  const words = String(text || '').split(' ');
  const lines = [];
  let line = '';
  for (const word of words) {
    const test = line ? `${line} ${word}` : word;
    if (ctx.measureText(test).width > 430 && line) {
      lines.push(line);
      line = word;
    } else {
      line = test;
    }
  }
  if (line) lines.push(line);
  const shown = lines.slice(0, 2);
  const startY = shown.length === 1 ? 64 : 44;
  shown.forEach((entry, index) => ctx.fillText(entry, 256, startY + index * 36));
  const texture = new THREE.CanvasTexture(canvas);
  texture.colorSpace = THREE.SRGBColorSpace;
  texture.minFilter = THREE.LinearFilter;
  const sprite = new THREE.Sprite(new THREE.SpriteMaterial({ map: texture, transparent: true, depthTest: false, depthWrite: false }));
  sprite.scale.set(3.3, 1.03, 1);
  return sprite;
}

function petSpeech(text, duration = 4.4) {
  if (!companion) return;
  if (petBubble) {
    companion.remove(petBubble.sprite);
    petBubble.sprite.material.map?.dispose?.();
    petBubble.sprite.material.dispose?.();
    petBubble = null;
  }
  const sprite = makeSpeechBubble(text);
  sprite.position.y = 1.28;
  companion.add(sprite);
  petBubble = { sprite, life: duration };
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
  const material = (color, extra = {}) => new THREE.MeshStandardMaterial({ color, roughness: 0.86, metalness: 0.02, ...extra });
  const shade = (color, amount) => {
    const value = new THREE.Color(color);
    value.offsetHSL(0, 0, amount);
    return `#${value.getHexString()}`;
  };
  const addBox = (parent, width, height, depth, color, x, y, z, rotation) => {
    const mesh = new THREE.Mesh(new THREE.BoxGeometry(width, height, depth), material(color));
    mesh.position.set(x, y, z);
    if (rotation) mesh.rotation.set(rotation[0] || 0, rotation[1] || 0, rotation[2] || 0);
    parent.add(mesh);
    return mesh;
  };
  const character = normalizeCharacter(characterInput);
  const hair = character.hair;
  const hairDark = shade(hair, -0.08);
  const shirtDark = shade(character.shirt, -0.16);
  const skinShade = shade(character.skin, -0.06);

  addBox(group, 0.62, 0.72, 0.34, character.shirt, 0, 1.12, 0);
  addBox(group, 0.64, 0.08, 0.36, shirtDark, 0, 0.83, 0);

  if (character.outfit === 'dress') {
    addBox(group, 0.72, 0.44, 0.44, shade(character.shirt, -0.04), 0, 0.74, 0);
    addBox(group, 0.76, 0.07, 0.46, shade(character.shirt, 0.12), 0, 0.52, 0);
  } else if (character.outfit === 'hoodie') {
    addBox(group, 0.4, 0.2, 0.16, shirtDark, 0, 1.48, -0.22);
    addBox(group, 0.3, 0.15, 0.04, shade(character.shirt, -0.08), 0, 0.94, 0.185);
    addBox(group, 0.02, 0.14, 0.02, '#f7f3e8', -0.07, 1.34, 0.185);
    addBox(group, 0.02, 0.14, 0.02, '#f7f3e8', 0.07, 1.34, 0.185);
  } else if (character.outfit === 'overalls') {
    addBox(group, 0.34, 0.3, 0.03, shade(character.pants, 0.08), 0, 1.25, 0.18);
    addBox(group, 0.07, 0.34, 0.03, shade(character.pants, 0.02), -0.14, 1.35, 0.176, [0, 0, 0.06]);
    addBox(group, 0.07, 0.34, 0.03, shade(character.pants, 0.02), 0.14, 1.35, 0.176, [0, 0, -0.06]);
    addBox(group, 0.26, 0.12, 0.02, shade(character.pants, -0.1), 0, 1.21, 0.198);
  } else if (character.outfit === 'jacket') {
    addBox(group, 0.02, 0.68, 0.02, '#f7f3e8', 0, 1.12, 0.185);
    addBox(group, 0.18, 0.1, 0.04, shade(character.shirt, -0.26), -0.16, 1.44, 0.18, [0, 0, -0.5]);
    addBox(group, 0.18, 0.1, 0.04, shade(character.shirt, -0.26), 0.16, 1.44, 0.18, [0, 0, 0.5]);
    addBox(group, 0.1, 0.08, 0.02, shirtDark, -0.14, 1.02, 0.185);
    addBox(group, 0.1, 0.08, 0.02, shirtDark, 0.14, 1.02, 0.185);
  } else {
    addBox(group, 0.2, 0.12, 0.02, shade(character.shirt, 0.2), 0, 1.24, 0.18);
  }

  addBox(group, 0.34, 0.06, 0.36, shade(character.shirt, 0.16), 0, 1.44, 0);

  addBox(group, 0.5, 0.5, 0.5, character.skin, 0, 1.72, 0);
  addBox(group, 0.54, 0.14, 0.54, hair, 0, 2.0, 0);
  addBox(group, 0.08, 0.3, 0.5, hairDark, -0.27, 1.76, 0);
  addBox(group, 0.08, 0.3, 0.5, hairDark, 0.27, 1.76, 0);
  addBox(group, 0.5, 0.08, 0.06, hair, 0, 1.98, 0.28);
  addBox(group, 0.09, 0.1, 0.02, '#f7f3e8', -0.12, 1.78, 0.256);
  addBox(group, 0.09, 0.1, 0.02, '#f7f3e8', 0.12, 1.78, 0.256);
  const leftEye = addBox(group, 0.045, 0.07, 0.025, '#1b2430', -0.1, 1.78, 0.268);
  const rightEye = addBox(group, 0.045, 0.07, 0.025, '#1b2430', 0.1, 1.78, 0.268);
  addBox(group, 0.16, 0.025, 0.025, shade(hair, 0.18), -0.12, 1.87, 0.258);
  addBox(group, 0.16, 0.025, 0.025, shade(hair, 0.18), 0.12, 1.87, 0.258);
  const mouth = addBox(group, 0.14, 0.03, 0.03, '#7a3b4a', 0, 1.61, 0.262);
  const leftCheek = addBox(group, 0.05, 0.05, 0.02, shade(character.skin, -0.1), -0.19, 1.66, 0.25);
  const rightCheek = addBox(group, 0.05, 0.05, 0.02, shade(character.skin, -0.1), 0.19, 1.66, 0.25);
  group.userData.face = { leftEye, rightEye, mouth, leftCheek, rightCheek };

  const style = character.hairStyle;
  if (style === 'bob') {
    addBox(group, 0.1, 0.34, 0.34, hairDark, -0.24, 1.82, 0.04);
    addBox(group, 0.1, 0.34, 0.34, hairDark, 0.24, 1.82, 0.04);
    addBox(group, 0.46, 0.2, 0.08, hair, 0, 1.7, -0.25);
  } else if (style === 'long') {
    addBox(group, 0.1, 0.58, 0.26, hairDark, -0.24, 1.66, -0.06);
    addBox(group, 0.1, 0.58, 0.26, hairDark, 0.24, 1.66, -0.06);
    addBox(group, 0.46, 0.66, 0.1, hair, 0, 1.6, -0.25);
    addBox(group, 0.06, 0.24, 0.1, hair, -0.18, 1.5, 0.2);
    addBox(group, 0.06, 0.24, 0.1, hair, 0.18, 1.5, 0.2);
  } else if (style === 'curly') {
    for (let index = 0; index < 8; index++) {
      const angle = (index / 8) * Math.PI * 2;
      addBox(group, 0.15, 0.15, 0.15, index % 2 ? hair : hairDark, Math.cos(angle) * 0.24, 1.98 + Math.sin(angle) * 0.05, -0.02 + Math.sin(angle) * 0.21);
    }
    addBox(group, 0.16, 0.16, 0.16, hair, 0, 2.12, -0.12);
  } else if (style === 'bun') {
    addBox(group, 0.22, 0.2, 0.22, hair, 0, 2.13, -0.08);
    addBox(group, 0.18, 0.06, 0.18, hairDark, 0, 2.04, -0.08);
  } else if (style === 'ponytail') {
    addBox(group, 0.16, 0.46, 0.16, hairDark, 0, 1.86, -0.34, [0.12, 0, 0]);
    addBox(group, 0.1, 0.2, 0.1, hair, 0, 2.06, -0.28);
  } else if (style === 'buzz') {
    addBox(group, 0.5, 0.05, 0.5, hairDark, 0, 2.03, 0);
    addBox(group, 0.52, 0.12, 0.06, hair, 0, 1.9, -0.26);
  } else if (style === 'spacebuns') {
    addBox(group, 0.2, 0.2, 0.2, hair, -0.23, 2.12, -0.03);
    addBox(group, 0.2, 0.2, 0.2, hair, 0.23, 2.12, -0.03);
    addBox(group, 0.12, 0.06, 0.12, hairDark, -0.23, 2.03, -0.03);
    addBox(group, 0.12, 0.06, 0.12, hairDark, 0.23, 2.03, -0.03);
  }

  if (character.glasses !== 'none') {
    if (character.glasses === 'round') {
      for (const x of [-0.11, 0.11]) {
        const ring = new THREE.Mesh(new THREE.TorusGeometry(0.062, 0.016, 6, 14), material('#3a2e28'));
        ring.position.set(x, 1.79, 0.262);
        group.add(ring);
        addBox(group, 0.05, 0.012, 0.02, '#3a2e28', x, 1.79, 0.262);
      }
      addBox(group, 0.06, 0.012, 0.02, '#3a2e28', 0, 1.79, 0.264);
    } else if (character.glasses === 'shades') {
      addBox(group, 0.44, 0.1, 0.03, '#18222c', 0, 1.79, 0.262);
      addBox(group, 0.09, 0.08, 0.01, '#3d5f78', -0.12, 1.79, 0.28);
      addBox(group, 0.09, 0.08, 0.01, '#3d5f78', 0.12, 1.79, 0.28);
    } else {
      addBox(group, 0.5, 0.14, 0.06, '#7cd6ff', 0, 1.79, 0.26);
      addBox(group, 0.54, 0.06, 0.5, '#24344f', 0, 1.79, -0.02);
      addBox(group, 0.46, 0.07, 0.01, '#c9fbff', 0, 1.79, 0.295);
    }
  }

  const armColor = character.outfit === 'jacket' ? shade(character.shirt, -0.1) : character.shirt;
  const armPivot = (x) => {
    const pivot = new THREE.Group();
    pivot.position.set(x, 1.44, 0);
    group.add(pivot);
    addBox(pivot, 0.16, 0.5, 0.2, armColor, 0, -0.25, 0);
    addBox(pivot, 0.17, 0.1, 0.21, shade(armColor, -0.14), 0, -0.47, 0);
    addBox(pivot, 0.15, 0.12, 0.18, character.skin, 0, -0.55, 0);
    return pivot;
  };
  const legPivot = (x) => {
    const pivot = new THREE.Group();
    pivot.position.set(x, 0.74, 0);
    group.add(pivot);
    addBox(pivot, 0.22, 0.55, 0.24, character.pants, 0, -0.3, 0);
    addBox(pivot, 0.24, 0.12, 0.3, character.shoes || '#24344f', 0, -0.62, 0.03);
    addBox(pivot, 0.24, 0.03, 0.3, shade(character.shoes || '#24344f', 0.2), 0, -0.57, 0.03);
    return pivot;
  };
  const leftArm = armPivot(-0.4);
  const rightArm = armPivot(0.4);
  const leftLeg = legPivot(-0.16);
  const rightLeg = legPivot(0.16);
  const holdAnchor = new THREE.Group();
  holdAnchor.position.set(0, -0.58, 0.06);
  rightArm.add(holdAnchor);

  const hat = character.hat;
  if (hat === 'leaf') {
    addBox(group, 0.3, 0.07, 0.18, '#6fd13c', 0.14, 2.11, 0.02, [0, 0, -0.45]);
    addBox(group, 0.08, 0.09, 0.08, '#4fa52c', 0.02, 2.08, 0.02);
  } else if (hat === 'flower') {
    addBox(group, 0.1, 0.1, 0.1, '#ffc94d', 0.17, 2.11, 0.03);
    for (let index = 0; index < 5; index++) {
      const angle = (index / 5) * Math.PI * 2;
      addBox(group, 0.09, 0.05, 0.09, '#ff7ad9', 0.17 + Math.cos(angle) * 0.08, 2.12, 0.03 + Math.sin(angle) * 0.08);
    }
  } else if (hat === 'star') {
    const star = new THREE.Mesh(new THREE.OctahedronGeometry(0.11), material('#ffc94d', { emissive: '#ffc94d', emissiveIntensity: 0.35 }));
    star.position.set(0.05, 2.15, 0.02);
    group.add(star);
    const inner = new THREE.Mesh(new THREE.OctahedronGeometry(0.06), material('#fff1b8'));
    inner.position.copy(star.position);
    group.add(inner);
  } else if (hat === 'bandana') {
    addBox(group, 0.56, 0.1, 0.5, '#ff8a6b', 0, 1.99, 0);
    addBox(group, 0.14, 0.2, 0.06, '#ff8a6b', 0, 1.9, -0.3, [0, 0, 0.25]);
    addBox(group, 0.1, 0.16, 0.05, '#ffb39c', 0.09, 1.86, -0.31, [0, 0, -0.4]);
  } else if (hat === 'beanie') {
    addBox(group, 0.56, 0.2, 0.56, '#b07cff', 0, 2.06, 0);
    addBox(group, 0.58, 0.08, 0.58, '#8f5bd6', 0, 1.98, 0);
    addBox(group, 0.16, 0.16, 0.16, '#f7f3e8', 0, 2.22, 0);
  } else if (hat === 'cap') {
    addBox(group, 0.54, 0.13, 0.54, '#5aa9ff', 0, 2.06, 0);
    addBox(group, 0.44, 0.04, 0.24, '#3d7fc4', 0, 2.0, 0.34);
    addBox(group, 0.1, 0.06, 0.02, '#f7f3e8', 0, 2.09, 0.272);
  } else if (hat === 'crown') {
    addBox(group, 0.44, 0.1, 0.44, '#ffc94d', 0, 2.09, 0);
    for (const x of [-0.16, 0, 0.16]) {
      addBox(group, 0.08, 0.16, 0.06, '#ffc94d', x, 2.19, 0.19);
    }
    addBox(group, 0.06, 0.06, 0.03, '#ff7ad9', 0, 2.11, 0.226);
    addBox(group, 0.04, 0.04, 0.03, '#7cd6ff', -0.12, 2.09, 0.226);
    addBox(group, 0.04, 0.04, 0.03, '#7cd6ff', 0.12, 2.09, 0.226);
  } else if (hat === 'halo') {
    const halo = new THREE.Mesh(
      new THREE.TorusGeometry(0.21, 0.022, 8, 28),
      material('#ffe28a', { emissive: '#ffc94d', emissiveIntensity: 0.8 })
    );
    halo.position.set(0, 2.34, 0);
    halo.rotation.x = Math.PI / 2;
    group.add(halo);
  }

  if (character.back === 'cape') {
    addBox(group, 0.56, 0.92, 0.04, shade(character.shirt, -0.24), 0, 1.05, -0.2, [0.08, 0, 0]);
    addBox(group, 0.34, 0.08, 0.1, '#ffc94d', 0, 1.45, -0.16);
  } else if (character.back === 'wings') {
    addBox(group, 0.06, 0.4, 0.24, '#f4fbff', -0.36, 1.22, -0.24, [0.1, 0, 0.5]);
    addBox(group, 0.06, 0.4, 0.24, '#f4fbff', 0.36, 1.22, -0.24, [0.1, 0, -0.5]);
    addBox(group, 0.05, 0.22, 0.14, '#b8e7ff', -0.44, 1.12, -0.26, [0.1, 0, 0.7]);
    addBox(group, 0.05, 0.22, 0.14, '#b8e7ff', 0.44, 1.12, -0.26, [0.1, 0, -0.7]);
  } else if (character.back === 'backpack') {
    addBox(group, 0.38, 0.42, 0.18, '#d9a86a', 0, 1.1, -0.27);
    addBox(group, 0.3, 0.16, 0.06, '#a3763f', 0, 0.98, -0.37);
    addBox(group, 0.07, 0.5, 0.04, '#8b5a3c', -0.16, 1.18, -0.17);
    addBox(group, 0.07, 0.5, 0.04, '#8b5a3c', 0.16, 1.18, -0.17);
  } else if (character.back === 'shell') {
    addBox(group, 0.44, 0.5, 0.12, '#ffb3c7', 0, 1.15, -0.27);
    addBox(group, 0.1, 0.44, 0.06, '#ff7ad9', -0.13, 1.15, -0.35, [0, 0, 0.1]);
    addBox(group, 0.1, 0.44, 0.06, '#ff7ad9', 0.13, 1.15, -0.35, [0, 0, -0.1]);
    addBox(group, 0.32, 0.1, 0.06, '#ff7ad9', 0, 1.15, -0.36);
  }

  group.userData.limbs = { leftArm, rightArm, leftLeg, rightLeg };
  group.userData.heldAnchor = holdAnchor;
  group.userData.heldId = null;
  group.userData.character = character;
  if (tag) group.add(makeNameTag(name));
  return group;
}

function setAvatarHeldItem(avatar, id) {
  const anchor = avatar?.userData?.heldAnchor;
  if (!anchor || avatar.userData.heldId === id) return;
  avatar.userData.heldId = id;
  while (anchor.children.length) {
    const child = anchor.children.pop();
    disposeNode(child);
  }
  if (!id) return;
  const model = createHeldModel(id);
  if (!model) return;
  model.scale.setScalar(0.72);
  model.position.set(0.02, -0.06, 0.02);
  model.rotation.set(-0.2, 0.2, 0.3);
  anchor.add(model);
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
  const body = addBox(group, 0.4, 0.34, 0.5, base, 0, 0.34, 0);
  addBox(group, 0.3, 0.2, 0.08, light, 0, 0.3, 0.245);
  addBox(group, 0.36, 0.06, 0.44, '#ffd76a', 0, 0.47, 0.02);
  addBox(group, 0.08, 0.08, 0.04, character.skin || '#f7d9b8', 0, 0.45, 0.25);
  const head = addBox(group, 0.3, 0.26, 0.28, light, 0, 0.56, 0.22);
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
    addBox(group, 0.12, 0.12, 0.12, '#fff7f0', 0, 0.36, -0.28);
  } else if (character.petType === 'coruja') {
    ears.push(addBox(group, 0.1, 0.12, 0.08, dark, -0.12, 0.74, 0.18));
    ears.push(addBox(group, 0.1, 0.12, 0.08, dark, 0.12, 0.74, 0.18));
    addBox(group, 0.06, 0.22, 0.3, light, -0.22, 0.38, 0);
    addBox(group, 0.06, 0.22, 0.3, light, 0.22, 0.38, 0);
    addBox(group, 0.07, 0.05, 0.06, '#ffb46b', 0, 0.51, 0.4);
    addBox(group, 0.11, 0.11, 0.02, '#fff7e6', -0.08, 0.6, 0.372);
    addBox(group, 0.11, 0.11, 0.02, '#fff7e6', 0.08, 0.6, 0.372);
  } else if (character.petType === 'gato') {
    ears.push(addBox(group, 0.12, 0.16, 0.08, dark, -0.11, 0.75, 0.18));
    ears.push(addBox(group, 0.12, 0.16, 0.08, dark, 0.11, 0.75, 0.18));
    for (const side of [-1, 1]) {
      for (let i = 0; i < 3; i++) {
        addBox(group, 0.16, 0.012, 0.012, '#f7f3e8', side * 0.22, 0.5 + i * 0.025, 0.34);
      }
    }
    addBox(group, 0.06, 0.18, 0.06, dark, 0, 0.36, -0.3);
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

  group.userData.parts = { tail, ears, legs, body, head, petType: character.petType };
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
  petBubble = null;
  petStayAnchor = null;
  companion = createPetModel(settings.character);
  companion.position.copy(player.position);
  companion.position.y += 0.04;
  scene.add(companion);
  if (stats.petMode === 'stay') petStayAnchor = companion.position.clone();
}

function petGroundHeight(x, z, nearY) {
  if (!world) return null;
  const bx = Math.floor(x);
  const bz = Math.floor(z);
  for (let y = Math.floor(nearY); y >= Math.floor(nearY) - 6; y--) {
    if (world.isSolid(bx, y, bz)) return y + 1;
  }
  return null;
}

function updateCompanion(dt) {
  if (!companion || !player) return;
  petSpeechCooldown = Math.max(0, petSpeechCooldown - dt);
  petPettingCooldown = Math.max(0, petPettingCooldown - dt);

  const mode = stats.petMode === 'stay' ? 'stay' : 'follow';
  let target;
  if (mode === 'stay') {
    if (!petStayAnchor) petStayAnchor = companion.position.clone();
    target = petStayAnchor;
  } else {
    const yaw = player.yaw;
    const forward = 2.05;
    const side = 0.34;
    target = new THREE.Vector3(
      player.position.x - Math.sin(yaw) * forward + Math.cos(yaw) * side,
      player.position.y,
      player.position.z - Math.cos(yaw) * forward - Math.sin(yaw) * side
    );
  }

  const dx = target.x - companion.position.x;
  const dz = target.z - companion.position.z;
  const flatDist = Math.hypot(dx, dz);
  if (mode === 'follow' && flatDist > 16) {
    companion.position.set(target.x, player.position.y + 0.04, target.z);
  } else if (flatDist > 0.08) {
    const speed = mode === 'follow' ? Math.min(1, dt * (flatDist > 5 ? 5.5 : 3.2)) : 0;
    companion.position.x += dx * speed;
    companion.position.z += dz * speed;
  }

  const ground = petGroundHeight(companion.position.x, companion.position.z, companion.position.y + 1.3);
  const baseY = ground != null ? ground + 0.02 : player.position.y + 0.04;
  const moving = mode === 'follow' && flatDist > 0.28;
  const running = moving && flatDist > 3.6;
  const now = performance.now();
  const gait = now * (running ? 0.021 : 0.014);
  const hop = moving
    ? Math.abs(Math.sin(gait)) * (running ? 0.11 : 0.055)
    : Math.sin(now * 0.0018) * 0.012;
  companion.position.y += (baseY + hop - companion.position.y) * Math.min(1, dt * 10);

  const facing = flatDist > 0.18
    ? Math.atan2(dx, dz)
    : Math.atan2(player.position.x - companion.position.x, player.position.z - companion.position.z);
  companion.rotation.y = facing;

  const parts = companion.userData.parts;
  if (parts) {
    const time = now * 0.006;
    const wagSpeed = moving ? (running ? 3.4 : 2.4) : 1.45;
    const wagAmount = moving ? 0.55 : 0.32;
    parts.tail.rotation.y = Math.sin(time * wagSpeed) * wagAmount;
    parts.tail.rotation.x = moving ? Math.sin(time * wagSpeed * 2) * 0.08 : 0;
    parts.ears.forEach((ear, index) => {
      const bounce = moving ? Math.sin(gait * 2 + index * Math.PI) * (running ? 0.18 : 0.1) : 0;
      ear.rotation.z = Math.sin(time * 2.1 + index) * 0.1 + bounce;
    });
    parts.legs.forEach((leg, index) => {
      const phase = Math.sin(gait * 2.4 + index * 1.7);
      leg.position.y = 0.1 + (moving ? Math.max(0, phase) * (running ? 0.06 : 0.035) : 0);
      leg.rotation.x = moving ? phase * (running ? 0.28 : 0.16) : 0;
    });
    if (parts.head) {
      const toPlayer = Math.atan2(player.position.x - companion.position.x, player.position.z - companion.position.z);
      const look = Math.atan2(Math.sin(toPlayer - facing), Math.cos(toPlayer - facing));
      parts.head.rotation.y += ((moving ? 0 : Math.max(-0.55, Math.min(0.55, look))) - parts.head.rotation.y) * Math.min(1, dt * 8);
      parts.head.rotation.x = moving ? Math.sin(gait * 2) * 0.04 : Math.sin(time * 0.8) * 0.06;
      parts.head.position.y = 0.56 + (moving ? Math.abs(Math.sin(gait)) * 0.015 : 0);
    }
    if (parts.body) {
      parts.body.rotation.z = moving ? Math.sin(gait * 2) * (running ? 0.07 : 0.04) : 0;
      parts.body.position.y = 0.34 + (moving ? Math.abs(Math.sin(gait)) * 0.018 : Math.sin(time * 0.9) * 0.008);
    }
  }

  petBubbleTimer -= dt;
  if (flatDist < 2.6 && petBubbleTimer <= 0) {
    petBubbleTimer = 9 + Math.random() * 7;
    petReact(isNightTime() && Math.random() < 0.4 ? 'night' : 'idle');
  }

  if (petBubble) {
    petBubble.life -= dt;
    petBubble.sprite.material.opacity = Math.max(0, Math.min(1, petBubble.life));
    if (petBubble.life <= 0) {
      companion.remove(petBubble.sprite);
      petBubble.sprite.material.map?.dispose?.();
      petBubble.sprite.material.dispose?.();
      petBubble = null;
    }
  }
}

const PET_LINES = {
  idle: ['Vamos explorar juntos!', 'Sinto cheiro de flores.', 'Cadê meu petisco?', 'Este lugar é lindo!', 'Posso te seguir?'],
  night: ['As estrelas estão acesas.', 'O céu está brilhando!', 'Boa noite, amigo.'],
  treat: ['Hmm, petisco de Lumi!', 'Mais um?', 'Delícia!'],
  craft: ['Ficou lindo!', 'Você é ótimo nisso!'],
  mine: ['Cuidado com a pedra!', 'Ouvi um brilho!', 'Achei algo?'],
  place: ['Ficou aconchegante!', 'Gostei daqui!'],
  pet: ['Adoro carinho!', 'Você é meu favorito!', 'Mais carinho, por favor!'],
  land: ['Uhu!', 'De novo!'],
  jump: ['Uhu!']
};

function petReact(kind = 'happy') {
  if (!companion) return;
  const pet = companion.userData.pet || { color: '#b8ff6a', name: 'Lumi' };
  const color = kind === 'treat' || kind === 'pet' ? '#ff9ec7' : kind === 'mine' ? '#ffd76a' : pet.color;
  particles.burst(companion.position.x, companion.position.y + 0.55, companion.position.z, color, kind === 'pet' ? 18 : 10, 0.55, 1.7);
  if (kind === 'pet') {
    particles.burst(companion.position.x, companion.position.y + 0.7, companion.position.z, '#ffd76a', 8, 0.4, 1.2);
  }
  if (['treat', 'craft', 'land', 'pet'].includes(kind)) audio.pet(kind);
  const list = PET_LINES[kind];
  if (!list) return;
  if (kind !== 'idle' && kind !== 'night' && petSpeechCooldown > 0) return;
  petSpeech(list[Math.floor(Math.random() * list.length)]);
  petSpeechCooldown = kind === 'idle' || kind === 'night' ? 6 : 9;
}

function togglePetMode() {
  if (!companion) refreshCompanion();
  const next = stats.petMode === 'stay' ? 'follow' : 'stay';
  stats.petMode = next;
  petStayAnchor = next === 'stay' && companion ? companion.position.clone() : null;
  const petName = companion?.userData?.pet?.name || 'Lumi';
  ui.showHint(next === 'stay' ? `${petName} vai ficar aqui · G para chamar` : `${petName} está seguindo você · G para ficar`, 3200);
  petReact(next === 'stay' ? 'idle' : 'jump');
  updateHud(true);
  saveNow({ silent: true });
}

function petCompanion() {
  if (!running || !ready || !player) return;
  if (!companion) refreshCompanion();
  if (!companion) return;
  const distance = companion.position.distanceTo(player.position);
  if (distance > 4) {
    ui.showHint('Chegue mais perto do seu pet para fazer carinho.', 2600);
    return;
  }
  if (petPettingCooldown > 0) return;
  petPettingCooldown = 1.2;
  stats.petHappiness = Math.min(5, (stats.petHappiness ?? 3) + 1);
  petReact('pet');
  updateHud(true);
  saveNow({ silent: true });
}

function feedPet() {
  if (!inventory || !player) return;
  if (!companion) refreshCompanion();
  if (currentWorld.mode !== 'creative') inventory.removeOneSelected();
  player.refreshAbilities(inventory);
  ui.renderHotbar(inventory, currentWorld.mode === 'creative');
  audio.eat();
  ui.swingHand('craft');
  stats.petHappiness = Math.min(5, (stats.petHappiness ?? 3) + 1);
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
    npcs.push({
      group,
      def,
      baseY: y,
      index: 0,
      homeX: x,
      homeZ: z,
      phase: Math.random() * Math.PI * 2,
      speed: 0.35 + Math.random() * 0.25,
      radius: 1.6 + Math.random() * 1.1
    });
  }
}

function updateNpcs(dt) {
  if (!player || !npcs.length) return;
  let found = null;
  let foundDistance = Infinity;
  const now = performance.now() * 0.001;
  for (const npc of npcs) {
    const dx = player.position.x - npc.group.position.x;
    const dz = player.position.z - npc.group.position.z;
    const distance = Math.hypot(dx, dz);
    const near = distance < 5;
    if (near) {
      npc.group.rotation.y = Math.atan2(dx, dz);
    } else {
      const targetX = npc.homeX + Math.cos(now * npc.speed + npc.phase) * npc.radius;
      const targetZ = npc.homeZ + Math.sin(now * npc.speed * 0.8 + npc.phase) * npc.radius * 0.7;
      const stepX = targetX - npc.group.position.x;
      const stepZ = targetZ - npc.group.position.z;
      const stepLength = Math.hypot(stepX, stepZ);
      if (stepLength > 0.04) {
        const move = Math.min(stepLength, 0.55 * dt + 0.008);
        npc.group.position.x += (stepX / stepLength) * move;
        npc.group.position.z += (stepZ / stepLength) * move;
        npc.group.rotation.y = Math.atan2(stepX, stepZ);
      }
      npc.baseY = npcGroundY(npc.group.position.x, npc.group.position.z, npc.baseY + 2);
    }
    const walk = near ? 0 : Math.sin(now * 5 + npc.phase) * 0.025;
    npc.group.position.y = npc.baseY + walk + Math.sin(now * 2.5 + npc.phase) * 0.035;
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
  group.rotation.y = finite(data.yaw, 0) + Math.PI;
  group.userData.signature = characterSignature(character, data.name);
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

function animateAvatarFace(avatar) {
  const face = avatar?.userData?.face;
  if (!face) return;
  const now = performance.now();
  const blinkWave = Math.sin(now * 0.0021 + (avatar.id || 0));
  const blinking = blinkWave > 0.986;
  const eyeScale = blinking ? 0.12 : 1;
  face.leftEye.scale.y += (eyeScale - face.leftEye.scale.y) * 0.45;
  face.rightEye.scale.y = face.leftEye.scale.y;
  const smile = 1 + Math.sin(now * 0.0014) * 0.08;
  face.mouth.scale.x = smile;
  const blush = 0.82 + Math.sin(now * 0.0011) * 0.08;
  face.leftCheek.scale.setScalar(blush);
  face.rightCheek.scale.setScalar(blush);
}

function updateLocalAvatar(dt = 0) {
  if (!localAvatar || !player) return;
  localAvatar.visible = thirdPerson && !photoMode;
  if (!localAvatar.visible) return;
  localAvatar.position.copy(player.position);
  localAvatar.rotation.y = player.yaw + Math.PI;
  animateAvatarFace(localAvatar);
  const limbs = localAvatar.userData.limbs;
  if (!limbs) return;
  setAvatarHeldItem(localAvatar, inventory?.selectedSlot()?.id || null);

  const moving = player.onGround && Math.hypot(player.velocity.x, player.velocity.z) > 0.35;
  const walk = moving ? Math.sin(performance.now() * 0.012) * 0.42 : 0;
  let leftArmX = walk;
  let rightArmX = -walk;
  let leftLegX = -walk * 0.8;
  let rightLegX = walk * 0.8;
  let leftArmZ = 0;
  let rightArmZ = 0;

  if (avatarCraftTimer > 0) {
    avatarCraftTimer = Math.max(0, avatarCraftTimer - dt);
    const craftSwing = Math.sin((0.55 - avatarCraftTimer) * 18) * 0.35;
    leftArmX = -1.25 + craftSwing;
    rightArmX = -1.05 - craftSwing;
    leftArmZ = -0.18;
    rightArmZ = 0.18;
    leftLegX = 0;
    rightLegX = 0;
  } else if (player.fly) {
    leftArmX = -0.9 + Math.sin(performance.now() * 0.006) * 0.08;
    rightArmX = -0.9 - Math.sin(performance.now() * 0.006) * 0.08;
    leftLegX = 0.18;
    rightLegX = -0.18;
  } else if (!player.onGround) {
    leftArmX = -1.1;
    rightArmX = -1.1;
    leftLegX = 0.35;
    rightLegX = -0.2;
  }

  if (swingPose > 0 && avatarCraftTimer <= 0) {
    if (swingType === 'attack') {
      rightArmX = -0.35 - swingPose * 1.55;
      rightArmZ = 0.18;
    } else if (swingType === 'place') {
      rightArmX = -0.25 - swingPose * 0.9;
      rightArmZ = 0.08;
    } else {
      rightArmX = -0.2 - swingPose * 1.4;
      rightArmZ = 0.05;
    }
  } else if (localAvatar.userData.heldId && player.onGround && !moving) {
    rightArmX = Math.min(rightArmX, -0.16);
  }

  limbs.leftArm.rotation.x = leftArmX;
  limbs.rightArm.rotation.x = rightArmX;
  limbs.leftArm.rotation.z = leftArmZ;
  limbs.rightArm.rotation.z = rightArmZ;
  limbs.leftLeg.rotation.x = leftLegX;
  limbs.rightLeg.rotation.x = rightLegX;
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
  const target = new THREE.Vector3(player.position.x, player.position.y + 1.42, player.position.z);
  const horizontal = new THREE.Vector3(-Math.sin(player.yaw), 0, -Math.cos(player.yaw));
  const distance = cameraClearDistance(target, horizontal, 4.8);
  const cos = Math.cos(player.pitch);
  const aim = new THREE.Vector3(-Math.sin(player.yaw) * cos, Math.sin(player.pitch), -Math.cos(player.yaw) * cos);
  const desired = target.clone().addScaledVector(horizontal, -distance);
  desired.y += 1.05 - player.pitch * 0.9;
  if (world?.isSolid(Math.floor(desired.x), Math.floor(desired.y - 0.25), Math.floor(desired.z))) {
    desired.y = target.y + 0.55;
  }
  camera.position.lerp(desired, Math.min(1, dt * 16));
  const look = target.clone().addScaledVector(aim, 8);
  camera.lookAt(look);
}

function toggleThirdPerson() {
  if (!running || !ready) return;
  thirdPerson = !thirdPerson;
  if (player) player.cameraLocked = thirdPerson;
  ui.setThirdPerson(thirdPerson);
  notifyNative('view-changed', { thirdPerson });
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
    const signature = characterSignature(data.character, data.name);
    let avatar = remoteAvatars.get(data.id);
    if (!avatar || avatar.userData.signature !== signature) {
      if (avatar) removeRemoteAvatar(data.id);
      avatar = createRemoteAvatar(data);
      remoteAvatars.set(data.id, avatar);
    }
    const target = new THREE.Vector3(finite(data.x), finite(data.y, 24, -8, 80), finite(data.z));
    avatar.position.lerp(target, Math.min(1, dt * 10));
    avatar.rotation.y = finite(data.yaw, 0) + Math.PI;
    animateAvatarFace(avatar);
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
  buildHistory.clear();
  ambientField.reset();
  cloudLayer.reset();
  toggleMap(false);
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
  notifyNative('menu', {});
}

function enterPhoto() {
  if (!running || !ready || photoMode) return;
  photoMode = true;
  paused = false;
  if (ui.modalOpen) ui.hideAllModals();
  saveNow({ silent: true });
  clearHeldInputs();
  ui.setTargetInfo(null);
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
    player.viewBob = settings.viewBob !== false;
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
    thirdPerson = !!settings.startThird;
    if (player) player.cameraLocked = thirdPerson;
    if (localAvatar) localAvatar.visible = thirdPerson && !photoMode;
    ui.setThirdPerson(thirdPerson);
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
    ui.flashRotateHint();
    updateDayNight(0);
    updateHud(true);

    running = true;
    ready = true;
    notifyNative('ready', { world: currentWorld?.name || null, thirdPerson, touch: isTouch });
    if (thirdPerson) updateThirdPersonCamera(1);
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

function onChatPhrase(id) {
  if (!multiplayer?.connected) {
    ui?.toast({ title: 'Falas rápidas', body: 'Entre em uma sala para conversar com frases prontas.', rarity: 'comum' });
    return;
  }
  const result = multiplayer.sendChat(id);
  if (result === 'rate') ui?.showHint('Calma! Espere um instante para enviar outra frase.', 1800);
  else if (result === 'offline') ui?.toast({ title: 'Sala desconectada', body: 'Tente entrar novamente para conversar.', rarity: 'comum' });
  else if (result === 'invalid') ui?.toast({ title: 'Frase indisponível', body: 'Escolha uma das falas prontas.', rarity: 'comum' });
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
  let index = inventory.hotbar.findIndex((slot) => slot && slot.id === id);
  if (index < 0) index = inventory.hotbar.findIndex((slot) => !slot);
  if (index < 0) index = inventory.selected;
  inventory.hotbar[index] = { id, count: 1, infinite: true };
  inventory.select(index);
  discoverItem(id, 1, true);
  player.refreshAbilities(inventory);
  ui.renderHotbar(inventory, true);
  ui.renderInventory();
  audio.ui();
  ui.showHint(`${item.name} selecionado. Use Colocar para construir.`, 2600);
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
  notifyNative('pause', { world: currentWorld?.name || null });
  audio.open();
}

function onResume() {
  paused = false;
  uiOpen = false;
  audio.close();
  notifyNative('resume', {});
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
  const next = clampSetting(value, 0.4, 2.2, 1);
  settings.sensitivity = next;
  saveSettings({ sensitivity: next });
}

function onTouchSensitivity(value) {
  const next = clampSetting(value, 0.6, 2.6, 1.6);
  settings.touchSensitivity = next;
  saveSettings({ touchSensitivity: next });
}

function onInvertY(enabled) {
  settings.invertY = !!enabled;
  saveSettings({ invertY: settings.invertY });
}

function applyCameraSettings() {
  const fov = clampSetting(settings.fov, 60, 100, 74);
  settings.fov = fov;
  camera.fov = fov;
  camera.updateProjectionMatrix();
  if (player) player.viewBob = settings.viewBob !== false;
}

function applyEffectSettings() {
  const showParticles = settings.particles !== false;
  particles.points.visible = showParticles;
  ambientField.points.visible = showParticles;
  cloudLayer.group.visible = settings.clouds !== false;
  if (ui) ui.setFps(lastFps, settings.showFps);
}

function applyAllSettings() {
  applyGraphics(settings.quality);
  applyCameraSettings();
  applyEffectSettings();
  audio.setEnabled(settings.sound);
  if (ui) ui.applySettings(settings);
  notifyNative('settings-changed', { settings: { ...settings } });
}

function onFov(value) {
  settings.fov = clampSetting(value, 60, 100, 74);
  saveSettings({ fov: settings.fov });
  applyCameraSettings();
  ui.applySettings(settings);
}

function onViewBob(enabled) {
  settings.viewBob = !!enabled;
  saveSettings({ viewBob: settings.viewBob });
  if (player) player.viewBob = settings.viewBob;
  ui.applySettings(settings);
}

function onLeftHand(enabled) {
  settings.leftHand = !!enabled;
  saveSettings({ leftHand: settings.leftHand });
  ui.applySettings(settings);
}

function onStartThird(enabled) {
  settings.startThird = !!enabled;
  saveSettings({ startThird: settings.startThird });
}

function onShowFps(enabled) {
  settings.showFps = !!enabled;
  saveSettings({ showFps: settings.showFps });
  ui.setFps(lastFps, settings.showFps);
}

function onParticles(enabled) {
  settings.particles = !!enabled;
  saveSettings({ particles: settings.particles });
  applyEffectSettings();
}

function onClouds(enabled) {
  settings.clouds = !!enabled;
  saveSettings({ clouds: settings.clouds });
  applyEffectSettings();
}

function onUiScale(value) {
  settings.uiScale = ['compact', 'normal', 'large'].includes(value) ? value : 'normal';
  saveSettings({ uiScale: settings.uiScale });
  ui.applySettings(settings);
}

function onResetSettings() {
  const character = settings.character;
  const tutorialDone = settings.tutorialDone;
  settings = normalizeSettings({ character, tutorialDone });
  settings.character = normalizeCharacter(character);
  saveSettings(settings);
  applyAllSettings();
  ui.showHint('Configurações restauradas para o padrão.', 2600);
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
  if (!$('missions-modal')?.classList.contains('hidden')) {
    ui.closeMissions();
    return;
  }
  if (!$('packs-modal')?.classList.contains('hidden')) {
    ui.closePacks();
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

$('btn-map-close')?.addEventListener('click', () => toggleMap(false));

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
  onMoveToHotbar: moveItemToHotbar,
  onSelectHotbar,
  onInventoryChanged,
  onChestClose,
  onPause,
  onResume,
  onOpenMap: () => {
    ui.hideAllModals();
    toggleMap(true);
  },
  onOpenMissions: () => {
    ui.hideAllModals();
    ui.openMissions(currentMissions());
  },
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
  onTouchSensitivity,
  onInvertY,
  onCoordsToggle,
  onQuality,
  onFov,
  onViewBob,
  onLeftHand,
  onStartThird,
  onShowFps,
  onParticles,
  onClouds,
  onUiScale,
  onResetSettings,
  onCityWorld: onPrebuiltWorld,
  onOpenWorld,
  onFullscreen,
  onThirdPerson,
  onPetToggle: togglePetMode,
  onChatPhrase,
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
  onChat: (entry) => ui?.pushChatMessage(entry),
  onLeavePlayer: () => ui?.toast({ title: 'Um amigo saiu', body: 'A sala continua com os outros exploradores.', rarity: 'comum' }),
  onHostLeft
});

const params = new URLSearchParams(window.location.search);
const demoMode = params.get('demo') === '1';
const nativeApp = params.get('app') === '1' || typeof window.LulicraftBridge?.postMessage === 'function';
if (demoMode && params.get('tutorial') !== '1') settings.tutorialDone = true;

function queryBool(value) {
  return ['1', 'true', 'on', 'yes'].includes(String(value || '').toLowerCase());
}

function queryNumber(name, key, min, max) {
  if (!params.has(name)) return;
  const value = Number(params.get(name));
  if (Number.isFinite(value)) settings[key] = clampSetting(value, min, max, settings[key]);
}

function applyQuerySettings() {
  const patch = {};
  for (const [name, key] of [
    ['sound', 'sound'],
    ['invert', 'invertY'],
    ['lefthand', 'leftHand'],
    ['viewbob', 'viewBob'],
    ['coords', 'showCoords'],
    ['fps', 'showFps'],
    ['particles', 'particles'],
    ['clouds', 'clouds'],
    ['third', 'startThird']
  ]) {
    if (params.has(name)) patch[key] = queryBool(params.get(name));
  }
  if (params.has('quality') && ['auto', 'pixel', 'turbo'].includes(params.get('quality'))) {
    patch.quality = params.get('quality');
  }
  if (params.has('ui') && ['compact', 'normal', 'large'].includes(params.get('ui'))) {
    patch.uiScale = params.get('ui');
  }
  settings = normalizeSettings({ ...settings, ...patch });
  queryNumber('sens', 'sensitivity', 0.4, 2.2);
  queryNumber('touchsens', 'touchSensitivity', 0.6, 2.6);
  queryNumber('fov', 'fov', 60, 100);
  if (ui) ui.applySettings(settings);
}

applyQuerySettings();

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
    get petMode() { return stats.petMode === 'stay' ? 'stay' : 'follow'; },
    get petHappiness() { return stats.petHappiness ?? 3; },
    get petBubble() { return petBubble; },
    get ambientField() { return ambientField; },
    get cloudLayer() { return cloudLayer; },
    get stats() { return stats; },
    get npcs() { return npcs; },
    toggleThirdPerson,
    toggleMap,
    currentMissions,
    togglePetMode,
    petCompanion,
    talkToNpc,
    feedPet,
    tryBreak,
    tryPlace,
    useWeapon,
    triggerSwing,
    startMining: () => {
      mouseLeft = true;
      resetMining();
      triggerSwing('mine', 0.32);
    },
    stopMining: () => {
      mouseLeft = false;
      resetMining();
    },
    getItem,
    get projectiles() { return projectiles; },
    get viewModel() { return viewModel; },
    get viewCamera() { return viewCamera; },
    get camera() { return camera; },
    get mining() { return mining; },
    get swing() { return { timer: swingTimer, pose: swingPose, type: swingType }; },
    openChestAt,
    saveNow,
    buildSnapshot
  };
}

applyAllSettings();
ui.setFullscreen(isFullscreen());
ui.setTouchMode(isTouch);
refreshMenu();
ui.showMenu();

function applySettingsPatchFromNative(patch = {}) {
  const merged = normalizeSettings({ ...settings, ...patch });
  merged.character = normalizeCharacter(merged.character);
  settings = merged;
  saveSettings(settings);
  applyAllSettings();
  return { ...settings };
}

function notifyNative(type, data = {}) {
  const channel = window.LulicraftBridge;
  if (!channel || typeof channel.postMessage !== 'function') return;
  try {
    channel.postMessage(JSON.stringify({ type, ...data }));
  } catch (error) {
    console.warn('Bridge nativa indisponível', error);
  }
}

function hasUiOverlay() {
  return !!(
    photoMode ||
    ui?.modalOpen ||
    ui?.pauseOpen ||
    ui?.inventoryOpen ||
    ui?.chestOpen ||
    !$('settings-modal')?.classList.contains('hidden') ||
    !$('tutorial')?.classList.contains('hidden') ||
    !$('create-modal')?.classList.contains('hidden') ||
    !$('code-modal')?.classList.contains('hidden')
  );
}

window.LulicraftNative = {
  version: '1.3.0',
  getSettings: () => ({ ...settings }),
  setSettings: (patch) => applySettingsPatchFromNative(patch),
  toggleThirdPerson: () => {
    toggleThirdPerson();
    return thirdPerson;
  },
  setThirdPerson: (active) => {
    const next = !!active;
    if (running && ready && next !== thirdPerson) toggleThirdPerson();
    return thirdPerson;
  },
  isReady: () => !!(running && ready),
  getState: () => ({
    running,
    ready,
    thirdPerson,
    touch: isTouch,
    world: currentWorld?.name || null,
    fps: lastFps
  }),
  openSettings: () => ui.openSettings(),
  pause: () => {
    if (running && ready) onPause();
  },
  saveNow: () => {
    if (running && ready) saveNow({ silent: true });
    return true;
  },
  handleBack: () => {
    if (hasUiOverlay()) {
      handleEscape();
      return 'dismiss';
    }
    if (running && ready) {
      quitToMenu();
      return 'quit';
    }
    return 'none';
  },
  quitToMenu
};
window.dispatchEvent(new CustomEvent('lulicraft:native-ready'));

if (nativeApp) {
  document.body.classList.add('native-app');
  $('btn-native-home')?.classList.remove('hidden');
  $('btn-native-home')?.addEventListener('click', () => notifyNative('exit', {}));
  $('btn-pause-fullscreen')?.classList.add('hidden');
}

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
  viewCamera.aspect = window.innerWidth / window.innerHeight;
  viewCamera.updateProjectionMatrix();
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

  if ((event.ctrlKey || event.metaKey) && event.code === 'KeyZ') {
    event.preventDefault();
    if (event.shiftKey) redoBuild();
    else undoBuild();
    return;
  }
  if ((event.ctrlKey || event.metaKey) && event.code === 'KeyY') {
    event.preventDefault();
    redoBuild();
    return;
  }

  if (event.code === 'Tab') {
    event.preventDefault();
    if (!uiOpen && !photoMode) toggleMap();
    return;
  }
  if (event.code === 'KeyJ') {
    event.preventDefault();
    if (!uiOpen && !photoMode) {
      ui.openMissions(currentMissions());
      audio.open();
    }
    return;
  }
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
  if (event.code === 'KeyB') {
    event.preventDefault();
    if (currentWorld?.mode === 'creative') {
      ui.openCreativePalette();
      audio.open();
    } else {
      ui.openInventory('backpack');
      ui.showHint('No Aventura, quebre blocos para conseguir materiais.', 2600);
      audio.open();
    }
    return;
  }
  if (event.code === 'KeyQ') {
    event.preventDefault();
    quickStoreSelected();
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
  if (event.code === 'KeyG') {
    event.preventDefault();
    togglePetMode();
    return;
  }
  if (event.code === 'KeyH') {
    event.preventDefault();
    petCompanion();
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
    const index = Number(event.code.slice(5)) - 1;
    if (event.shiftKey && inventory) {
      event.preventDefault();
      const slot = inventory.hotbar[inventory.selected];
      const target = inventory.hotbar[index];
      inventory.hotbar[index] = slot;
      inventory.hotbar[inventory.selected] = target;
      inventory.select(index);
      onInventoryChanged();
      return;
    }
    onSelectHotbar(index);
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
    resetMining();
    triggerSwing('mine', 0.32);
  } else if (event.button === 1) {
    event.preventDefault();
    pickBlock();
  } else if (event.button === 2) {
    mouseRight = true;
    actionCooldown = 0;
    tryPlace();
    actionCooldown = 0.22;
  }
});

document.addEventListener('mouseup', (event) => {
  if (event.button === 0) {
    mouseLeft = false;
    resetMining();
  }
  if (event.button === 2) mouseRight = false;
});

document.addEventListener('mousemove', (event) => {
  if (!player || document.pointerLockElement !== canvas) return;
  const sensitivity = settings.sensitivity || 1;
  const invert = settings.invertY ? -1 : 1;
  player.yaw -= event.movementX * 0.0022 * sensitivity;
  player.pitch -= event.movementY * 0.0022 * sensitivity * invert;
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
  const sensitivity = settings.touchSensitivity || 1.6;
  const invert = settings.invertY ? -1 : 1;
  player.yaw -= dx * 0.004 * sensitivity;
  player.pitch -= dy * 0.004 * sensitivity * invert;
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
  resetMining();
  triggerSwing('mine', 0.32);
}, () => {
  mouseLeft = false;
  resetMining();
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
  fpsAccum += dt;
  fpsFrames += 1;
  fpsNotifyAccum += dt;
  if (fpsAccum >= 0.5) {
    lastFps = Math.round(fpsFrames / fpsAccum);
    fpsAccum = 0;
    fpsFrames = 0;
    if (ui) ui.setFps(lastFps, settings.showFps);
  }
  if (fpsNotifyAccum >= 2) {
    fpsNotifyAccum = 0;
    if (running && ready) notifyNative('fps', { fps: lastFps });
  }

  if (running && ready) {
    const active = !uiOpen && !paused && !photoMode;
    if (active) {
      playtimeSession += dt * 1000;
      player.update(dt, readInput());
      updateSwing(dt);
      const frameHit = player.raycast();
      updateTargetOutline(frameHit);
      updateLocalAvatar(dt);
      updateCompanion(dt);
      updateNpcs(dt);
      ui.setLandingProgress(player.fly ? player.landingHold / player.holdToLandSeconds : 0);
      updateThirdPersonCamera(dt);
      updateViewmodel(dt);
      audio.ambientTick(dt, isNightTime());

      actionCooldown -= dt;
      if (mouseLeft) updateMining(dt, frameHit);
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
      if (settings.particles !== false) ambientField.update(dt, player, currentBiomeId(), isNightTime());
      if (settings.clouds !== false) cloudLayer.update(dt, player, Math.max(0, Math.sin((timeOfDay - 0.25) * Math.PI * 2)));
      if (mapOpen) {
        mapAccum += dt;
        if (mapAccum >= 0.35) {
          mapAccum = 0;
          drawMap();
        }
      }
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
      if (targetOutline) targetOutline.visible = false;
      viewModel.visible = false;
      camera.position.set(player.position.x, player.position.y + 1.62, player.position.z);
      camera.rotation.set(player.pitch, player.yaw, 0, 'YXZ');
      updateDayNight(dt * 0.15);
      if (settings.particles !== false) ambientField.update(dt, player, currentBiomeId(), isNightTime());
      if (settings.clouds !== false) cloudLayer.update(dt, player, Math.max(0, Math.sin((timeOfDay - 0.25) * Math.PI * 2)));
      if (mapOpen) {
        mapAccum += dt;
        if (mapAccum >= 0.35) {
          mapAccum = 0;
          drawMap();
        }
      }
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
    renderer.clear();
    renderer.render(scene, camera);
    if (viewModel.visible) {
      renderer.clearDepth();
      renderer.render(viewScene, viewCamera);
    }
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


