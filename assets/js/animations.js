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
    
    window.addEventListener("mousemove", (e) => {
        gsap.to(cursor, {
            x: e.clientX,
            y: e.clientY,
            opacity: 1,
            duration: 0.4,
            ease: "power2.out"
        });
    });

    document.querySelectorAll("a, button, .case-item, article, #blog a.group").forEach(el => {
        el.addEventListener("mouseenter", () => gsap.to(cursor, { scale: 6, opacity: 0.15 }));
        el.addEventListener("mouseleave", () => gsap.to(cursor, { scale: 1, opacity: 1 }));
    });
};

// Blog Cards High-Level Animations
const initBlogAnimations = () => {
    const articles = document.querySelectorAll("#blog article, #blog a.group");
    
    articles.forEach((article) => {
        const image = article.querySelector(".blog-card-image");

        // Subtle lift only — image/overlay hover stays on CSS so group-hover works
        article.addEventListener("mouseenter", () => {
            gsap.to(article, {
                y: -15,
                duration: 0.6,
                ease: "expo.out",
                boxShadow: "0 30px 60px -15px rgba(207, 255, 4, 0.1)"
            });
        });

        article.addEventListener("mouseleave", () => {
            gsap.to(article, {
                y: 0,
                duration: 0.6,
                ease: "expo.out",
                boxShadow: "none",
                clearProps: "transform,boxShadow"
            });
            if (image) gsap.set(image, { clearProps: "transform" });
        });
    });
};

// Portfolio & Case hover
const initPortfolioAnimations = () => {
    const cases = document.querySelectorAll(".case-item");
    if (!cases.length) return;

    // Hover explícito: mesmo estado em todos os cards (CSS :hover + classe)
    cases.forEach((item) => {
        const enter = () => item.classList.add("is-hovered");
        const leave = () => item.classList.remove("is-hovered");
        item.addEventListener("pointerenter", enter);
        item.addEventListener("pointerleave", leave);
        item.addEventListener("focusin", enter);
        item.addEventListener("focusout", leave);
    });
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
                borderBottom: "1px solid rgba(139, 197, 63, 0.2)",
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
