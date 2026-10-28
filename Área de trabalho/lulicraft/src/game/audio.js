const BIOME_AMBIENCE = {
  meadow: { filter: 620, gain: 0.014, min: 3.4, max: 7.5 },
  forest: { filter: 430, gain: 0.018, min: 2.8, max: 6.2 },
  desert: { filter: 980, gain: 0.009, min: 4.2, max: 8.5 },
  tundra: { filter: 1180, gain: 0.008, min: 4.8, max: 9 },
  coast: { filter: 760, gain: 0.02, min: 3.2, max: 6.8 }
};

export class AudioEngine {
  constructor(enabled = true) {
    this.enabled = enabled;
    this.ctx = null;
    this.master = null;
    this.noiseBuffer = null;
    this.ambience = null;
    this.ambienceBuffer = null;
    this.ambienceTimer = 3;
    this.biome = 'meadow';
  }

  setEnabled(enabled) {
    this.enabled = enabled;
    if (this.master) this.master.gain.value = enabled ? 0.28 : 0;
    if (!enabled) this.stopAmbience();
  }

  ensure() {
    if (!this.enabled) return null;
    try {
      if (!this.ctx) {
        const Ctx = window.AudioContext || window.webkitAudioContext;
        if (!Ctx) return null;
        this.ctx = new Ctx();
        this.master = this.ctx.createGain();
        this.master.gain.value = 0.28;
        this.master.connect(this.ctx.destination);
      }
      if (this.ctx.state === 'suspended') this.ctx.resume();
      return this.ctx;
    } catch {
      return null;
    }
  }

  tone(freq, duration = 0.12, type = 'sine', gain = 0.18, delay = 0, slideTo = null) {
    const ctx = this.ensure();
    if (!ctx) return;
    const t0 = ctx.currentTime + delay;
    const osc = ctx.createOscillator();
    const env = ctx.createGain();
    osc.type = type;
    osc.frequency.setValueAtTime(freq, t0);
    if (slideTo) osc.frequency.exponentialRampToValueAtTime(Math.max(30, slideTo), t0 + duration);
    env.gain.setValueAtTime(0.0001, t0);
    env.gain.exponentialRampToValueAtTime(gain, t0 + 0.012);
    env.gain.exponentialRampToValueAtTime(0.0001, t0 + duration);
    osc.connect(env);
    env.connect(this.master);
    osc.start(t0);
    osc.stop(t0 + duration + 0.02);
  }

  getAmbienceNoise() {
    const ctx = this.ensure();
    if (!ctx) return null;
    if (!this.ambienceBuffer) {
      const length = Math.floor(ctx.sampleRate * 3);
      const buffer = ctx.createBuffer(1, length, ctx.sampleRate);
      const data = buffer.getChannelData(0);
      let last = 0;
      for (let i = 0; i < length; i++) {
        const white = Math.random() * 2 - 1;
        last = last * 0.985 + white * 0.015;
        data[i] = last * 3;
      }
      this.ambienceBuffer = buffer;
    }
    return this.ambienceBuffer;
  }

  startAmbience() {
    if (!this.enabled || this.ambience) return;
    const ctx = this.ensure();
    const buffer = this.getAmbienceNoise();
    if (!ctx || !buffer) return;
    const config = BIOME_AMBIENCE[this.biome] || BIOME_AMBIENCE.meadow;
    const src = ctx.createBufferSource();
    src.buffer = buffer;
    src.loop = true;
    const filter = ctx.createBiquadFilter();
    filter.type = 'lowpass';
    filter.frequency.value = config.filter;
    const gain = ctx.createGain();
    gain.gain.value = config.gain;
    src.connect(filter);
    filter.connect(gain);
    gain.connect(this.master);
    src.start();
    this.ambience = { src, gain, filter };
  }

  setBiome(biome = 'meadow') {
    if (!biome || this.biome === biome) return;
    this.biome = BIOME_AMBIENCE[biome] ? biome : 'meadow';
    if (!this.ambience) return;
    const ctx = this.ensure();
    if (!ctx) return;
    const config = BIOME_AMBIENCE[this.biome];
    this.ambience.filter.frequency.setTargetAtTime(config.filter, ctx.currentTime, 0.35);
    this.ambience.gain.gain.setTargetAtTime(config.gain, ctx.currentTime, 0.5);
    this.ambienceTimer = Math.min(this.ambienceTimer, 0.6);
  }

