import { getItem, getItemIcon, getRarity, RARITY, COLLECTION_IDS, CREATIVE_PALETTE } from './items.js';
import { RECIPES } from './recipes.js';
import { addToArrays, quickMove } from './inventory.js';
import { thumbnailFor, encodeWorldCode, decodeWorldCode, getDaily } from './save.js';
import {
  CHARACTER_PRESETS,
  COLOR_SWATCHES,
  WEAPON_SKINS,
  DEFAULT_CHARACTER,
  characterFromPreset,
  normalizeCharacter
} from './characters.js';
import { PREBUILT_WORLDS } from './prebuilt.js';

const $ = (id) => document.getElementById(id);

const TUTORIAL_STEPS = [
  {
    art: '✦',
    title: 'Mova-se pelo mundo',
    text: 'Use WASD para andar, o mouse para olhar e Espaço para pular. No celular, use o joystick e arraste a tela para olhar.'
  },
  {
    art: '◆',
    title: 'Quebre e coloque blocos',
    text: 'Clique com o botão esquerdo para quebrar. Clique com o direito para colocar o bloco ou usar a arma mágica selecionada.'
  },
  {
    art: '▦',
    title: 'Sua mochila tem abas',
    text: 'Aperte E para abrir a mochila. Lá ficam a bolsa de sementes, a Forja de receitas e o Álbum de coleção.'
  },
  {
    art: '★',
    title: 'Descubra itens raros',
    text: 'Minérios brilhantes, baús de santuário e árvores arco-íris escondem itens raros. Cada descoberta entra no seu Álbum.'
  }
];

const SEED_WORDS_A = ['lumi', 'aurora', 'coral', 'musgo', 'nuvem', 'prisma', 'mel', 'estrela', 'jardim', 'orvalho'];
const SEED_WORDS_B = ['feliz', 'doce', 'brilhante', 'sereno', 'magico', 'tranquilo', 'colorido', 'secreto'];

function el(tag, className = '', text = '') {
  const node = document.createElement(tag);
  if (className) node.className = className;
  if (text) node.textContent = text;
  return node;
}

function formatPlaytime(ms) {
  const minutes = Math.floor((Number(ms) || 0) / 60000);
  if (minutes < 60) return `${minutes} min`;
  const hours = Math.floor(minutes / 60);
  const rest = minutes % 60;
  return `${hours} h ${rest} min`;
}

export class GameUI {
  constructor(actions = {}) {
    this.actions = actions;
    this.state = {
      inventory: null,
      mode: 'adventure',
      world: null,
      worldName: 'Mundo',
      seed: '',
      playerName: ''
    };
    this.settings = { sound: true, tutorialDone: false, sensitivity: 1, showCoords: false, character: DEFAULT_CHARACTER };
    this.character = normalizeCharacter(DEFAULT_CHARACTER);
    this.pendingCharacter = null;
    this.settingsFromPause = false;
    this.currentTab = 'backpack';
    this.held = null;
    this.dragSource = null;
    this.thirdPerson = false;
    this.chest = null;
    this.inventoryOpen = false;
    this.chestOpen = false;
    this.pauseOpen = false;
    this.modalOpen = false;
    this.tutorialStep = 0;
    this.tutorialForce = false;
    this.createMode = 'adventure';
    this.multiplayerFromPause = false;
    this.multiplayerState = { status: 'idle', message: 'Crie uma sala ou entre com um convite.', code: '', invite: '', players: [], selfId: '' };
    this.lastPhotoInfo = null;
    this.toastTimers = new Set();
    this.bind();
  }

