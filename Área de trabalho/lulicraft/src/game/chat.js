export const CHAT_PHRASES = Object.freeze([
  { id: 'hello', glyph: '✦', text: 'Oi! Vamos explorar?' },
  { id: 'follow', glyph: '✦', text: 'Me segue!' },
  { id: 'wait', glyph: '◇', text: 'Espera um pouquinho.' },
  { id: 'help', glyph: '♥', text: 'Preciso de ajuda aqui.' },
  { id: 'thanks', glyph: '✿', text: 'Obrigado!' },
  { id: 'pretty', glyph: '✿', text: 'Que lugar lindo!' },
  { id: 'build', glyph: '⌂', text: 'Vamos construir juntos?' },
  { id: 'found', glyph: '✧', text: 'Achei algo raro!' },
  { id: 'pet', glyph: '◆', text: 'Meu pet é demais!' },
  { id: 'night', glyph: '☾', text: 'Boa noite!' },
  { id: 'bye', glyph: '✦', text: 'Até logo!' },
  { id: 'cheer', glyph: '★', text: 'Você consegue!' }
]);

const CHAT_IDS = new Set(CHAT_PHRASES.map((phrase) => phrase.id));

export const CHAT_RATE_MS = 1500;
export const HOST_CHAT_RATE_MS = 2000;

export function sanitizeChat(value) {
  if (typeof value !== 'string') return null;
  const id = value.trim().toLowerCase().slice(0, 24);
  return CHAT_IDS.has(id) ? id : null;
}

export function getChatPhrase(value) {
  const id = sanitizeChat(value);
  if (!id) return null;
  return CHAT_PHRASES.find((phrase) => phrase.id === id) || null;
}
