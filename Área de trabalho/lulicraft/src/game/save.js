import { hashString } from '../core/rng.js';

const INDEX_KEY = 'lulicraft:index';
const WORLD_PREFIX = 'lulicraft:world:';
const SETTINGS_KEY = 'lulicraft:settings';
const DAILY_KEY = 'lulicraft:daily';
const MAX_WORLDS = 12;
const VERSION = 1;

const THUMB_PALETTES = [
  ['#b8ff6a', '#3f9e4d', '#1b3a2a'],
  ['#ffb3c7', '#b07cff', '#2a1b3d'],
  ['#ffc94d', '#ff8a6b', '#3d2a1b'],
  ['#b8e7ff', '#7cd6ff', '#1b2a3d'],
  ['#ff7ad9', '#7cd6ff', '#2a1b3d']
];

function safeParse(raw, fallback) {
  if (!raw) return fallback;
  try {
    const parsed = JSON.parse(raw);
    return parsed ?? fallback;
  } catch {
    return fallback;
  }
}

function storage() {
  try {
    const test = '__lulicraft_test__';
    localStorage.setItem(test, '1');
    localStorage.removeItem(test);
    return localStorage;
  } catch {
    return null;
  }
}

function cleanText(value, max) {
  return String(value ?? '').replace(/[<>]/g, '').trim().slice(0, max);
}

export function newId() {
  return `w_${Date.now().toString(36)}_${Math.random().toString(36).slice(2, 8)}`;
}

export function slugify(value) {
  return String(value || 'mundo')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .slice(0, 40) || 'mundo';
}

export function thumbnailFor(seed) {
  const h = hashString(String(seed));
  const palette = THUMB_PALETTES[h % THUMB_PALETTES.length];
  return { c1: palette[0], c2: palette[1], c3: palette[2] };
}

export function getIndex() {
  const store = storage();
  if (!store) return [];
  const list = safeParse(store.getItem(INDEX_KEY), []);
  if (!Array.isArray(list)) return [];
  return list.filter((entry) => entry && typeof entry.id === 'string');
}

function writeIndex(index) {
  const store = storage();
  if (!store) return false;
  try {
    store.setItem(INDEX_KEY, JSON.stringify(index));
    return true;
  } catch {
    return false;
  }
}

export function listWorlds() {
  return getIndex().sort((a, b) => (b.updatedAt || 0) - (a.updatedAt || 0));
}

export function canCreateWorld() {
  return getIndex().length < MAX_WORLDS;
}

export function createWorld({ name, seed, mode = 'adventure' }) {
  const index = getIndex();
  if (index.length >= MAX_WORLDS) {
    return { ok: false, error: `Você já tem ${MAX_WORLDS} mundos. Apague um para criar outro.` };
  }
  const cleanName = cleanText(name, 32) || 'Meu Mundo de Lumi';
  const cleanSeed = cleanText(seed, 64) || String(Date.now());
  const cleanMode = mode === 'creative' ? 'creative' : 'adventure';
  const id = newId();
  const now = Date.now();
  const entry = {
    id,
    name: cleanName,
    seed: cleanSeed,
    mode: cleanMode,
    createdAt: now,
    updatedAt: now,
    playtime: 0,
    rare: 0,
    thumb: thumbnailFor(cleanSeed)
  };
  index.push(entry);
  if (!writeIndex(index)) return { ok: false, error: 'Não foi possível salvar no navegador.' };
  return { ok: true, entry };
}

export function getWorld(id) {
  const store = storage();
  if (!store) return null;
  const raw = store.getItem(WORLD_PREFIX + id);
  const snapshot = safeParse(raw, null);
  if (!snapshot || typeof snapshot !== 'object') return null;
  return normalizeSnapshot(snapshot, id);
}

export function saveSnapshot(snapshot) {
  const store = storage();
  if (!store) return { ok: false, error: 'Armazenamento indisponível.' };
  const normalized = normalizeSnapshot(snapshot, snapshot.id);
  if (!normalized) return { ok: false, error: 'Save inválido.' };
  try {
    store.setItem(WORLD_PREFIX + normalized.id, JSON.stringify(normalized));
  } catch {
    return { ok: false, error: 'O navegador está sem espaço. Exporte e apague um mundo antigo.' };
  }
  const index = getIndex();
  const position = index.findIndex((entry) => entry.id === normalized.id);
  const entry = {
    id: normalized.id,
    name: normalized.name,
    seed: normalized.seed,
    mode: normalized.mode,
    createdAt: normalized.createdAt,
    updatedAt: Date.now(),
    playtime: normalized.playtime || 0,
    rare: (normalized.discovered || []).filter((id) => isRareId(id)).length,
    thumb: thumbnailFor(normalized.seed)
  };
  if (position >= 0) index[position] = { ...index[position], ...entry };
  else index.push(entry);
  if (!writeIndex(index)) return { ok: false, error: 'Não foi possível atualizar a lista de mundos.' };
  return { ok: true };
}

