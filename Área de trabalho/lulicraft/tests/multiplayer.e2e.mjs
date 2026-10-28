import { spawn } from 'node:child_process';
import { existsSync, rmSync, writeFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import process from 'node:process';

const PROJECT_ROOT = fileURLToPath(new URL('..', import.meta.url));
const HTTP_PORT = 8124;
const HOST_CDP = 9334;
const GUEST_CDP = 9335;
const HOST_PROFILE = '/tmp/lulicraft-mp-host-profile';
const GUEST_PROFILE = '/tmp/lulicraft-mp-guest-profile';
const CHROME = process.env.CHROME_BIN || '/usr/bin/google-chrome';
const PYTHON = process.env.PYTHON_BIN || '/usr/bin/python3';
const ORIGIN = `http://127.0.0.1:${HTTP_PORT}`;

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

async function waitForCdp(port, timeout = 15000) {
  const started = Date.now();
  while (Date.now() - started < timeout) {
    try {
      const response = await fetch(`http://127.0.0.1:${port}/json/version`);
      if (response.ok) return response.json();
    } catch {
      // retry
    }
    await wait(200);
  }
  throw new Error(`Chrome DevTools endpoint ${port} did not start`);
}

class Cdp {
  constructor(url, label) {
    this.label = label;
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
    await this.send('Runtime.enable');
    await this.send('Page.enable');
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
      try {
        const value = await this.evaluate(expression);
        if (value) return value;
      } catch {
        // navigation can invalidate an execution context
      }
      await wait(200);
    }
    throw new Error(`${this.label} timed out waiting for: ${expression}`);
  }

  async front() {
    await this.send('Page.bringToFront');
  }

  close() {
    this.ws.close();
  }
}

