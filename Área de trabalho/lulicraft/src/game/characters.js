export const CHARACTER_PRESETS = [
  {
    id: 'lumi',
    name: 'Lumi',
    skin: '#f2c9a0',
    shirt: '#b8ff6a',
    pants: '#2f6f4e',
    hair: '#5a3b2e',
    accessory: 'leaf'
  },
  {
    id: 'nino',
    name: 'Nino',
    skin: '#8d5a3b',
    shirt: '#ff8a6b',
    pants: '#24344f',
    hair: '#211b18',
    accessory: 'star'
  },
  {
    id: 'bia',
    name: 'Bia',
    skin: '#f7d7b5',
    shirt: '#b07cff',
    pants: '#ffb3c7',
    hair: '#e07aa0',
    accessory: 'flower'
  },
  {
    id: 'tico',
    name: 'Tico',
    skin: '#c98e5f',
    shirt: '#7cd6ff',
    pants: '#d9b36c',
    hair: '#2c8f8f',
    accessory: 'goggles'
  },
  {
    id: 'dara',
    name: 'Dara',
    skin: '#6f4630',
    shirt: '#ffc94d',
    pants: '#1f6f6a',
    hair: '#3a2420',
    accessory: 'bandana'
  },
  {
    id: 'sol',
    name: 'Sol',
    skin: '#8d5a3b',
    shirt: '#ffc94d',
    pants: '#24344f',
    hair: '#211b18',
    accessory: 'star'
  },
  {
    id: 'maya',
    name: 'Maya',
    skin: '#c98e5f',
    shirt: '#ff7ad9',
    pants: '#2f6f4e',
    hair: '#3a2420',
    accessory: 'flower'
  },
  {
    id: 'kai',
    name: 'Kai',
    skin: '#f2c9a0',
    shirt: '#7cd6ff',
    pants: '#24344f',
    hair: '#2c8f8f',
    accessory: 'goggles'
  },
  {
    id: 'luna',
    name: 'Luna',
    skin: '#f7d7b5',
    shirt: '#b07cff',
    pants: '#1f6f6a',
    hair: '#f7f3e8',
    accessory: 'bandana'
  }
];

export const CHARACTER_ACCESSORIES = ['none', 'leaf', 'star', 'flower', 'goggles', 'bandana'];

export const WEAPON_SKINS = [
  { id: 'aurora', name: 'Aurora', color: '#b07cff' },
  { id: 'petal', name: 'Pétala', color: '#ff7ad9' },
  { id: 'solar', name: 'Solar', color: '#ffc94d' },
  { id: 'bubble', name: 'Bolha', color: '#7cd6ff' }
];

export const COLOR_SWATCHES = {
  skin: ['#f7d7b5', '#f2c9a0', '#c98e5f', '#8d5a3b', '#6f4630', '#4d2f21'],
  shirt: ['#b8ff6a', '#7cd6ff', '#ff8a6b', '#ffb3c7', '#b07cff', '#ffc94d', '#f7f3e8', '#24344f'],
  pants: ['#2f6f4e', '#24344f', '#1f6f6a', '#d9b36c', '#6b4b8a', '#3a2420', '#ffb3c7', '#f7f3e8'],
  hair: ['#5a3b2e', '#211b18', '#3a2420', '#e07aa0', '#2c8f8f', '#b07cff', '#d9b36c', '#f7f3e8']
};

export const DEFAULT_CHARACTER = {
  preset: 'lumi',
  custom: false,
  name: 'Lumi',
  skin: CHARACTER_PRESETS[0].skin,
  shirt: CHARACTER_PRESETS[0].shirt,
  pants: CHARACTER_PRESETS[0].pants,
  hair: CHARACTER_PRESETS[0].hair,
  accessory: CHARACTER_PRESETS[0].accessory,
  weaponSkin: 'aurora'
};

const HEX_COLOR = /^#[0-9a-f]{6}$/i;

function cleanName(value, fallback) {
  const name = String(value ?? '').replace(/[<>]/g, '').trim().slice(0, 16);
  return name || fallback;
}

function safeColor(value, fallback) {
  const color = String(value || '');
  return HEX_COLOR.test(color) ? color.toLowerCase() : fallback;
}

export function characterFromPreset(id) {
  const preset = CHARACTER_PRESETS.find((entry) => entry.id === id) || CHARACTER_PRESETS[0];
  return {
    preset: preset.id,
    custom: false,
    name: preset.name,
    skin: preset.skin,
    shirt: preset.shirt,
    pants: preset.pants,
    hair: preset.hair,
    accessory: preset.accessory,
    weaponSkin: 'aurora'
  };
}

export function normalizeCharacter(value) {
  const source = value && typeof value === 'object' ? value : {};
  const preset = CHARACTER_PRESETS.find((entry) => entry.id === source.preset) || CHARACTER_PRESETS[0];
  const hasPreset = CHARACTER_PRESETS.some((entry) => entry.id === source.preset);
  const custom = !!source.custom || (source.preset != null && !hasPreset);
  return {
    preset: custom ? 'custom' : preset.id,
    custom,
    name: cleanName(source.name, preset.name),
    skin: safeColor(source.skin, preset.skin),
    shirt: safeColor(source.shirt, preset.shirt),
    pants: safeColor(source.pants, preset.pants),
    hair: safeColor(source.hair, preset.hair),
    accessory: CHARACTER_ACCESSORIES.includes(source.accessory) ? source.accessory : preset.accessory,
    weaponSkin: WEAPON_SKINS.some((entry) => entry.id === source.weaponSkin) ? source.weaponSkin : 'aurora'
  };
}

export function getWeaponSkin(character) {
  const value = normalizeCharacter(character);
  return WEAPON_SKINS.find((entry) => entry.id === value.weaponSkin) || WEAPON_SKINS[0];
}

export function characterLabel(character) {
  const value = normalizeCharacter(character);
  return value.name || 'Explorador';
}
