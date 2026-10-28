export function hashString(str) {
  let h = 2166136261 >>> 0;
  const s = String(str);
  for (let i = 0; i < s.length; i++) {
    h ^= s.charCodeAt(i);
    h = Math.imul(h, 16777619);
  }
  return h >>> 0;
}

export function mulberry32(a) {
  let t = a >>> 0;
  return function () {
    t += 0x6d2b79f5;
    let r = t;
    r = Math.imul(r ^ (r >>> 15), r | 1);
    r ^= r + Math.imul(r ^ (r >>> 7), r | 61);
    return ((r ^ (r >>> 14)) >>> 0) / 4294967296;
  };
}

export function hash2i(x, y, seed) {
  let h = seed ^ Math.imul(x | 0, 374761393) ^ Math.imul(y | 0, 668265263);
  h = Math.imul(h ^ (h >>> 13), 1274126177);
  return (h ^ (h >>> 16)) >>> 0;
}

export function hash3i(x, y, z, seed) {
  let h = seed ^ Math.imul(x | 0, 374761393) ^ Math.imul(y | 0, 668265263) ^ Math.imul(z | 0, 2246822519);
  h = Math.imul(h ^ (h >>> 13), 1274126177);
  return (h ^ (h >>> 16)) >>> 0;
}

export function randomAt(seed, x, y, z) {
  return hash3i(x, y, z, seed) / 4294967296;
}

function smooth(t) {
  return t * t * (3 - 2 * t);
}

export function noise2(seed, x, y) {
  const xi = Math.floor(x);
  const yi = Math.floor(y);
  const xf = x - xi;
  const yf = y - yi;
  const u = smooth(xf);
  const v = smooth(yf);
  const a = hash2i(xi, yi, seed) / 4294967295;
  const b = hash2i(xi + 1, yi, seed) / 4294967295;
  const c = hash2i(xi, yi + 1, seed) / 4294967295;
  const d = hash2i(xi + 1, yi + 1, seed) / 4294967295;
  const top = a + (b - a) * u;
  const bottom = c + (d - c) * u;
  return top + (bottom - top) * v;
}

export function fbm2(seed, x, y, octaves = 4, lacunarity = 2, gain = 0.5) {
  let amp = 1;
  let freq = 1;
  let sum = 0;
  let norm = 0;
  for (let i = 0; i < octaves; i++) {
    sum += noise2(seed + i * 1013, x * freq, y * freq) * amp;
    norm += amp;
    amp *= gain;
    freq *= lacunarity;
  }
  return sum / norm;
}

export function noise3(seed, x, y, z) {
  const xi = Math.floor(x);
  const yi = Math.floor(y);
  const zi = Math.floor(z);
  const xf = x - xi;
  const yf = y - yi;
  const zf = z - zi;
  const u = smooth(xf);
  const v = smooth(yf);
  const w = smooth(zf);
  const c000 = hash3i(xi, yi, zi, seed) / 4294967295;
  const c100 = hash3i(xi + 1, yi, zi, seed) / 4294967295;
  const c010 = hash3i(xi, yi + 1, zi, seed) / 4294967295;
  const c110 = hash3i(xi + 1, yi + 1, zi, seed) / 4294967295;
  const c001 = hash3i(xi, yi, zi + 1, seed) / 4294967295;
  const c101 = hash3i(xi + 1, yi, zi + 1, seed) / 4294967295;
  const c011 = hash3i(xi, yi + 1, zi + 1, seed) / 4294967295;
  const c111 = hash3i(xi + 1, yi + 1, zi + 1, seed) / 4294967295;
  const x00 = c000 + (c100 - c000) * u;
  const x10 = c010 + (c110 - c010) * u;
  const x01 = c001 + (c101 - c001) * u;
  const x11 = c011 + (c111 - c011) * u;
  const y0 = x00 + (x10 - x00) * v;
  const y1 = x01 + (x11 - x01) * v;
  return y0 + (y1 - y0) * w;
}

export function fbm3(seed, x, y, z, octaves = 3, lacunarity = 2, gain = 0.5) {
  let amp = 1;
  let freq = 1;
  let sum = 0;
  let norm = 0;
  for (let i = 0; i < octaves; i++) {
    sum += noise3(seed + i * 1409, x * freq, y * freq, z * freq) * amp;
    norm += amp;
    amp *= gain;
    freq *= lacunarity;
  }
  return sum / norm;
}

export function pick(rand, list) {
  return list[Math.floor(rand() * list.length)];
}