function launchChrome(port, profile) {
  rmSync(profile, { recursive: true, force: true });
  return spawn(CHROME, [
    '--headless=new',
    `--remote-debugging-port=${port}`,
    `--user-data-dir=${profile}`,
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
}

async function pageFor(port, label) {
  const targets = await (await fetch(`http://127.0.0.1:${port}/json`)).json();
  const page = targets.find((target) => target.type === 'page');
  if (!page) throw new Error(`No Chrome page target on ${port}`);
  const cdp = new Cdp(page.webSocketDebuggerUrl, label);
  await cdp.open();
  return cdp;
}

async function prepare(cdp) {
  await cdp.send('Page.navigate', { url: `${ORIGIN}/?menu=1&debug=1` });
  await cdp.waitFor('!!window.__lulicraft');
  await cdp.evaluate("localStorage.clear(); localStorage.setItem('lulicraft:settings', JSON.stringify({ tutorialDone: true }));");
  const fresh = Date.now();
  await cdp.send('Page.navigate', { url: `${ORIGIN}/?menu=1&debug=1&fresh=${fresh}` });
  await cdp.waitFor(`location.search.includes('fresh=${fresh}') && document.readyState === 'complete' && !!window.__lulicraft && !document.getElementById("menu").classList.contains("hidden")`);
}

let server = null;
let hostChrome = null;
let guestChrome = null;
let host = null;
let guest = null;

try {
  if (!existsSync(CHROME)) throw new Error(`Chrome not found at ${CHROME}`);
  server = spawn(PYTHON, ['-m', 'http.server', String(HTTP_PORT), '--bind', '127.0.0.1'], {
    cwd: PROJECT_ROOT,
    stdio: 'ignore'
  });
  if (!(await waitForHttp(`${ORIGIN}/`))) throw new Error('Static server did not start');

  hostChrome = launchChrome(HOST_CDP, HOST_PROFILE);
  guestChrome = launchChrome(GUEST_CDP, GUEST_PROFILE);
  await Promise.all([waitForCdp(HOST_CDP), waitForCdp(GUEST_CDP)]);
  [host, guest] = await Promise.all([pageFor(HOST_CDP, 'host'), pageFor(GUEST_CDP, 'guest')]);

  console.log('launching host room');
  await host.front();
  await prepare(host);
  await host.evaluate('document.getElementById("btn-multiplayer").click()');
  await host.waitFor('!document.getElementById("multiplayer-modal").classList.contains("hidden")');
  await host.evaluate('document.getElementById("btn-host-room").click()');
  console.log('waiting for host room code');
  let code;
  try {
    code = await host.waitFor("window.__lulicraft.multiplayer?.code?.length === 5 ? window.__lulicraft.multiplayer.code : ''", 45000);
  } catch (error) {
    const diagnostic = await host.evaluate(`(() => {
      const mp = window.__lulicraft.multiplayer;
      return {
        status: mp?.lastStatus,
        role: mp?.role,
        code: mp?.code,
        modal: document.getElementById('multiplayer-modal')?.innerText?.slice(0, 500),
        toast: document.getElementById('toast')?.textContent
      };
    })()`);
    console.error('host diagnostic', diagnostic);
    console.error('host exceptions', host.exceptions, host.consoleErrors);
    throw error;
  }
  await host.waitFor('window.__lulicraft.running === true && window.__lulicraft.multiplayer?.connected === true', 45000);
  const hostInfo = await host.evaluate('({ seed: window.__lulicraft.world.seed, players: window.__lulicraft.multiplayer.players.size })');
  console.log('host ready', code, hostInfo);

  console.log('joining as guest');
  await guest.front();
  await prepare(guest);
  await guest.send('Page.navigate', { url: `${ORIGIN}/?join=${code}&debug=1` });
  await guest.waitFor(`location.search.includes('join=${code}') && !!window.__lulicraft && !document.getElementById("multiplayer-modal").classList.contains("hidden")`);
  const prefilled = await guest.evaluate('document.getElementById("join-code").value');
  await guest.evaluate('document.getElementById("btn-join-room").click()');
  console.log('waiting for guest world');
  await guest.waitFor('window.__lulicraft.running === true && window.__lulicraft.multiplayer?.connected === true', 45000);
  await wait(1200);
  const guestInfo = await guest.evaluate('({ seed: window.__lulicraft.world.seed, players: window.__lulicraft.multiplayer.players.size, role: window.__lulicraft.multiplayer.role })');
  const hostPlayers = await host.evaluate('window.__lulicraft.multiplayer.players.size');
  const guestPlayers = await guest.evaluate('window.__lulicraft.multiplayer.players.size');
  const hudPlayers = await guest.evaluate('document.getElementById("hud-players").textContent');
  console.log('guest ready', guestInfo, 'players', hostPlayers, guestPlayers, 'hud', hudPlayers);

  const block = await host.evaluate(`(() => {
    const p = window.__lulicraft.player.position;
    const x = Math.floor(p.x) + 2;
    const y = Math.floor(p.y) + 2;
    const z = Math.floor(p.z);
    window.__lulicraft.world.setBlock(x, y, z, 5, true);
    window.__lulicraft.multiplayer.sendBlock(x, y, z, 5);
    return { x, y, z };
  })()`);
  await wait(1500);
  const guestBlock = await guest.evaluate(`window.__lulicraft.world.getBlock(${block.x}, ${block.y}, ${block.z})`);

  console.log('testing safe chat');
  const chat = await host.evaluate(`(() => {
    const mp = window.__lulicraft.multiplayer;
    const first = mp.sendChat('hello');
    const invalid = mp.sendChat('<img src=x onerror=alert(1)>');
    const rate = mp.sendChat('thanks');
    window.__lulicraft.ui.setChatAvailable(true);
    window.__lulicraft.ui.toggleChat(true);
    return {
      first,
      invalid,
      rate,
      feed: document.querySelectorAll('#chat-feed .chat-line').length,
      text: document.querySelector('#chat-feed .chat-line')?.textContent || '',
      phrases: document.querySelectorAll('#chat-phrases .chat-phrase').length
    };
  })()`);
  await wait(1200);
  const guestChat = await guest.evaluate(`(() => ({
    feed: document.querySelectorAll('#chat-feed .chat-line').length,
    text: document.querySelector('#chat-feed .chat-line')?.textContent || '',
    injected: document.querySelector('#chat-feed')?.innerHTML.includes('<img')
  }))()`);

  console.log('testing guest leave');
  await guest.evaluate('window.__lulicraft.multiplayer.leave()');
  await wait(1500);
  const hostAfterGuestLeave = await host.evaluate('window.__lulicraft.multiplayer.players.size');

  console.log('testing guest rejoin');
  await guest.evaluate('document.getElementById("btn-multiplayer").click()');
  await guest.waitFor('!document.getElementById("multiplayer-modal").classList.contains("hidden")');
  await guest.evaluate(`document.getElementById('join-code').value = '${code}'; document.getElementById('btn-join-room').click();`);
  await guest.waitFor('window.__lulicraft.multiplayer?.connected === true && window.__lulicraft.multiplayer.role === "guest"', 45000);
  console.log('guest rejoined');
  await wait(1000);

  console.log('testing host leave');
  await host.evaluate('window.__lulicraft.multiplayer.leave()');
  await wait(2200);
  const guestAfterHostLeave = await guest.evaluate('({ seed: window.__lulicraft.world.seed, role: window.__lulicraft.multiplayer.role, connected: window.__lulicraft.multiplayer.connected, running: window.__lulicraft.running })');

  const shot = await host.send('Page.captureScreenshot', { format: 'png' });
  writeFileSync('/tmp/lulicraft-multiplayer-e2e.png', Buffer.from(shot.data, 'base64'));

  const results = {
    code,
    prefilled,
    hostSeed: hostInfo.seed,
    guestSeed: guestInfo.seed,
    hostPlayers,
    guestPlayers,
    guestRole: guestInfo.role,
    hudPlayers,
    guestBlock,
    chat,
    guestChat,
    hostAfterGuestLeave,
    guestAfterHostLeave,
    exceptions: host.exceptions.length + guest.exceptions.length,
    consoleErrors: host.consoleErrors.length + guest.consoleErrors.length
  };

  const failures = [];
  if (prefilled !== code) failures.push(`invite prefill: ${prefilled}`);
  if (hostInfo.seed !== guestInfo.seed) failures.push('world seed mismatch');
  if (hostPlayers < 2) failures.push(`host players: ${hostPlayers}`);
  if (guestPlayers < 2) failures.push(`guest players: ${guestPlayers}`);
  if (guestInfo.role !== 'guest') failures.push(`guest role: ${guestInfo.role}`);
  if (!hudPlayers.includes('2')) failures.push(`hud players: ${hudPlayers}`);
  if (guestBlock !== 5) failures.push(`guest block: ${guestBlock}`);
  if (chat.first !== 'sent') failures.push(`chat send: ${JSON.stringify(chat)}`);
  if (chat.invalid !== 'invalid') failures.push(`chat invalid allowlist: ${JSON.stringify(chat)}`);
  if (chat.rate !== 'rate') failures.push(`chat rate limit: ${JSON.stringify(chat)}`);
  if (chat.phrases !== 12) failures.push(`chat phrases: ${chat.phrases}`);
  if (chat.feed !== 1 || !chat.text.includes('Oi!')) failures.push(`host chat feed: ${JSON.stringify(chat)}`);
  if (guestChat.feed !== 1 || !guestChat.text.includes('Oi!') || guestChat.injected) failures.push(`guest chat feed: ${JSON.stringify(guestChat)}`);
  if (hostAfterGuestLeave !== 1) failures.push(`host players after leave: ${hostAfterGuestLeave}`);
  if (!guestAfterHostLeave.running) failures.push('guest stopped after host left');
  if (guestAfterHostLeave.role !== null) failures.push(`guest role after host left: ${guestAfterHostLeave.role}`);
  if (guestAfterHostLeave.connected) failures.push('guest still connected after host left');
  if (guestAfterHostLeave.seed !== hostInfo.seed) failures.push('guest seed changed after host left');
  if (results.exceptions > 0) failures.push(`runtime exceptions: ${results.exceptions}`);
  if (results.consoleErrors > 0) failures.push(`console errors: ${results.consoleErrors}`);

  console.log(JSON.stringify(results, null, 2));
  if (failures.length) {
    console.error(`Multiplayer E2E failures:\n- ${failures.join('\n- ')}`);
    process.exitCode = 1;
  } else {
    console.log('Multiplayer E2E passed');
  }
} finally {
  host?.close();
  guest?.close();
  hostChrome?.kill('SIGTERM');
  guestChrome?.kill('SIGTERM');
  server?.kill('SIGTERM');
  await wait(400);
}
