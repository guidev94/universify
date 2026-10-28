import { normalizeCharacter } from './characters.js';
import { sanitizeChat, CHAT_RATE_MS, HOST_CHAT_RATE_MS } from './chat.js';

const PEER_PREFIX = 'lulicraft-v1-';
const CODE_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
const CODE_LENGTH = 5;
const MAX_PLAYERS = 6;
const SEND_INTERVAL = 0.1;
const MAX_COORD = 1000000;
const MAX_PATCH_KEYS = 64;
const MAX_PATCH_ENTRIES = 8192;

function cleanName(value) {
  return String(value ?? '').replace(/[<>]/g, '').trim().slice(0, 16) || 'Explorador';
}

function cleanCode(value) {
  return String(value ?? '').toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, CODE_LENGTH);
}

function makeCode() {
  const bytes = new Uint8Array(CODE_LENGTH);
  if (globalThis.crypto?.getRandomValues) {
    globalThis.crypto.getRandomValues(bytes);
  } else {
    for (let i = 0; i < bytes.length; i++) bytes[i] = Math.floor(Math.random() * 256);
  }
  let code = '';
  for (const byte of bytes) code += CODE_ALPHABET[byte % CODE_ALPHABET.length];
  return code;
}

function finite(value, fallback = 0, min = -MAX_COORD, max = MAX_COORD) {
  const number = Number(value);
  if (!Number.isFinite(number)) return fallback;
  return Math.max(min, Math.min(max, number));
}

function sanitizePlayer(value, fallbackId = '') {
  if (!value || typeof value !== 'object') return null;
  return {
    id: String(value.id || fallbackId || '').slice(0, 80),
    name: cleanName(value.name),
    character: normalizeCharacter(value.character),
    x: finite(value.x),
    y: finite(value.y, 24, -8, 80),
    z: finite(value.z),
    yaw: finite(value.yaw, 0, -Math.PI * 4, Math.PI * 4),
    pitch: finite(value.pitch, 0, -Math.PI / 2, Math.PI / 2)
  };
}

function sanitizeBlock(message) {
  if (!message || typeof message !== 'object') return null;
  const x = Math.floor(finite(message.x, NaN));
  const y = Math.floor(finite(message.y, NaN, 0, 47));
  const z = Math.floor(finite(message.z, NaN));
  const id = Math.floor(finite(message.id, NaN, 0, 255));
  if (![x, y, z, id].every(Number.isFinite)) return null;
  return { x, y, z, id };
}

function sanitizeItems(items) {
  if (items == null) return null;
  if (!Array.isArray(items)) return [];
  return items.slice(0, 27).map((slot) => {
    if (!slot || typeof slot !== 'object') return null;
    const id = typeof slot.id === 'string' ? slot.id.slice(0, 64) : Math.floor(finite(slot.id, -1, 0, 4096));
    const count = Math.floor(finite(slot.count, 0, 0, 999));
    if (id === -1 || count <= 0) return null;
    return { id, count };
  });
}

function sanitizeChest(message) {
  if (!message || typeof message !== 'object') return null;
  const x = Math.floor(finite(message.x, NaN));
  const y = Math.floor(finite(message.y, NaN, 0, 47));
  const z = Math.floor(finite(message.z, NaN));
  if (![x, y, z].every(Number.isFinite)) return null;
  return { x, y, z, items: sanitizeItems(message.items) };
}

function sanitizePatch(patch) {
  if (!patch || typeof patch !== 'object') return null;
  const clean = {};
  let entries = 0;
  for (const [key, diff] of Object.entries(patch).slice(0, MAX_PATCH_KEYS)) {
    if (!/^-?\d+,-?\d+$/.test(key) || !diff || typeof diff !== 'object') continue;
    const next = {};
    for (const [index, id] of Object.entries(diff).slice(0, MAX_PATCH_ENTRIES)) {
      const idx = Math.floor(finite(index, NaN, 0, 16 * 16 * 48 - 1));
      const blockId = Math.floor(finite(id, NaN, 0, 255));
      if (Number.isFinite(idx) && Number.isFinite(blockId)) {
        next[idx] = blockId;
        entries += 1;
      }
      if (entries >= MAX_PATCH_ENTRIES) break;
    }
    if (Object.keys(next).length) clean[key] = next;
    if (entries >= MAX_PATCH_ENTRIES) break;
  }
  return Object.keys(clean).length ? clean : null;
}

