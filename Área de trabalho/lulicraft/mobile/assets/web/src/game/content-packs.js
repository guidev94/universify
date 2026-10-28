import { B, BLOCKS } from './blocks.js';

export const PACK_VERSION = 1;
export const MAX_PACK_BYTES = 64 * 1024;
export const MAX_PACKS = 8;
export const MAX_PACK_BLOCKS = 24;
const STORAGE_KEY = 'lulicraft:content-packs';
const ACTIVE_KEY = 'lulicraft:active-pack';
const ALLOWED_PALETTE = new Set(['lumi', 'sky', 'pink', 'coral', 'violet', 'gold', 'cream']);
const ID_RE = /^[a-z0-9][a-z0-9-]{1,31}$/;
const HEX_RE = /^#[0-9a-f]{6}$/i;

function cleanText(value, max) {
  return String(value ?? '')
    .replace(/[\u0000-\u001f\u007f]/g, ' ')
    .trim()
    .slice(0, max);
}

function readJson(key, fallback) {
  try {
    const raw = localStorage.getItem(key);
    return raw ? JSON.parse(raw) : fallback;
  } catch {
    return fallback;
  }
}

function writeJson(key, value) {
  try {
    localStorage.setItem(key, JSON.stringify(value));
    return true;
  } catch {
    return false;
  }
}

export function validateContentPack(raw) {
  if (!raw || typeof raw !== 'object' || Array.isArray(raw)) return { ok: false, error: 'O pacote precisa ser um objeto JSON.' };
  if (Number(raw.version) !== PACK_VERSION) return { ok: false, error: 'Versão de pacote não suportada.' };
  const id = cleanText(raw.id, 32).toLowerCase();
  if (!ID_RE.test(id)) return { ok: false, error: 'Use um id curto com letras, números e hífen.' };
  const name = cleanText(raw.name, 40);
  if (name.length < 2) return { ok: false, error: 'Dê um nome ao pacote.' };
  const author = cleanText(raw.author, 30) || 'Comunidade';
  const description = cleanText(raw.description, 160);

  const palette = {};
  if (raw.palette !== undefined) {
    if (!raw.palette || typeof raw.palette !== 'object' || Array.isArray(raw.palette)) return { ok: false, error: 'Paleta inválida.' };
    for (const [key, value] of Object.entries(raw.palette)) {
      if (!ALLOWED_PALETTE.has(key)) return { ok: false, error: `Cor não permitida: ${key}.` };
      if (typeof value !== 'string' || !HEX_RE.test(value)) return { ok: false, error: `Cor inválida em ${key}.` };
      palette[key] = value.toLowerCase();
    }
  }

  const blocks = [];
  if (raw.blocks !== undefined) {
    if (!Array.isArray(raw.blocks)) return { ok: false, error: 'Lista de blocos inválida.' };
    for (const value of raw.blocks.slice(0, MAX_PACK_BLOCKS + 1)) {
      const blockId = Number(value);
      if (!Number.isInteger(blockId) || blockId <= B.AIR || blockId >= BLOCKS.length) {
        return { ok: false, error: 'O pacote contém um bloco desconhecido.' };
      }
      if (!blocks.includes(blockId)) blocks.push(blockId);
    }
    if (blocks.length > MAX_PACK_BLOCKS) return { ok: false, error: `Use no máximo ${MAX_PACK_BLOCKS} blocos.` };
  }

  return {
    ok: true,
    pack: { version: PACK_VERSION, id, name, author, description, palette, blocks }
  };
}

export function parseContentPack(text) {
  const value = String(text ?? '');
  if (new TextEncoder().encode(value).length > MAX_PACK_BYTES) return { ok: false, error: 'O pacote é grande demais.' };
  let raw;
  try {
    raw = JSON.parse(value);
  } catch {
    return { ok: false, error: 'JSON inválido. Confira vírgulas e aspas.' };
  }
  return validateContentPack(raw);
}

export function listContentPacks() {
  const stored = readJson(STORAGE_KEY, []);
  if (!Array.isArray(stored)) return [];
  const packs = [];
  for (const item of stored) {
    const result = validateContentPack(item);
    if (result.ok && !packs.some((pack) => pack.id === result.pack.id)) packs.push(result.pack);
    if (packs.length >= MAX_PACKS) break;
  }
  return packs;
}

export function saveContentPack(pack) {
  const result = validateContentPack(pack);
  if (!result.ok) return result;
  const packs = listContentPacks().filter((item) => item.id !== result.pack.id);
  if (packs.length >= MAX_PACKS) return { ok: false, error: `Você já tem ${MAX_PACKS} pacotes instalados.` };
  packs.push(result.pack);
  if (!writeJson(STORAGE_KEY, packs)) return { ok: false, error: 'Não foi possível salvar o pacote.' };
  return { ok: true, pack: result.pack };
}

export function removeContentPack(id) {
  const packs = listContentPacks().filter((pack) => pack.id !== id);
  if (!writeJson(STORAGE_KEY, packs)) return false;
  if (getActivePackId() === id) setActivePack('');
  return true;
}

export function getActivePackId() {
  try {
    return localStorage.getItem(ACTIVE_KEY) || '';
  } catch {
    return '';
  }
}

export function getActivePack() {
  const id = getActivePackId();
  return listContentPacks().find((pack) => pack.id === id) || null;
}

export function setActivePack(id) {
  const pack = listContentPacks().find((item) => item.id === id);
  try {
    if (pack) localStorage.setItem(ACTIVE_KEY, pack.id);
    else localStorage.removeItem(ACTIVE_KEY);
  } catch {
    return false;
  }
  applyContentPack(pack);
  return true;
}

export function applyContentPack(pack) {
  if (typeof document === 'undefined') return;
  const root = document.documentElement;
  for (const key of ALLOWED_PALETTE) root.style.removeProperty(`--${key}`);
  if (!pack) return;
  for (const [key, value] of Object.entries(pack.palette || {})) {
    if (ALLOWED_PALETTE.has(key) && HEX_RE.test(value)) root.style.setProperty(`--${key}`, value);
  }
}

export function getPackBlockIds() {
  return getActivePack()?.blocks?.slice() || [];
}
