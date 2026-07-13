// Button hover uses CSS only — GSAP transform fights scale/icon animations
const initMagneticButtons = () => {
    // Keep API for init order; hover is handled by .btn-anim CSS
};

// Spotlight effect
const initSpotlightEffect = () => {
    const bentoItems = document.querySelectorAll('.bento-item, .case-item, .service-card, .produto-card');
    
    bentoItems.forEach(item => {
        const media = item.querySelector('.case-media') || item;
        item.addEventListener('mousemove', e => {
            const rect = media.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            media.style.setProperty('--mouse-x', `${x}px`);
            media.style.setProperty('--mouse-y', `${y}px`);
        });
    });
};

// Contact Modal Logic
const initContactModal = () => {
    const modal = document.querySelector("#contact-modal");
    if (!modal) return;
    const modalContent = modal.querySelector(".relative");
    const openBtns = document.querySelectorAll(".open-modal");
    const closeBtn = document.querySelector("#close-modal");

    const openModal = () => {
        modal.classList.remove("pointer-events-none");
        gsap.to(modal, { opacity: 1, duration: 0.4 });
        gsap.fromTo(modalContent, 
            { y: 50, opacity: 0 },
            { y: 0, opacity: 1, duration: 0.6, ease: "power4.out", delay: 0.2 }
        );
        document.body.style.overflow = "hidden";
    };

    const closeModal = () => {
        gsap.to(modalContent, { y: 50, opacity: 0, duration: 0.4, ease: "power4.in" });
        gsap.to(modal, { 
            opacity: 0, 
            duration: 0.4, 
            delay: 0.2,
            onComplete: () => {
                modal.classList.add("pointer-events-none");
                document.body.style.overflow = "";
            }
        });
    };

    openBtns.forEach(btn => btn.addEventListener("click", (e) => {
        e.preventDefault();
        e.stopPropagation();
        openModal();
    }));
    
    if (closeBtn) closeBtn.addEventListener("click", closeModal);
    
    modal.addEventListener("click", (e) => {
        if (e.target.classList.contains("absolute") && e.target.classList.contains("inset-0")) closeModal();
    });

    window.addEventListener("keydown", (e) => {
        if (e.key === "Escape") closeModal();
    });

    window.openContactModal = openModal;

    // Contact form → WhatsApp with filled message
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
    const modalContent = modal.querySelector(".relative");
    const closeBtn = document.querySelector("#close-case-modal");
    const caseItems = document.querySelectorAll(".open-case");
    const similarBtn = modal.querySelector(".open-modal-from-case");

    const openCase = (data) => {
        document.querySelector("#case-modal-img").src = data.img;
        document.querySelector("#case-modal-title").textContent = data.title;
        document.querySelector("#case-modal-category").textContent = data.category;
        document.querySelector("#case-modal-desc").textContent = data.desc;

        modal.classList.remove("pointer-events-none");
        gsap.to(modal, { opacity: 1, duration: 0.4 });
        gsap.fromTo(modalContent, 
            { y: 50, opacity: 0 },
            { y: 0, opacity: 1, duration: 0.6, ease: "power4.out", delay: 0.2 }
        );
        document.body.style.overflow = "hidden";
    };

    const closeModal = () => {
        gsap.to(modalContent, { y: 50, opacity: 0, duration: 0.4, ease: "power4.in" });
        gsap.to(modal, { 
            opacity: 0, 
            duration: 0.4, 
            delay: 0.2,
            onComplete: () => {
                modal.classList.add("pointer-events-none");
                document.body.style.overflow = "";
            }
        });
    };

    caseItems.forEach(item => {
        item.addEventListener("click", (e) => {
            if (e.target.closest('.open-modal')) return;
            const data = {
                title: item.getAttribute('data-case-title'),
                category: item.getAttribute('data-case-category'),
                img: item.getAttribute('data-case-img'),
                desc: item.getAttribute('data-case-desc')
            };
            if (data.title) {
                openCase(data);
            }
        });
    });

    if (closeBtn) closeBtn.addEventListener("click", closeModal);

    if (similarBtn) {
        similarBtn.addEventListener("click", (e) => {
            e.preventDefault();
            e.stopPropagation();
            closeModal();
            setTimeout(() => {
                if (window.openContactModal) window.openContactModal();
            }, 600);
        });
    }
    
    modal.addEventListener("click", (e) => {
        if (e.target.classList.contains("absolute") && e.target.classList.contains("inset-0")) closeModal();
    });

    window.addEventListener("keydown", (e) => {
        if (e.key === "Escape") closeModal();
    });
};

// Mobile Menu Logic
const initMobileMenu = () => {
    const trigger = document.querySelector("#mobile-menu-trigger");
    const menu = document.querySelector("#mobile-menu");
    const menuContent = document.querySelector("#mobile-menu-content");
    const closeBtn = document.querySelector("#mobile-menu-close");
    const links = menu.querySelectorAll("nav a");

    const openMenu = () => {
        menu.classList.remove("invisible");
        gsap.to(menu, { opacity: 1, duration: 0.3, pointerEvents: "auto" });
        gsap.to(menuContent, { x: 0, duration: 0.6, ease: "expo.out" });
        document.body.style.overflow = "hidden";
    };

    const closeMenu = () => {
        gsap.to(menuContent, { x: "100%", duration: 0.5, ease: "expo.in" });
        gsap.to(menu, { 
            opacity: 0, 
            duration: 0.4, 
            delay: 0.2,
            onComplete: () => {
                menu.classList.add("invisible");
                document.body.style.overflow = "";
            }
        });
    };

    if (trigger) trigger.addEventListener("click", openMenu);
    if (closeBtn) closeBtn.addEventListener("click", closeMenu);
    
    links.forEach(link => {
        link.addEventListener("click", closeMenu);
    });

    menu.addEventListener("click", (e) => {
        if (e.target === menu) closeMenu();
    });
};

// Initialize
window.addEventListener("DOMContentLoaded", () => {
    initMagneticButtons();
    initSpotlightEffect();
    initContactModal();
    initCaseModal();
    initMobileMenu();
});
