import {
  buildWhatsAppMessage,
  buildWhatsAppUrl,
  getCaseDataFromElement,
  shouldIgnoreCaseClick
} from "./site-utils.mjs";

const gsap = window.gsap;

const initMagneticButtons = () => {};

const initSpotlightEffect = () => {
  const bentoItems = document.querySelectorAll(
    ".bento-item, .case-item, .service-card, .produto-card"
  );

  bentoItems.forEach((item) => {
    const media = item.querySelector(".case-media") || item;
    item.addEventListener("mousemove", (e) => {
      const rect = media.getBoundingClientRect();
      media.style.setProperty("--mouse-x", `${e.clientX - rect.left}px`);
      media.style.setProperty("--mouse-y", `${e.clientY - rect.top}px`);
    });
  });
};

const getLenis = () => window.lenis || null;

const lockPageScroll = () => {
  const lenis = getLenis();
  if (lenis) lenis.stop();
  document.documentElement.classList.add("modal-open");
  document.body.classList.add("modal-open");
};

const unlockPageScroll = () => {
  const anyOpen = document.querySelector(".site-modal.is-open");
  if (anyOpen) return;
  const lenis = getLenis();
  if (lenis) lenis.start();
  document.documentElement.classList.remove("modal-open");
  document.body.classList.remove("modal-open");
};

const openSiteModal = (modal) => {
  if (!modal || !gsap) {
    modal?.classList.add("is-open");
    return;
  }
  const panel = modal.querySelector("[data-modal-panel]");
  gsap.killTweensOf([modal, panel].filter(Boolean));

  modal.classList.add("is-open");
  modal.setAttribute("aria-hidden", "false");
  lockPageScroll();

  gsap.fromTo(modal, { opacity: 0 }, { opacity: 1, duration: 0.35, ease: "power2.out" });
  if (panel) {
    gsap.fromTo(
      panel,
      { y: 40, opacity: 0 },
      { y: 0, opacity: 1, duration: 0.5, ease: "power4.out", delay: 0.05 }
    );
  }
};

const closeSiteModal = (modal) => {
  if (!modal || !modal.classList.contains("is-open")) return;
  const panel = modal.querySelector("[data-modal-panel]");

  const finish = () => {
    modal.classList.remove("is-open");
    modal.setAttribute("aria-hidden", "true");
    if (gsap) {
      gsap.set(modal, { clearProps: "opacity" });
      if (panel) gsap.set(panel, { clearProps: "opacity,transform" });
    }
    unlockPageScroll();
  };

  if (!gsap) {
    finish();
    return;
  }

  gsap.killTweensOf([modal, panel].filter(Boolean));
  const tl = gsap.timeline({ onComplete: finish });
  if (panel) tl.to(panel, { y: 30, opacity: 0, duration: 0.25, ease: "power2.in" }, 0);
  tl.to(modal, { opacity: 0, duration: 0.3, ease: "power2.in" }, panel ? 0.05 : 0);
};

const initContactModal = () => {
  const modal = document.querySelector("#contact-modal");
  if (!modal) return;

  const openBtns = document.querySelectorAll(".open-modal");
  const closeBtn = document.querySelector("#close-modal");

  const openModal = () => openSiteModal(modal);
  const closeModal = () => closeSiteModal(modal);

  openBtns.forEach((btn) => {
    btn.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();
      const caseModal = document.querySelector("#case-modal");
      if (caseModal?.classList.contains("is-open")) {
        closeSiteModal(caseModal);
        window.setTimeout(openModal, 340);
        return;
      }
      openModal();
    });
  });

  if (closeBtn) closeBtn.addEventListener("click", closeModal);
  modal.addEventListener("click", (e) => {
    if (e.target === modal || e.target?.hasAttribute?.("data-modal-backdrop")) {
      closeModal();
    }
  });

  window.openContactModal = openModal;
  window.closeContactModal = closeModal;
  window.closeSiteModal = closeSiteModal;

  const form = document.querySelector("#contact-form");
  if (form) {
    form.addEventListener("submit", (e) => {
      e.preventDefault();
      const data = new FormData(form);
      const message = buildWhatsAppMessage({
        nome: data.get("nome"),
        email: data.get("email"),
        mensagem: data.get("mensagem")
      });
      window.open(buildWhatsAppUrl(message), "_blank", "noopener,noreferrer");
      closeModal();
      form.reset();
    });
  }
};