  bind() {
    $('btn-quick-play')?.addEventListener('click', () => this.actions.onQuickPlay?.());
    $('btn-create')?.addEventListener('click', () => this.openCreate());
    $('btn-create-empty')?.addEventListener('click', () => this.openCreate());
    $('btn-daily')?.addEventListener('click', () => this.actions.onDaily?.());
    $('btn-import')?.addEventListener('click', () => $('import-file')?.click());
    $('import-file')?.addEventListener('change', (event) => {
      const file = event.target.files?.[0];
      event.target.value = '';
      if (file) this.actions.onImportFile?.(file);
    });
    $('btn-code')?.addEventListener('click', () => this.openCode('', true));
    $('btn-settings')?.addEventListener('click', () => this.openSettings());
    $('btn-character')?.addEventListener('click', () => this.openCharacter());
    $('btn-multiplayer')?.addEventListener('click', () => this.openMultiplayer());
    $('btn-invite')?.addEventListener('click', () => this.openMultiplayer());
    $('btn-tutorial-menu')?.addEventListener('click', () => this.openTutorial(true));

    document.querySelectorAll('[data-close]').forEach((button) => {
      button.addEventListener('click', () => {
        const target = button.dataset.close;
        if (target === 'inventory') this.closeInventory();
        else if (target === 'chest') this.closeChest();
        else if (target === 'pause') this.closePause();
        else if (target === 'create') this.closeCreate();
        else if (target === 'tutorial') this.closeTutorial();
        else if (target === 'settings') this.closeSettings();
        else if (target === 'character') this.closeCharacter();
        else if (target === 'multiplayer') this.closeMultiplayer();
        else if (target === 'code') this.closeCode();
      });
    });

    $('btn-create-cancel')?.addEventListener('click', () => this.closeCreate());
    $('btn-create-confirm')?.addEventListener('click', () => {
      const data = {
        name: $('world-name-input')?.value || '',
        seed: $('world-seed-input')?.value || '',
        mode: this.createMode,
        rareKit: !!$('world-rare-kit')?.checked
      };
      this.actions.onCreateWorld?.(data);
    });
    $('btn-random-seed')?.addEventListener('click', () => {
      const seed = `${SEED_WORDS_A[Math.floor(Math.random() * SEED_WORDS_A.length)]}-${SEED_WORDS_B[Math.floor(Math.random() * SEED_WORDS_B.length)]}-${Math.floor(Math.random() * 900 + 100)}`;
      if ($('world-seed-input')) $('world-seed-input').value = seed;
    });
    document.querySelectorAll('.mode-card').forEach((card) => {
      card.addEventListener('click', () => {
        this.createMode = card.dataset.mode === 'creative' ? 'creative' : 'adventure';
        document.querySelectorAll('.mode-card').forEach((other) => other.classList.toggle('active', other === card));
      });
    });

    $('inventory-tabs')?.addEventListener('click', (event) => {
      const tab = event.target.closest('[data-tab]');
      if (!tab) return;
      this.currentTab = tab.dataset.tab;
      document.querySelectorAll('#inventory-tabs .tab').forEach((button) => button.classList.toggle('active', button === tab));
      this.renderInventory();
    });

    $('inventory-body')?.addEventListener('pointerdown', (event) => this.handleSlotDown(event));
    $('inventory-body')?.addEventListener('contextmenu', (event) => this.handleSlotContext(event));
    $('chest-modal')?.addEventListener('pointerdown', (event) => this.handleSlotDown(event));
    $('chest-modal')?.addEventListener('contextmenu', (event) => this.handleSlotContext(event));
    document.addEventListener('pointermove', (event) => this.updateGhost(event.clientX, event.clientY));
    document.addEventListener('pointerup', (event) => this.handlePointerUp(event));
    document.addEventListener('pointercancel', () => this.hideGhost());

    $('hotbar')?.addEventListener('click', (event) => {
      const slot = event.target.closest('.hotbar-slot');
      if (slot) this.actions.onSelectHotbar?.(Number(slot.dataset.index));
    });

    $('btn-inventory')?.addEventListener('click', () => this.openInventory('backpack'));
    $('btn-creative-palette')?.addEventListener('click', () => this.openCreativePalette());

    $('btn-pause')?.addEventListener('click', () => this.actions.onPause?.());
    $('btn-resume')?.addEventListener('click', () => this.closePause());
    $('btn-save')?.addEventListener('click', () => this.actions.onSave?.());
    $('btn-pause-photo')?.addEventListener('click', () => this.actions.onPhoto?.());
    $('btn-export')?.addEventListener('click', () => this.actions.onExport?.());
    $('btn-world-code')?.addEventListener('click', () => this.actions.onWorldCode?.());
    $('btn-pause-settings')?.addEventListener('click', () => this.openSettings());
    $('btn-quit')?.addEventListener('click', () => this.actions.onQuit?.());

    $('btn-sound')?.addEventListener('click', () => this.actions.onSoundToggle?.());
    $('btn-fullscreen-menu')?.addEventListener('click', () => this.actions.onFullscreen?.());
    $('btn-fullscreen')?.addEventListener('click', () => this.actions.onFullscreen?.());
    $('btn-pause-fullscreen')?.addEventListener('click', () => this.actions.onFullscreen?.());
    $('btn-third-person')?.addEventListener('click', () => this.actions.onThirdPerson?.());
    $('btn-photo')?.addEventListener('click', () => this.actions.onPhoto?.());
    $('btn-photo-exit')?.addEventListener('click', () => this.closePhoto());
    $('btn-photo-download')?.addEventListener('click', () => this.actions.onPhotoDownload?.());
    $('btn-card-download')?.addEventListener('click', () => this.actions.onCardDownload?.());

    $('btn-settings-done')?.addEventListener('click', () => this.closeSettings());
    $('btn-multiplayer-close')?.addEventListener('click', () => this.closeMultiplayer());
    $('btn-host-room')?.addEventListener('click', () => this.actions.onHostRoom?.());
    $('btn-join-room')?.addEventListener('click', () => this.actions.onJoinRoom?.($('join-code')?.value || ''));
    $('join-code')?.addEventListener('keydown', (event) => {
      if (event.key === 'Enter') this.actions.onJoinRoom?.($('join-code')?.value || '');
    });
    $('btn-copy-invite')?.addEventListener('click', () => this.copyText(this.multiplayerState.invite, 'Convite copiado!'));
    $('btn-leave-room')?.addEventListener('click', () => this.actions.onLeaveRoom?.());
    $('btn-character-cancel')?.addEventListener('click', () => this.closeCharacter());
    $('btn-character-save')?.addEventListener('click', () => this.saveCharacter());
    $('character-name')?.addEventListener('input', (event) => {
      if (!this.pendingCharacter) return;
      this.pendingCharacter.name = String(event.target.value || '').slice(0, 16);
      this.renderCharacterPreview();
    });
    $('character-presets')?.addEventListener('click', (event) => {
      const card = event.target.closest('[data-character-preset]');
      if (!card || !this.pendingCharacter) return;
      const name = this.pendingCharacter.name;
      const weaponSkin = this.pendingCharacter.weaponSkin;
      this.pendingCharacter = characterFromPreset(card.dataset.characterPreset);
      this.pendingCharacter.name = name || this.pendingCharacter.name;
      this.pendingCharacter.weaponSkin = weaponSkin || this.pendingCharacter.weaponSkin;
      this.renderCharacterEditor();
    });
    $('character-colors')?.addEventListener('click', (event) => {
      const swatch = event.target.closest('[data-character-part]');
      if (!swatch || !this.pendingCharacter) return;
      const part = swatch.dataset.characterPart;
      if (!['skin', 'shirt', 'pants', 'hair'].includes(part)) return;
      this.pendingCharacter[part] = swatch.dataset.color;
      this.pendingCharacter.custom = true;
      this.pendingCharacter.preset = 'custom';
      this.renderCharacterEditor();
    });
    $('weapon-skins')?.addEventListener('click', (event) => {
      const skin = event.target.closest('[data-weapon-skin]');
      if (!skin || !this.pendingCharacter) return;
      this.pendingCharacter.weaponSkin = skin.dataset.weaponSkin;
      this.renderCharacterEditor();
    });
    $('setting-sound')?.addEventListener('change', (event) => this.actions.onSoundToggle?.(event.target.checked));
    $('setting-sens')?.addEventListener('input', (event) => {
      const value = Number(event.target.value) || 1;
      if ($('sens-value')) $('sens-value').textContent = `${value.toFixed(1)}×`;
      this.actions.onSensitivity?.(value);
    });
    $('setting-coords')?.addEventListener('change', (event) => this.actions.onCoordsToggle?.(event.target.checked));
    $('btn-replay-tutorial')?.addEventListener('click', () => {
      this.closeSettings();
      this.openTutorial(true);
    });

    $('btn-tutorial-next')?.addEventListener('click', () => this.nextTutorial());
    $('btn-tutorial-skip')?.addEventListener('click', () => this.closeTutorial());

    $('btn-copy-code')?.addEventListener('click', () => this.copyCode());
    $('btn-code-import-toggle')?.addEventListener('click', () => {
      $('code-import-box')?.classList.toggle('hidden');
      $('world-code-input')?.focus();
    });
    $('btn-code-import')?.addEventListener('click', () => {
      const code = $('world-code-input')?.value || '';
      this.actions.onImportCode?.(code);
    });
  }

  setActions(actions) {
    this.actions = { ...this.actions, ...actions };
  }

  setState(patch) {
    this.state = { ...this.state, ...patch };
  }

  applySettings(settings) {
    this.settings = { ...this.settings, ...settings };
    if ($('setting-sound')) $('setting-sound').checked = !!this.settings.sound;
    if ($('setting-sens')) $('setting-sens').value = String(this.settings.sensitivity || 1);
    if ($('sens-value')) $('sens-value').textContent = `${Number(this.settings.sensitivity || 1).toFixed(1)}×`;
    if ($('setting-coords')) $('setting-coords').checked = !!this.settings.showCoords;
    if (this.settings.character) this.character = normalizeCharacter(this.settings.character);
    if ($('btn-character')) $('btn-character').title = `Personagem: ${this.character.name}`;
    this.setSound(this.settings.sound);
  }

  setSound(enabled) {
    const button = $('btn-sound');
    if (button) button.classList.toggle('muted', !enabled);
  }

  renderMenu(worlds, daily) {
    const grid = $('worlds-grid');
    if (!grid) return;
    grid.textContent = '';
    const dailyInfo = daily || getDaily();
    const dailyCard = this.buildDailyCard(dailyInfo);
    grid.appendChild(dailyCard);

    for (const world of worlds) {
      grid.appendChild(this.buildWorldCard(world));
    }

    const empty = $('worlds-empty');
    if (empty) empty.classList.toggle('hidden', worlds.length > 0);
    if ($('btn-daily')) $('btn-daily').title = `Seed de hoje: ${dailyInfo.seed}`;
    this.renderPrebuiltWorlds();
  }