  stopAmbience() {
    if (!this.ambience) return;
    try {
      this.ambience.src.stop();
      this.ambience.src.disconnect();
      this.ambience.gain.disconnect();
    } catch {
      // Audio may already be stopped by the browser.
    }
    this.ambience = null;
  }

  ambientTick(dt, night = false) {
    if (!this.enabled) return;
    this.startAmbience();
    this.ambienceTimer -= dt;
    if (this.ambienceTimer > 0) return;
    const config = BIOME_AMBIENCE[this.biome] || BIOME_AMBIENCE.meadow;
    this.ambienceTimer = night ? 4.6 + Math.random() * 4.8 : config.min + Math.random() * (config.max - config.min);
    if (night) {
      this.tone(1180 + Math.random() * 180, 0.035, 'sine', 0.01, 0, 1040);
      this.tone(1640 + Math.random() * 220, 0.03, 'sine', 0.008, 0.11, 1420);
      return;
    }
    if (this.biome === 'forest') {
      this.tone(520 + Math.random() * 160, 0.08, 'sine', 0.012, 0, 680);
      this.noise(0.24, 2800, 0.008, 0.05);
      return;
    }
    if (this.biome === 'desert') {
      this.noise(0.32, 1500, 0.012);
      if (Math.random() > 0.55) this.tone(880 + Math.random() * 240, 0.16, 'triangle', 0.01, 0.1, 1320);
      return;
    }
    if (this.biome === 'tundra') {
      this.noise(0.28, 2200, 0.009);
      this.tone(1480 + Math.random() * 200, 0.22, 'sine', 0.009, 0.08, 1960);
      return;
    }
    if (this.biome === 'coast') {
      this.noise(0.42, 700, 0.018);
      this.noise(0.2, 1300, 0.008, 0.18);
      if (Math.random() > 0.5) this.tone(940 + Math.random() * 180, 0.14, 'triangle', 0.012, 0.22, 1260);
      return;
    }
    const base = 1900 + Math.random() * 500;
    this.tone(base, 0.045, 'sine', 0.018, 0, base * 1.35);
    this.tone(base * 1.18, 0.04, 'sine', 0.014, 0.075, base * 1.55);
    if (Math.random() > 0.5) this.tone(base * 1.32, 0.035, 'sine', 0.01, 0.16, base * 1.7);
  }

  getNoise() {
    const ctx = this.ensure();
    if (!ctx) return null;
    if (!this.noiseBuffer) {
      const length = Math.floor(ctx.sampleRate * 0.5);
      const buffer = ctx.createBuffer(1, length, ctx.sampleRate);
      const data = buffer.getChannelData(0);
      for (let i = 0; i < length; i++) data[i] = Math.random() * 2 - 1;
      this.noiseBuffer = buffer;
    }
    return this.noiseBuffer;
  }

  noise(duration = 0.1, frequency = 900, gain = 0.12, delay = 0) {
    const ctx = this.ensure();
    const buffer = this.getNoise();
    if (!ctx || !buffer) return;
    const t0 = ctx.currentTime + delay;
    const src = ctx.createBufferSource();
    src.buffer = buffer;
    const filter = ctx.createBiquadFilter();
    filter.type = 'lowpass';
    filter.frequency.setValueAtTime(frequency, t0);
    filter.frequency.exponentialRampToValueAtTime(Math.max(120, frequency * 0.35), t0 + duration);
    const env = ctx.createGain();
    env.gain.setValueAtTime(gain, t0);
    env.gain.exponentialRampToValueAtTime(0.0001, t0 + duration);
    src.connect(filter);
    filter.connect(env);
    env.connect(this.master);
    src.start(t0);
    src.stop(t0 + duration + 0.02);
  }

  breakBlock(block) {
    const hardness = block?.hardness || 1;
    this.noise(0.09 + hardness * 0.02, 620 + hardness * 180, 0.12);
    this.tone(150 + hardness * 18, 0.07, 'triangle', 0.06, 0, 90);
  }

