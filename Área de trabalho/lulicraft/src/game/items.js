import { B, BLOCKS, PLACEABLE_BLOCKS, getBlockIcon } from './blocks.js';
import { hash3i, mulberry32 } from '../core/rng.js';

export const RARITY = {
  comum: { key: 'comum', label: 'Comum', color: '#9aa7a0', stars: 1, sound: 220 },
  incomum: { key: 'incomum', label: 'Incomum', color: '#5fd38a', stars: 2, sound: 330 },
  raro: { key: 'raro', label: 'Raro', color: '#5aa9ff', stars: 3, sound: 440 },
  epico: { key: 'epico', label: 'Épico', color: '#b07cff', stars: 4, sound: 554 },
  lendario: { key: 'lendario', label: 'Lendário', color: '#ffc94d', stars: 5, sound: 660 },
  mitico: { key: 'mitico', label: 'Mítico', color: '#ff7ad9', stars: 6, sound: 880 }
};

export const RARITY_ORDER = ['comum', 'incomum', 'raro', 'epico', 'lendario', 'mitico'];

export function rarityRank(rarity) {
  return Math.max(0, RARITY_ORDER.indexOf(rarity));
}

function rect(ctx, x, y, w, h, color) {
  ctx.fillStyle = color;
  ctx.fillRect(x, y, w, h);
}

function poly(ctx, points, color) {
  ctx.fillStyle = color;
  ctx.beginPath();
  ctx.moveTo(points[0][0], points[0][1]);
  for (let i = 1; i < points.length; i++) ctx.lineTo(points[i][0], points[i][1]);
  ctx.closePath();
  ctx.fill();
}

function makeIcon(draw) {
  const canvas = document.createElement('canvas');
  canvas.width = 32;
  canvas.height = 32;
  const ctx = canvas.getContext('2d');
  ctx.imageSmoothingEnabled = false;
  ctx.scale(2, 2);
  draw(ctx);
  return canvas.toDataURL();
}

function drawShard(ctx) {
  poly(ctx, [[8, 1], [12, 5], [13, 10], [9, 15], [4, 12], [3, 6]], '#ffd76a');
  poly(ctx, [[8, 1], [10, 6], [9, 13], [7, 8]], '#fff1b8');
  poly(ctx, [[4, 12], [7, 8], [9, 15]], '#e0a93a');
  rect(ctx, 9, 3, 1, 1, '#ffffff');
}

function drawGem(ctx) {
  poly(ctx, [[8, 2], [13, 6], [12, 12], [8, 14], [4, 12], [3, 6]], '#b07cff');
  poly(ctx, [[8, 2], [10, 6], [8, 13], [6, 6]], '#d9b3ff');
  poly(ctx, [[3, 6], [6, 6], [4, 12]], '#7a4fd0');
  rect(ctx, 9, 4, 1, 1, '#ffffff');
}

function drawMushroom(ctx) {
  rect(ctx, 7, 8, 2, 7, '#f2e6c9');
  rect(ctx, 6, 8, 1, 3, '#d9c9a3');
  rect(ctx, 5, 5, 6, 3, '#8a5fb8');
  rect(ctx, 4, 6, 8, 2, '#b07cff');
  rect(ctx, 6, 3, 4, 2, '#b07cff');
  rect(ctx, 5, 6, 1, 1, '#ffc94d');
  rect(ctx, 9, 5, 1, 1, '#ffc94d');
  rect(ctx, 7, 4, 1, 1, '#ffe9a3');
}

function drawSeed(ctx) {
  poly(ctx, [[8, 2], [12, 6], [12, 11], [8, 15], [4, 11], [4, 6]], '#f2e6c9');
  rect(ctx, 5, 6, 6, 2, '#ff8a6b');
  rect(ctx, 4, 8, 8, 2, '#ffc94d');
  rect(ctx, 4, 10, 8, 2, '#7cd6ff');
  rect(ctx, 6, 12, 4, 2, '#b07cff');
  rect(ctx, 7, 3, 1, 3, '#ffffff');
}

function drawFeather(ctx) {
  poly(ctx, [[8, 1], [12, 4], [12, 9], [9, 14], [7, 14], [5, 9], [5, 4]], '#f4fbff');
  poly(ctx, [[8, 2], [9, 6], [8, 13], [7, 7]], '#b8e7ff');
  rect(ctx, 7, 13, 2, 3, '#d9c27a');
  rect(ctx, 5, 5, 1, 1, '#ffffff');
}