  renderPrebuiltWorlds() {
    const grid = $('prebuilt-grid');
    if (!grid) return;
    grid.textContent = '';
    for (const entry of PREBUILT_WORLDS) {
      const card = el('article', 'prebuilt-card');
      card.style.setProperty('--accent', entry.accent);
      const art = el('div', 'prebuilt-art');
      art.appendChild(el('span', 'prebuilt-emoji', entry.emoji));
      const body = el('div', 'prebuilt-body');
      body.appendChild(el('h4', '', entry.name));
      body.appendChild(el('p', '', entry.desc));
      const foot = el('div', 'prebuilt-foot');
      foot.appendChild(el('span', 'world-stat', entry.mode === 'creative' ? 'Criativo' : 'Aventura Cozy'));
      if (entry.rareKit) foot.appendChild(el('span', 'world-stat rare-kit-chip', 'Kit de Raros'));
      const play = el('button', 'primary-btn', 'Jogar');
      play.type = 'button';
      play.addEventListener('click', () => this.actions.onPrebuiltWorld?.(entry));
      foot.appendChild(play);
      body.appendChild(foot);
      card.appendChild(art);
      card.appendChild(body);
      grid.appendChild(card);
    }
  }

  buildDailyCard(daily) {
    const card = el('article', 'world-card daily-card');
    const thumb = el('div', 'world-thumb');
    const palette = thumbnailFor(daily.seed);
    thumb.style.setProperty('--c1', palette.c1);
    thumb.style.setProperty('--c2', palette.c2);
    thumb.style.setProperty('--c3', palette.c3);
    const badge = el('span', 'world-thumb-badge', 'Mundo do Dia');
    thumb.appendChild(badge);
    const body = el('div', 'world-card-body');
    const title = el('div', 'world-card-title');
    const titleWrap = el('div');
    titleWrap.appendChild(el('h4', '', 'Mundo do Dia'));
    titleWrap.appendChild(el('small', '', daily.seed));
    title.appendChild(titleWrap);
    body.appendChild(title);
    const stats = el('div', 'world-card-stats');
    stats.appendChild(el('span', 'world-stat', 'Aventura Cozy'));
    stats.appendChild(el('span', 'world-stat', 'Mesmo mundo para todos'));
    body.appendChild(stats);
    const actions = el('div', 'world-card-actions');
    const play = el('button', 'primary-btn', 'Jogar o mundo de hoje');
    play.type = 'button';
    play.addEventListener('click', () => this.actions.onDaily?.());
    actions.appendChild(play);
    body.appendChild(actions);
    card.appendChild(thumb);
    card.appendChild(body);
    return card;
  }

  buildWorldCard(world) {
    const card = el('article', 'world-card');
    const thumb = el('div', 'world-thumb');
    const palette = world.thumb || thumbnailFor(world.seed);
    thumb.style.setProperty('--c1', palette.c1);
    thumb.style.setProperty('--c2', palette.c2);
    thumb.style.setProperty('--c3', palette.c3);
    const badge = el('span', 'world-thumb-badge', world.mode === 'creative' ? 'Criativo' : 'Aventura Cozy');
    thumb.appendChild(badge);
    const body = el('div', 'world-card-body');
    const title = el('div', 'world-card-title');
    const titleWrap = el('div');
    titleWrap.appendChild(el('h4', '', world.name || 'Mundo'));
    titleWrap.appendChild(el('small', '', `seed ${world.seed || 'lumi'}`));
    title.appendChild(titleWrap);
    body.appendChild(title);
    const stats = el('div', 'world-card-stats');
    stats.appendChild(el('span', 'world-stat', `${formatPlaytime(world.playtime)} jogados`));
    stats.appendChild(el('span', 'world-stat', `${world.rare || 0} raros`));
    body.appendChild(stats);
    const actions = el('div', 'world-card-actions');
    const play = el('button', 'primary-btn', 'Jogar');
    play.type = 'button';
    play.addEventListener('click', () => this.actions.onPlayWorld?.(world.id));
    const exportBtn = el('button', 'icon-action', '↓');
    exportBtn.type = 'button';
    exportBtn.title = 'Exportar mundo';
    exportBtn.setAttribute('aria-label', `Exportar ${world.name}`);
    exportBtn.addEventListener('click', () => this.actions.onExportWorld?.(world.id));
    const deleteBtn = el('button', 'icon-action', '×');
    deleteBtn.type = 'button';
    deleteBtn.title = 'Apagar mundo';
    deleteBtn.setAttribute('aria-label', `Apagar ${world.name}`);
    deleteBtn.addEventListener('click', () => this.actions.onDeleteWorld?.(world.id));
    actions.appendChild(play);
    actions.appendChild(exportBtn);
    actions.appendChild(deleteBtn);
    body.appendChild(actions);
    card.appendChild(thumb);
    card.appendChild(body);
    return card;
  }

  showMenu() {
    $('menu')?.classList.remove('hidden');
    $('hud')?.classList.add('hidden');
    $('loading')?.classList.add('hidden');
    $('photo-bar')?.classList.add('hidden');
    this.hideAllModals();
    document.body.classList.remove('photo-mode');
  }

  hideMenu() {
    $('menu')?.classList.add('hidden');
  }

  showLoading(title, text) {
    $('loading')?.classList.remove('hidden');
    if ($('loading-title')) $('loading-title').textContent = title || 'Preparando seu mundo';
    if ($('loading-text')) $('loading-text').textContent = text || 'Plantando árvores arco-íris…';
    if ($('loading-bar')) $('loading-bar').style.width = '4%';
  }

  setLoading(progress, text) {
    if ($('loading-bar')) $('loading-bar').style.width = `${Math.max(4, Math.min(100, progress))}%`;
    if (text && $('loading-text')) $('loading-text').textContent = text;
  }

  hideLoading() {
    $('loading')?.classList.add('hidden');
  }

  showHUD() {
    $('hud')?.classList.remove('hidden');
  }

  hideHUD() {
    $('hud')?.classList.add('hidden');
  }

  renderHotbar(inventory, creative) {
    const hotbar = $('hotbar');
    if (!hotbar) return;
    hotbar.textContent = '';
    for (let i = 0; i < 9; i++) {
      const slot = inventory?.hotbar?.[i] || null;
      const button = el('button', 'hotbar-slot');
      button.type = 'button';
      button.dataset.index = String(i);
      button.setAttribute('aria-label', slot ? `Slot ${i + 1}: ${getItem(slot.id).name} x${slot.count}` : `Slot ${i + 1} vazio`);
      if (i === inventory?.selected) button.classList.add('selected');
      if (slot) {
        const rarity = getRarity(slot.id);
        button.style.setProperty('--rarity', rarity.color);
        const img = el('img');
        img.src = getItemIcon(slot.id);
        img.alt = getItem(slot.id).name;
        button.appendChild(img);
        if (slot.count > 1) button.appendChild(el('span', 'slot-count', String(slot.count)));
        button.title = `${getItem(slot.id).name}${creative ? ' (infinito)' : ''}`;
      }
      button.appendChild(el('span', 'slot-key', String(i + 1)));
      hotbar.appendChild(button);
    }
    const selected = inventory?.hotbar?.[inventory?.selected] || null;
    const hand = $('hand');
    const handItem = $('hand-item');
    if (hand && handItem) {
      hand.classList.toggle('hidden', !selected || this.thirdPerson);
      if (selected) {
        handItem.src = getItemIcon(selected.id);
        handItem.alt = getItem(selected.id).name;
      }
    }
  }

