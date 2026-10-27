import { spawn } from 'node:child_process';
import { existsSync, rmSync, writeFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import process from 'node:process';

const PROJECT_ROOT = fileURLToPath(new URL('..', import.meta.url));
const HTTP_PORT = 8123;
const CDP_PORT = 9333;
const PROFILE = '/tmp/lulicraft-e2e-profile';
const CHROME = process.env.CHROME_BIN || '/usr/bin/google-chrome';
const PYTHON = process.env.PYTHON_BIN || '/usr/bin/python3';

const wait = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

async function waitForHttp(url, timeout = 15000) {
  const started = Date.now();
  while (Date.now() - started < timeout) {
    try {
      const response = await fetch(url);
      if (response.ok) return true;
    } catch {
      // retry
    }
    await wait(200);
  }
  return false;
}

async function waitForCdp(timeout = 15000) {
  const started = Date.now();
  while (Date.now() - started < timeout) {
    try {
      const response = await fetch(`http://127.0.0.1:${CDP_PORT}/json/version`);
      if (response.ok) return response.json();
    } catch {
      // retry
    }
    await wait(200);
  }
  throw new Error('Chrome DevTools endpoint did not start');
}

class Cdp {
  constructor(url) {
    this.ws = new WebSocket(url);
    this.pending = new Map();
    this.nextId = 0;
    this.exceptions = [];
    this.consoleErrors = [];
    this.ws.addEventListener('message', (event) => {
      const message = JSON.parse(event.data);
      if (message.id) {
        const entry = this.pending.get(message.id);
        if (!entry) return;
        this.pending.delete(message.id);
        if (message.error) entry.reject(new Error(JSON.stringify(message.error)));
        else entry.resolve(message.result);
        return;
      }
      if (message.method === 'Runtime.exceptionThrown') {
        this.exceptions.push(message.params.exceptionDetails.text || 'unknown exception');
      }
      if (message.method === 'Runtime.consoleAPICalled' && message.params.type === 'error') {
        this.consoleErrors.push(message.params.args.map((arg) => arg.value ?? arg.description ?? arg.type).join(' '));
      }
    });
  }

  async open() {
    await new Promise((resolve, reject) => {
      this.ws.addEventListener('open', resolve, { once: true });
      this.ws.addEventListener('error', reject, { once: true });
    });
  }

  send(method, params = {}) {
    return new Promise((resolve, reject) => {
      const id = ++this.nextId;
      this.pending.set(id, { resolve, reject });
      this.ws.send(JSON.stringify({ id, method, params }));
    });
  }

  async evaluate(expression) {
    const result = await this.send('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true });
    if (result.exceptionDetails) throw new Error(result.exceptionDetails.text || 'evaluation failed');
    return result.result.value;
  }

  async waitFor(expression, timeout = 15000) {
    const started = Date.now();
    while (Date.now() - started < timeout) {
      const value = await this.evaluate(expression);
      if (value) return value;
      await wait(200);
    }
    throw new Error(`Timed out waiting for: ${expression}`);
  }

  close() {
    this.ws.close();
  }
}

let server = null;
let chrome = null;
let cdp = null;

try {
  if (!existsSync(CHROME)) throw new Error(`Chrome not found at ${CHROME}`);
  server = spawn(PYTHON, ['-m', 'http.server', String(HTTP_PORT), '--bind', '127.0.0.1'], {
    cwd: PROJECT_ROOT,
    stdio: 'ignore'
  });
  if (!(await waitForHttp(`http://127.0.0.1:${HTTP_PORT}/`))) throw new Error('Static server did not start');

  rmSync(PROFILE, { recursive: true, force: true });
  chrome = spawn(CHROME, [
    '--headless=new',
    `--remote-debugging-port=${CDP_PORT}`,
    `--user-data-dir=${PROFILE}`,
    '--no-sandbox',
    '--disable-gpu',
    '--use-gl=swiftshader',
    '--enable-unsafe-swiftshader',
    '--ozone-platform=headless',
    '--noerrdialogs',
    '--no-first-run',
    '--window-size=1280,900',
    'about:blank'
  ], { stdio: 'ignore' });

  await waitForCdp();
  const targets = await (await fetch(`http://127.0.0.1:${CDP_PORT}/json`)).json();
  const page = targets.find((target) => target.type === 'page');
  if (!page) throw new Error('No Chrome page target');

  cdp = new Cdp(page.webSocketDebuggerUrl);
  await cdp.open();
  await cdp.send('Runtime.enable');
  await cdp.send('Page.enable');

  await cdp.send('Page.navigate', { url: `http://127.0.0.1:${HTTP_PORT}/?menu=1&debug=1` });
  await cdp.waitFor('!!window.__lulicraft && !document.getElementById("menu").classList.contains("hidden")');
  await cdp.evaluate('localStorage.clear(); location.reload();');
  await cdp.waitFor('!!window.__lulicraft && !document.getElementById("menu").classList.contains("hidden")');

  const results = {};
  results.prebuiltCards = await cdp.evaluate('document.querySelectorAll("#prebuilt-grid .prebuilt-card").length');
  results.communityCards = await cdp.evaluate('document.querySelectorAll("#community-grid .community-card").length');
  results.cityCards = await cdp.evaluate('document.querySelectorAll("#city-grid .city-card").length');
  results.randomWorldButton = await cdp.evaluate('!!document.getElementById("btn-random-world")');
  results.characterButton = await cdp.evaluate('!!document.getElementById("btn-character")');
  results.logoLoaded = await cdp.evaluate('document.querySelector(".hero-logo").naturalWidth > 0');
  results.fullscreenButton = await cdp.evaluate('!!document.getElementById("btn-fullscreen-menu")');
  const menuShot = await cdp.send('Page.captureScreenshot', { format: 'png' });
  writeFileSync('/tmp/lulicraft-menu.png', Buffer.from(menuShot.data, 'base64'));
  const layout = await cdp.send('Page.getLayoutMetrics');
  const menuFull = await cdp.send('Page.captureScreenshot', {
    format: 'png',
    captureBeyondViewport: true,
    clip: {
      x: 0,
      y: 0,
      width: Math.ceil(layout.cssContentSize.width),
      height: Math.ceil(layout.cssContentSize.height),
      scale: 1
    }
  });
  writeFileSync('/tmp/lulicraft-menu-full.png', Buffer.from(menuFull.data, 'base64'));
  await cdp.evaluate("document.getElementById('city-grid')?.scrollIntoView({ block: 'start' })");
  await wait(350);
  const cityShot = await cdp.send('Page.captureScreenshot', { format: 'png' });
  writeFileSync('/tmp/lulicraft-cities.png', Buffer.from(cityShot.data, 'base64'));
  await cdp.evaluate("document.getElementById('menu').scrollTop = 0");

  await cdp.evaluate('document.getElementById("btn-character").click()');
  await cdp.waitFor('!document.getElementById("character-modal").classList.contains("hidden")');
  results.presets = await cdp.evaluate('document.querySelectorAll("#character-presets .character-preset").length');
  results.avatarDetails = await cdp.evaluate('document.querySelectorAll("#character-preview .avatar-ear, #character-preview .avatar-shoe, #character-preview .avatar-hand").length');
  results.swatches = await cdp.evaluate('document.querySelectorAll("#character-colors .color-swatch").length');
  results.weaponSkins = await cdp.evaluate('document.querySelectorAll("#weapon-skins .weapon-skin").length');
  results.petTypes = await cdp.evaluate('document.querySelectorAll("#pet-types .pet-type").length');
  results.petColors = await cdp.evaluate('document.querySelectorAll("#pet-colors .pet-color").length');
  results.petPreview = await cdp.evaluate('document.getElementById("character-preview").dataset.pet');
  await cdp.evaluate('document.querySelector("[data-pet-type=\\\"coruja\\\"]").click()');
  results.petPreviewAfterClick = await cdp.evaluate('document.getElementById("character-preview").dataset.pet');
  const characterShot = await cdp.send('Page.captureScreenshot', { format: 'png' });
  writeFileSync('/tmp/lulicraft-character.png', Buffer.from(characterShot.data, 'base64'));
  await cdp.evaluate('document.querySelector("[data-weapon-skin=\\"solar\\"]").click()');
  await cdp.evaluate(`
    document.getElementById('character-name').value = 'Teste E2E';
    document.getElementById('character-name').dispatchEvent(new Event('input', { bubbles: true }));
    document.getElementById('btn-character-save').click();
  `);
  await cdp.waitFor('document.getElementById("character-modal").classList.contains("hidden")');
  results.savedCharacter = await cdp.evaluate('JSON.parse(localStorage.getItem("lulicraft:settings")).character.name');
  results.savedWeaponSkin = await cdp.evaluate('JSON.parse(localStorage.getItem("lulicraft:settings")).character.weaponSkin');
  results.savedPetType = await cdp.evaluate('JSON.parse(localStorage.getItem("lulicraft:settings")).character.petType');
  await cdp.evaluate('document.getElementById("btn-settings").click()');
  await cdp.waitFor('!document.getElementById("settings-modal").classList.contains("hidden")');
  await cdp.evaluate(`
    const select = document.getElementById('setting-graphics');
    select.value = 'turbo';
    select.dispatchEvent(new Event('change', { bubbles: true }));
  `);
  results.graphicsMode = await cdp.evaluate('document.body.dataset.graphics');
  results.savedGraphics = await cdp.evaluate('JSON.parse(localStorage.getItem("lulicraft:settings")).quality');
  await cdp.evaluate('document.getElementById("btn-settings-done").click()');

  await cdp.evaluate('document.getElementById("btn-create").click()');
  await cdp.waitFor('!document.getElementById("create-modal").classList.contains("hidden")');
  await cdp.evaluate(`
    document.getElementById('world-name-input').value = 'Mundo E2E';
    document.getElementById('world-seed-input').value = 'lulicraft-e2e';
    document.getElementById('world-rare-kit').checked = true;
    document.getElementById('btn-create-confirm').click();
  `);
  await cdp.waitFor('window.__lulicraft.running === true', 20000);
  results.running = await cdp.evaluate('window.__lulicraft.running');
  results.worldName = await cdp.evaluate('document.getElementById("hud-world-name").textContent');
  results.rareKit = await cdp.evaluate(`(() => {
    const inv = window.__lulicraft.inventory;
    return ['aurora_pickaxe','cloud_boots','lumi_charm','heart_lumi','prism_seed','rainbow_sapling','star_soup','moon_berry','bubble_wand','star_sling','petal_blade','aurora_gem','cloud_feather','star_mushroom']
      .every((id) => inv.countItem(id) > 0);
  })()`);
  results.hotbarSlots = await cdp.evaluate('document.querySelectorAll("#hotbar .hotbar-slot").length');
  await cdp.evaluate('window.__lulicraft.ui.closeTutorial()');
  await wait(350);
  results.companion = await cdp.evaluate('window.__lulicraft.companion?.userData.parts?.petType || null');
  results.npcCount = await cdp.evaluate('window.__lulicraft.npcs.length');
  results.npcPrompt = await cdp.evaluate(`(() => {
    const npc = window.__lulicraft.npcs[0];
    if (!npc) return null;
    window.__lulicraft.player.position.set(npc.group.position.x, npc.baseY, npc.group.position.z + 2.2);
    return npc.def.name;
  })()`);
  await wait(450);
  results.npcHintVisible = await cdp.evaluate('!document.getElementById("npc-hint").classList.contains("hidden")');
  results.npcTalk = await cdp.evaluate(`(() => {
    window.__lulicraft.talkToNpc();
    const titles = [...document.querySelectorAll('#toasts .toast-title')];
    return titles.at(-1)?.textContent || '';
  })()`);
  results.inventoryExpanded = await cdp.evaluate(`(() => {
    const inv = window.__lulicraft.inventory;
    for (let i = 0; i < 48; i++) inv.addItem('stick', 64);
    return { rows: inv.backpack.length, sticks: inv.countItem('stick') };
  })()`);
  results.handVisible = await cdp.evaluate('!document.getElementById("hand").classList.contains("hidden")');
  results.onlineChip = await cdp.evaluate('document.getElementById("hud-players").textContent');
  results.thirdPerson = await cdp.evaluate(`(() => {
    window.__lulicraft.toggleThirdPerson();
    return { active: window.__lulicraft.thirdPerson, visible: !!window.__lulicraft.localAvatar?.visible };
  })()`);
  results.handSwing = await cdp.evaluate(`(() => {
    window.__lulicraft.ui.swingHand();
    return document.getElementById('hand').classList.contains('swing');
  })()`);
  results.pickupChip = await cdp.evaluate(`(() => {
    window.__lulicraft.ui.showPickup('lumi_shard', 3);
    return document.querySelector('#pickup-feed .pickup-chip strong')?.textContent || '';
  })()`);
  results.landing = await cdp.evaluate(`(() => {
    const game = window.__lulicraft;
    game.player.fly = true;
    game.player.landingHold = 3.96;
    game.player.position.y = Math.max(30, game.player.position.y + 2);
    window.dispatchEvent(new KeyboardEvent('keydown', { code: 'Space', key: ' ', bubbles: true }));
    return { fly: game.player.fly, hold: game.player.landingHold };
  })()`);
  await wait(1800);
  results.landingAfter = await cdp.evaluate(`(() => {
    window.dispatchEvent(new KeyboardEvent('keyup', { code: 'Space', key: ' ', bubbles: true }));
    return { fly: window.__lulicraft.player.fly, hintHidden: document.getElementById('landing-hint').classList.contains('hidden') };
  })()`);
  results.weaponUse = await cdp.evaluate(`(() => {
    const inv = window.__lulicraft.inventory;
    const found = inv.findSlot('bubble_wand');
    if (!found) return { ok: false, reason: 'missing bubble_wand' };
    if (found.arr === inv.hotbar) inv.select(found.index);
    const before = window.__lulicraft.projectiles.items.length;
    window.__lulicraft.useWeapon(window.__lulicraft.getItem('bubble_wand'), null);
    return { ok: window.__lulicraft.projectiles.items.length > before, before, after: window.__lulicraft.projectiles.items.length };
  })()`);
  results.exceptions = cdp.exceptions.length;
  results.consoleErrors = cdp.consoleErrors.length;

  await cdp.send('Page.navigate', { url: `http://127.0.0.1:${HTTP_PORT}/?debug=1` });
  await cdp.waitFor('!!window.__lulicraft && window.__lulicraft.running === true', 20000);
  results.autoPlay = await cdp.evaluate('window.__lulicraft.running === true');
  results.menuHiddenOnLoad = await cdp.evaluate('document.getElementById("menu").classList.contains("hidden")');

  const shot = await cdp.send('Page.captureScreenshot', { format: 'png' });
  writeFileSync('/tmp/lulicraft-e2e.png', Buffer.from(shot.data, 'base64'));

  const failures = [];
  if (results.prebuiltCards !== 6) failures.push(`prebuilt cards: ${results.prebuiltCards}`);
  if (results.communityCards !== 6) failures.push(`community cards: ${results.communityCards}`);
  if (results.cityCards !== 6) failures.push(`city cards: ${results.cityCards}`);
  if (!results.randomWorldButton) failures.push('random world button missing');
  if (!results.characterButton) failures.push('character button missing');
  if (results.presets !== 9) failures.push(`presets: ${results.presets}`);
  if (results.avatarDetails < 6) failures.push(`avatar details: ${results.avatarDetails}`);
  if (results.swatches !== 30) failures.push(`swatches: ${results.swatches}`);
  if (results.weaponSkins !== 4) failures.push(`weapon skins: ${results.weaponSkins}`);
  if (results.petTypes !== 4) failures.push(`pet types: ${results.petTypes}`);
  if (results.petColors !== 8) failures.push(`pet colors: ${results.petColors}`);
  if (results.petPreview !== 'lumi') failures.push(`pet preview: ${results.petPreview}`);
  if (results.petPreviewAfterClick !== 'coruja') failures.push(`pet preview click: ${results.petPreviewAfterClick}`);
  if (!results.logoLoaded) failures.push('hero logo did not load');
  if (!results.fullscreenButton) failures.push('fullscreen button missing');
  if (results.savedCharacter !== 'Teste E2E') failures.push(`saved character: ${results.savedCharacter}`);
  if (results.savedWeaponSkin !== 'solar') failures.push(`saved weapon skin: ${results.savedWeaponSkin}`);
  if (results.savedPetType !== 'coruja') failures.push(`saved pet type: ${results.savedPetType}`);
  if (results.graphicsMode !== 'turbo') failures.push(`graphics mode: ${results.graphicsMode}`);
  if (results.savedGraphics !== 'turbo') failures.push(`saved graphics: ${results.savedGraphics}`);
  if (!results.running) failures.push('world did not start');
  if (results.worldName !== 'Mundo E2E') failures.push(`world name: ${results.worldName}`);
  if (!results.rareKit) failures.push('rare kit incomplete');
  if (results.hotbarSlots !== 9) failures.push(`hotbar slots: ${results.hotbarSlots}`);
  if (!results.handVisible) failures.push('hand item missing');
  if (results.companion !== 'coruja') failures.push(`companion type: ${results.companion}`);
  if (results.npcCount !== 3) failures.push(`npc count: ${results.npcCount}`);
  if (!results.npcPrompt) failures.push('npc prompt setup failed');
  if (!results.npcHintVisible) failures.push('npc hint not visible');
  if (!results.npcTalk.includes(',')) failures.push(`npc talk: ${results.npcTalk}`);
  if (results.inventoryExpanded.rows <= 27) failures.push(`inventory did not expand: ${JSON.stringify(results.inventoryExpanded)}`);
  if (results.inventoryExpanded.sticks !== 48 * 64) failures.push(`inventory lost sticks: ${JSON.stringify(results.inventoryExpanded)}`);
  if (!results.landing.fly) failures.push('landing setup failed');
  if (results.landingAfter.fly) failures.push(`hold-to-land did not land: ${JSON.stringify(results.landingAfter)}`);
  if (!results.landingAfter.hintHidden) failures.push('landing hint stayed visible');
  if (!/online/.test(results.onlineChip)) failures.push(`online chip: ${results.onlineChip}`);
  if (!results.thirdPerson.active || !results.thirdPerson.visible) failures.push(`third person: ${JSON.stringify(results.thirdPerson)}`);
  if (!results.handSwing) failures.push('hand swing missing');
  if (!results.pickupChip.includes('+3')) failures.push(`pickup chip: ${results.pickupChip}`);
  if (!results.weaponUse.ok) failures.push(`weapon use: ${JSON.stringify(results.weaponUse)}`);
  if (!results.autoPlay) failures.push('root did not auto quick play');
  if (!results.menuHiddenOnLoad) failures.push('menu visible on root load');
  if (results.exceptions > 0) failures.push(`runtime exceptions: ${results.exceptions}`);
  if (results.consoleErrors > 0) failures.push(`console errors: ${results.consoleErrors}`);

  console.log(JSON.stringify(results, null, 2));
  if (failures.length) {
    console.error(`E2E failures:\n- ${failures.join('\n- ')}`);
    process.exitCode = 1;
  } else {
    console.log('E2E smoke passed');
  }
} finally {
  cdp?.close();
  chrome?.kill('SIGTERM');
  server?.kill('SIGTERM');
  await wait(300);
}