function drawCookie(ctx) {
  rect(ctx, 4, 4, 8, 8, '#d9a86a');
  rect(ctx, 3, 6, 10, 4, '#d9a86a');
  rect(ctx, 5, 5, 2, 2, '#f2c9a0');
  rect(ctx, 6, 9, 1, 1, '#6f4630');
  rect(ctx, 10, 7, 1, 1, '#6f4630');
  rect(ctx, 8, 11, 1, 1, '#6f4630');
}

function drawCandy(ctx) {
  poly(ctx, [[4, 3], [7, 5], [9, 5], [12, 3], [12, 8], [9, 6], [7, 6], [4, 8]], '#ff8ad9');
  rect(ctx, 7, 8, 2, 7, '#f2e6c9');
  rect(ctx, 6, 5, 4, 2, '#ffc94d');
  rect(ctx, 8, 9, 1, 5, '#ffb3c7');
}

function drawHeart(ctx, ring = false) {
  const c = '#ff7ad9';
  rect(ctx, 4, 4, 8, 3, c);
  rect(ctx, 3, 6, 10, 4, c);
  rect(ctx, 4, 10, 8, 3, c);
  rect(ctx, 5, 12, 6, 2, c);
  rect(ctx, 6, 14, 4, 1, c);
  rect(ctx, 5, 5, 2, 1, '#ffc2ee');
  rect(ctx, 9, 6, 1, 1, '#d94fb0');
  if (ring) {
    ctx.strokeStyle = '#ffc94d';
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.arc(8, 8, 7, 0, Math.PI * 2);
    ctx.stroke();
  }
}

function drawCrown(ctx) {
  poly(ctx, [[3, 12], [5, 5], [8, 9], [11, 5], [13, 12]], '#ffc94d');
  rect(ctx, 3, 11, 10, 3, '#e0a93a');
  rect(ctx, 4, 12, 8, 1, '#fff1b8');
  rect(ctx, 5, 6, 1, 3, '#ffe9a3');
  rect(ctx, 8, 8, 1, 2, '#ffe9a3');
  rect(ctx, 10, 6, 1, 3, '#ffe9a3');
  rect(ctx, 7, 12, 2, 2, '#ff7ad9');
  rect(ctx, 4, 13, 1, 1, '#7cd6ff');
  rect(ctx, 11, 13, 1, 1, '#7cd6ff');
}

function drawStick(ctx) {
  for (let i = 0; i < 9; i++) rect(ctx, 4 + i, 12 - i, 2, 2, i % 3 === 0 ? '#b5824f' : '#9b6a3f');
}

function drawPickaxe(ctx, head, shine) {
  for (let i = 0; i < 9; i++) rect(ctx, 4 + i, 12 - i, 2, 2, i % 3 === 0 ? '#b5824f' : '#9b6a3f');
  rect(ctx, 8, 2, 6, 2, head);
  rect(ctx, 6, 3, 3, 2, head);
  rect(ctx, 13, 4, 2, 3, head);
  rect(ctx, 8, 2, 4, 1, shine);
}

function drawBoots(ctx) {
  rect(ctx, 4, 4, 3, 6, '#b8e7ff');
  rect(ctx, 9, 4, 3, 6, '#b8e7ff');
  rect(ctx, 3, 9, 5, 3, '#f4fbff');
  rect(ctx, 8, 9, 5, 3, '#f4fbff');
  rect(ctx, 3, 11, 5, 1, '#7cd6ff');
  rect(ctx, 8, 11, 5, 1, '#7cd6ff');
  rect(ctx, 4, 5, 1, 2, '#ffffff');
  rect(ctx, 9, 5, 1, 2, '#ffffff');
}

function drawSoup(ctx) {
  poly(ctx, [[3, 8], [13, 8], [11, 14], [5, 14]], '#d9c27a');
  rect(ctx, 4, 9, 8, 2, '#ffc94d');
  rect(ctx, 5, 10, 6, 1, '#ff8a6b');
  rect(ctx, 6, 4, 1, 3, '#f4fbff');
  rect(ctx, 9, 3, 1, 3, '#f4fbff');
  rect(ctx, 3, 14, 10, 1, '#9c6a38');
}

function drawBerry(ctx) {
  rect(ctx, 5, 5, 6, 6, '#ffd76a');
  rect(ctx, 4, 7, 8, 3, '#ffc94d');
  rect(ctx, 6, 4, 3, 2, '#5fd38a');
  rect(ctx, 6, 6, 2, 2, '#fff1b8');
}

