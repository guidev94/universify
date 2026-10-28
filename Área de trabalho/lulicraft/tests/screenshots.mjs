import { spawn } from 'node:child_process';
import { existsSync, mkdirSync, rmSync, writeFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';

const PROJECT_ROOT = fileURLToPath(new URL('..', import.meta.url));
const OUT = `${PROJECT_ROOT}/docs/screenshots`;
const HTTP_PORT = 8124;
const CDP_PORT = 9334;
const PROFILE = '/tmp/lulicraft-shots-profile';
const CHROME = process.env.CHROME_BIN || '/usr/bin/google-chrome';
const PYTHON = process.env.PYTHON_BIN || '/usr/bin/python3';
const wait = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

async function waitForHttp(url, timeout = 15000) {
  const started = Date.now();
  while (Date.now() - started < timeout) {
    try {
      if ((await fetch(url)).ok) return true;
    } catch {}
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
    } catch {}
    await wait(200);
  }
  throw new Error('Chrome DevTools endpoint did not start');
}

class Cdp {
  constructor(url) {
    this.ws = new WebSocket(url);
    this.pending = new Map();
    this.nextId = 0;
    this.ws.addEventListener('message', (event) => {
      const message = JSON.parse(event.data);
      if (!message.id) return;
      const entry = this.pending.get(message.id);
      if (!entry) return;
      this.pending.delete(message.id);
      if (message.error) entry.reject(new Error(JSON.stringify(message.error)));
      else entry.resolve(message.result);
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
    if (result.exceptionDetails) throw new Error(result.exceptionDetails.exception?.description || result.exceptionDetails.text || 'evaluation failed');
    return result.result.value;
  }
  async waitFor(expression, timeout = 20000) {
    const started = Date.now();
    while (Date.now() - started < timeout) {
      if (await this.evaluate(expression)) return true;
      await wait(200);
    }
    throw new Error(`Timed out waiting for: ${expression}`);
  }
  close() { this.ws.close(); }
}

let server;
let chrome;
let cdp;
let count = 0;
async function shot(name, full = false) {
  await cdp.evaluate('document.querySelectorAll(".toast, .pickup-chip").forEach((node) => node.remove())').catch(() => {});
  await wait(260);
  const params = { format: 'png', fromSurface: true };
  if (full) {
    const layout = await cdp.send('Page.getLayoutMetrics');
    params.captureBeyondViewport = true;
    params.clip = {
      x: 0,
      y: 0,
      width: Math.ceil(layout.cssContentSize.width),
      height: Math.ceil(layout.cssContentSize.height),
      scale: 1
    };
  }
  const result = await cdp.send('Page.captureScreenshot', params);
  writeFileSync(`${OUT}/${name}.png`, Buffer.from(result.data, 'base64'));
  count += 1;
}

try {
  mkdirSync(OUT, { recursive: true });
  if (!existsSync(CHROME)) throw new Error(`Chrome not found at ${CHROME}`);
  server = spawn(PYTHON, ['-m', 'http.server', String(HTTP_PORT), '--bind', '127.0.0.1'], { cwd: PROJECT_ROOT, stdio: 'ignore' });
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
    '--disable-background-timer-throttling',
    '--disable-backgrounding-occluded-windows',
    '--disable-renderer-backgrounding',
    '--disable-features=CalculateNativeWinOcclusion',
    '--noerrdialogs',
    '--no-first-run',
    '--window-size=1440,1000',
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
  await cdp.send('Page.bringToFront');
  try {
    await cdp.send('Emulation.setFocusEmulationEnabled', { enabled: true });
    await cdp.send('Page.setWebLifecycleState', { state: 'active' });
  } catch {}

  await cdp.send('Page.navigate', { url: `http://127.0.0.1:${HTTP_PORT}/?menu=1&debug=1` });
  await cdp.waitFor('!!window.__lulicraft && !document.getElementById("menu").classList.contains("hidden")');
  await cdp.evaluate('localStorage.clear(); location.reload();');
  await cdp.waitFor('!!window.__lulicraft && !document.getElementById("menu").classList.contains("hidden")');
  await wait(700);
  await shot('01-menu', true);

  await cdp.evaluate('document.getElementById("btn-character").click()');
  await cdp.waitFor('!document.getElementById("character-modal").classList.contains("hidden")');
  await wait(400);
  await shot('02-personagem');

  await cdp.evaluate('document.getElementById("btn-character-cancel").click()');
  await cdp.evaluate('document.getElementById("btn-settings").click()');
  await cdp.waitFor('!document.getElementById("settings-modal").classList.contains("hidden")');
  await shot('03-configuracoes');

  await cdp.evaluate('document.getElementById("btn-packs").click()');
  await cdp.waitFor('!document.getElementById("packs-modal").classList.contains("hidden")');
  await cdp.evaluate('document.getElementById("btn-pack-example").click(); document.getElementById("btn-pack-import").click();');
  await wait(350);
  await shot('04-pacotes');
  await cdp.evaluate('document.getElementById("btn-packs-close").click(); document.getElementById("btn-settings-done").click();');

  await cdp.evaluate('document.getElementById("btn-create").click()');
  await cdp.waitFor('!document.getElementById("create-modal").classList.contains("hidden")');
  await shot('05-criar-mundo');
  await cdp.evaluate('document.getElementById("btn-create-cancel").click()');

  await cdp.evaluate(`
    const card = [...document.querySelectorAll('.prebuilt-card')].find((node) => node.textContent.includes('Minha Casa'));
    card.querySelector('button').click();
  `);
  await cdp.waitFor('window.__lulicraft.running === true', 30000);
  await cdp.waitFor('window.__lulicraft.ready === true', 30000);
  await cdp.evaluate('window.__lulicraft.ui.closeTutorial()');
  await wait(2400);
  await cdp.evaluate(`
    document.querySelectorAll('.toast, .pickup-chip').forEach((node) => node.remove());
    const player = window.__lulicraft.player;
    const world = window.__lulicraft.world;
    let spot = null;
    for (let radius = 46; radius <= 74 && !spot; radius += 2) {
      for (let step = 0; step < 24 && !spot; step++) {
        const angle = (step / 24) * Math.PI * 2;
        const x = Math.round(Math.cos(angle) * radius);
        const z = Math.round(Math.sin(angle) * radius);
        const y = world.heightAt(x, z);
        let clear = y > 22;
        for (let dx = -2; dx <= 2 && clear; dx++) {
          for (let dz = -2; dz <= 2 && clear; dz++) {
            for (let dy = 0; dy <= 4; dy++) {
              if (world.getBlock(x + dx, y + dy, z + dz) !== 0) { clear = false; break; }
            }
          }
        }
        if (clear) spot = { x, y, z };
      }
    }
    spot = spot || { x: 0, y: world.heightAt(0, 0) + 2, z: 8 };
    player.setPosition(spot.x, spot.y + 1, spot.z);
    player.yaw = Math.atan2(-spot.x, -spot.z);
    player.pitch = -0.04;
    if (window.__lulicraft.petBubble) window.__lulicraft.petBubble.sprite.visible = false;
  `);
  await wait(900);
  await cdp.evaluate('document.querySelectorAll(".toast, .pickup-chip").forEach((node) => node.remove())');
  await shot('06-gameplay');

  await cdp.evaluate('window.__lulicraft.toggleThirdPerson()');
  await wait(700);
  await shot('07-terceira-pessoa');
  await cdp.evaluate('window.__lulicraft.toggleThirdPerson()');

  await cdp.evaluate('window.__lulicraft.ui.openInventory("backpack")');
  await cdp.waitFor('window.__lulicraft.ui.inventoryOpen === true');
  await wait(400);
  await shot('08-inventario');
  await cdp.evaluate(`
    const input = document.getElementById('inventory-search');
    input.value = 'lumi';
    input.dispatchEvent(new Event('input', { bubbles: true }));
  `);
  await wait(300);
  await shot('09-inventario-busca');
  await cdp.evaluate('window.__lulicraft.ui.closeInventory()');

  await cdp.evaluate('window.__lulicraft.toggleMap(true)');
  await wait(500);
  await shot('10-mapa');
  await cdp.evaluate('window.__lulicraft.toggleMap(false)');

  await cdp.evaluate('window.__lulicraft.ui.openMissions(window.__lulicraft.currentMissions())');
  await cdp.waitFor('!document.getElementById("missions-modal").classList.contains("hidden")');
  await wait(300);
  await shot('11-missoes');
  await cdp.evaluate('window.__lulicraft.ui.closeMissions()');

  await cdp.evaluate('document.getElementById("btn-pause").click()');
  await cdp.waitFor('!document.getElementById("pause-modal").classList.contains("hidden")');
  await wait(350);
  await shot('12-pausa');

  await cdp.evaluate('document.getElementById("btn-invite").click()');
  await cdp.waitFor('!document.getElementById("multiplayer-modal").classList.contains("hidden")');
  await wait(300);
  await shot('13-multiplayer');
  await cdp.evaluate('window.__lulicraft.ui.closeMultiplayer()');
  await cdp.waitFor('!document.getElementById("pause-modal").classList.contains("hidden")');
  await cdp.evaluate('document.getElementById("btn-resume").click()');

  await cdp.send('Emulation.setDeviceMetricsOverride', { width: 390, height: 844, deviceScaleFactor: 1, mobile: true });
  await cdp.send('Emulation.setTouchEmulationEnabled', { enabled: true, maxTouchPoints: 5 });
  await cdp.send('Page.navigate', { url: `http://127.0.0.1:${HTTP_PORT}/?menu=1&debug=1` });
  await cdp.waitFor('!!window.__lulicraft && !document.getElementById("menu").classList.contains("hidden")');
  await wait(700);
  await shot('14-mobile-menu');
  await cdp.evaluate(`
    const card = [...document.querySelectorAll('.prebuilt-card')].find((node) => node.textContent.includes('Minha Casa'));
    card.querySelector('button').click();
  `);
  await cdp.waitFor('window.__lulicraft.running === true', 30000);
  await cdp.waitFor('window.__lulicraft.ready === true', 30000);
  await cdp.evaluate(`
    window.__lulicraft.ui.closeTutorial();
    document.getElementById('rotate-hint')?.classList.add('hidden');
    if (window.__lulicraft.petBubble) {
      window.__lulicraft.petBubble.sprite.visible = false;
      window.__lulicraft.petBubble.life = 0;
    }
    document.querySelectorAll('.toast, .pickup-chip').forEach((node) => node.remove());
  `);
  await wait(1200);
  await shot('15-mobile-gameplay');
  await cdp.send('Emulation.clearDeviceMetricsOverride');
  await cdp.send('Emulation.setTouchEmulationEnabled', { enabled: false });

  console.log(JSON.stringify({ screenshots: count, dir: OUT }, null, 2));
} finally {
  cdp?.close();
  chrome?.kill('SIGTERM');
  server?.kill('SIGTERM');
}