  swingHand() {
    const hand = $('hand');
    if (!hand) return;
    hand.classList.remove('swing');
    void hand.offsetWidth;
    hand.classList.add('swing');
  }

  setThirdPerson(active) {
    this.thirdPerson = !!active;
    const hand = $('hand');
    const selected = this.state?.inventory?.hotbar?.[this.state?.inventory?.selected] || null;
    if (hand) hand.classList.toggle('hidden', this.thirdPerson || !selected);
  }

  setFullscreen(active) {
    for (const id of ['btn-fullscreen-menu', 'btn-fullscreen', 'btn-pause-fullscreen']) {
      const button = $(id);
      if (!button) continue;
      button.title = active ? 'Sair da tela cheia (F11)' : 'Entrar em tela cheia (F11)';
      button.setAttribute('aria-label', active ? 'Sair da tela cheia' : 'Entrar em tela cheia');
      button.classList.toggle('active', !!active);
    }
  }

  renderHUD(data) {
    if (!data) return;
    if ($('hud-world-name')) $('hud-world-name').textContent = data.worldName || 'Mundo';
    if ($('hud-biome')) $('hud-biome').textContent = data.biome || 'Prado Musgo';
    if ($('hud-lumi')) $('hud-lumi').textContent = String(data.lumi || 0);
    if ($('clock-text')) $('clock-text').textContent = data.clock || 'Manhã';
    if ($('clock-icon')) $('clock-icon').textContent = data.clockIcon || '☀';
    if ($('quest-text')) $('quest-text').textContent = data.quest || 'Colete 5 Fragmentos de Lumi';
    if ($('quest-progress')) $('quest-progress').textContent = data.questProgress || '0/5';
    const buffs = $('buffs');
    if (buffs) {
      buffs.textContent = '';
      for (const buff of data.buffs || []) {
        buffs.appendChild(el('span', 'buff', `${buff.label} · ${buff.seconds}s`));
      }
    }
    const coords = $('hud-coords');
    if (coords) {
      const show = data.showCoords;
      coords.classList.toggle('hidden', !show);
      if (show) coords.textContent = `X ${Math.round(data.coords?.x || 0)} · Y ${Math.round(data.coords?.y || 0)} · Z ${Math.round(data.coords?.z || 0)}`;
    }
  }

  showHint(text, duration = 4200) {
    const hint = $('hint');
    if (!hint) return;
    hint.textContent = text;
    hint.style.opacity = '1';
    clearTimeout(this.hintTimer);
    this.hintTimer = setTimeout(() => {
      hint.style.opacity = '0';
    }, duration);
  }

  toast({ title, body, rarity = 'comum', icon = null }) {
    const container = $('toasts');
    if (!container) return;
    while (container.children.length >= 4) container.firstElementChild?.remove();
    const tone = RARITY[rarity] || RARITY.comum;
    const toast = el('div', 'toast');
    toast.style.setProperty('--rarity', tone.color);
    const head = el('div', 'toast-head');
    if (icon) {
      const iconBox = el('span', 'toast-icon');
      const img = el('img');
      img.src = icon;
      img.alt = '';
      iconBox.appendChild(img);
      head.appendChild(iconBox);
    }
    head.appendChild(el('span', 'toast-title', title || 'Descoberta'));
    toast.appendChild(head);
    if (body) toast.appendChild(el('div', 'toast-body', body));
    if (rarity && rarity !== 'comum') toast.appendChild(el('span', 'toast-rarity', `${tone.label} · ${'★'.repeat(tone.stars)}`));
    container.appendChild(toast);
    const timer = setTimeout(() => {
      toast.classList.add('out');
      setTimeout(() => toast.remove(), 220);
      this.toastTimers.delete(timer);
    }, 3800);
    this.toastTimers.add(timer);
  }

  openLayer(modalId) {
    $('modal-layer')?.classList.remove('hidden');
    ['inventory-modal', 'chest-modal', 'pause-modal', 'create-modal', 'character-modal', 'multiplayer-modal', 'tutorial', 'settings-modal', 'code-modal'].forEach((id) => {
      $(id)?.classList.toggle('hidden', id !== modalId);
    });
    if (!this.modalOpen) {
      this.modalOpen = true;
      this.actions.onModalState?.(true);
    }
  }

  hideLayer() {
    $('modal-layer')?.classList.add('hidden');
    ['inventory-modal', 'chest-modal', 'pause-modal', 'create-modal', 'character-modal', 'multiplayer-modal', 'tutorial', 'settings-modal', 'code-modal'].forEach((id) => {
      $(id)?.classList.add('hidden');
    });
    this.inventoryOpen = false;
    this.chestOpen = false;
    this.pauseOpen = false;
    if (this.modalOpen) {
      this.modalOpen = false;
      this.actions.onModalState?.(false);
    }
  }

  hideAllModals() {
    this.returnHeld(true);
    this.hideGhost();
    this.hideLayer();
  }

  openInventory(tab = 'backpack') {
    this.currentTab = tab;
    document.querySelectorAll('#inventory-tabs .tab').forEach((button) => {
      button.classList.toggle('active', button.dataset.tab === tab);
    });
    this.inventoryOpen = true;
    this.chestOpen = false;
    this.pauseOpen = false;
    this.openLayer('inventory-modal');
    this.renderInventory();
  }

  closeInventory() {
    if (!this.returnHeld()) return;
    this.inventoryOpen = false;
    this.hideLayer();
    this.actions.onInventoryChanged?.();
  }

  openChest(items, key, inventory) {
    this.chest = { items, key };
    this.state.inventory = inventory;
    this.inventoryOpen = false;
    this.chestOpen = true;
    this.pauseOpen = false;
    this.openLayer('chest-modal');
    this.renderChest();
  }

  closeChest() {
    if (!this.returnHeld()) return;
    const key = this.chest?.key;
    const items = (this.chest?.items || []).map((slot) => (slot ? { ...slot } : null));
    this.chestOpen = false;
    this.chest = null;
    this.hideLayer();
    this.actions.onChestClose?.(key, items);
    this.actions.onInventoryChanged?.();
  }

  openPause(info = {}) {
    this.pauseOpen = true;
    this.inventoryOpen = false;
    this.chestOpen = false;
    if ($('pause-world-name')) $('pause-world-name').textContent = info.name || this.state.worldName || 'Mundo';
    if ($('pause-world-meta')) $('pause-world-meta').textContent = `seed ${info.seed || this.state.seed || 'lumi'} · ${formatPlaytime(info.playtime || 0)}`;
    this.openLayer('pause-modal');
  }

  closePause() {
    this.pauseOpen = false;
    this.hideLayer();
    this.actions.onResume?.();
  }

  openCreate() {
    if ($('world-name-input')) $('world-name-input').value = '';
    if ($('world-seed-input')) $('world-seed-input').value = '';
    this.createMode = 'adventure';
    document.querySelectorAll('.mode-card').forEach((card) => card.classList.toggle('active', card.dataset.mode === 'adventure'));
    this.openLayer('create-modal');
    setTimeout(() => $('world-name-input')?.focus(), 60);
  }

  closeCreate() {
    this.hideLayer();
  }

  openMultiplayer() {
    this.multiplayerFromPause = !!this.pauseOpen;
    this.renderMultiplayer();
    this.openLayer('multiplayer-modal');
  }