function drawSapling(ctx) {
  rect(ctx, 7, 9, 2, 6, '#9b6a3f');
  rect(ctx, 4, 5, 3, 3, '#ffb3c7');
  rect(ctx, 9, 4, 3, 3, '#b8ff9e');
  rect(ctx, 6, 3, 4, 3, '#a8d8ff');
  rect(ctx, 5, 7, 2, 2, '#ffe28a');
  rect(ctx, 9, 7, 2, 2, '#d4b3ff');
}

function drawPetalBlade(ctx) {
  for (let i = 0; i < 6; i++) rect(ctx, 4 + i, 12 - i, 2, 2, i % 2 ? '#b5824f' : '#9b6a3f');
  poly(ctx, [[8, 3], [13, 5], [12, 10], [8, 8]], '#ffb3c7');
  poly(ctx, [[8, 3], [10, 5], [10, 8], [8, 7]], '#ff7ad9');
  rect(ctx, 5, 11, 3, 2, '#ffc94d');
}

function drawBubbleWand(ctx) {
  for (let i = 0; i < 7; i++) rect(ctx, 4 + i, 13 - i, 2, 2, i % 2 ? '#b5824f' : '#9b6a3f');
  ctx.strokeStyle = '#7cd6ff';
  ctx.lineWidth = 1;
  ctx.beginPath();
  ctx.arc(11, 5, 4, 0, Math.PI * 2);
  ctx.stroke();
  rect(ctx, 10, 3, 2, 1, '#f4fbff');
}

function drawStarSling(ctx) {
  rect(ctx, 7, 8, 2, 7, '#9b6a3f');
  rect(ctx, 4, 4, 2, 4, '#b5824f');
  rect(ctx, 10, 4, 2, 4, '#b5824f');
  rect(ctx, 6, 6, 4, 1, '#9b6a3f');
  rect(ctx, 4, 3, 1, 1, '#ffc94d');
  rect(ctx, 11, 3, 1, 1, '#ffc94d');
  rect(ctx, 8, 1, 2, 2, '#ffe9a3');
}

function drawSunHammer(ctx) {
  rect(ctx, 7, 6, 2, 9, '#9b6a3f');
  rect(ctx, 4, 3, 8, 4, '#ffc94d');
  rect(ctx, 3, 4, 2, 2, '#e0a93a');
  rect(ctx, 11, 4, 2, 2, '#e0a93a');
  rect(ctx, 5, 3, 4, 1, '#fff1b8');
  rect(ctx, 7, 5, 2, 1, '#ff8a6b');
}

function drawAuroraBow(ctx) {
  ctx.strokeStyle = '#b07cff';
  ctx.lineWidth = 2;
  ctx.beginPath();
  ctx.arc(6, 8, 7, -Math.PI / 2, Math.PI / 2);
  ctx.stroke();
  ctx.strokeStyle = '#f4fbff';
  ctx.lineWidth = 1;
  ctx.beginPath();
  ctx.moveTo(6, 1);
  ctx.lineTo(6, 15);
  ctx.stroke();
  rect(ctx, 8, 7, 5, 1, '#ffc94d');
  rect(ctx, 12, 6, 2, 3, '#ff7ad9');
}

const ICON_DRAWERS = {
  shard: drawShard,
  gem: drawGem,
  mushroom: drawMushroom,
  seed: drawSeed,
  feather: drawFeather,
  heart: (ctx) => drawHeart(ctx, false),
  charm: (ctx) => drawHeart(ctx, true),
  crown: drawCrown,
  stick: drawStick,
  pick_wood: (ctx) => drawPickaxe(ctx, '#b5824f', '#d9a86a'),
  pick_stone: (ctx) => drawPickaxe(ctx, '#9aa7b2', '#d4dde4'),
  pick_crystal: (ctx) => drawPickaxe(ctx, '#4fc9dd', '#c9fbff'),
  pick_aurora: (ctx) => drawPickaxe(ctx, '#ffc94d', '#fff1b8'),
  boots: drawBoots,
  soup: drawSoup,
  berry: drawBerry,
  cookie: drawCookie,
  candy: drawCandy,
  sapling: drawSapling,
  blade: drawPetalBlade,
  bubble_wand: drawBubbleWand,
  sling: drawStarSling,
  hammer: drawSunHammer,
  bow: drawAuroraBow
};

