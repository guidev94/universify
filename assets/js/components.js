// Button hover uses CSS only — GSAP transform fights scale/icon animations
const initMagneticButtons = () => {};

// Spotlight effect
const initSpotlightEffect = () => {
    const bentoItems = document.querySelectorAll(".bento-item, .case-item, .service-card, .produto-card");

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
    if (!modal) return;
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
    gsap.killTweensOf([modal, panel].filter(Boolean));

    const finish = () => {
        modal.classList.remove("is-open");
        modal.setAttribute("aria-hidden", "true");
        gsap.set(modal, { clearProps: "opacity" });
        if (panel) gsap.set(panel, { clearProps: "opacity,transform" });
        unlockPageScroll();
    };

    const tl = gsap.timeline({ onComplete: finish });
    if (panel) tl.to(panel, { y: 30, opacity: 0, duration: 0.25, ease: "power2.in" }, 0);
    tl.to(modal, { opacity: 0, duration: 0.3, ease: "power2.in" }, panel ? 0.05 : 0);
};

// Contact Modal Logic
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

    modal.querySelector("[data-modal-backdrop]")?.addEventListener("click", closeModal);

    window.openContactModal = openModal;
    window.closeContactModal = closeModal;

    const form = document.querySelector("#contact-form");
    if (form) {
        form.addEventListener("submit", (e) => {
            e.preventDefault();
            const data = new FormData(form);
            const nome = (data.get("nome") || "").toString().trim();
            const email = (data.get("email") || "").toString().trim();
            const messageBody = (data.get("mensagem") || "").toString().trim();
            const message = `Olá WV_Todoz! Meu nome é ${nome}. E-mail: ${email}.\n\n${messageBody}`;
            window.open(`https://wa.me/551155311779?text=${encodeURIComponent(message)}`, "_blank", "noopener,noreferrer");
            closeModal();
            form.reset();
        });
    }
};

// Case Modal Logic
const initCaseModal = () => {
    const modal = document.querySelector("#case-modal");
    if (!modal) return;

    const closeBtn = document.querySelector("#close-case-modal");
    const caseItems = document.querySelectorAll(".open-case");
    const similarBtn = modal.querySelector(".open-modal-from-case");

    const openCase = (data) => {
        document.querySelector("#case-modal-img").src = data.img || "";
        document.querySelector("#case-modal-title").textContent = data.title || "";
        document.querySelector("#case-modal-category").textContent = data.category || "";
        document.querySelector("#case-modal-desc").textContent = data.desc || "";
        openSiteModal(modal);
    };

    const closeModal = () => closeSiteModal(modal);

    caseItems.forEach((item) => {
        item.addEventListener("click", (e) => {
            if (e.target.closest("a, .open-modal")) return;
            if (item.dataset.dragged === "1") {
                item.dataset.dragged = "0";
                return;
            }
            openCase({
                title: item.getAttribute("data-case-title"),
                category: item.getAttribute("data-case-category"),
                img: item.getAttribute("data-case-img"),
                desc: item.getAttribute("data-case-desc")
            });
        });
    });

    if (closeBtn) closeBtn.addEventListener("click", closeModal);
    modal.querySelector("[data-modal-backdrop]")?.addEventListener("click", closeModal);

    if (similarBtn) {
        similarBtn.addEventListener("click", (e) => {
            e.preventDefault();
            e.stopPropagation();
            closeModal();
            window.setTimeout(() => window.openContactModal?.(), 320);
        });
    }
};

// Mobile Menu Logic
const initMobileMenu = () => {
    const trigger = document.querySelector("#mobile-menu-trigger");
    const menu = document.querySelector("#mobile-menu");
    if (!menu) return;
    const menuContent = document.querySelector("#mobile-menu-content");
    const closeBtn = document.querySelector("#mobile-menu-close");
    const links = menu.querySelectorAll("nav a");

    const openMenu = () => {
        menu.classList.remove("invisible");
        gsap.to(menu, { opacity: 1, duration: 0.3, pointerEvents: "auto" });
        gsap.to(menuContent, { x: 0, duration: 0.6, ease: "expo.out" });
        lockPageScroll();
    };

    const closeMenu = () => {
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

window.addEventListener("DOMContentLoaded", () => {
    initMagneticButtons();
    initSpotlightEffect();
    initContactModal();
    initCaseModal();
    initMobileMenu();
});