  closeMultiplayer(force = false) {
    const fromPause = this.multiplayerFromPause && !force;
    this.multiplayerFromPause = false;
    if (fromPause) {
      this.openLayer('pause-modal');
      return;
    }
    this.hideLayer();
  }

  setMultiplayerState(patch = {}) {
    this.multiplayerState = { ...this.multiplayerState, ...patch };
    this.renderMultiplayer();
  }

  setJoinCode(code) {
    if ($('join-code')) $('join-code').value = String(code || '').toUpperCase().slice(0, 5);
  }

  renderMultiplayer() {
    const state = this.multiplayerState;
    const status = $('multiplayer-status');
    const text = $('multiplayer-status-text');
    if (text) text.textContent = state.message || 'Crie uma sala ou entre com um convite.';
    if (status) {
      status.classList.toggle('busy', ['hosting', 'joining', 'reconnecting'].includes(state.status));
      status.classList.toggle('error', state.status === 'error');
      status.classList.toggle('ok', state.status === 'connected');
    }
    const room = $('room-box');
    if (room) room.classList.toggle('hidden', !state.code);
    if ($('room-code')) $('room-code').textContent = state.code || '-----';
    const host = $('btn-host-room');
    if (host) {
      host.disabled = !!state.code;
      host.textContent = state.code ? 'Sala criada' : 'Criar sala';
    }
    const join = $('btn-join-room');
    if (join) join.disabled = !!state.code;
    if ($('btn-leave-room')) $('btn-leave-room').classList.toggle('hidden', !state.code);
    const players = Array.isArray(state.players) ? state.players : [];
    const list = $('room-players');
    if (list) {
      list.textContent = '';
      if (!players.length) {
        list.appendChild(el('span', 'room-player empty', 'Só você por enquanto.'));
      } else {
        for (const player of players) {
          const chip = el('span', 'room-player');
          chip.appendChild(el('i', 'player-dot'));
          chip.appendChild(el('span', '', player.name || 'Explorador'));
          if (player.id === state.selfId) chip.appendChild(el('small', '', 'você'));
          list.appendChild(chip);
        }
      }
    }
    const hudPlayers = $('hud-players');
    if (hudPlayers) {
      const online = Math.max(1, players.length);
      hudPlayers.classList.remove('hidden');
      hudPlayers.textContent = `◈ ${online} online`;
      hudPlayers.title = players.length ? players.map((player) => player.name || 'Explorador').join(' · ') : 'Você está explorando sozinho';
    }
  }

  openCharacter() {
    this.pendingCharacter = normalizeCharacter(this.character);
    this.renderCharacterEditor();
    this.openLayer('character-modal');
    setTimeout(() => $('character-name')?.focus(), 60);
  }

  closeCharacter() {
    this.pendingCharacter = null;
    this.hideLayer();
  }

  renderCharacterEditor() {
    const character = this.pendingCharacter || this.character;
    if ($('character-name')) $('character-name').value = character.name;

    const presets = $('character-presets');
    if (presets) {
      presets.textContent = '';
      for (const preset of CHARACTER_PRESETS) {
        const card = el('button', 'character-preset');
        card.type = 'button';
        card.dataset.characterPreset = preset.id;
        card.classList.toggle('active', !character.custom && character.preset === preset.id);
        const chips = el('span', 'preset-colors');
        for (const color of [preset.hair, preset.skin, preset.shirt, preset.pants]) {
          const chip = el('i');
          chip.style.background = color;
          chips.appendChild(chip);
        }
        card.appendChild(chips);
        card.appendChild(el('strong', '', preset.name));
        presets.appendChild(card);
      }
    }

    const colors = $('character-colors');
    if (colors) {
      colors.textContent = '';
      const labels = { skin: 'Pele', shirt: 'Camisa', pants: 'Calça', hair: 'Cabelo' };
      for (const part of ['skin', 'shirt', 'pants', 'hair']) {
        const row = el('div', 'color-row');
        row.appendChild(el('span', 'color-label', labels[part]));
        const swatches = el('div', 'color-swatches');
        for (const color of COLOR_SWATCHES[part]) {
          const swatch = el('button', 'color-swatch');
          swatch.type = 'button';
          swatch.style.setProperty('--swatch', color);
          swatch.dataset.characterPart = part;
          swatch.dataset.color = color;
          swatch.title = `${labels[part]}: ${color}`;
          swatch.setAttribute('aria-label', `${labels[part]} ${color}`);
          swatch.classList.toggle('active', character[part] === color);
          swatches.appendChild(swatch);
        }
        row.appendChild(swatches);
        colors.appendChild(row);
      }
    }

    const weaponSkins = $('weapon-skins');
    if (weaponSkins) {
      weaponSkins.textContent = '';
      for (const skin of WEAPON_SKINS) {
        const button = el('button', 'weapon-skin');
        button.type = 'button';
        button.dataset.weaponSkin = skin.id;
        button.style.setProperty('--skin-color', skin.color);
        button.classList.toggle('active', character.weaponSkin === skin.id);
        button.appendChild(el('i'));
        button.appendChild(el('span', '', skin.name));
        weaponSkins.appendChild(button);
      }
    }

    this.renderCharacterPreview();
  }

  renderCharacterPreview() {
    const character = this.pendingCharacter || this.character;
    const preview = $('character-preview');
    if (preview) {
      preview.style.setProperty('--skin', character.skin);
      preview.style.setProperty('--shirt', character.shirt);
      preview.style.setProperty('--pants', character.pants);
      preview.style.setProperty('--hair', character.hair);
      preview.className = `character-preview accessory-${character.accessory}`;
    }
    if ($('character-preview-name')) $('character-preview-name').textContent = character.name || 'Explorador';
  }

  saveCharacter() {
    const character = normalizeCharacter(this.pendingCharacter || this.character);
    this.character = character;
    this.settings.character = character;
    this.actions.onCharacterSave?.(character);
    this.closeCharacter();
  }

  openSettings() {
    this.settingsFromPause = !!this.pauseOpen;
    this.openLayer('settings-modal');
    this.applySettings(this.settings);
  }

  closeSettings() {
    const fromPause = this.settingsFromPause;
    this.settingsFromPause = false;
    if (fromPause) {
      this.openLayer('pause-modal');
      this.actions.onSettingsClose?.(true);
      return;
    }
    this.hideLayer();
    this.actions.onSettingsClose?.(false);
  }

  openTutorial(force = false) {
    this.tutorialStep = 0;
    this.tutorialForce = force;
    this.openLayer('tutorial');
    this.renderTutorial();
  }

  renderTutorial() {
    const step = TUTORIAL_STEPS[this.tutorialStep];
    if (!step) return;
    if ($('tutorial-step-label')) $('tutorial-step-label').textContent = `Passo ${this.tutorialStep + 1} de ${TUTORIAL_STEPS.length}`;
    if ($('tutorial-step-title')) $('tutorial-step-title').textContent = step.title;
    if ($('tutorial-step-text')) $('tutorial-step-text').textContent = step.text;
    if ($('tutorial-art')) $('tutorial-art').textContent = step.art;
    const dots = $('tutorial-dots');
    if (dots) {
      dots.textContent = '';
      TUTORIAL_STEPS.forEach((_, index) => {
        dots.appendChild(el('span', `tutorial-dot${index === this.tutorialStep ? ' active' : ''}`));
      });
    }
    if ($('btn-tutorial-next')) $('btn-tutorial-next').textContent = this.tutorialStep === TUTORIAL_STEPS.length - 1 ? 'Começar a jogar' : 'Próximo';
  }