function waitForOpen(peer, timeout = 12000) {
  return new Promise((resolve, reject) => {
    const timer = setTimeout(() => {
      cleanup();
      reject(new Error('Tempo esgotado ao conectar.'));
    }, timeout);
    const onOpen = () => {
      cleanup();
      resolve();
    };
    const onError = (error) => {
      cleanup();
      reject(error);
    };
    const cleanup = () => {
      clearTimeout(timer);
      peer.off?.('open', onOpen);
      peer.off?.('error', onError);
    };
    peer.once('open', onOpen);
    peer.once('error', onError);
  });
}

function waitForConnection(conn, timeout = 12000) {
  return new Promise((resolve, reject) => {
    const timer = setTimeout(() => {
      cleanup();
      reject(new Error('O anfitrião não respondeu.'));
    }, timeout);
    const onOpen = () => {
      cleanup();
      resolve();
    };
    const onError = (error) => {
      cleanup();
      reject(error);
    };
    const cleanup = () => {
      clearTimeout(timer);
      conn.off?.('open', onOpen);
      conn.off?.('error', onError);
    };
    conn.once('open', onOpen);
    conn.once('error', onError);
  });
}

export class Multiplayer {
  constructor(callbacks = {}) {
    this.callbacks = callbacks;
    this.peer = null;
    this.role = null;
    this.code = '';
    this.invite = '';
    this.hostId = '';
    this.selfId = '';
    this.hostConn = null;
    this.connections = new Map();
    this.players = new Map();
    this.profile = { character: normalizeCharacter(null), name: 'Explorador' };
    this.accumulator = 0;
    this.lastStatus = 'idle';
    this.destroyed = false;
    this.lastChatAt = 0;
    this.hostChatTimes = new Map();
  }

  get connected() {
    return this.role === 'host' ? !!this.peer && !this.peer.destroyed : !!this.hostConn && this.hostConn.open;
  }

  get isHost() {
    return this.role === 'host';
  }

  get isGuest() {
    return this.role === 'guest';
  }

  setStatus(status, detail = {}) {
    this.lastStatus = status;
    this.callbacks.onStatus?.(status, detail);
  }

  getPeerConstructor() {
    const Peer = globalThis.Peer;
    if (!Peer) throw new Error('Multiplayer indisponível neste navegador.');
    return Peer;
  }

  attach(world, profile = {}) {
    this.world = world || null;
    if (profile.character || profile.name) {
      this.profile = {
        character: normalizeCharacter(profile.character || this.profile.character),
        name: cleanName(profile.name || this.profile.name)
      };
    }
    if (this.role === 'host' && this.selfId) {
      const current = this.players.get(this.selfId);
      if (!current) {
        this.players.set(this.selfId, {
          id: this.selfId,
          name: this.profile.name,
          character: this.profile.character,
          x: 0,
          y: 24,
          z: 0,
          yaw: 0,
          pitch: 0
        });
        this.emitPlayers();
      }
    }
  }

  async host({ getSnapshot, character, name } = {}) {
    const Peer = this.getPeerConstructor();
    this.leave({ silent: true });
    this.role = 'host';
    this.profile = { character: normalizeCharacter(character), name: cleanName(name) };
    this.getSnapshot = getSnapshot;
    this.setStatus('hosting', { message: 'Criando uma sala segura…' });

    let lastError = null;
    for (let attempt = 0; attempt < 3; attempt++) {
      const code = makeCode();
      let peer = null;
      try {
        peer = new Peer(`${PEER_PREFIX}${code}`, { debug: 0 });
        await waitForOpen(peer);
        this.peer = peer;
        this.code = code;
        this.selfId = peer.id;
        this.hostId = peer.id;
        this.invite = this.makeInvite(code);
        peer.on('connection', (conn) => this.acceptConnection(conn));
        peer.on('error', (error) => this.handlePeerError(error));
        peer.on('disconnected', () => {
          if (!this.destroyed) this.setStatus('reconnecting', { message: 'Reconectando a sala…' });
          peer.reconnect?.();
        });
        this.setStatus('hosting', { message: 'Sala pronta. Compartilhe o convite!', code, invite: this.invite });
        this.emitPlayers();
        return { ok: true, code, invite: this.invite };
      } catch (error) {
        lastError = error;
        try {
          peer?.destroy();
        } catch {
          // ignore
        }
        if (error?.type !== 'unavailable-id') break;
      }
    }
    this.role = null;
    this.peer = null;
    this.code = '';
    this.invite = '';
    this.hostId = '';
    this.selfId = '';
    this.setStatus('error', { message: lastError?.message || 'Não foi possível criar a sala.' });
    throw lastError || new Error('Não foi possível criar a sala.');
  }