export const ITEMS = {};

function addItem(item) {
  ITEMS[item.id] = {
    stack: 64,
    place: false,
    category: 'material',
    rarity: 'comum',
    ...item
  };
}

addItem({
  id: 'lumi_shard',
  name: 'Fragmento de Lumi',
  desc: 'Uma faísca que ainda lembra do sol.',
  rarity: 'incomum',
  category: 'material',
  icon: { kind: 'shard' }
});
addItem({
  id: 'aurora_gem',
  name: 'Gema Aurora',
  desc: 'Guarda uma pequena aurora dentro dela.',
  rarity: 'raro',
  category: 'material',
  icon: { kind: 'gem' }
});
addItem({
  id: 'star_mushroom',
  name: 'Cogumelo Estelar',
  desc: 'Brilha no escuro e deixa os pés ligeiros.',
  rarity: 'raro',
  category: 'alimento',
  stack: 16,
  icon: { kind: 'mushroom' }
});
addItem({
  id: 'prism_seed',
  name: 'Semente Prismática',
  desc: 'Plante na grama para fazer nascer uma árvore arco-íris.',
  rarity: 'raro',
  category: 'semente',
  stack: 16,
  icon: { kind: 'seed' }
});
addItem({
  id: 'cloud_feather',
  name: 'Pena de Nuvem',
  desc: 'Leve como um sonho. Faz botas que pulam duas vezes.',
  rarity: 'epico',
  category: 'material',
  stack: 16,
  icon: { kind: 'feather' }
});
addItem({
  id: 'heart_lumi',
  name: 'Coração de Lumi',
  desc: 'Pulsa perto de minérios escondidos.',
  rarity: 'epico',
  category: 'charme',
  stack: 1,
  icon: { kind: 'heart' }
});
addItem({
  id: 'lumi_charm',
  name: 'Amuleto de Lumi',
  desc: 'Um coração de luz que revela tesouros.',
  rarity: 'epico',
  category: 'charme',
  stack: 1,
  icon: { kind: 'charm' }
});
addItem({
  id: 'crown_lumi',
  name: 'Coroa de Lumi',
  desc: 'A lembrança mais rara do mundo. Só existe uma.',
  rarity: 'mitico',
  category: 'trofeu',
  stack: 1,
  icon: { kind: 'crown' }
});
addItem({
  id: 'stick',
  name: 'Graveto',
  desc: 'Um pedacinho de madeira cheio de ideias.',
  category: 'material',
  icon: { kind: 'stick' }
});
addItem({
  id: 'wood_pickaxe',
  name: 'Picareta de Mel',
  desc: 'Quebra blocos um pouco mais rápido.',
  category: 'ferramenta',
  stack: 1,
  icon: { kind: 'pick_wood' }
});
addItem({
  id: 'stone_pickaxe',
  name: 'Picareta Sereno',
  desc: 'Firme e confiável para explorar cavernas.',
  category: 'ferramenta',
  stack: 1,
  icon: { kind: 'pick_stone' }
});
addItem({
  id: 'crystal_pickaxe',
  name: 'Picareta de Cristal',
  desc: 'Alcança mais longe e quebra mais rápido.',
  rarity: 'incomum',
  category: 'ferramenta',
  stack: 1,
  icon: { kind: 'pick_crystal' }
});
addItem({
  id: 'aurora_pickaxe',
  name: 'Picareta Aurora',
  desc: 'Um golpe abre um buraco de 3×3.',
  rarity: 'lendario',
  category: 'ferramenta',
  stack: 1,
  icon: { kind: 'pick_aurora' }
});
addItem({
  id: 'cloud_boots',
  name: 'Botas de Nuvem',
  desc: 'Permitem um segundo salto no ar.',
  rarity: 'epico',
  category: 'calcado',
  stack: 1,
  icon: { kind: 'boots' }
});
addItem({
  id: 'star_soup',
  name: 'Sopa Estelar',
  desc: 'Deixa você rápido e saltitante por 20 segundos.',
  rarity: 'raro',
  category: 'alimento',
  stack: 16,
  icon: { kind: 'soup' }
});
addItem({
  id: 'berry',
  name: 'Mel Silvestre',
  desc: 'Doce e energizante. Um empurrãozinho de velocidade.',
  category: 'alimento',
  icon: { kind: 'berry' }
});
addItem({
  id: 'rainbow_sapling',
  name: 'Broto Arco-Íris',
  desc: 'Plante na grama para crescer uma árvore de cores.',
  rarity: 'raro',
  category: 'semente',
  stack: 16,
  icon: { kind: 'sapling' }
});
addItem({
  id: 'moon_berry',
  name: 'Frutinha da Lua',
  desc: 'Doce como a noite. Deixa seu salto mais alto por 12 segundos.',
  rarity: 'raro',
  category: 'alimento',
  stack: 16,
  icon: { kind: 'berry' }
});
addItem({
  id: 'rainbow_berry',
  name: 'Frutinha Arco-Íris',
  desc: 'Cada mordida tem uma cor. Dá velocidade por 20 segundos.',
  rarity: 'raro',
  category: 'alimento',
  stack: 16,
  icon: { kind: 'berry' }
});
addItem({
  id: 'star_cookie',
  name: 'Biscoito Estelar',
  desc: 'Crocrante, quentinho e cheio de brilho. Velocidade e salto por 10 segundos.',
  rarity: 'incomum',
  category: 'alimento',
  stack: 16,
  icon: { kind: 'cookie' }
});
addItem({
  id: 'cloud_candy',
  name: 'Algodão-doce de Nuvem',
  desc: 'Leve como nuvem. Deixa seu salto bem alto por 18 segundos.',
  rarity: 'epico',
  category: 'alimento',
  stack: 16,
  icon: { kind: 'candy' }
});
addItem({
  id: 'petal_blade',
  name: 'Lâmina de Pétalas',
  desc: 'Um golpe de pétalas que acelera seus passos por 8 segundos.',
  rarity: 'raro',
  category: 'arma',
  stack: 1,
  weapon: { kind: 'petal' },
  icon: { kind: 'blade' }
});
addItem({
  id: 'bubble_wand',
  name: 'Varinha de Bolhas',
  desc: 'Sopra bolhas de luz que flutuam pelo mundo.',
  rarity: 'incomum',
  category: 'arma',
  stack: 1,
  weapon: { kind: 'bubble' },
  icon: { kind: 'bubble_wand' }
});
addItem({
  id: 'star_sling',
  name: 'Estilingue Estelar',
  desc: 'Lança estrelinhas que explodem em brilho ao tocar o chão.',
  rarity: 'raro',
  category: 'arma',
  stack: 1,
  weapon: { kind: 'star' },
  icon: { kind: 'sling' }
});
addItem({
  id: 'sun_hammer',
  name: 'Martelo Solar',
  desc: 'Um martelo de luz: quebra uma área 3×3 e dá um pulo mais alto.',
  rarity: 'epico',
  category: 'arma',
  stack: 1,
  weapon: { kind: 'hammer' },
  icon: { kind: 'hammer' }
});
addItem({
  id: 'aurora_bow',
  name: 'Arco Aurora',
  desc: 'Dispara flechas de aurora que iluminam o caminho.',
  rarity: 'lendario',
  category: 'arma',
  stack: 1,
  weapon: { kind: 'bow' },
  icon: { kind: 'bow' }
});

