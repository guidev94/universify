import { B } from './blocks.js';
import { getItem } from './items.js';

export const RECIPES = [
  {
    id: 'planks_mel',
    out: { id: B.PLANKS, count: 4 },
    ingredients: [{ id: B.LOG, count: 1 }],
    hint: 'Troncos viram tábuas.'
  },
  {
    id: 'planks_rainbow',
    out: { id: B.PLANKS, count: 4 },
    ingredients: [{ id: B.RAINBOW_LOG, count: 1 }],
    hint: 'Madeira arco-íris também vira tábua.'
  },
  {
    id: 'stick',
    out: { id: 'stick', count: 4 },
    ingredients: [{ id: B.PLANKS, count: 1 }],
    hint: 'A base de quase toda ferramenta.'
  },
  {
    id: 'chest',
    out: { id: B.CHEST, count: 1 },
    ingredients: [{ id: B.PLANKS, count: 8 }],
    hint: 'Guarde seus tesouros.'
  },
  {
    id: 'lumi_lamp',
    out: { id: B.LUMI_LAMP, count: 1 },
    ingredients: [{ id: 'lumi_shard', count: 2 }, { id: 'stick', count: 1 }],
    hint: 'Uma luz para a sua base.'
  },
  {
    id: 'wood_pickaxe',
    out: { id: 'wood_pickaxe', count: 1 },
    ingredients: [{ id: B.PLANKS, count: 3 }, { id: 'stick', count: 2 }],
    hint: 'Sua primeira picareta.'
  },
  {
    id: 'stone_pickaxe',
    out: { id: 'stone_pickaxe', count: 1 },
    ingredients: [{ id: B.STONE, count: 3 }, { id: 'stick', count: 2 }],
    hint: 'Mais rápida que a de mel.'
  },
  {
    id: 'crystal_pickaxe',
    out: { id: 'crystal_pickaxe', count: 1 },
    ingredients: [{ id: B.CRYSTAL, count: 3 }, { id: 'stick', count: 2 }],
    hint: 'Alcança mais longe.'
  },
  {
    id: 'aurora_pickaxe',
    out: { id: 'aurora_pickaxe', count: 1 },
    ingredients: [{ id: 'aurora_gem', count: 3 }, { id: 'stick', count: 2 }, { id: 'lumi_shard', count: 2 }],
    hint: 'Quebra uma área de 3×3.'
  },
  {
    id: 'cloud_boots',
    out: { id: 'cloud_boots', count: 1 },
    ingredients: [{ id: 'cloud_feather', count: 2 }, { id: B.PLANKS, count: 2 }],
    hint: 'Salto duplo para explorar.'
  },
  {
    id: 'lumi_charm',
    out: { id: 'lumi_charm', count: 1 },
    ingredients: [{ id: 'lumi_shard', count: 8 }, { id: 'aurora_gem', count: 1 }],
    hint: 'Revela minérios por perto.'
  },
  {
    id: 'star_soup',
    out: { id: 'star_soup', count: 1 },
    ingredients: [{ id: 'star_mushroom', count: 1 }, { id: 'lumi_shard', count: 1 }],
    hint: 'Velocidade e salto por 20 s.'
  },
  {
    id: 'prism_block',
    out: { id: B.PRISM, count: 1 },
    ingredients: [{ id: 'aurora_gem', count: 4 }, { id: B.CRYSTAL, count: 4 }],
    hint: 'Um bloco que brilha como aurora.'
  },
  {
    id: 'rainbow_sapling',
    out: { id: 'rainbow_sapling', count: 1 },
    ingredients: [{ id: 'prism_seed', count: 1 }, { id: 'star_mushroom', count: 1 }],
    hint: 'Plante para nascer uma árvore arco-íris.'
  },
  {
    id: 'moon_berry',
    out: { id: 'moon_berry', count: 1 },
    ingredients: [{ id: 'berry', count: 2 }, { id: 'lumi_shard', count: 1 }],
    hint: 'Uma frutinha que deixa o salto mais alto.'
  },
  {
    id: 'rainbow_berry',
    out: { id: 'rainbow_berry', count: 2 },
    ingredients: [{ id: 'berry', count: 1 }, { id: 'lumi_shard', count: 1 }],
    hint: 'Duas frutinhas coloridas para correr mais rápido.'
  },
  {
    id: 'star_cookie',
    out: { id: 'star_cookie', count: 1 },
    ingredients: [{ id: 'star_mushroom', count: 1 }, { id: 'berry', count: 1 }],
    hint: 'Um biscoito quentinho com velocidade e salto.'
  },
  {
    id: 'cloud_candy',
    out: { id: 'cloud_candy', count: 2 },
    ingredients: [{ id: 'cloud_feather', count: 1 }, { id: 'berry', count: 2 }],
    hint: 'Doce de nuvem para saltos bem altos.'
  },
  {
    id: 'pet_treat',
    out: { id: 'pet_treat', count: 2 },
    ingredients: [{ id: 'berry', count: 2 }, { id: 'lumi_shard', count: 1 }],
    hint: 'Um petisco para agradar seu companheiro.'
  },
  {
    id: 'bubble_wand',
    out: { id: 'bubble_wand', count: 1 },
    ingredients: [{ id: B.CLOUD, count: 2 }, { id: 'stick', count: 2 }],
    hint: 'Uma varinha para brincar com bolhas de luz.'
  },
  {
    id: 'star_sling',
    out: { id: 'star_sling', count: 1 },
    ingredients: [{ id: 'stick', count: 3 }, { id: 'lumi_shard', count: 2 }],
    hint: 'Lança estrelinhas que brilham ao cair.'
  },
  {
    id: 'petal_blade',
    out: { id: 'petal_blade', count: 1 },
    ingredients: [{ id: B.FLOWER_PINK, count: 2 }, { id: 'stick', count: 2 }],
    hint: 'Pétalas leves que aceleram seus passos.'
  },
  {
    id: 'sun_hammer',
    out: { id: 'sun_hammer', count: 1 },
    ingredients: [{ id: B.STONE, count: 4 }, { id: 'stick', count: 2 }, { id: 'lumi_shard', count: 2 }],
    hint: 'Quebra 3×3 e dá um impulso de salto.'
  },
  {
    id: 'aurora_bow',
    out: { id: 'aurora_bow', count: 1 },
    ingredients: [{ id: 'aurora_gem', count: 2 }, { id: 'stick', count: 3 }, { id: 'cloud_feather', count: 1 }],
    hint: 'Flechas de aurora para iluminar o caminho.'
  }
];

export function getRecipe(id) {
  return RECIPES.find((recipe) => recipe.id === id) || null;
}

export function recipeName(recipe) {
  return getItem(recipe.out.id).name;
}
