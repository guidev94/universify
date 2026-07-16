// Classic components (no ES modules) — uses window.WVSiteUtils + window.gsap
const gsap = window.gsap;
const utils = window.WVSiteUtils || {};

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
    if (!modal) return;
    const panel = modal.querySelector("[data-modal-panel]");
    if (gsap) gsap.killTweensOf([modal, panel].filter(Boolean));

    modal.classList.add("is-open");
    modal.setAttribute("aria-hidden", "false");
    lockPageScroll();

    if (!gsap) return;
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

const getUtmParam = (key) => {
    try {
        return new URLSearchParams(window.location.search).get(key) || "";
    } catch (e) {
        return "";
    }
};

const fillLeadContext = (btn) => {
    const typeInput = document.querySelector("#wv-lead-type");
    const sectionInput = document.querySelector("#wv-lead-section");
    const pageInput = document.querySelector("#wv-lead-source-page");
    const titleInput = document.querySelector("#wv-lead-source-title");
    const utmSource = document.querySelector("#wv-lead-utm-source");
    const utmMedium = document.querySelector("#wv-lead-utm-medium");
    const utmCampaign = document.querySelector("#wv-lead-utm-campaign");

    const type = btn?.getAttribute?.("data-wv-lead-type") || "contato";
    const section = btn?.getAttribute?.("data-wv-section") || "";
    if (typeInput) typeInput.value = type;
    if (sectionInput) sectionInput.value = section;
    if (pageInput) pageInput.value = window.location.href;
    if (titleInput) titleInput.value = document.title || "";
    if (utmSource) utmSource.value = getUtmParam("utm_source");
    if (utmMedium) utmMedium.value = getUtmParam("utm_medium");
    if (utmCampaign) utmCampaign.value = getUtmParam("utm_campaign");
};

const initContactModal = () => {
    const modal = document.querySelector("#contact-modal");
    if (!modal) return;

    const openBtns = document.querySelectorAll("button.open-modal, a.open-modal");
    const closeBtn = document.querySelector("#close-modal");
    const openModal = (btn) => {
        fillLeadContext(btn);
        openSiteModal(modal);
    };
    const closeModal = () => closeSiteModal(modal);

    openBtns.forEach((btn) => {
        btn.addEventListener("click", (e) => {
            e.preventDefault();
            e.stopPropagation();
            const caseModal = document.querySelector("#case-modal");
            if (caseModal?.classList.contains("is-open")) {
                closeSiteModal(caseModal);
                window.setTimeout(() => openModal(btn), 340);
                return;
            }
            openModal(btn);
        });
    });

    if (closeBtn) closeBtn.addEventListener("click", closeModal);
    modal.addEventListener("click", (e) => {
        if (e.target === modal || e.target?.hasAttribute?.("data-modal-backdrop")) closeModal();
    });

    window.openContactModal = (btn) => openModal(btn || null);
    window.closeContactModal = closeModal;

    const form = document.querySelector("#contact-form");
    if (form) {
        form.addEventListener("submit", async (e) => {
            e.preventDefault();
            const data = new FormData(form);
            const feedback = document.querySelector("#wv-lead-feedback");
            const submitBtn = form.querySelector('[type="submit"]');
            const cfg = window.wvLeads || {};
            const labelDefault = submitBtn?.getAttribute("data-wv-submit-label") || submitBtn?.textContent || "Enviar";

            if (feedback) {
                feedback.hidden = false;
                feedback.textContent = "";
            }
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = (cfg.i18n && cfg.i18n.sending) || "Enviando…";
            }

            let saved = false;
            if (cfg.ajaxUrl && cfg.nonce) {
                data.append("action", cfg.action || "wv_submit_lead");
                data.append("nonce", cfg.nonce);
                try {
                    const res = await fetch(cfg.ajaxUrl, {
                        method: "POST",
                        body: data,
                        credentials: "same-origin"
                    });
                    const json = await res.json();
                    saved = Boolean(json && json.success);
                    if (feedback) {
                        feedback.textContent = saved
                            ? (json.data?.message || cfg.i18n?.success || "Enviado.")
                            : (json.data?.message || cfg.i18n?.error || "Erro ao enviar.");
                        feedback.style.color = saved ? "" : "#f87171";
                    }
                } catch (err) {
                    if (feedback) {
                        feedback.textContent = (cfg.i18n && cfg.i18n.error) || "Erro ao enviar.";
                        feedback.style.color = "#f87171";
                    }
                }
            }

            const buildMsg = utils.buildWhatsAppMessage || (({ nome, email, mensagem }) =>
                `Olá WV_Todoz! Meu nome é ${nome}. E-mail: ${email}.\n\n${mensagem}`);
            const buildUrl = utils.buildWhatsAppUrl || ((message, phone) =>
                `https://wa.me/${phone || "551155311779"}?text=${encodeURIComponent(message)}`);
            const shouldWa = cfg.openWhatsApp !== false;
            if (shouldWa && (saved || !cfg.ajaxUrl)) {
                const message = buildMsg({
                    nome: data.get("nome"),
                    email: data.get("email"),
                    mensagem: data.get("mensagem")
                });
                window.open(buildUrl(message, cfg.whatsappPhone), "_blank", "noopener,noreferrer");
            }

            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = labelDefault;
            }

            if (saved || !cfg.ajaxUrl) {
                window.setTimeout(() => {
                    closeModal();
                    form.reset();
                    if (feedback) {
                        feedback.hidden = true;
                        feedback.textContent = "";
                    }
                    fillLeadContext(null);
                }, saved ? 700 : 0);
            }
        });
    }
};

