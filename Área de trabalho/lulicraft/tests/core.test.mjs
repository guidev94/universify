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
const { ITEMS, getItem, getRarity, rarityRank, isRare } = await import('../src/game/items.js');
const { RECIPES, getRecipe } = await import('../src/game/recipes.js');
const { CHARACTER_PRESETS, DEFAULT_CHARACTER, WEAPON_SKINS, PETS, HAIR_STYLES, OUTFITS, HATS, GLASSES, BACK_ITEMS, characterFromPreset, characterSignature, getWeaponSkin, getPet, normalizeCharacter } = await import('../src/game/characters.js');
const { PREBUILT_WORLDS, getPrebuilt } = await import('../src/game/prebuilt.js');
const { COMMUNITY_WORLDS, getCommunityWorld } = await import('../src/game/community.js');
const { BRAZIL_WORLDS, getBrazilWorld } = await import('../src/game/brazil.js');
const { randomSeed, randomWorldName } = await import('../src/game/seeds.js');
const { normalizeRoomCode, makeRoomCode } = await import('../src/game/multiplayer.js');
const { CHAT_PHRASES, sanitizeChat, getChatPhrase, CHAT_RATE_MS, HOST_CHAT_RATE_MS } = await import('../src/game/chat.js');
const save = await import('../src/game/save.js');
const packs = await import('../src/game/content-packs.js');
const { BuildHistory, makeChange } = await import('../src/game/build-history.js');

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
  assert.equal(CHARACTER_PRESETS.length, 18);
  assert.equal(new Set(CHARACTER_PRESETS.map((preset) => preset.id)).size, CHARACTER_PRESETS.length);
  for (const entry of CHARACTER_PRESETS) {
    const fromPreset = characterFromPreset(entry.id);
    assert.equal(fromPreset.preset, entry.id);
    assert.equal(fromPreset.name, entry.name);
    assert.ok(HAIR_STYLES.some((style) => style.id === entry.hairStyle));
    assert.ok(OUTFITS.some((outfit) => outfit.id === entry.outfit));
    assert.ok(HATS.some((hat) => hat.id === entry.hat));
    assert.ok(GLASSES.some((glasses) => glasses.id === entry.glasses));
    assert.ok(BACK_ITEMS.some((back) => back.id === entry.back));
  }
  assert.equal(WEAPON_SKINS.length, 6);
  assert.equal(normalizeCharacter({ weaponSkin: 'petal' }).weaponSkin, 'petal');
  assert.equal(normalizeCharacter({ weaponSkin: 'nope' }).weaponSkin, 'aurora');
  assert.equal(getWeaponSkin({ weaponSkin: 'solar' }).color, '#ffc94d');
  assert.equal(PETS.length, 4);
  assert.equal(normalizeCharacter({ petType: 'coruja', petColor: '#ABC123' }).petColor, '#abc123');
  assert.equal(getPet({ petType: 'coelho' }).id, 'coelho');
  assert.equal(getPet({ petType: 'nope' }).id, 'lumi');
});

test('wardrobe layers default from presets and validate ids', () => {
  const preset = characterFromPreset('nori');
  assert.equal(preset.hairStyle, 'spacebuns');
  assert.equal(preset.outfit, 'hoodie');
  assert.equal(preset.hat, 'cap');
  assert.equal(preset.glasses, 'shades');
  assert.equal(preset.back, 'backpack');
  assert.ok(HAIR_STYLES.length >= 8);
  assert.ok(OUTFITS.length >= 5);
  assert.ok(HATS.length >= 9);
  assert.ok(GLASSES.length >= 4);
  assert.ok(BACK_ITEMS.length >= 5);

  const fallback = normalizeCharacter({ preset: 'lumi', hairStyle: 'nope', outfit: 'nope', hat: 'nope', glasses: 'nope', back: 'nope' });
  assert.equal(fallback.hairStyle, 'bob');
  assert.equal(fallback.outfit, 'tee');
  assert.equal(fallback.hat, 'leaf');
  assert.equal(fallback.glasses, 'none');
  assert.equal(fallback.back, 'none');

  const legacy = normalizeCharacter({ preset: 'lumi', accessory: 'goggles' });
  assert.equal(legacy.glasses, 'goggles');
  assert.equal(legacy.hat, 'leaf');

  assert.equal(normalizeCharacter({ petName: '<Nina>' }).petName, 'Nina');
  assert.equal(normalizeCharacter({ petType: 'coruja' }).petName, 'Coruja');
  assert.equal(characterSignature({ preset: 'lumi' }, 'Lumi'), characterSignature(characterFromPreset('lumi')));
  assert.notEqual(characterSignature({ preset: 'lumi', hat: 'crown' }), characterSignature({ preset: 'lumi' }));
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
  assert.equal(PREBUILT_WORLDS.length, 7);
  assert.equal(PREBUILT_WORLDS.filter((world) => world.rareKit).length, 3);
  assert.equal(new Set(PREBUILT_WORLDS.map((world) => world.id)).size, PREBUILT_WORLDS.length);
  assert.equal(new Set(PREBUILT_WORLDS.map((world) => world.seed)).size, PREBUILT_WORLDS.length);
  for (const world of PREBUILT_WORLDS) {
    assert.ok(['adventure', 'creative'].includes(world.mode));
    assert.ok(world.name.length > 2);
    assert.equal(getPrebuilt(world.id), world);
  }
  assert.equal(getPrebuilt('missing'), null);
});

