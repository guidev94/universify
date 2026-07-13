gsap.registerPlugin(ScrollTrigger);

// Inicialização AOS
AOS.init({
    duration: 1000,
    once: true,
    easing: 'ease-out-expo'
});

// Cursor Customizado Reativo
const initCursor = () => {
    const cursor = document.querySelector("#cursor");
    if (!cursor) return;

    window.addEventListener("mousemove", (e) => {
        gsap.to(cursor, {
            x: e.clientX,
            y: e.clientY,
            opacity: 1,
            duration: 0.4,
            ease: "power2.out"
        });
    });

    document.querySelectorAll("a, button, .case-item, .produto-card, .service-card, .blog-card").forEach(el => {
        el.addEventListener("mouseenter", () => gsap.to(cursor, { scale: 6, opacity: 0.15 }));
        el.addEventListener("mouseleave", () => gsap.to(cursor, { scale: 1, opacity: 1 }));
    });
};

// Blog: hover via CSS (.blog-card) — sem GSAP transform (quebrava group-hover)
const initBlogAnimations = () => {};

// Portfolio horizontal: hover + scroll nativo (sem transform no track)
const initPortfolioAnimations = () => {
    const cases = document.querySelectorAll(".case-item");
    const rail = document.querySelector("#portfolio-rail");

    cases.forEach((item) => {
        const enter = () => item.classList.add("is-hovered");
        const leave = (e) => {
            if (e.type === "focusout" && item.contains(e.relatedTarget)) return;
            item.classList.remove("is-hovered");
        };
        item.addEventListener("pointerenter", enter);
        item.addEventListener("pointerleave", leave);
        item.addEventListener("focusin", enter);
        item.addEventListener("focusout", leave);
    });

    if (!rail) return;

    // Rodinha vertical → scroll horizontal (track não usa transform)
    rail.addEventListener(
        "wheel",
        (e) => {
            if (Math.abs(e.deltaY) <= Math.abs(e.deltaX)) return;
            if (rail.scrollWidth <= rail.clientWidth) return;
            rail.scrollLeft += e.deltaY;
            e.preventDefault();
        },
        { passive: false }
    );

    // Drag to scroll (sem abrir modal ao arrastar)
    let isDown = false;
    let didDrag = false;
    let startX = 0;
    let startScroll = 0;

    rail.addEventListener("pointerdown", (e) => {
        if (e.pointerType === "mouse" && e.button !== 0) return;
        if (e.target.closest("a, button")) return;
        isDown = true;
        didDrag = false;
        startX = e.clientX;
        startScroll = rail.scrollLeft;
        rail.classList.add("is-dragging");
        rail.setPointerCapture?.(e.pointerId);
    });

    rail.addEventListener("pointermove", (e) => {
        if (!isDown) return;
        const dx = e.clientX - startX;
        if (Math.abs(dx) > 6) didDrag = true;
        rail.scrollLeft = startScroll - dx;
    });

    const endDrag = () => {
        isDown = false;
        rail.classList.remove("is-dragging");
    };
    rail.addEventListener("pointerup", endDrag);
    rail.addEventListener("pointercancel", endDrag);

    rail.addEventListener("click", (e) => {
        if (!didDrag) return;
        e.preventDefault();
        e.stopPropagation();
        didDrag = false;
    }, true);

    const scrollByCard = (dir) => {
        const card = rail.querySelector(".case-item");
        const step = card ? card.getBoundingClientRect().width + 40 : 720;
        rail.scrollBy({ left: dir * step, behavior: "smooth" });
    };

    document.querySelector("#portfolio-prev")?.addEventListener("click", () => scrollByCard(-1));
    document.querySelector("#portfolio-next")?.addEventListener("click", () => scrollByCard(1));
};

// Transformação do Header ao Scroll
const initHeaderAnim = () => {
    ScrollTrigger.create({
        start: "top -100",
        onEnter: () => {
            gsap.to("#main-header", {
                paddingTop: "1.2rem",
                paddingBottom: "1.2rem",
                backgroundColor: "rgba(10, 10, 10, 0.8)",
                backdropFilter: "blur(20px)",
                borderBottom: "1px solid rgba(207, 255, 4, 0.2)",
                duration: 0.5
            });
        },
        onLeaveBack: () => {
            gsap.to("#main-header", {
                paddingTop: "1.5rem",
                paddingBottom: "1.5rem",
                backgroundColor: "transparent",
                backdropFilter: "blur(0px)",
                borderBottom: "1px solid transparent",
                duration: 0.5
            });
        }
    });
};

// Inicializar Todas
window.addEventListener("DOMContentLoaded", () => {
    initCursor();
    initPortfolioAnimations();
    initHeaderAnim();
    initBlogAnimations();
});