const initCaseModal = () => {
  const modal = document.querySelector("#case-modal");
  if (!modal) return;

  const closeBtn = document.querySelector("#close-case-modal");
  const caseItems = document.querySelectorAll(".open-case");
  const similarBtn = modal.querySelector(".open-modal-from-case");

  const openCase = (data) => {
    if (!data) return;
    document.querySelector("#case-modal-img").src = data.img || "";
    document.querySelector("#case-modal-title").textContent = data.title || "";
    document.querySelector("#case-modal-category").textContent = data.category || "";
    document.querySelector("#case-modal-desc").textContent = data.desc || "";
    openSiteModal(modal);
  };

  const closeModal = () => closeSiteModal(modal);

  caseItems.forEach((item) => {
    item.addEventListener("click", (e) => {
      if (shouldIgnoreCaseClick(e.target)) return;
      if (item.dataset.dragged === "1") {
        item.dataset.dragged = "0";
        return;
      }
      openCase(getCaseDataFromElement(item));
    });
  });

  if (closeBtn) closeBtn.addEventListener("click", closeModal);
  modal.addEventListener("click", (e) => {
    if (e.target === modal || e.target?.hasAttribute?.("data-modal-backdrop")) {
      closeModal();
    }
  });

  if (similarBtn) {
    similarBtn.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();
      closeModal();
      window.setTimeout(() => window.openContactModal?.(), 320);
    });
  }
};

const initMobileMenu = () => {
  const trigger = document.querySelector("#mobile-menu-trigger");
  const menu = document.querySelector("#mobile-menu");
  if (!menu) return;
  const menuContent = document.querySelector("#mobile-menu-content");
  const closeBtn = document.querySelector("#mobile-menu-close");
  const links = menu.querySelectorAll("nav a");

  const openMenu = () => {
    menu.classList.remove("invisible");
    if (gsap) {
      gsap.to(menu, { opacity: 1, duration: 0.3, pointerEvents: "auto" });
      gsap.to(menuContent, { x: 0, duration: 0.6, ease: "expo.out" });
    } else {
      menu.style.opacity = "1";
      menu.style.pointerEvents = "auto";
      if (menuContent) menuContent.style.transform = "translateX(0)";
    }
    lockPageScroll();
  };

  const closeMenu = () => {
    if (gsap) {
      gsap.to(menuContent, { x: "100%", duration: 0.5, ease: "expo.in" });
      gsap.to(menu, {
        opacity: 0,
        duration: 0.4,
        delay: 0.2,
        onComplete: () => {
          menu.classList.add("invisible");
          unlockPageScroll();
        }
      });
    } else {
      if (menuContent) menuContent.style.transform = "translateX(100%)";
      menu.style.opacity = "0";
      menu.classList.add("invisible");
      unlockPageScroll();
    }
  };

  if (trigger) trigger.addEventListener("click", openMenu);
  if (closeBtn) closeBtn.addEventListener("click", closeMenu);
  links.forEach((link) => link.addEventListener("click", closeMenu));
  menu.addEventListener("click", (e) => {
    if (e.target === menu) closeMenu();
  });
};

window.addEventListener("keydown", (e) => {
  if (e.key !== "Escape") return;
  closeSiteModal(document.querySelector("#contact-modal.is-open"));
  closeSiteModal(document.querySelector("#case-modal.is-open"));
});

const boot = () => {
  initMagneticButtons();
  initSpotlightEffect();
  initContactModal();
  initCaseModal();
  initMobileMenu();
  window.__wvBooted = true;
};

if (document.readyState === "loading") {
  window.addEventListener("DOMContentLoaded", boot);
} else {
  boot();
}