function isRareId(id) {
  return ['lumi_shard', 'aurora_gem', 'star_mushroom', 'prism_seed', 'cloud_feather', 'heart_lumi', 'lumi_charm', 'crown_lumi', 'crystal_pickaxe', 'aurora_pickaxe', 'cloud_boots', 'star_soup', 'rainbow_sapling'].includes(String(id));
}

export function deleteWorld(id) {
  const store = storage();
  if (!store) return false;
  store.removeItem(WORLD_PREFIX + id);
  const index = getIndex().filter((entry) => entry.id !== id);
  return writeIndex(index);
}

export function normalizeSnapshot(snapshot, fallbackId) {
  if (!snapshot || typeof snapshot !== 'object') return null;
  const id = cleanText(snapshot.id || fallbackId, 64);
  if (!id) return null;
  const mode = snapshot.mode === 'creative' ? 'creative' : 'adventure';
  return {
    version: VERSION,
    id,
    name: cleanText(snapshot.name, 32) || 'Meu Mundo de Lumi',
    seed: cleanText(snapshot.seed, 64) || 'lumi',
    mode,
    createdAt: Number(snapshot.createdAt) || Date.now(),
    updatedAt: Number(snapshot.updatedAt) || Date.now(),
    playtime: Math.max(0, Number(snapshot.playtime) || 0),
    timeOfDay: Math.min(1, Math.max(0, Number(snapshot.timeOfDay) || 0.3)),
    player: {
      x: Number(snapshot.player?.x) || 0,
      y: Number(snapshot.player?.y) || 24,
      z: Number(snapshot.player?.z) || 0,
      yaw: Number(snapshot.player?.yaw) || 0,
      pitch: Number(snapshot.player?.pitch) || 0,
      fly: !!snapshot.player?.fly
    },
    inventory: {
      hotbar: Array.isArray(snapshot.inventory?.hotbar) ? snapshot.inventory.hotbar.slice(0, 9) : new Array(9).fill(null),
      backpack: Array.isArray(snapshot.inventory?.backpack) ? snapshot.inventory.backpack.slice(0, 27) : new Array(27).fill(null),
      pouch: Array.isArray(snapshot.inventory?.pouch) ? snapshot.inventory.pouch.slice(0, 9) : new Array(9).fill(null),
      selected: Math.min(8, Math.max(0, Number(snapshot.inventory?.selected) || 0))
    },
    chests: snapshot.chests && typeof snapshot.chests === 'object' ? snapshot.chests : {},
    diffs: snapshot.diffs && typeof snapshot.diffs === 'object' ? snapshot.diffs : {},
    discovered: Array.isArray(snapshot.discovered) ? snapshot.discovered.slice(0, 200) : [],
    stats: snapshot.stats && typeof snapshot.stats === 'object' ? snapshot.stats : {},
    achievements: snapshot.achievements && typeof snapshot.achievements === 'object' ? snapshot.achievements : {},
    crownPlaced: !!snapshot.crownPlaced,
    biome: cleanText(snapshot.biome, 32) || 'meadow'
  };
}

export function getSettings() {
  const store = storage();
  const defaults = { sound: true, tutorialDone: false, sensitivity: 1, showCoords: false, quality: 'auto', character: null };
  if (!store) return defaults;
  return { ...defaults, ...safeParse(store.getItem(SETTINGS_KEY), {}) };
}

export function saveSettings(patch) {
  const store = storage();
  if (!store) return;
  const next = { ...getSettings(), ...patch };
  try {
    store.setItem(SETTINGS_KEY, JSON.stringify(next));
  } catch {
    return;
  }
}

export function todayKey(date = new Date()) {
  const y = date.getFullYear();
  const m = String(date.getMonth() + 1).padStart(2, '0');
  const d = String(date.getDate()).padStart(2, '0');
  return `${y}-${m}-${d}`;
}

