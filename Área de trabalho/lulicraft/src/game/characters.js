export const HAIR_STYLES = [
  { id: 'short', name: 'Curto' },
  { id: 'bob', name: 'Chanel' },
  { id: 'long', name: 'Longo' },
  { id: 'curly', name: 'Cacheado' },
  { id: 'bun', name: 'Coque' },
  { id: 'ponytail', name: 'Rabo' },
  { id: 'buzz', name: 'Raspado' },
  { id: 'spacebuns', name: 'Dois coquinhos' }
];

export const OUTFITS = [
  { id: 'tee', name: 'Camiseta' },
  { id: 'hoodie', name: 'Moletom' },
  { id: 'overalls', name: 'Jardineira' },
  { id: 'jacket', name: 'Jaqueta' },
  { id: 'dress', name: 'Vestido' }
];

export const HATS = [
  { id: 'none', name: 'Sem chapéu' },
  { id: 'leaf', name: 'Folhinha' },
  { id: 'flower', name: 'Flor' },
  { id: 'star', name: 'Estrela' },
  { id: 'bandana', name: 'Bandana' },
  { id: 'beanie', name: 'Touca' },
  { id: 'cap', name: 'Boné' },
  { id: 'crown', name: 'Coroa' },
  { id: 'halo', name: 'Auréola' }
];

export const GLASSES = [
  { id: 'none', name: 'Sem óculos' },
  { id: 'round', name: 'Redondos' },
  { id: 'shades', name: 'Escuros' },
  { id: 'goggles', name: 'Óculos de sol' }
];

export const BACK_ITEMS = [
  { id: 'none', name: 'Nada' },
  { id: 'cape', name: 'Capa' },
  { id: 'wings', name: 'Asas' },
  { id: 'backpack', name: 'Mochila' },
  { id: 'shell', name: 'Concha' }
];

