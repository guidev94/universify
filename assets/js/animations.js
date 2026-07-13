gsap.registerPlugin(ScrollTrigger);

AOS.init({
    duration: 1000,
    once: true,
    easing: "ease-out-expo"
});

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

    document.querySelectorAll("a, button, .case-item, .produto-card, .service-card, .blog-card").forEach((el) => {
        el.addEventListener("mouseenter", () => gsap.to(cursor, { scale: 6, opacity: 0.15 }));
        el.addEventListener("mouseleave", () => gsap.to(cursor, { scale: 1, opacity: 1 }));
    });
};

const initBlogAnimations = () => {};

/**
 * Cases: pin da seção + progressão vertical → scrollLeft (sem transform no track).
 * pinType "fixed" evita o bug de hit-test do pin com transform.
 */
const initPortfolioAnimations = () => {
    const section = document.querySelector("#portfolio");
    const rail = document.querySelector("#portfolio-rail");
    const track = document.querySelector("#portfolio-horizontal");
    const cases = document.querySelectorAll(".case-item");
    if (!section || !rail || !track || !cases.length) return;

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

    const getMaxScroll = () => Math.max(0, rail.scrollWidth - rail.clientWidth);

    // Entrada dos cards
    gsap.from(cases, {
        autoAlpha: 0,
        y: 48,
        scale: 0.96,
        duration: 0.9,
        stagger: 0.12,
        ease: "power3.out",
        clearProps: "transform",
        scrollTrigger: {
            trigger: section,
            start: "top 75%",
            once: true
        }
    });

    const mm = gsap.matchMedia();

    mm.add("(min-width: 768px)", () => {
        const st = ScrollTrigger.create({
            trigger: section,
            start: "top top",
            end: () => `+=${Math.max(getMaxScroll(), window.innerHeight * 0.8)}`,
            pin: true,
            pinType: "fixed",
            scrub: 0.85,
            anticipatePin: 1,
            invalidateOnRefresh: true,
            onUpdate: (self) => {
                rail.scrollLeft = self.progress * getMaxScroll();
            }
        });

        const onResize = () => ScrollTrigger.refresh();
        window.addEventListener("resize", onResize);

        return () => {
            window.removeEventListener("resize", onResize);
            st.kill();
            rail.scrollLeft = 0;
        };
    });

    // Mobile: wheel horizontal + drag leve
    mm.add("(max-width: 767px)", () => {
        rail.addEventListener(
            "wheel",
            (e) => {
                if (Math.abs(e.deltaY) <= Math.abs(e.deltaX)) return;
                if (!getMaxScroll()) return;
                rail.scrollLeft += e.deltaY;
                e.preventDefault();
            },
            { passive: false }
        );

        let isDown = false;
        let didDrag = false;
        let startX = 0;
        let startScroll = 0;

        const onDown = (e) => {
            if (e.pointerType === "mouse" && e.button !== 0) return;
            if (e.target.closest("a, button")) return;
            isDown = true;
            didDrag = false;
            startX = e.clientX;
            startScroll = rail.scrollLeft;
            rail.classList.add("is-dragging");
        };
        const onMove = (e) => {
            if (!isDown) return;
            const dx = e.clientX - startX;
            if (Math.abs(dx) > 8) {
                didDrag = true;
                cases.forEach((c) => {
                    c.dataset.dragged = "1";
                });
            }
            rail.scrollLeft = startScroll - dx;
        };
        const onUp = () => {
            isDown = false;
            rail.classList.remove("is-dragging");
            window.setTimeout(() => {
                cases.forEach((c) => {
                    c.dataset.dragged = "0";
                });
            }, 50);
        };

        rail.addEventListener("pointerdown", onDown);
        rail.addEventListener("pointermove", onMove);
        rail.addEventListener("pointerup", onUp);
        rail.addEventListener("pointercancel", onUp);

        return () => {
            rail.removeEventListener("pointerdown", onDown);
            rail.removeEventListener("pointermove", onMove);
            rail.removeEventListener("pointerup", onUp);
            rail.removeEventListener("pointercancel", onUp);
        };
    });
};

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

window.addEventListener("DOMContentLoaded", () => {
    initCursor();
    initPortfolioAnimations();
    initHeaderAnim();
    initBlogAnimations();
});