  nextTutorial() {
    if (this.tutorialStep >= TUTORIAL_STEPS.length - 1) {
      this.closeTutorial();
      return;
    }
    this.tutorialStep += 1;
    this.renderTutorial();
  }

  closeTutorial() {
    this.hideLayer();
    this.actions.onTutorialDone?.();
  }

  openCode(code, importMode = false) {
    this.codeFromPause = !!this.pauseOpen;
    if ($('world-code-output')) $('world-code-output').value = code || '';
    $('code-import-box')?.classList.toggle('hidden', !importMode);
    if ($('world-code-input')) $('world-code-input').value = '';
    this.openLayer('code-modal');
    if (importMode) setTimeout(() => $('world-code-input')?.focus(), 60);
  }

  closeCode(force = false) {
    const fromPause = this.codeFromPause && !force;
    this.codeFromPause = false;
    if (fromPause) {
      this.openLayer('pause-modal');
      return;
    }
    this.hideLayer();
  }

  copyCode() {
    const code = $('world-code-output')?.value || '';
    this.copyText(code, 'Código copiado!');
  }

  async copyText(text, message) {
    if (!text) return;
    try {
      await navigator.clipboard.writeText(text);
      this.toast({ title: message || 'Copiado!', body: 'Agora é só compartilhar.', rarity: 'incomum' });
      return;
    } catch {
      const area = document.createElement('textarea');
      area.value = text;
      area.style.position = 'fixed';
      area.style.opacity = '0';
      document.body.appendChild(area);
      area.select();
      try {
        document.execCommand('copy');
        this.toast({ title: message || 'Copiado!', body: 'Agora é só compartilhar.', rarity: 'incomum' });
      } catch {
        this.toast({ title: 'Não foi possível copiar', body: 'Selecione o texto e copie manualmente.', rarity: 'comum' });
      }
      area.remove();
    }
  }

  openPhoto() {
    $('hud')?.classList.add('hidden');
    $('photo-bar')?.classList.remove('hidden');
    document.body.classList.add('photo-mode');
    this.actions.onPhotoEnter?.();
  }

  closePhoto() {
    $('photo-bar')?.classList.add('hidden');
    $('hud')?.classList.remove('hidden');
    document.body.classList.remove('photo-mode');
    this.actions.onPhotoExit?.();
  }

  setTouchMode(isTouch) {
    $('mobile-controls')?.classList.toggle('hidden', !isTouch);
    document.body.classList.toggle('touch-mode', isTouch);
  }

  arrayFor(type) {
    if (type === 'chest') return this.chest?.items || null;
    if (!this.state.inventory) return null;
    if (type === 'hotbar') return this.state.inventory.hotbar;
    if (type === 'backpack') return this.state.inventory.backpack;
    if (type === 'pouch') return this.state.inventory.pouch;
    return null;
  }

  slotElement(slot, type, index) {
    const button = el('button', 'slot');
    button.type = 'button';
    button.dataset.type = type;
    button.dataset.index = String(index);
    if (slot) {
      const item = getItem(slot.id);
      const rarity = getRarity(slot.id);
      button.classList.add('has-item');
      button.style.setProperty('--rarity', rarity.color);
      button.title = `${item.name} · ${rarity.label}${slot.count > 1 ? ` ×${slot.count}` : ''}\n${item.desc}`;
      button.setAttribute('aria-label', `${item.name}, ${rarity.label}, quantidade ${slot.count}`);
      const img = el('img');
      img.src = getItemIcon(slot.id);
      img.alt = item.name;
      button.appendChild(img);
      if (slot.count > 1) button.appendChild(el('span', 'slot-count', String(slot.count)));
    } else {
      button.setAttribute('aria-label', 'Slot vazio');
      button.appendChild(el('span', 'slot-empty-mark', '·'));
    }
    return button;
  }

  renderInventory() {
    const body = $('inventory-body');
    if (!body) return;
    body.textContent = '';
    const inventory = this.state.inventory;
    if (!inventory) return;
    if ($('btn-creative-palette')) $('btn-creative-palette').classList.toggle('hidden', this.state.mode !== 'creative');

    if (this.currentTab === 'backpack') {
      body.appendChild(this.gridSection('Barra rápida', 'Sempre à mão', inventory.hotbar, 'hotbar'));
      body.appendChild(this.gridSection('Mochila', `${inventory.backpack.filter(Boolean).length}/27 slots`, inventory.backpack, 'backpack'));
      if (this.state.mode === 'creative') body.appendChild(this.creativePalette());
    } else if (this.currentTab === 'pouch') {
      body.appendChild(this.gridSection('Bolsa de Sementes', 'Sementes e alimentos entram aqui automaticamente', inventory.pouch, 'pouch', 'pouch-grid'));
    } else if (this.currentTab === 'craft') {
      body.appendChild(this.renderCrafting());
    } else if (this.currentTab === 'album') {
      body.appendChild(this.renderAlbum());
    }
    this.updateGhostPosition();
  }

  gridSection(title, subtitle, slots, type, extraClass = '') {
    const wrap = el('div', 'inventory-columns');
    const head = el('div', 'inventory-section-title');
    const text = el('div');
    text.appendChild(el('h3', '', title));
    head.appendChild(text);
    head.appendChild(el('span', '', subtitle));
    wrap.appendChild(head);
    const grid = el('div', `slot-grid ${extraClass}`.trim());
    slots.forEach((slot, index) => grid.appendChild(this.slotElement(slot, type, index)));
    wrap.appendChild(grid);
    return wrap;
  }

  creativePalette() {
    const wrap = el('div', 'creative-palette');
    wrap.appendChild(el('h3', '', 'Paleta criativa'));
    const grid = el('div', 'creative-grid');
    for (const id of CREATIVE_PALETTE) {
      const button = el('button', 'creative-block');
      button.type = 'button';
      button.title = getItem(id).name;
      button.setAttribute('aria-label', `Pegar ${getItem(id).name}`);
      const img = el('img');
      img.src = getItemIcon(id);
      img.alt = getItem(id).name;
      button.appendChild(img);
      button.addEventListener('click', () => {
        this.actions.onCreativePick?.(id);
        this.renderInventory();
        this.renderHotbar(this.state.inventory, true);
      });
      grid.appendChild(button);
    }
    wrap.appendChild(grid);
    return wrap;
  }

  openCreativePalette() {
    this.currentTab = 'backpack';
    this.renderInventory();
  }