const initCaseModal = () => {
    const modal = document.querySelector("#case-modal");
    if (!modal) return;

    const closeBtn = document.querySelector("#close-case-modal");
    const caseItems = document.querySelectorAll(".open-case");
    const similarBtn = modal.querySelector(".open-modal-from-case");
    const getCase = utils.getCaseDataFromElement || ((el) => ({
        title: el.getAttribute("data-case-title"),
        category: el.getAttribute("data-case-category"),
        img: el.getAttribute("data-case-img"),
        desc: el.getAttribute("data-case-desc")
    }));
    const ignoreClick = utils.shouldIgnoreCaseClick || ((t) => Boolean(t.closest?.("a, .open-modal")));

    const openCase = (data) => {
        if (!data?.title) return;
        document.querySelector("#case-modal-img").src = data.img || "";
        document.querySelector("#case-modal-title").textContent = data.title || "";
        document.querySelector("#case-modal-category").textContent = data.category || "";
        document.querySelector("#case-modal-desc").textContent = data.desc || "";
        openSiteModal(modal);
    };

    caseItems.forEach((item) => {
        item.addEventListener("click", (e) => {
            if (ignoreClick(e.target)) return;
            if (item.dataset.dragged === "1") {
                item.dataset.dragged = "0";
                return;
            }
            openCase(getCase(item));
        });
    });

    if (closeBtn) closeBtn.addEventListener("click", () => closeSiteModal(modal));
    modal.addEventListener("click", (e) => {
        if (e.target === modal || e.target?.hasAttribute?.("data-modal-backdrop")) closeSiteModal(modal);
    });

    if (similarBtn) {
        similarBtn.addEventListener("click", (e) => {
            e.preventDefault();
            e.stopPropagation();
            closeSiteModal(modal);
            window.setTimeout(() => window.openContactModal?.(similarBtn), 320);
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
    const dismissEls = menu.querySelectorAll("[data-mobile-menu-dismiss]");
    let closing = false;

    const setOpenState = (open) => {
        menu.classList.toggle("is-open", open);
        menu.setAttribute("aria-hidden", open ? "false" : "true");
        if (trigger) {
            trigger.setAttribute("aria-expanded", open ? "true" : "false");
            trigger.setAttribute("aria-label", open ? "Fechar menu" : "Abrir menu");
        }
    };

    const openMenu = () => {
        if (closing || menu.classList.contains("is-open")) return;
        menu.classList.remove("invisible");
        setOpenState(true);
        if (gsap) {
            gsap.killTweensOf([menu, menuContent].filter(Boolean));
            gsap.to(menu, { opacity: 1, duration: 0.3, pointerEvents: "auto" });
            gsap.to(menuContent, { x: 0, duration: 0.55, ease: "expo.out" });
        } else {
            menu.style.opacity = "1";
            menu.style.pointerEvents = "auto";
            if (menuContent) menuContent.style.transform = "translateX(0)";
        }
        lockPageScroll();
    };

    const closeMenu = (onDone) => {
        if (closing) return;
        if (!menu.classList.contains("is-open")) {
            if (typeof onDone === "function") onDone();
            return;
        }
        closing = true;
        setOpenState(false);

        const finish = () => {
            menu.classList.add("invisible");
            menu.style.pointerEvents = "none";
            unlockPageScroll();
            closing = false;
            if (typeof onDone === "function") onDone();
        };

        if (gsap) {
            gsap.killTweensOf([menu, menuContent].filter(Boolean));
            gsap.to(menuContent, { x: "100%", duration: 0.4, ease: "expo.in" });
            gsap.to(menu, {
                opacity: 0,
                duration: 0.3,
                delay: 0.12,
                pointerEvents: "none",
                onComplete: finish
            });
        } else {
            if (menuContent) menuContent.style.transform = "translateX(100%)";
            menu.style.opacity = "0";
            finish();
        }
    };

    const resolveHash = (href) => {
        if (!href) return "";
        if (href.startsWith("#")) return href;
        try {
            const url = new URL(href, window.location.href);
            const here = window.location.pathname.replace(/\/$/, "") || "/";
            const there = url.pathname.replace(/\/$/, "") || "/";
            const samePage = there === here || /(?:^|\/)index\.html?$/.test(url.pathname);
            return samePage ? url.hash || "" : "";
        } catch (_) {
            return "";
        }
    };

    const scrollToHash = (hash) => {
        const target = document.querySelector(hash);
        if (!target) return;
        const header = document.querySelector("#main-header");
        const offset = header ? -Math.ceil(header.getBoundingClientRect().height + 12) : -88;
        const lenis = getLenis();
        if (lenis) {
            lenis.start();
            lenis.scrollTo(target, { offset, immediate: false });
        } else {
            const top = target.getBoundingClientRect().top + window.pageYOffset + offset;
            window.scrollTo({ top, behavior: "smooth" });
        }
        try {
            history.pushState(null, "", hash);
        } catch (_) {
            /* ignore */
        }
    };

    if (trigger) {
        trigger.setAttribute("aria-controls", "mobile-menu");
        trigger.setAttribute("aria-expanded", "false");
        trigger.addEventListener("click", openMenu);
    }
    if (closeBtn) closeBtn.addEventListener("click", () => closeMenu());
    dismissEls.forEach((el) => el.addEventListener("click", () => closeMenu()));

    links.forEach((link) => {
        link.addEventListener("click", (e) => {
            const hash = resolveHash(link.getAttribute("href"));
            if (hash && hash.length > 1 && document.querySelector(hash)) {
                e.preventDefault();
                closeMenu(() => scrollToHash(hash));
                return;
            }
            closeMenu();
        });
    });

    menu.addEventListener("click", (e) => {
        if (e.target === menu) closeMenu();
    });

    window.addEventListener("keydown", (e) => {
        if (e.key === "Escape" && menu.classList.contains("is-open")) closeMenu();
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
    window.__wvBooted = true;
});
