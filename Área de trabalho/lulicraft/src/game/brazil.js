export const BRAZIL_WORLDS = [
  {
    id: 'br-rio',
    name: 'Rio de Lumi',
    city: 'Rio de Janeiro',
    state: 'RJ',
    seed: 'brasil-rio-2026',
    mode: 'adventure',
    accent: '#7cd6ff',
    emoji: '⛰',
    creator: 'Brasil',
    likes: 312,
    rareKit: true,
    desc: 'Praias, morros e um pôr do sol que deixa o mar cor de aurora.'
  },
  {
    id: 'br-sp',
    name: 'Avenida das Cores',
    city: 'São Paulo',
    state: 'SP',
    seed: 'brasil-sp-2026',
    mode: 'creative',
    accent: '#ff8a6b',
    emoji: '▦',
    creator: 'Brasil',
    likes: 287,
    desc: 'Uma metrópole criativa para construir prédios, praças e murais.'
  },
  {
    id: 'br-salvador',
    name: 'Pelourinho de Blocos',
    city: 'Salvador',
    state: 'BA',
    seed: 'brasil-salvador-2026',
    mode: 'adventure',
    accent: '#ffc94d',
    emoji: '♫',
    creator: 'Brasil',
    likes: 241,
    desc: 'Casinhas coloridas, música no ar e tesouros escondidos nas ladeiras.'
  },
  {
    id: 'br-recife',
    name: 'Pontes do Recife',
    city: 'Recife',
    state: 'PE',
    seed: 'brasil-recife-2026',
    mode: 'adventure',
    accent: '#5fd38a',
    emoji: '≈',
    creator: 'Brasil',
    likes: 198,
    desc: 'Rios, pontes e manguezais para explorar com calma e curiosidade.'
  },
  {
    id: 'br-manaus',
    name: 'Amazônia Encantada',
    city: 'Manaus',
    state: 'AM',
    seed: 'brasil-manaus-2026',
    mode: 'adventure',
    accent: '#b8ff6a',
    emoji: '❧',
    creator: 'Brasil',
    likes: 176,
    rareKit: true,
    desc: 'Floresta densa, rio largo e cogumelos estelares entre as árvores.'
  },
  {
    id: 'br-floripa',
    name: 'Ilha da Magia',
    city: 'Florianópolis',
    state: 'SC',
    seed: 'brasil-floripa-2026',
    mode: 'creative',
    accent: '#ffb3c7',
    emoji: '☼',
    creator: 'Brasil',
    likes: 154,
    desc: 'Praias, dunas e um cantinho tranquilo para construir sua casa.'
  }
];

export function getBrazilWorld(id) {
  return BRAZIL_WORLDS.find((entry) => entry.id === id) || null;
}