  renderCrafting() {
    const wrap = el('div');
    wrap.appendChild(el('h3', '', 'Forja de Lumis'));
    const sub = el('p', 'recipe-hint', 'Escolha uma receita. Os ingredientes saem de todos os seus inventários.');
    wrap.appendChild(sub);
    const list = el('div', 'recipe-list');
    for (const recipe of RECIPES) {
      const item = getItem(recipe.out.id);
      const rarity = getRarity(recipe.out.id);
      const can = this.state.mode === 'creative' || this.state.inventory.canCraft(recipe.ingredients);
      const card = el('div', `recipe-card${can ? '' : ' locked'}`);
      card.style.setProperty('--rarity', rarity.color);
      const icon = el('div', 'recipe-icon');
      const img = el('img');
      img.src = getItemIcon(recipe.out.id);
      img.alt = item.name;
      icon.appendChild(img);
      const info = el('div');
      info.appendChild(el('div', 'recipe-name', `${item.name}${recipe.out.count > 1 ? ` ×${recipe.out.count}` : ''}`));
      info.appendChild(el('div', 'recipe-hint', recipe.hint || ''));
      const ingredients = el('div', 'recipe-ingredients');
      for (const ing of recipe.ingredients) {
        const have = this.state.inventory.countItem(ing.id);
        const chip = el('span', `ingredient ${have >= ing.count ? 'ok' : 'missing'}`, `${getItem(ing.id).name} ${have}/${ing.count}`);
        ingredients.appendChild(chip);
      }
      info.appendChild(ingredients);
      const button = el('button', 'primary-btn', 'Criar');
      button.type = 'button';
      button.disabled = !can;
      button.addEventListener('click', () => {
        this.actions.onCraft?.(recipe.id);
      });
      card.appendChild(icon);
      card.appendChild(info);
      card.appendChild(button);
      list.appendChild(card);
    }
    wrap.appendChild(list);
    return wrap;
  }

  renderAlbum() {
    const discovered = new Set((this.state.world?.discovered || []).map((id) => String(id)));
    const wrap = el('div');
    const head = el('div', 'album-head');
    const title = el('div');
    title.appendChild(el('h3', '', 'Álbum de Coleção'));
    head.appendChild(title);
    const count = COLLECTION_IDS.filter((id) => discovered.has(String(id))).length;
    head.appendChild(el('span', 'recipe-hint', `${count}/${COLLECTION_IDS.length} descobertos`));
    wrap.appendChild(head);
    const bar = el('div', 'album-progress');
    const fill = el('div');
    fill.style.width = `${(count / COLLECTION_IDS.length) * 100}%`;
    bar.appendChild(fill);
    wrap.appendChild(bar);
    const grid = el('div', 'album-grid');
    for (const id of COLLECTION_IDS) {
      const item = getItem(id);
      const known = discovered.has(String(id));
      const card = el('div', `album-item${known ? '' : ' unknown'}`);
      const rarity = getRarity(id);
      card.style.setProperty('--rarity', rarity.color);
      const img = el('img');
      img.src = getItemIcon(id);
      img.alt = known ? item.name : 'Item não descoberto';
      card.appendChild(img);
      card.appendChild(el('strong', '', known ? item.name : '???'));
      card.appendChild(el('small', '', known ? `${rarity.label} · ${'★'.repeat(rarity.stars)}` : 'Não descoberto'));
      if (known) card.title = item.desc;
      grid.appendChild(card);
    }
    wrap.appendChild(grid);
    return wrap;
  }

  renderChest() {
    const grid = $('chest-grid');
    const invGrid = $('chest-inventory-grid');
    if (!grid || !invGrid || !this.chest || !this.state.inventory) return;
    grid.textContent = '';
    invGrid.textContent = '';
    this.chest.items.forEach((slot, index) => grid.appendChild(this.slotElement(slot, 'chest', index)));
    const inv = this.state.inventory;
    let index = 0;
    for (const slot of inv.hotbar) {
      invGrid.appendChild(this.slotElement(slot, 'hotbar', index));
      index += 1;
    }
    inv.backpack.forEach((slot, i) => invGrid.appendChild(this.slotElement(slot, 'backpack', i)));
    this.updateGhostPosition();
  }

  handleSlotDown(event) {
    const slotEl = event.target.closest('.slot');
    if (!slotEl) return;
    event.preventDefault();
    const type = slotEl.dataset.type;
    const index = Number(slotEl.dataset.index);
    const arr = this.arrayFor(type);
    if (!arr) return;
    if (event.shiftKey) {
      this.quickMoveSlot(type, index);
      return;
    }
    if (this.held) {
      this.placeHeld(type, index);
      this.dragSource = null;
    this.thirdPerson = false;
    } else if (arr[index]) {
      this.held = { ...arr[index] };
      arr[index] = null;
      this.dragSource = { type, index };
      this.actions.onInventoryChanged?.();
    }
    this.renderOpenPanels();
    this.updateGhostPosition(event.clientX, event.clientY);
  }

  handleSlotContext(event) {
    const slotEl = event.target.closest('.slot');
    if (!slotEl) return;
    event.preventDefault();
    const type = slotEl.dataset.type;
    const index = Number(slotEl.dataset.index);
    const arr = this.arrayFor(type);
    if (!arr) return;
    if (!this.held) {
      const slot = arr[index];
      if (slot && slot.count > 1) {
        const half = Math.ceil(slot.count / 2);
        slot.count -= half;
        this.held = { id: slot.id, count: half };
      }
    } else {
      const slot = arr[index];
      if (!slot) {
        arr[index] = { id: this.held.id, count: 1 };
        this.held.count -= 1;
        if (this.held.count <= 0) this.held = null;
      } else if (slot.id === this.held.id) {
        const max = getItem(slot.id).stack || 64;
        if (slot.count < max) {
          slot.count += 1;
          this.held.count -= 1;
          if (this.held.count <= 0) this.held = null;
        }
      }
    }
    this.renderOpenPanels();
    this.updateGhostPosition(event.clientX, event.clientY);
  }

  handlePointerUp(event) {
    if (!this.held || !this.dragSource) return;
    const slotEl = document.elementFromPoint(event.clientX, event.clientY)?.closest('.slot');
    if (!slotEl) return;
    const type = slotEl.dataset.type;
    const index = Number(slotEl.dataset.index);
    if (type === this.dragSource.type && index === this.dragSource.index) return;
    this.placeHeld(type, index);
    this.dragSource = null;
    this.thirdPerson = false;
    this.renderOpenPanels();
    this.updateGhostPosition();
  }

  placeHeld(type, index) {
    const arr = this.arrayFor(type);
    if (!arr || !this.held) return;
    const target = arr[index];
    if (!target) {
      arr[index] = this.held;
      this.held = null;
    } else if (target.id === this.held.id) {
      const max = getItem(target.id).stack || 64;
      const space = max - target.count;
      const add = Math.min(space, this.held.count);
      target.count += add;
      this.held.count -= add;
      if (this.held.count <= 0) this.held = null;
    } else {
      arr[index] = this.held;
      this.held = target;
    }
    this.actions.onInventoryChanged?.();
  }

  quickMoveSlot(type, index) {
    const arr = this.arrayFor(type);
    if (!arr || !arr[index]) return;
    let destinations;
    if (type === 'chest') destinations = [this.state.inventory.hotbar, this.state.inventory.backpack, this.state.inventory.pouch];
    else if (this.chestOpen) destinations = [this.chest.items];
    else if (type === 'hotbar') destinations = [this.state.inventory.backpack, this.state.inventory.pouch];
    else if (type === 'pouch') destinations = [this.state.inventory.backpack, this.state.inventory.hotbar];
    else destinations = [this.state.inventory.hotbar, this.state.inventory.pouch];
    quickMove(arr, index, destinations);
    this.actions.onInventoryChanged?.();
    this.renderOpenPanels();
  }

