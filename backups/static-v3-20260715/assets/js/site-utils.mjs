/**
 * Pure helpers — shared by browser (window.WVSiteUtils) and unit tests.
 */

export const REQUIRED_SECTION_IDS = [
  "sobre",
  "servicos",
  "portfolio",
  "produtos",
  "blog",
  "clientes",
  "aplicacoes"
];

export const NAV_HASHES = ["#sobre", "#servicos", "#produtos", "#aplicacoes", "#portfolio", "#blog"];

export function buildWhatsAppMessage({ nome = "", email = "", mensagem = "" } = {}) {
  const safeNome = String(nome).trim();
  const safeEmail = String(email).trim();
  const safeMsg = String(mensagem).trim();
  return `Olá WV_Todoz! Meu nome é ${safeNome}. E-mail: ${safeEmail}.\n\n${safeMsg}`;
}

export function buildWhatsAppUrl(message, phone = "551155311779") {
  return `https://wa.me/${phone}?text=${encodeURIComponent(message)}`;
}

export function getCaseDataFromElement(el) {
  if (!el) return null;
  const title = el.getAttribute?.("data-case-title") || el.dataset?.caseTitle || "";
  if (!title) return null;
  return {
    title,
    category: el.getAttribute?.("data-case-category") || el.dataset?.caseCategory || "",
    img: el.getAttribute?.("data-case-img") || el.dataset?.caseImg || "",
    desc: el.getAttribute?.("data-case-desc") || el.dataset?.caseDesc || ""
  };
}

export function isModalOpen(modal) {
  return Boolean(modal?.classList?.contains("is-open"));
}

export function shouldIgnoreCaseClick(target) {
  if (!target || typeof target.closest !== "function") return false;
  return Boolean(target.closest("a, .open-modal"));
}

export function heroLogosOnSingleRow(containerWidth, logoWidths, gap) {
  if (!logoWidths?.length) return false;
  const gaps = Math.max(0, logoWidths.length - 1) * gap;
  const total = logoWidths.reduce((a, b) => a + b, 0) + gaps;
  return total <= containerWidth + 0.5;
}

/** Attach for classic script usage */
export function attachToGlobal(globalObj = globalThis) {
  globalObj.WVSiteUtils = {
    REQUIRED_SECTION_IDS,
    NAV_HASHES,
    buildWhatsAppMessage,
    buildWhatsAppUrl,
    getCaseDataFromElement,
    isModalOpen,
    shouldIgnoreCaseClick,
    heroLogosOnSingleRow
  };
  return globalObj.WVSiteUtils;
}