export const CHARACTER_PRESETS = [
  {
    id: 'lumi',
    name: 'Lumi',
    skin: '#f2c9a0',
    shirt: '#b8ff6a',
    pants: '#2f6f4e',
    hair: '#5a3b2e',
    accessory: 'leaf',
    hairStyle: 'bob',
    outfit: 'tee',
    hat: 'leaf',
    glasses: 'none',
    back: 'none',
    shoes: '#24344f'
  },
  {
    id: 'nino',
    name: 'Nino',
    skin: '#8d5a3b',
    shirt: '#ff8a6b',
    pants: '#24344f',
    hair: '#211b18',
    accessory: 'star',
    hairStyle: 'short',
    outfit: 'hoodie',
    hat: 'none',
    glasses: 'none',
    back: 'backpack',
    shoes: '#17231f'
  },
  {
    id: 'bia',
    name: 'Bia',
    skin: '#f7d7b5',
    shirt: '#b07cff',
    pants: '#ffb3c7',
    hair: '#e07aa0',
    accessory: 'flower',
    hairStyle: 'long',
    outfit: 'dress',
    hat: 'flower',
    glasses: 'none',
    back: 'none',
    shoes: '#ffb3c7'
  },
  {
    id: 'tico',
    name: 'Tico',
    skin: '#c98e5f',
    shirt: '#7cd6ff',
    pants: '#d9b36c',
    hair: '#2c8f8f',
    accessory: 'goggles',
    hairStyle: 'curly',
    outfit: 'overalls',
    hat: 'cap',
    glasses: 'round',
    back: 'none',
    shoes: '#2f6f4e'
  },
  {
    id: 'dara',
    name: 'Dara',
    skin: '#6f4630',
    shirt: '#ffc94d',
    pants: '#1f6f6a',
    hair: '#3a2420',
    accessory: 'bandana',
    hairStyle: 'bun',
    outfit: 'jacket',
    hat: 'bandana',
    glasses: 'none',
    back: 'cape',
    shoes: '#3a2420'
  },
  {
    id: 'sol',
    name: 'Sol',
    skin: '#8d5a3b',
    shirt: '#ffc94d',
    pants: '#24344f',
    hair: '#211b18',
    accessory: 'star',
    hairStyle: 'buzz',
    outfit: 'tee',
    hat: 'star',
    glasses: 'shades',
    back: 'none',
    shoes: '#24344f'
  },
  {
    id: 'maya',
    name: 'Maya',
    skin: '#c98e5f',
    shirt: '#ff7ad9',
    pants: '#2f6f4e',
    hair: '#3a2420',
    accessory: 'flower',
    hairStyle: 'ponytail',
    outfit: 'dress',
    hat: 'flower',
    glasses: 'round',
    back: 'wings',
    shoes: '#ffb3c7'
  },
  {
    id: 'kai',
    name: 'Kai',
    skin: '#f2c9a0',
    shirt: '#7cd6ff',
    pants: '#24344f',
    hair: '#2c8f8f',
    accessory: 'goggles',
    hairStyle: 'short',
    outfit: 'jacket',
    hat: 'beanie',
    glasses: 'goggles',
    back: 'backpack',
    shoes: '#1f6f6a'
  },
  {
    id: 'luna',
    name: 'Luna',
    skin: '#f7d7b5',
    shirt: '#b07cff',
    pants: '#1f6f6a',
    hair: '#f7f3e8',
    accessory: 'bandana',
    hairStyle: 'long',
    outfit: 'hoodie',
    hat: 'halo',
    glasses: 'none',
    back: 'wings',
    shoes: '#b07cff'
  },
  {
    id: 'nori',
    name: 'Nori',
    skin: '#f2c9a0',
    shirt: '#ff8a6b',
    pants: '#3f3a63',
    hair: '#e07aa0',
    accessory: 'none',
    hairStyle: 'spacebuns',
    outfit: 'hoodie',
    hat: 'cap',
    glasses: 'shades',
    back: 'backpack',
    shoes: '#ffc94d'
  },
  {
    id: 'iris',
    name: 'Iris',
    skin: '#c98e5f',
    shirt: '#5fd38a',
    pants: '#d9b36c',
    hair: '#5a3b2e',
    accessory: 'flower',
    hairStyle: 'curly',
    outfit: 'overalls',
    hat: 'none',
    glasses: 'round',
    back: 'shell',
    shoes: '#8b5a3c'
  },
  {
    id: 'zoe',
    name: 'Zoe',
    skin: '#f7d7b5',
    shirt: '#f7f3e8',
    pants: '#1f6f6a',
    hair: '#211b18',
    accessory: 'none',
    hairStyle: 'ponytail',
    outfit: 'jacket',
    hat: 'beanie',
    glasses: 'none',
    back: 'cape',
    shoes: '#24344f'
  },
  {
    id: 'pipo',
    name: 'Pipo',
    skin: '#c98e5f',
    shirt: '#5fd38a',
    pants: '#3f3a63',
    hair: '#211b18',
    accessory: 'star',
    hairStyle: 'curly',
    outfit: 'tee',
    hat: 'cap',
    glasses: 'round',
    back: 'backpack',
    shoes: '#ffc94d'
  },
  {
    id: 'nara',
    name: 'Nara',
    skin: '#6f4630',
    shirt: '#ff7ad9',
    pants: '#24344f',
    hair: '#e07aa0',
    accessory: 'flower',
    hairStyle: 'bun',
    outfit: 'dress',
    hat: 'flower',
    glasses: 'none',
    back: 'none',
    shoes: '#ffb3c7'
  },
  {
    id: 'teo',
    name: 'Teo',
    skin: '#f2c9a0',
    shirt: '#ffc94d',
    pants: '#1f6f6a',
    hair: '#5a3b2e',
    accessory: 'leaf',
    hairStyle: 'short',
    outfit: 'overalls',
    hat: 'leaf',
    glasses: 'none',
    back: 'none',
    shoes: '#2f6f4e'
  },
  {
    id: 'yumi',
    name: 'Yumi',
    skin: '#f7d7b5',
    shirt: '#7cd6ff',
    pants: '#3f3a63',
    hair: '#b07cff',
    accessory: 'star',
    hairStyle: 'ponytail',
    outfit: 'hoodie',
    hat: 'star',
    glasses: 'none',
    back: 'wings',
    shoes: '#b07cff'
  },
  {
    id: 'cacau',
    name: 'Cacau',
    skin: '#8d5a3b',
    shirt: '#ff8a6b',
    pants: '#d9b36c',
    hair: '#3a2420',
    accessory: 'bandana',
    hairStyle: 'curly',
    outfit: 'jacket',
    hat: 'bandana',
    glasses: 'shades',
    back: 'shell',
    shoes: '#8b5a3c'
  },
  {
    id: 'vivi',
    name: 'Vivi',
    skin: '#c98e5f',
    shirt: '#f7f3e8',
    pants: '#2f6f4e',
    hair: '#2c8f8f',
    accessory: 'goggles',
    hairStyle: 'bob',
    outfit: 'tee',
    hat: 'halo',
    glasses: 'goggles',
    back: 'cape',
    shoes: '#f7f3e8'
  }
];

