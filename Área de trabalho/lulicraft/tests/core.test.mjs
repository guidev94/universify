import test from 'node:test';
import assert from 'node:assert/strict';

const store = new Map();
globalThis.localStorage = {
  getItem: (key) => (store.has(key) ? store.get(key) : null),
  setItem: (key, value) => store.set(key, String(value)),
  removeItem: (key) => store.delete(key),
  clear: () => store.clear()
};

const { World, BIOMES, CHUNK_SIZE, WORLD_HEIGHT, SEA_LEVEL, chestKey } = await import('../src/game/world.js');
const { B } = await import('../src/game/blocks.js');
const { Inventory } = await import('../src/game/inventory.js');
const { ITEMS, getItem, getRarity, rarityRank } = await import('../src/game/items.js');
const { RECIPES, getRecipe } = await import('../src/game/recipes.js');
const { CHARACTER_PRESETS, DEFAULT_CHARACTER, WEAPON_SKINS, characterFromPreset, getWeaponSkin, normalizeCharacter } = await import('../src/game/characters.js');
const { PREBUILT_WORLDS, getPrebuilt } = await import('../src/game/prebuilt.js');
const { normalizeRoomCode, makeRoomCode } = await import('../src/game/multiplayer.js');
const save = await import('../src/game/save.js');

function resetStorage() {
  store.clear();
}

function makeSnapshot(entry, extra = {}) {
  return {
    id: entry.id,
    name: entry.name,
    seed: entry.seed,
    mode: entry.mode,
    createdAt: entry.createdAt,
    updatedAt: entry.updatedAt,
    playtime: 12000,
    player: { x: 4, y: 26, z: 4, yaw: 0, pitch: 0 },
    inventory: { hotbar: [{ id: 1, count: 8 }], backpack: [], pouch: [], selected: 0 },
    diffs: {},
    chests: {},
    discovered: [],
    stats: {},
    achievements: {},
    crownPlaced: false,
    biome: 'meadow',
    ...extra
  };
}

test('characters normalize presets and reject invalid colors', () => {
  const preset = characterFromPreset('bia');
  assert.equal(preset.preset, 'bia');
  assert.equal(preset.name, 'Bia');
  assert.equal(preset.custom, false);

  const custom = normalizeCharacter({ preset: 'custom', custom: true, name: '<Dara>', skin: 'red', shirt: '#ABC123', hair: '#123456' });
  assert.equal(custom.name, 'Dara');
  assert.equal(custom.skin, DEFAULT_CHARACTER.skin);
  assert.equal(custom.shirt, '#abc123');
  assert.equal(custom.custom, true);

  assert.equal(normalizeCharacter(null).preset, 'lumi');
  assert.equal(CHARACTER_PRESETS.length, 9);
  assert.equal(WEAPON_SKINS.length, 4);
  assert.equal(normalizeCharacter({ weaponSkin: 'petal' }).weaponSkin, 'petal');
  assert.equal(normalizeCharacter({ weaponSkin: 'nope' }).weaponSkin, 'aurora');
  assert.equal(getWeaponSkin({ weaponSkin: 'solar' }).color, '#ffc94d');
});

test('weapon items are non-violent magical tools with recipes', () => {
  const weapons = ['petal_blade', 'bubble_wand', 'star_sling', 'sun_hammer', 'aurora_bow'];
  for (const id of weapons) {
    assert.equal(ITEMS[id].category, 'arma');
    assert.ok(ITEMS[id].weapon?.kind);
    assert.equal(ITEMS[id].stack, 1);
    assert.ok(RECIPES.some((recipe) => recipe.out.id === id), `${id} has a recipe`);
  }
  assert.equal(getItem('moon_berry').category, 'alimento');
});

test('prebuilt worlds have unique ids and valid modes', () => {
  assert.equal(PREBUILT_WORLDS.length, 6);
  assert.equal(PREBUILT_WORLDS.filter((world) => world.rareKit).length, 2);
  assert.equal(new Set(PREBUILT_WORLDS.map((world) => world.id)).size, PREBUILT_WORLDS.length);
  assert.equal(new Set(PREBUILT_WORLDS.map((world) => world.seed)).size, PREBUILT_WORLDS.length);
  for (const world of PREBUILT_WORLDS) {
    assert.ok(['adventure', 'creative'].includes(world.mode));
    assert.ok(world.name.length > 2);
    assert.equal(getPrebuilt(world.id), world);
  }
  assert.equal(getPrebuilt('missing'), null);
});

test('multiplayer invite codes are short, safe and normalized', () => {
  for (let i = 0; i < 40; i++) {
    const code = makeRoomCode();
    assert.match(code, /^[A-HJ-NP-Z2-9]{5}$/);
    assert.equal(normalizeRoomCode(code), code);
  }
  assert.equal(normalizeRoomCode(' ab-c1 2 '), 'ABC12');
  assert.equal(normalizeRoomCode('abcdef'), 'ABCDE');
  assert.equal(normalizeRoomCode(null), '');
  assert.equal(normalizeRoomCode('inválido!'), 'INVLI');
});

