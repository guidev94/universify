export const COMMUNITY_WORLDS = [
  {
    id: 'com-mel',
    name: 'Bosque de Mel',
    seed: 'comunidade-mel-317',
    mode: 'adventure',
    accent: '#ffd166',
    emoji: '✿',
    creator: 'Lia',
    likes: 214,
    desc: 'Trilhas doces, árvores de mel e esconderijos para explorar devagar.'
  },
  {
    id: 'com-nuvem',
    name: 'Cidade das Nuvens',
    seed: 'comunidade-nuvem-842',
    mode: 'creative',
    accent: '#7cd6ff',
    emoji: '☁',
    creator: 'Theo',
    likes: 189,
    desc: 'Ilhas flutuantes e blocos infinitos para construir no céu.'
  },
  {
    id: 'com-cristal',
    name: 'Caverna de Cristal',
    seed: 'comunidade-cristal-509',
    mode: 'adventure',
    accent: '#b07cff',
    emoji: '◆',
    creator: 'Nina',
    likes: 156,
    rareKit: true,
    desc: 'Um passeio brilhante com kit de raros para quem gosta de descobrir.'
  },
  {
    id: 'com-pomar',
    name: 'Pomar Arco-Íris',
    seed: 'comunidade-pomar-631',
    mode: 'adventure',
    accent: '#ffb3c7',
    emoji: '✦',
    creator: 'Bia',
    likes: 143,
    desc: 'Flores, frutas coloridas e um horizonte perfeito para fotos.'
  },
  {
    id: 'com-sol',
    name: 'Ateliê do Sol',
    seed: 'comunidade-sol-274',
    mode: 'creative',
    accent: '#ff8a6b',
    emoji: '☀',
    creator: 'Ravi',
    likes: 98,
    desc: 'Um mundo aberto e quente para grandes construções criativas.'
  },
  {
    id: 'com-luar',
    name: 'Ilha do Luar',
    seed: 'comunidade-luar-408',
    mode: 'adventure',
    accent: '#8ad7ff',
    emoji: '☾',
    creator: 'Maya',
    likes: 87,
    rareKit: true,
    desc: 'Noite calma, cogumelos estrelados e tesouros escondidos.'
  }
];

export function getCommunityWorld(id) {
  return COMMUNITY_WORLDS.find((entry) => entry.id === id) || null;
}