export const CHARACTER_ACCESSORIES = ['none', 'leaf', 'star', 'flower', 'goggles', 'bandana'];

export const PETS = [
  { id: 'lumi', name: 'Lumi', icon: '✦', color: '#b8ff6a', desc: 'Uma raposinha de luz que adora descobrir itens novos.' },
  { id: 'gato', name: 'Gatinho', icon: '♢', color: '#ffb3c7', desc: 'Curioso e elegante, sempre um passo atrás de você.' },
  { id: 'coruja', name: 'Coruja', icon: '☾', color: '#7cd6ff', desc: 'Observa tudo do alto e reage quando você forja.' },
  { id: 'coelho', name: 'Coelho', icon: '❀', color: '#f7f3e8', desc: 'Pula junto com você e adora doces de nuvem.' }
];

export const PET_COLORS = ['#b8ff6a', '#7cd6ff', '#ff8a6b', '#ffb3c7', '#b07cff', '#ffc94d', '#f7f3e8', '#24344f'];

export const WEAPON_SKINS = [
  { id: 'aurora', name: 'Aurora', color: '#b07cff' },
  { id: 'petal', name: 'Pétala', color: '#ff7ad9' },
  { id: 'solar', name: 'Solar', color: '#ffc94d' },
  { id: 'bubble', name: 'Bolha', color: '#7cd6ff' },
  { id: 'mint', name: 'Menta', color: '#5fd38a' },
  { id: 'candy', name: 'Doce', color: '#ffb3c7' }
];

export const COLOR_SWATCHES = {
  skin: ['#f7d7b5', '#f2c9a0', '#c98e5f', '#8d5a3b', '#6f4630', '#4d2f21'],
  shirt: ['#b8ff6a', '#7cd6ff', '#ff8a6b', '#ffb3c7', '#b07cff', '#ffc94d', '#f7f3e8', '#24344f', '#5fd38a', '#ff7ad9'],
  pants: ['#2f6f4e', '#24344f', '#1f6f6a', '#d9b36c', '#6b4b8a', '#3a2420', '#ffb3c7', '#f7f3e8'],
  hair: ['#5a3b2e', '#211b18', '#3a2420', '#e07aa0', '#2c8f8f', '#b07cff', '#d9b36c', '#f7f3e8', '#ff7ad9', '#7cd6ff'],
  shoes: ['#24344f', '#17231f', '#2f6f4e', '#8b5a3c', '#ffb3c7', '#ffc94d', '#b07cff', '#f7f3e8']
};

const HAIR_IDS = HAIR_STYLES.map((entry) => entry.id);
const OUTFIT_IDS = OUTFITS.map((entry) => entry.id);
const HAT_IDS = HATS.map((entry) => entry.id);
const GLASSES_IDS = GLASSES.map((entry) => entry.id);
const BACK_IDS = BACK_ITEMS.map((entry) => entry.id);