  placeBlock() {
    this.tone(180, 0.08, 'triangle', 0.12, 0, 120);
    this.noise(0.05, 700, 0.06);
  }

  step() {
    this.noise(0.045, 380, 0.025);
  }

  ui() {
    this.tone(520, 0.05, 'sine', 0.06);
  }

  pickup(rarity = 'comum') {
    const map = { comum: 330, incomum: 392, raro: 523, epico: 659, lendario: 784, mitico: 1047 };
    const base = map[rarity] || 330;
    this.tone(base, 0.09, 'sine', 0.1);
    this.tone(base * 1.5, 0.12, 'sine', 0.07, 0.06);
  }

  rare(rarity = 'raro') {
    const map = { raro: 523, epico: 659, lendario: 784, mitico: 1047 };
    const base = map[rarity] || 523;
    [1, 1.25, 1.5, 2].forEach((mult, index) => {
      this.tone(base * mult, 0.3, 'sine', 0.11, index * 0.085);
    });
    this.noise(0.35, 4200, 0.035, 0.04);
  }

  craft(rarity = 'comum') {
    const base = rarity === 'lendario' ? 523 : rarity === 'epico' ? 440 : 392;
    [1, 1.25, 1.5].forEach((mult, index) => {
      this.tone(base * mult, 0.16, 'triangle', 0.09, index * 0.07);
    });
  }

  achievement() {
    [523, 659, 784, 1047].forEach((freq, index) => {
      this.tone(freq, 0.24, 'sine', 0.1, index * 0.09);
    });
  }

  open() {
    this.tone(300, 0.08, 'sine', 0.08, 0, 420);
  }

  close() {
    this.tone(420, 0.08, 'sine', 0.07, 0, 280);
  }

  eat() {
    this.noise(0.12, 500, 0.08);
    this.tone(260, 0.12, 'triangle', 0.07, 0.03, 340);
  }

  jump() {
    this.tone(240, 0.09, 'sine', 0.06, 0, 330);
  }

  land() {
    this.tone(180, 0.12, 'sine', 0.08, 0, 110);
    this.noise(0.12, 460, 0.045);
  }

  pet(kind = 'happy') {
    if (kind === 'treat') {
      [660, 880, 990].forEach((freq, index) => this.tone(freq, 0.12, 'sine', 0.055, index * 0.06));
      return;
    }
    if (kind === 'craft') {
      this.tone(740, 0.08, 'sine', 0.04, 0, 990);
      this.tone(990, 0.1, 'sine', 0.035, 0.06, 1240);
      return;
    }
    if (kind === 'land') {
      this.tone(420, 0.08, 'sine', 0.04, 0, 620);
      return;
    }
    this.tone(620, 0.06, 'sine', 0.035, 0, 780);
    this.tone(780, 0.08, 'sine', 0.025, 0.05, 920);
  }

  npc() {
    [392, 494, 587].forEach((freq, index) => {
      this.tone(freq, 0.11, 'triangle', 0.045, index * 0.07);
    });
  }

  weapon(kind = 'star') {
    if (kind === 'bubble') {
      this.tone(680, 0.12, 'sine', 0.07, 0, 980);
      this.tone(920, 0.16, 'sine', 0.05, 0.05, 1240);
    } else if (kind === 'petal') {
      this.tone(520, 0.12, 'triangle', 0.06, 0, 780);
      this.noise(0.16, 2400, 0.025);
    } else if (kind === 'bow') {
      this.tone(880, 0.18, 'sine', 0.08, 0, 1320);
      this.noise(0.1, 3200, 0.03);
    } else if (kind === 'hammer') {
      this.tone(160, 0.18, 'triangle', 0.1, 0, 90);
      this.noise(0.18, 700, 0.06);
    } else {
      this.tone(740, 0.1, 'sine', 0.07, 0, 1040);
      this.tone(1100, 0.16, 'sine', 0.05, 0.05, 1500);
    }
  }

  pop() {
    this.tone(620, 0.06, 'sine', 0.045, 0, 940);
  }
}