for (const block of PLACEABLE_BLOCKS) {
  addItem({
    id: block.id,
    name: block.name,
    desc: block.plant ? 'Uma plantinha de Lumi.' : 'Um bloco para construir e decorar.',
    rarity: block.rarity,
    category: 'bloco',
    place: true,
    blockId: block.id,
    icon: { kind: 'block', blockId: block.id }
  });
}

export const COLLECTION_IDS = [
  'lumi_shard', 'aurora_gem', 'star_mushroom', 'prism_seed', 'cloud_feather',
  'heart_lumi', 'lumi_charm', 'crown_lumi', 'wood_pickaxe', 'stone_pickaxe',
  'crystal_pickaxe', 'aurora_pickaxe', 'cloud_boots', 'star_soup', 'berry',
  'moon_berry', 'rainbow_berry', 'star_cookie', 'cloud_candy',
  'petal_blade', 'bubble_wand', 'star_sling', 'sun_hammer', 'aurora_bow',
  'rainbow_sapling', B.CRYSTAL, B.RAINBOW_LOG, B.RAINBOW_LEAVES,
  B.MUSHROOM_STAR, B.PRISM, B.CLOUD, B.LUMI_LAMP, B.CORAL, B.BERRY_BUSH
];

export const CREATIVE_PALETTE = PLACEABLE_BLOCKS.map((block) => block.id);