  async join({ code, character, name } = {}) {
    const Peer = this.getPeerConstructor();
    const roomCode = cleanCode(code);
    if (roomCode.length !== CODE_LENGTH) throw new Error('Código de convite inválido.');
    this.leave({ silent: true });
    this.role = 'guest';
    this.profile = { character: normalizeCharacter(character), name: cleanName(name) };
    this.setStatus('joining', { message: 'Entrando na sala…' });

    let peer = null;
    try {
      peer = new Peer(undefined, { debug: 0 });
      await waitForOpen(peer);
      this.peer = peer;
      this.selfId = peer.id;
      this.hostId = `${PEER_PREFIX}${roomCode}`;
      this.code = roomCode;
      this.invite = this.makeInvite(roomCode);
      peer.on('error', (error) => this.handlePeerError(error));
      peer.on('disconnected', () => {
        if (!this.destroyed) this.setStatus('reconnecting', { message: 'Reconectando…' });
        peer.reconnect?.();
      });

      const conn = peer.connect(this.hostId, { reliable: true, serialization: 'json' });
      await waitForConnection(conn);
      this.hostConn = conn;
      this.bindConnection(conn);
      this.setStatus('joining', { message: 'Conectado. Preparando o mundo…', code: roomCode, invite: this.invite });
      return { ok: true, code: roomCode, invite: this.invite };
    } catch (error) {
      try {
        peer?.destroy();
      } catch {
        // ignore
      }
      this.role = null;
      this.peer = null;
      this.hostConn = null;
      this.code = '';
      this.invite = '';
      this.hostId = '';
      this.selfId = '';
      const message = error?.type === 'peer-unavailable'
        ? 'Sala não encontrada. Confira o código.'
        : error?.message || 'Não foi possível entrar na sala.';
      this.setStatus('error', { message });
      throw new Error(message);
    }
  }

  makeInvite(code) {
    const base = `${location.origin}${location.pathname}`;
    return `${base}?join=${encodeURIComponent(code)}`;
  }

  acceptConnection(conn) {
    if (this.connections.size >= MAX_PLAYERS - 1) {
      conn.close();
      return;
    }
    conn.on('open', () => {
      this.connections.set(conn.peer, conn);
      this.bindConnection(conn);
      const snapshot = this.getSnapshot?.();
      if (!snapshot) {
        conn.close();
        this.connections.delete(conn.peer);
        return;
      }
      conn.send({
        type: 'welcome',
        code: this.code,
        hostId: this.hostId,
        snapshot,
        players: this.playerList()
      });
      this.emitPlayers();
    });
    conn.on('close', () => this.removePlayer(conn.peer));
    conn.on('error', () => this.removePlayer(conn.peer));
  }

  bindConnection(conn) {
    conn.on('data', (message) => this.handleMessage(conn, message));
    conn.on('close', () => {
      this.removePlayer(conn.peer);
      if (this.isGuest) this.handleHostLost();
    });
    conn.on('error', () => {
      this.removePlayer(conn.peer);
      if (this.isGuest) this.handleHostLost();
    });
  }

  handleHostLost() {
    if (this.destroyed || !this.isGuest) return;
    this.destroyed = true;
    try {
      this.peer?.destroy();
    } catch {
      // ignore
    }
    this.peer = null;
    this.hostConn = null;
    this.connections.clear();
    this.players.clear();
    this.hostChatTimes.clear();
    this.lastChatAt = 0;
    this.role = null;
    this.code = '';
    this.invite = '';
    this.hostId = '';
    this.selfId = '';
    this.destroyed = false;
    this.setStatus('left', { message: 'O anfitrião saiu da sala.' });
    this.callbacks.onHostLeft?.();
  }

  handleMessage(conn, message) {
    if (!message || typeof message !== 'object' || typeof message.type !== 'string') return;
    if (this.isHost) this.handleHostMessage(conn, message);
    else this.handleGuestMessage(message);
  }

