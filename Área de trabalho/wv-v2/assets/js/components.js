// Magnetic Button Effect
const initMagneticButtons = () => {
    const magneticButtons = document.querySelectorAll('.magnetic');

    magneticButtons.forEach(btn => {
        btn.addEventListener('mousemove', function(e) {
            const position = btn.getBoundingClientRect();
            const x = e.pageX - position.left - position.width / 2;
            const y = e.pageY - position.top - position.height / 2;

            gsap.to(btn, {
                x: x * 0.3,
                y: y * 0.3,
                duration: 0.5,
                ease: "power2.out"
            });
        });

        btn.addEventListener('mouseleave', function(e) {
            gsap.to(btn, {
                x: 0,
                y: 0,
                duration: 0.5,
                ease: "elastic.out(1, 0.3)"
            });
        });
    });
};

// Spotlight effect
const initSpotlightEffect = () => {
    const bentoItems = document.querySelectorAll('.bento-item, .case-item, .service-card');
    
    bentoItems.forEach(item => {
        item.addEventListener('mousemove', e => {
            const rect = item.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            item.style.setProperty('--mouse-x', `${x}px`);
            item.style.setProperty('--mouse-y', `${y}px`);
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

// Initialize
window.addEventListener("DOMContentLoaded", () => {
    initMagneticButtons();
    initSpotlightEffect();
    initContactModal();
    initCaseModal();
});