export function getItem(id) {
  return ITEMS[id] || {
    id,
    name: 'Item de Lumi',
    desc: 'Um item misterioso.',
    rarity: 'comum',
    category: 'material',
    stack: 64,
    place: false
  };
}

const iconCache = new Map();

export function getItemIcon(id) {
  if (iconCache.has(id)) return iconCache.get(id);
  const item = getItem(id);
  let url = '';
  if (item.icon?.kind === 'block') {
    url = getBlockIcon(item.icon.blockId);
  } else if (item.icon?.kind && ICON_DRAWERS[item.icon.kind]) {
    url = makeIcon(ICON_DRAWERS[item.icon.kind]);
  } else {
    url = makeIcon(drawStick);
  }
  iconCache.set(id, url);
  return url;
}

export function getRarity(id) {
  return RARITY[getItem(id).rarity] || RARITY.comum;
}

export function isRare(id) {
  return rarityRank(getItem(id).rarity) >= rarityRank('raro');
}

export function maxStack(id) {
  return getItem(id).stack || 64;
}

export function rollBlockDrops(blockId) {
  const block = BLOCKS[blockId];
  if (!block || !block.drop) return [];
  const drop = block.drop;
  const min = drop.min || 1;
  const max = drop.max || min;
  const count = min + Math.floor(Math.random() * (max - min + 1));
  const result = [{ id: drop.id, count }];
  if (drop.extra && Math.random() < drop.extra.chance) {
    result.push({ id: drop.extra.id, count: 1 });
  }
  return result;
}

const LOOT_TABLE = [
  { id: 'planks', weight: 20, min: 4, max: 12 },
  { id: 'stick', weight: 16, min: 2, max: 6 },
  { id: 'berry', weight: 14, min: 2, max: 5 },
  { id: 'lumi_shard', weight: 22, min: 1, max: 3 },
  { id: B.CRYSTAL, weight: 10, min: 1, max: 2 },
  { id: B.LUMI_LAMP, weight: 8, min: 1, max: 2 },
  { id: 'aurora_gem', weight: 10, min: 1, max: 2 },
  { id: 'star_mushroom', weight: 8, min: 1, max: 2 },
  { id: 'prism_seed', weight: 6, min: 1, max: 1 },
  { id: 'cloud_feather', weight: 4, min: 1, max: 1 },
  { id: 'heart_lumi', weight: 3, min: 1, max: 1 },
  { id: 'aurora_pickaxe', weight: 1.5, min: 1, max: 1 },
  { id: 'cloud_boots', weight: 2.5, min: 1, max: 1 },
  { id: 'moon_berry', weight: 7, min: 1, max: 2 },
  { id: 'rainbow_berry', weight: 5, min: 1, max: 2 },
  { id: 'star_cookie', weight: 6, min: 1, max: 2 },
  { id: 'cloud_candy', weight: 3, min: 1, max: 1 },
  { id: 'bubble_wand', weight: 5, min: 1, max: 1 },
  { id: 'star_sling', weight: 3, min: 1, max: 1 },
  { id: 'petal_blade', weight: 3, min: 1, max: 1 },
  { id: 'sun_hammer', weight: 1.5, min: 1, max: 1 },
  { id: 'aurora_bow', weight: 1, min: 1, max: 1 }
];

export function rollChestLoot(seed, x, y, z, hasCrown) {
  const rng = mulberry32(hash3i(x, y, z, seed ^ 0x9e3779b9));
  const count = 3 + Math.floor(rng() * 3);
  const total = LOOT_TABLE.reduce((sum, entry) => sum + entry.weight, 0);
  const result = [];
  let hasRare = false;
  for (let i = 0; i < count; i++) {
    let roll = rng() * total;
    let picked = LOOT_TABLE[0];
    for (const entry of LOOT_TABLE) {
      roll -= entry.weight;
      if (roll <= 0) {
        picked = entry;
        break;
      }
    }
    const amount = picked.min + Math.floor(rng() * (picked.max - picked.min + 1));
    result.push({ id: picked.id, count: amount });
    if (isRare(picked.id)) hasRare = true;
  }
  if (!hasRare) result.push({ id: 'aurora_gem', count: 1 });
  if (hasCrown) result.push({ id: 'crown_lumi', count: 1 });
  return result;
}

export function slotEquals(a, b) {
  return !!a && !!b && a.id === b.id;
}