  handleHostMessage(conn, message) {
    if (message.type === 'hello') {
      const player = sanitizePlayer(message.player, conn.peer);
      if (!player) return;
      this.players.set(conn.peer, player);
      this.broadcast({ type: 'join', player }, conn.peer);
      this.emitPlayers();
      return;
    }
    if (message.type === 'player') {
      const player = sanitizePlayer(message.player, conn.peer);
      if (!player) return;
      this.players.set(conn.peer, player);
      this.broadcast({ type: 'player', player }, conn.peer);
      return;
    }
    if (message.type === 'block') {
      const block = sanitizeBlock(message);
      if (!block) return;
      this.callbacks.onBlock?.(block);
      this.broadcast(block, conn.peer);
      return;
    }
    if (message.type === 'chest') {
      const chest = sanitizeChest(message);
      if (!chest) return;
      this.callbacks.onChest?.(chest);
      this.broadcast(chest, conn.peer);
      return;
    }
    if (message.type === 'patch') {
      const patch = sanitizePatch(message.patch);
      if (!patch) return;
      this.callbacks.onPatch?.(patch);
      this.broadcast({ type: 'patch', patch }, conn.peer);
      return;
    }
    if (message.type === 'chat') {
      const id = sanitizeChat(message.id);
      if (!id) return;
      const now = Date.now();
      const last = this.hostChatTimes.get(conn.peer) || 0;
      if (now - last < HOST_CHAT_RATE_MS) return;
      this.hostChatTimes.set(conn.peer, now);
      const player = this.players.get(conn.peer);
      const payload = {
        type: 'chat',
        id,
        playerId: conn.peer,
        name: cleanName(player?.name || 'Explorador'),
        at: now
      };
      this.callbacks.onChat?.({ ...payload, self: false });
      this.broadcast(payload);
      return;
    }
    if (message.type === 'leave') this.removePlayer(conn.peer);
  }

  handleGuestMessage(message) {
    if (message.type === 'welcome') {
      this.players.clear();
      for (const player of Array.isArray(message.players) ? message.players : []) {
        const clean = sanitizePlayer(player);
        if (clean) this.players.set(clean.id, clean);
      }
      const snapshot = message.snapshot || {};
      const spawn = snapshot.player || {};
      this.selfPlayer = {
        id: this.selfId,
        name: this.profile.name,
        character: this.profile.character,
        x: finite(spawn.x, 0) + 1.5,
        y: finite(spawn.y, 24, -8, 80),
        z: finite(spawn.z, 0) + 1.5,
        yaw: finite(spawn.yaw, 0),
        pitch: 0
      };
      this.players.set(this.selfId, this.selfPlayer);
      this.hostConn?.send({ type: 'hello', player: this.selfPlayer });
      this.setStatus('connected', { message: 'Você entrou na sala!', code: this.code, invite: this.invite });
      this.callbacks.onWelcome?.({
        code: this.code,
        hostId: message.hostId || this.hostId,
        snapshot,
        players: this.playerList()
      });
      this.emitPlayers();
      return;
    }
    if (message.type === 'join') {
      const player = sanitizePlayer(message.player);
      if (player) this.players.set(player.id, player);
      this.emitPlayers();
      return;
    }
    if (message.type === 'player') {
      const player = sanitizePlayer(message.player);
      if (player) this.players.set(player.id, player);
      return;
    }
    if (message.type === 'block') {
      const block = sanitizeBlock(message);
      if (block) this.callbacks.onBlock?.(block);
      return;
    }
    if (message.type === 'chest') {
      const chest = sanitizeChest(message);
      if (chest) this.callbacks.onChest?.(chest);
      return;
    }
    if (message.type === 'patch') {
      const patch = sanitizePatch(message.patch);
      if (patch) this.callbacks.onPatch?.(patch);
      return;
    }
    if (message.type === 'chat') {
      const id = sanitizeChat(message.id);
      if (!id) return;
      this.callbacks.onChat?.({
        id,
        playerId: String(message.playerId || '').slice(0, 80),
        name: cleanName(message.name),
        at: Number.isFinite(message.at) ? message.at : Date.now(),
        self: String(message.playerId || '') === this.selfId
      });
      return;
    }
    if (message.type === 'left') {
      this.players.delete(message.id);
      this.emitPlayers();
    }
  }