export function getDaily() {
  const date = todayKey();
  const seed = `lumi-${date}`;
  const store = storage();
  const saved = store ? safeParse(store.getItem(DAILY_KEY), null) : null;
  if (saved && saved.date === date) return { date, seed, progress: saved.progress || 0, done: !!saved.done };
  const daily = { date, seed, progress: 0, done: false };
  if (store) {
    try {
      store.setItem(DAILY_KEY, JSON.stringify(daily));
    } catch {
      return daily;
    }
  }
  return daily;
}

export function saveDaily(daily) {
  const store = storage();
  if (!store) return;
  try {
    store.setItem(DAILY_KEY, JSON.stringify(daily));
  } catch {
    return;
  }
}

export function findDailyWorld() {
  const daily = getDaily();
  return listWorlds().find((entry) => entry.seed === daily.seed && entry.name === 'Mundo do Dia') || null;
}

function base64UrlEncode(text) {
  const bytes = new TextEncoder().encode(text);
  let binary = '';
  for (const byte of bytes) binary += String.fromCharCode(byte);
  return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
}

function base64UrlDecode(text) {
  const padded = text.replace(/-/g, '+').replace(/_/g, '/') + '==='.slice((text.length + 3) % 4);
  const binary = atob(padded);
  const bytes = new Uint8Array(binary.length);
  for (let i = 0; i < binary.length; i++) bytes[i] = binary.charCodeAt(i);
  return new TextDecoder().decode(bytes);
}

export function encodeWorldCode({ seed, name, mode }) {
  const payload = { s: String(seed).slice(0, 64), n: String(name).slice(0, 32), m: mode === 'creative' ? 'c' : 'a' };
  return `LC1.${base64UrlEncode(JSON.stringify(payload))}`;
}

export function decodeWorldCode(code) {
  const clean = String(code || '').trim();
  if (!clean.startsWith('LC1.')) return null;
  try {
    const payload = JSON.parse(base64UrlDecode(clean.slice(4)));
    if (!payload || typeof payload.s !== 'string') return null;
    return {
      seed: cleanText(payload.s, 64) || 'lumi',
      name: cleanText(payload.n, 32) || 'Mundo Compartilhado',
      mode: payload.m === 'c' ? 'creative' : 'adventure'
    };
  } catch {
    return null;
  }
}

export function exportWorld(id) {
  const snapshot = getWorld(id);
  if (!snapshot) return false;
  const blob = new Blob([JSON.stringify(snapshot, null, 2)], { type: 'application/json' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = `lulicraft-${slugify(snapshot.name)}.lulicraft`;
  document.body.appendChild(link);
  link.click();
  link.remove();
  setTimeout(() => URL.revokeObjectURL(url), 4000);
  return true;
}

export function exportSnapshot(snapshot) {
  if (!snapshot) return false;
  const blob = new Blob([JSON.stringify(snapshot, null, 2)], { type: 'application/json' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = `lulicraft-${slugify(snapshot.name)}.lulicraft`;
  document.body.appendChild(link);
  link.click();
  link.remove();
  setTimeout(() => URL.revokeObjectURL(url), 4000);
  return true;
}

export async function importWorldFile(file) {
  if (!file) return { ok: false, error: 'Nenhum arquivo escolhido.' };
  if (file.size > 8 * 1024 * 1024) return { ok: false, error: 'Arquivo grande demais.' };
  let text = '';
  try {
    text = await file.text();
  } catch {
    return { ok: false, error: 'Não foi possível ler o arquivo.' };
  }
  let parsed = null;
  try {
    parsed = JSON.parse(text);
  } catch {
    return { ok: false, error: 'Este arquivo não é um mundo de Lulicraft.' };
  }
  if (!parsed || typeof parsed !== 'object' || !parsed.seed || !parsed.name) {
    return { ok: false, error: 'Mundo inválido ou incompleto.' };
  }
  if (getIndex().length >= MAX_WORLDS) {
    return { ok: false, error: `Você já tem ${MAX_WORLDS} mundos. Apague um para importar.` };
  }
  const id = newId();
  const snapshot = normalizeSnapshot({ ...parsed, id, updatedAt: Date.now() }, id);
  const saved = saveSnapshot(snapshot);
  if (!saved.ok) return saved;
  return { ok: true, id };
}

export function importWorldCode(code) {
  const decoded = decodeWorldCode(code);
  if (!decoded) return { ok: false, error: 'Código de mundo inválido.' };
  return createWorld(decoded);
}

export function isStorageAvailable() {
  return !!storage();
}

export const SAVE_VERSION = VERSION;
export const MAX_WORLD_SLOTS = MAX_WORLDS;