test('world generation is deterministic and has a safe surface', () => {
  const a = new World({ seed: 'lumi-test' });
  const b = new World({ seed: 'lumi-test' });
  const c = new World({ seed: 'lumi-other' });
  const chunkA = a.getChunk(0, 0).blocks;
  const chunkB = b.getChunk(0, 0).blocks;
  const chunkC = c.getChunk(0, 0).blocks;
  assert.deepEqual([...chunkA], [...chunkB]);
  assert.notDeepEqual([...chunkA], [...chunkC]);

  const spawn = a.findSpawn();
  assert.ok(spawn.x >= -CHUNK_SIZE * 2 && spawn.x <= CHUNK_SIZE * 2);
  assert.ok(spawn.y > 0 && spawn.y < WORLD_HEIGHT);
  assert.ok(BIOMES[spawn.biome]);
  assert.equal(a.isSolid(spawn.x, spawn.y - 1, spawn.z), true);
  assert.equal(a.getBlock(spawn.x, spawn.y, spawn.z), B.AIR);
  assert.equal(a.getBlock(spawn.x, spawn.y + 1, spawn.z), B.AIR);
  assert.ok(Number.isFinite(spawn.yaw));
});

test('world block edits persist as diffs and chests are addressable', () => {
  const world = new World({ seed: 'lumi-edit' });
  const y = SEA_LEVEL + 8;
  world.setBlock(3, y, 5, B.CRYSTAL);
  assert.equal(world.getBlock(3, y, 5), B.CRYSTAL);
  const diffs = world.serializeDiffs();
  const key = world.key(0, 0);
  assert.equal(Object.keys(diffs).length > 0, true);
  assert.ok(Object.values(diffs[key]).includes(B.CRYSTAL));

  world.setChest(3, y, 5, [{ id: 'aurora_gem', count: 2 }]);
  assert.equal(world.chests.has(chestKey(3, y, 5)), true);
  assert.deepEqual(world.chests.get(chestKey(3, y, 5)), [{ id: 'aurora_gem', count: 2 }]);
});

test('inventory starter and rare kits are usable and rare-only', () => {
  const starter = new Inventory();
  starter.giveStarterKit();
  assert.equal(starter.countItem(1), 8);
  assert.equal(starter.countItem('wood_pickaxe'), 1);
  assert.equal(starter.hotbar.filter(Boolean).length, 5);

  const rare = new Inventory();
  rare.giveRareKit();
  const expected = ['aurora_pickaxe', 'cloud_boots', 'lumi_charm', 'heart_lumi', 'prism_seed', 'rainbow_sapling', 'star_soup', 'aurora_gem', 'cloud_feather', 'star_mushroom'];
  for (const id of expected) {
    assert.ok(rare.countItem(id) > 0, `${id} should be in the rare kit`);
    assert.ok(rarityRank(getRarity(id).key) >= rarityRank('raro'), `${id} should be rare or better`);
  }
  assert.equal(rare.canAdd('aurora_gem', 1), true);
  assert.equal(rare.countItem('wood_pickaxe'), 0);
});

test('all recipes reference valid items and positive counts', () => {
  assert.ok(RECIPES.length >= 14);
  for (const recipe of RECIPES) {
    assert.ok(getItem(recipe.out.id), `${recipe.id} output exists`);
    assert.ok(recipe.out.count > 0);
    assert.ok(recipe.ingredients.length > 0);
    for (const ingredient of recipe.ingredients) {
      assert.ok(getItem(ingredient.id), `${recipe.id} ingredient exists`);
      assert.ok(ingredient.count > 0);
    }
    assert.equal(getRecipe(recipe.id), recipe);
  }
});

test('save worlds, settings, codes and imports round-trip', () => {
  resetStorage();
  const created = save.createWorld({ name: 'Teste Unitário', seed: 'lumi-unit', mode: 'adventure' });
  assert.equal(created.ok, true);
  assert.equal(save.listWorlds().length, 1);

  const snapshot = makeSnapshot(created.entry, { diffs: { '0,0': { 123: B.CRYSTAL } }, discovered: ['aurora_gem'] });
  assert.equal(save.saveSnapshot(snapshot).ok, true);
  const loaded = save.getWorld(created.entry.id);
  assert.equal(loaded.name, 'Teste Unitário');
  assert.equal(loaded.diffs['0,0'][123], B.CRYSTAL);
  assert.equal(loaded.inventory.hotbar[0].id, 1);
  assert.equal(save.listWorlds()[0].rare, 1);

  save.saveSettings({ character: { preset: 'nino', name: 'Nino' }, showCoords: true });
  const settings = save.getSettings();
  assert.equal(settings.showCoords, true);
  assert.equal(settings.character.name, 'Nino');
  assert.equal(settings.sound, true);

  const code = save.encodeWorldCode({ seed: 'lumi-code', name: 'Código', mode: 'creative' });
  assert.match(code, /^LC1\./);
  assert.deepEqual(save.decodeWorldCode(code), { seed: 'lumi-code', name: 'Código', mode: 'creative' });
  assert.equal(save.decodeWorldCode('bad'), null);
  assert.equal(save.decodeWorldCode('LC1.not-base64'), null);

  const imported = save.importWorldCode(code);
  assert.equal(imported.ok, true);
  assert.equal(save.listWorlds().length, 2);
});

test('world slots are capped at twelve', () => {
  resetStorage();
  for (let i = 0; i < 12; i++) {
    assert.equal(save.createWorld({ name: `Mundo ${i}`, seed: `seed-${i}` }).ok, true);
  }
  const extra = save.createWorld({ name: 'Mundo extra', seed: 'seed-extra' });
  assert.equal(extra.ok, false);
  assert.match(extra.error, /12 mundos/);
  assert.equal(save.canCreateWorld(), false);
});
