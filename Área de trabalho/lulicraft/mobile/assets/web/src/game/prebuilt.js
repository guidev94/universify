export const PREBUILT_WORLDS = [
  {
    id: 'jardim',
    name: 'Jardim de Lumi',
    seed: 'lumi-jardim',
    mode: 'adventure',
    accent: '#b8ff6a',
    emoji: '✦',
    desc: 'Campos suaves, árvores de mel e um lugar seguro para começar.',
    rareKit: false
  },
  {
    id: 'arcoiris',
    name: 'Vale Arco-Íris',
    seed: 'lumi-arcoiris',
    mode: 'adventure',
    accent: '#ffb3c7',
    emoji: '◆',
    desc: 'Bosques coloridos, flores raras e trilhas cheias de surpresa.',
    rareKit: false
  },
  {
    id: 'nuvens',
    name: 'Ilha das Nuvens',
    seed: 'lumi-nuvens',
    mode: 'creative',
    accent: '#7cd6ff',
    emoji: '☁',
    desc: 'Blocos infinitos e voo livre para construir sem limites.',
    rareKit: false
  },
  {
    id: 'coral',
    name: 'Deserto Coral',
    seed: 'lumi-coral',
    mode: 'adventure',
    accent: '#ff8a6b',
    emoji: '☀',
    desc: 'Dunas quentes, cristais escondidos e um horizonte enorme.',
    rareKit: false
  },
  {
    id: 'aurora',
    name: 'Noite de Aurora',
    seed: 'lumi-aurora',
    mode: 'creative',
    accent: '#b07cff',
    emoji: '★',
    desc: 'Um céu mágico para grandes construções e fotos.',
    rareKit: true
  },
  {
    id: 'raros',
    name: 'Caixa de Raros',
    seed: 'lumi-raros',
    mode: 'adventure',
    accent: '#ff7ad9',
    emoji: '✧',
    desc: 'Comece com um kit lendário: ferramentas, sementes e tesouros raros.',
    rareKit: true
  },
  {
    id: 'minha-casa',
    name: 'Minha Casa',
    seed: 'lumi-minha-casa',
    mode: 'creative',
    accent: '#ffa8c8',
    emoji: '⌂',
    desc: 'Uma casa rosa, uma cidade inteira e baús cheios de raridades.',
    rareKit: true
  }
];

export function getPrebuilt(id) {
  return PREBUILT_WORLDS.find((entry) => entry.id === id) || null;
}