  removePlayer(id) {
    if (!id) return;
    this.connections.delete(id);
    this.hostChatTimes.delete(id);
    const existed = this.players.delete(id);
    if (existed) {
      this.broadcast({ type: 'left', id });
      this.emitPlayers();
      this.callbacks.onLeavePlayer?.(id);
    }
  }

  broadcast(message, exceptId = '') {
    for (const [id, conn] of this.connections) {
      if (id === exceptId) continue;
      try {
        conn.send(message);
      } catch {
        this.removePlayer(id);
      }
    }
  }

  send(message) {
    if (!this.connected) return false;
    if (this.isHost) {
      this.broadcast(message);
      return true;
    }
    try {
      this.hostConn.send(message);
      return true;
    } catch {
      return false;
    }
  }

  sendBlock(x, y, z, id) {
    return this.send({ type: 'block', x, y, z, id });
  }

  sendChest(x, y, z, items) {
    return this.send({ type: 'chest', x, y, z, items: sanitizeItems(items) });
  }

  sendPatch(patch) {
    const clean = sanitizePatch(patch);
    return clean ? this.send({ type: 'patch', patch: clean }) : false;
  }

  sendChat(id) {
    if (!this.connected) return 'offline';
    const clean = sanitizeChat(id);
    if (!clean) return 'invalid';
    const now = Date.now();
    if (now - this.lastChatAt < CHAT_RATE_MS) return 'rate';
    this.lastChatAt = now;
    if (this.isHost) {
      const player = this.players.get(this.selfId);
      const payload = {
        type: 'chat',
        id: clean,
        playerId: this.selfId,
        name: cleanName(player?.name || this.profile.name),
        at: now
      };
      this.broadcast(payload);
      this.callbacks.onChat?.({ ...payload, self: true });
      return 'sent';
    }
    return this.send({ type: 'chat', id: clean }) ? 'sent' : 'offline';
  }

  update(dt, player) {
    if (!this.connected || !player) return;
    this.accumulator += dt;
    if (this.accumulator < SEND_INTERVAL) return;
    this.accumulator = 0;
    const state = {
      id: this.selfId,
      name: this.profile.name,
      character: this.profile.character,
      x: finite(player.position?.x, 0),
      y: finite(player.position?.y, 24, -8, 80),
      z: finite(player.position?.z, 0),
      yaw: finite(player.yaw, 0),
      pitch: finite(player.pitch, 0, -Math.PI / 2, Math.PI / 2)
    };
    this.selfPlayer = state;
    this.players.set(this.selfId, state);
    this.send({ type: 'player', player: state });
  }

  playerList() {
    return Array.from(this.players.values());
  }

  emitPlayers() {
    this.callbacks.onPlayers?.(this.playerList());
  }

  handlePeerError(error) {
    if (this.destroyed) return;
    if (error?.type === 'peer-unavailable') {
      this.setStatus('error', { message: 'Sala não encontrada. Confira o código.' });
      this.callbacks.onError?.('Sala não encontrada. Confira o código.');
      return;
    }
    if (error?.type === 'network' || error?.type === 'server-error') {
      this.setStatus('error', { message: 'Multiplayer indisponível. Verifique sua conexão.' });
      this.callbacks.onError?.('Multiplayer indisponível. Verifique sua conexão.');
      return;
    }
    this.setStatus('error', { message: error?.message || 'Erro de conexão.' });
    this.callbacks.onError?.(error?.message || 'Erro de conexão.');
  }

  leave({ silent = false } = {}) {
    this.destroyed = true;
    if (!silent && this.selfId) this.send({ type: 'leave', id: this.selfId });
    for (const conn of this.connections.values()) {
      try {
        conn.close();
      } catch {
        // ignore
      }
    }
    try {
      this.hostConn?.close();
    } catch {
      // ignore
    }
    try {
      this.peer?.destroy();
    } catch {
      // ignore
    }
    this.peer = null;
    this.hostConn = null;
    this.connections.clear();
    this.players.clear();
    this.hostChatTimes.clear();
    this.lastChatAt = 0;
    this.role = null;
    this.code = '';
    this.invite = '';
    this.hostId = '';
    this.selfId = '';
    this.accumulator = 0;
    this.destroyed = false;
    if (!silent) this.setStatus('left', { message: 'Você saiu da sala.' });
  }
}

export { cleanCode as normalizeRoomCode, makeCode as makeRoomCode };
