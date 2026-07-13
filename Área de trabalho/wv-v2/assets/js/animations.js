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

    document.querySelectorAll("a, button, .case-item, article").forEach(el => {
        el.addEventListener("mouseenter", () => gsap.to(cursor, { scale: 6, opacity: 0.15 }));
        el.addEventListener("mouseleave", () => gsap.to(cursor, { scale: 1, opacity: 1 }));
    });
};

// Blog Cards High-Level Animations
const initBlogAnimations = () => {
    const articles = document.querySelectorAll("#blog article");
    
    articles.forEach((article, i) => {
        const image = article.querySelector("img");
        const content = article.querySelector("h4, p, div");

        // Subtle Hover Animation
        article.addEventListener("mouseenter", () => {
            gsap.to(article, {
                y: -15,
                duration: 0.6,
                ease: "expo.out",
                boxShadow: "0 30px 60px -15px rgba(207, 255, 4, 0.1)"
            });
            if (image) {
                gsap.to(image, {
                    scale: 1.1,
                    duration: 1.2,
                    ease: "power2.out"
                });
            }
        });

        article.addEventListener("mouseleave", () => {
            gsap.to(article, {
                y: 0,
                duration: 0.6,
                ease: "expo.out",
                boxShadow: "none"
            });
            if (image) {
                gsap.to(image, {
                    scale: 1,
                    duration: 0.8,
                    ease: "power2.out"
                });
            }
        });
    });
};

// Portfolio & Case Entrance Animations
const initPortfolioAnimations = () => {
    const portfolioWrapper = document.querySelector("#portfolio-horizontal");
    const cases = document.querySelectorAll(".case-item");
    
    if (!portfolioWrapper) return;

    // Horizontal Scroll Trigger
    gsap.to(portfolioWrapper, {
        x: () => -(portfolioWrapper.scrollWidth - window.innerWidth + 60),
        ease: "none",
        scrollTrigger: {
            trigger: "#portfolio",
            start: "top top",
            end: () => `+=${portfolioWrapper.scrollWidth}`,
            scrub: 1,
            pin: true,
            invalidateOnRefresh: true,
        }
    });

    // Reveal cases with stagger (Native GSAP instead of AOS for pinned sections)
    gsap.from(cases, {
        scrollTrigger: {
            trigger: "#portfolio",
            start: "top 80%",
            toggleActions: "play none none none"
        },
        y: 100,
        opacity: 0,
        stagger: 0.2,
        duration: 1.2,
        ease: "expo.out"
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