test('community worlds are curated, unique and playable', () => {
  assert.equal(COMMUNITY_WORLDS.length, 6);
  assert.equal(new Set(COMMUNITY_WORLDS.map((world) => world.id)).size, COMMUNITY_WORLDS.length);
  assert.equal(new Set(COMMUNITY_WORLDS.map((world) => world.seed)).size, COMMUNITY_WORLDS.length);
  for (const world of COMMUNITY_WORLDS) {
    assert.ok(['adventure', 'creative'].includes(world.mode));
    assert.ok(world.name.length > 2);
    assert.ok(world.creator.length > 1);
    assert.ok(world.likes > 0);
    assert.equal(getCommunityWorld(world.id), world);
  }
  assert.equal(getCommunityWorld('missing'), null);
});

test('brazil city worlds are curated, unique and playable', () => {
  assert.equal(BRAZIL_WORLDS.length, 6);
  assert.equal(new Set(BRAZIL_WORLDS.map((world) => world.id)).size, BRAZIL_WORLDS.length);
  assert.equal(new Set(BRAZIL_WORLDS.map((world) => world.seed)).size, BRAZIL_WORLDS.length);
  for (const world of BRAZIL_WORLDS) {
    assert.ok(['adventure', 'creative'].includes(world.mode));
    assert.ok(world.city.length > 2);
    assert.ok(world.state.length === 2);
    assert.ok(world.name.length > 2);
    assert.equal(getBrazilWorld(world.id), world);
  }
  assert.equal(getBrazilWorld('missing'), null);
});

test('pet treats are craftable and inventory grows without truncating', () => {
  assert.equal(getItem('pet_treat').category, 'pet');
  assert.ok(RECIPES.some((recipe) => recipe.out.id === 'pet_treat'));

  const inventory = new Inventory();
  for (let i = 0; i < 48; i++) inventory.addItem('stick', 64);
  assert.equal(inventory.countItem('stick'), 48 * 64);
  assert.ok(inventory.backpack.length > 27);
  const restored = new Inventory(inventory.serialize());
  assert.equal(restored.countItem('stick'), 48 * 64);
  assert.equal(restored.backpack.length, inventory.backpack.length);
});

test('random seeds are readable and world names stay short', () => {
  for (let i = 0; i < 30; i++) {
    const seed = randomSeed();
    assert.match(seed, /^[a-z]+-[a-z]+-\d{3}$/);
    const name = randomWorldName(seed);
    assert.ok(name.startsWith('Mundo '));
    assert.ok(name.length <= 20);
  }
});

