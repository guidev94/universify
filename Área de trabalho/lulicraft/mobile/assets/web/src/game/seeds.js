const SEED_WORDS_A = ['lumi', 'aurora', 'coral', 'musgo', 'nuvem', 'prisma', 'mel', 'estrela', 'jardim', 'orvalho'];
const SEED_WORDS_B = ['feliz', 'doce', 'brilhante', 'sereno', 'magico', 'tranquilo', 'colorido', 'secreto'];

function pick(list) {
  return list[Math.floor(Math.random() * list.length)];
}

export function randomSeed() {
  return `${pick(SEED_WORDS_A)}-${pick(SEED_WORDS_B)}-${Math.floor(Math.random() * 900 + 100)}`;
}

export function randomWorldName(seed = randomSeed()) {
  const first = String(seed).split('-')[0] || 'lumi';
  return `Mundo ${first.charAt(0).toUpperCase()}${first.slice(1)}`;
}