  returnHeld(silent = false) {
    if (!this.held) return true;
    const inventory = this.state.inventory;
    if (!inventory) {
      this.held = null;
      return true;
    }
    let left = inventory.addItem(this.held.id, this.held.count);
    if (left > 0 && this.chest) {
      left = addToArrays(this.held.id, left, [this.chest.items]);
    }
    if (left > 0) {
      if (!silent) this.toast({ title: 'Mochila cheia', body: 'Crie espaço antes de fechar o inventário.', rarity: 'comum' });
      return false;
    }
    this.held = null;
    this.hideGhost();
    return true;
  }

  renderOpenPanels() {
    if (this.inventoryOpen) this.renderInventory();
    if (this.chestOpen) this.renderChest();
    this.renderHotbar(this.state.inventory, this.state.mode === 'creative');
  }

  updateGhostPosition(x = this.lastPointerX, y = this.lastPointerY) {
    this.lastPointerX = x;
    this.lastPointerY = y;
    const ghost = $('drag-ghost');
    if (!ghost) return;
    if (!this.held) {
      ghost.classList.add('hidden');
      return;
    }
    ghost.classList.remove('hidden');
    ghost.textContent = '';
    const img = el('img');
    img.src = getItemIcon(this.held.id);
    img.alt = '';
    ghost.appendChild(img);
    if (x != null && y != null) {
      ghost.style.left = `${x}px`;
      ghost.style.top = `${y}px`;
    }
  }

  updateGhost(x, y) {
    if (!this.held) return;
    this.updateGhostPosition(x, y);
  }

  hideGhost() {
    $('drag-ghost')?.classList.add('hidden');
  }

  showPhotoInfo(info) {
    this.lastPhotoInfo = info;
  }

  downloadPhoto(canvas, info) {
    if (!canvas) return;
    const width = Math.min(1920, canvas.width);
    const height = Math.round((canvas.height / canvas.width) * width);
    const out = document.createElement('canvas');
    out.width = width;
    out.height = height;
    const ctx = out.getContext('2d');
    ctx.drawImage(canvas, 0, 0, width, height);
    this.drawWatermark(ctx, width, height, info);
    this.downloadDataUrl(out.toDataURL('image/png'), `lulicraft-foto-${Date.now()}.png`);
    this.toast({ title: 'Foto salva!', body: 'Sua lembrança de Lumi foi baixada.', rarity: 'incomum' });
  }

  drawWatermark(ctx, width, height, info) {
    ctx.save();
    const size = Math.max(14, Math.round(width * 0.018));
    ctx.font = `800 ${size}px "Nunito", sans-serif`;
    ctx.textAlign = 'right';
    ctx.fillStyle = 'rgba(0,0,0,0.45)';
    ctx.fillText(`Lulicraft · ${info?.worldName || 'Mundo'}`, width - size * 1.6, height - size * 1.5);
    ctx.fillStyle = 'rgba(255,255,255,0.9)';
    ctx.fillText(`Lulicraft · ${info?.worldName || 'Mundo'}`, width - size * 1.5, height - size * 1.6);
    ctx.restore();
  }

  downloadCard(canvas, info = {}) {
    if (!canvas) return;
    const W = 1080;
    const H = 1350;
    const out = document.createElement('canvas');
    out.width = W;
    out.height = H;
    const ctx = out.getContext('2d');
    const bg = ctx.createLinearGradient(0, 0, W, H);
    bg.addColorStop(0, '#0b141f');
    bg.addColorStop(0.5, '#14283a');
    bg.addColorStop(1, '#0b141f');
    ctx.fillStyle = bg;
    ctx.fillRect(0, 0, W, H);

    const imgH = 900;
    const sourceRatio = canvas.width / canvas.height;
    const targetRatio = W / imgH;
    let sx = 0;
    let sy = 0;
    let sw = canvas.width;
    let sh = canvas.height;
    if (sourceRatio > targetRatio) {
      sw = canvas.height * targetRatio;
      sx = (canvas.width - sw) / 2;
    } else {
      sh = canvas.width / targetRatio;
      sy = (canvas.height - sh) / 2;
    }
    ctx.drawImage(canvas, sx, sy, sw, sh, 0, 0, W, imgH);

    const overlay = ctx.createLinearGradient(0, imgH - 260, 0, imgH + 80);
    overlay.addColorStop(0, 'rgba(11,20,31,0)');
    overlay.addColorStop(0.65, 'rgba(11,20,31,0.85)');
    overlay.addColorStop(1, '#0b141f');
    ctx.fillStyle = overlay;
    ctx.fillRect(0, imgH - 260, W, 340);

    ctx.save();
    ctx.fillStyle = '#b8ff6a';
    ctx.font = '800 28px "Baloo 2", "Nunito", sans-serif';
    ctx.fillText('LULICRAFT · CARTÃO DO MUNDO', 72, 78);
    ctx.fillStyle = 'rgba(255,255,255,0.72)';
    ctx.font = '700 22px "Nunito", sans-serif';
    ctx.fillText('Um mundo de blocos, luz e descobertas.', 72, 114);
    ctx.restore();

    ctx.save();
    ctx.fillStyle = '#f7f3e8';
    ctx.font = '800 76px "Baloo 2", "Nunito", sans-serif';
    const title = info.worldName || 'Meu Mundo de Lumi';
    ctx.fillText(this.truncate(ctx, title, W - 144), 72, 1010);
    ctx.fillStyle = '#9fb3ae';
    ctx.font = '700 26px "Nunito", sans-serif';
    ctx.fillText(`seed ${info.seed || 'lumi'} · ${info.characterName || 'Lumi'} · ${info.mode === 'creative' ? 'Criativo' : 'Aventura Cozy'}`, 72, 1054);

    const stats = [
      ['✦', `${info.rare || 0} raros encontrados`],
      ['▦', `${info.discovered || 0} itens no álbum`],
      ['◆', `${info.blocks || 0} blocos transformados`]
    ];
    ctx.font = '700 28px "Nunito", sans-serif';
    stats.forEach(([icon, label], index) => {
      const y = 1130 + index * 48;
      ctx.fillStyle = '#b8ff6a';
      ctx.fillText(icon, 72, y);
      ctx.fillStyle = '#dce9e4';
      ctx.fillText(label, 112, y);
    });

    ctx.fillStyle = 'rgba(255,255,255,0.1)';
    ctx.fillRect(72, 1268, W - 144, 2);
    ctx.fillStyle = '#6f8580';
    ctx.font = '700 20px "Nunito", sans-serif';
    ctx.fillText('Feito com Lulicraft · sem contas, sem anúncios', 72, 1308);
    ctx.textAlign = 'right';
    ctx.fillStyle = '#b8ff6a';
    ctx.fillText(info.biome || 'Prado Musgo', W - 72, 1308);
    ctx.restore();

    this.downloadDataUrl(out.toDataURL('image/png'), `lulicraft-cartao-${Date.now()}.png`);
    this.toast({ title: 'Cartão do Mundo salvo!', body: 'Compartilhe sua descoberta com quem quiser.', rarity: 'raro' });
  }

  truncate(ctx, text, maxWidth) {
    if (ctx.measureText(text).width <= maxWidth) return text;
    let result = text;
    while (result.length > 1 && ctx.measureText(`${result}…`).width > maxWidth) result = result.slice(0, -1);
    return `${result}…`;
  }

  downloadDataUrl(url, filename) {
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    link.remove();
  }
}