test('chat only accepts allowlisted phrase ids', () => {
  assert.ok(CHAT_PHRASES.length >= 8);
  assert.equal(new Set(CHAT_PHRASES.map((phrase) => phrase.id)).size, CHAT_PHRASES.length);
  assert.equal(sanitizeChat('hello'), 'hello');
  assert.equal(sanitizeChat(' HELLO '), 'hello');
  assert.equal(sanitizeChat('<script>alert(1)</script>'), null);
  assert.equal(sanitizeChat('free text here'), null);
  assert.equal(sanitizeChat(null), null);
  assert.equal(sanitizeChat({ id: 'hello' }), null);
  assert.equal(getChatPhrase('thanks').text, 'Obrigado!');
  assert.equal(getChatPhrase('nope'), null);
  assert.ok(CHAT_RATE_MS >= 1000);
  assert.ok(HOST_CHAT_RATE_MS >= CHAT_RATE_MS);
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

test('Minha Casa spawns at the door with rare chests', () => {
  const world = new World({ seed: 'lumi-minha-casa' });
  const spawn = world.findSpawn();
  assert.equal(spawn.x, 0.5);
  assert.equal(spawn.z, 6.5);
  assert.equal(spawn.yaw, 0);
  world.getChunk(0, 0);
  assert.equal(world.getBlock(0, 28, 5), B.AIR, 'door is open');
  assert.equal(world.getBlock(0, 27, 5), B.PINK_PLANKS, 'porch step exists');
  assert.equal(world.getBlock(5, 28, 0), B.PINK_PLANKS, 'house wall is pink');
  assert.equal(world.getBlock(0, 34, 0), B.PRISM, 'roof is prism');
  assert.ok(world.chests.size >= 4, 'home has chests');
  const rare = [...world.chests.values()].filter((items) => items.some((slot) => slot && isRare(slot.id)));
  assert.ok(rare.length >= 4, 'home chests contain rare loot');
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


test('content packs validate palette, blocks and unsafe payloads', () => {
  const valid = packs.parseContentPack(JSON.stringify({
    version: 1,
    id: 'aurora-suave',
    name: 'Aurora Suave',
    author: 'Lumi',
    palette: { lumi: '#B8FF6A', pink: '#ff9edb' },
    blocks: [B.GRASS, B.CRYSTAL]
  }));
  assert.equal(valid.ok, true);
  assert.equal(valid.pack.id, 'aurora-suave');
  assert.equal(valid.pack.palette.lumi, '#b8ff6a');
  assert.deepEqual(valid.pack.blocks, [B.GRASS, B.CRYSTAL]);

  assert.equal(packs.parseContentPack('{').ok, false);
  assert.equal(packs.parseContentPack(JSON.stringify({ version: 1, id: '../evil', name: 'X' })).ok, false);
  assert.equal(packs.parseContentPack(JSON.stringify({ version: 1, id: 'safe-id', name: 'X', palette: { hack: '#ffffff' } })).ok, false);
  assert.equal(packs.parseContentPack(JSON.stringify({ version: 1, id: 'safe-id', name: 'X', palette: { lumi: 'red' } })).ok, false);
  assert.equal(packs.parseContentPack(JSON.stringify({ version: 1, id: 'safe-id', name: 'X', blocks: [999] })).ok, false);
  assert.equal(packs.parseContentPack(JSON.stringify({ version: 1, id: 'safe-id', name: 'X', blocks: [0] })).ok, false);
});


test('build history undoes and redoes blocks without growing forever', () => {
  const history = new BuildHistory(3);
  const change = (x) => makeChange({ x, y: 2, z: 3, before: B.AIR, after: B.GRASS });
  history.record('a', [change(1)]);
  history.record('b', [change(2)]);
  history.record('c', [change(3)]);
  history.record('d', [change(4)]);
  assert.equal(history.canUndo, true);
  assert.equal(history.undo().changes.length, 1);
  assert.equal(history.undo().changes.length, 1);
  assert.equal(history.undo().changes.length, 1);
  assert.equal(history.undo(), null);
  assert.equal(history.redo().changes.length, 1);
  assert.equal(history.redo().changes.length, 1);
  assert.equal(history.redo().changes.length, 1);
  assert.equal(history.redo(), null);
  assert.equal(history.undoStack.length, 3);
});

test('build history clones chest loot and exposes the next entry', () => {
  const history = new BuildHistory(4);
  const loot = [{ id: 'lumi_shard', count: 3 }];
  history.record('baú', [makeChange({
    x: 1, y: 2, z: 3,
    before: B.CHEST, after: B.AIR,
    chestBefore: [{ id: 'lumi_shard', count: 3 }],
    chestAfter: null,
    loot
  })]);
  loot[0].count = 99;
  const pending = history.peekUndo();
  assert.equal(pending.changes[0].loot[0].count, 3);
  assert.equal(history.peekRedo(), null);
  history.undo();
  assert.equal(history.peekUndo(), null);
  assert.equal(history.peekRedo().label, 'baú');
});