export const DEFAULT_CHARACTER = {
  preset: 'lumi',
  custom: false,
  name: 'Lumi',
  skin: CHARACTER_PRESETS[0].skin,
  shirt: CHARACTER_PRESETS[0].shirt,
  pants: CHARACTER_PRESETS[0].pants,
  hair: CHARACTER_PRESETS[0].hair,
  shoes: CHARACTER_PRESETS[0].shoes,
  accessory: CHARACTER_PRESETS[0].accessory,
  hairStyle: CHARACTER_PRESETS[0].hairStyle,
  outfit: CHARACTER_PRESETS[0].outfit,
  hat: CHARACTER_PRESETS[0].hat,
  glasses: CHARACTER_PRESETS[0].glasses,
  back: CHARACTER_PRESETS[0].back,
  weaponSkin: 'aurora',
  petType: 'lumi',
  petColor: '#b8ff6a',
  petName: 'Lumi'
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

function legacyHat(accessory) {
  return ['leaf', 'star', 'flower', 'bandana'].includes(accessory) ? accessory : null;
}

function legacyGlasses(accessory) {
  return accessory === 'goggles' ? 'goggles' : null;
}

export function characterFromPreset(id) {
  const preset = CHARACTER_PRESETS.find((entry) => entry.id === id) || CHARACTER_PRESETS[0];
  const pet = PETS.find((entry) => entry.id === 'lumi') || PETS[0];
  return {
    preset: preset.id,
    custom: false,
    name: preset.name,
    skin: preset.skin,
    shirt: preset.shirt,
    pants: preset.pants,
    hair: preset.hair,
    shoes: preset.shoes,
    accessory: preset.accessory,
    hairStyle: preset.hairStyle,
    outfit: preset.outfit,
    hat: preset.hat,
    glasses: preset.glasses,
    back: preset.back,
    weaponSkin: 'aurora',
    petType: pet.id,
    petColor: pet.color,
    petName: pet.name
  };
}

export function normalizeCharacter(value) {
  const source = value && typeof value === 'object' ? value : {};
  const preset = CHARACTER_PRESETS.find((entry) => entry.id === source.preset) || CHARACTER_PRESETS[0];
  const hasPreset = CHARACTER_PRESETS.some((entry) => entry.id === source.preset);
  const custom = !!source.custom || (source.preset != null && !hasPreset);
  const accessory = CHARACTER_ACCESSORIES.includes(source.accessory) ? source.accessory : preset.accessory;
  const hat = HAT_IDS.includes(source.hat)
    ? source.hat
    : legacyHat(accessory) || preset.hat;
  const glasses = GLASSES_IDS.includes(source.glasses)
    ? source.glasses
    : legacyGlasses(accessory) || preset.glasses;
  const pet = PETS.find((entry) => entry.id === source.petType) || PETS[0];
  const petType = PETS.some((entry) => entry.id === source.petType) ? source.petType : 'lumi';
  const petColor = safeColor(source.petColor, PETS.find((entry) => entry.id === petType)?.color || pet.color);
  return {
    preset: custom ? 'custom' : preset.id,
    custom,
    name: cleanName(source.name, preset.name),
    skin: safeColor(source.skin, preset.skin),
    shirt: safeColor(source.shirt, preset.shirt),
    pants: safeColor(source.pants, preset.pants),
    hair: safeColor(source.hair, preset.hair),
    shoes: safeColor(source.shoes, preset.shoes),
    accessory,
    hairStyle: HAIR_IDS.includes(source.hairStyle) ? source.hairStyle : preset.hairStyle,
    outfit: OUTFIT_IDS.includes(source.outfit) ? source.outfit : preset.outfit,
    hat,
    glasses,
    back: BACK_IDS.includes(source.back) ? source.back : preset.back,
    weaponSkin: WEAPON_SKINS.some((entry) => entry.id === source.weaponSkin) ? source.weaponSkin : 'aurora',
    petType,
    petColor,
    petName: cleanName(source.petName, PETS.find((entry) => entry.id === petType)?.name || pet.name)
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

export function getPet(character) {
  const value = normalizeCharacter(character);
  return PETS.find((entry) => entry.id === value.petType) || PETS[0];
}

export function characterSignature(character, name) {
  const value = normalizeCharacter(character);
  return [
    name ?? value.name,
    value.skin,
    value.shirt,
    value.pants,
    value.hair,
    value.shoes,
    value.hairStyle,
    value.outfit,
    value.hat,
    value.glasses,
    value.back,
    value.weaponSkin,
    value.petType,
    value.petColor,
    value.petName
  ].join('|');
}
