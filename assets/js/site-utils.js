/**
 * Classic browser helpers (also used by unit tests via site-utils.mjs).
 */
(function (global) {
  function buildWhatsAppMessage({ nome = "", email = "", mensagem = "" } = {}) {
    return `Olá WV_Todoz! Meu nome é ${String(nome).trim()}. E-mail: ${String(email).trim()}.\n\n${String(mensagem).trim()}`;
  }

  function buildWhatsAppUrl(message, phone = "551155311779") {
    return `https://wa.me/${phone}?text=${encodeURIComponent(message)}`;
  }

  function getCaseDataFromElement(el) {
    if (!el) return null;
    const title = el.getAttribute("data-case-title") || "";
    if (!title) return null;
    return {
      title,
      category: el.getAttribute("data-case-category") || "",
      img: el.getAttribute("data-case-img") || "",
      desc: el.getAttribute("data-case-desc") || ""
    };
  }

  function shouldIgnoreCaseClick(target) {
    if (!target || typeof target.closest !== "function") return false;
    return Boolean(target.closest("a, .open-modal"));
  }

  global.WVSiteUtils = {
    buildWhatsAppMessage,
    buildWhatsAppUrl,
    getCaseDataFromElement,
    shouldIgnoreCaseClick
  };
})(window);
