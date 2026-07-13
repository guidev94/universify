const gsap = window.gsap;
const ScrollTrigger = window.ScrollTrigger;

/** Force visibility — safety net if entrance tweens stall. */
const revealAnimatedContent = () => {
    document.documentElement.classList.add("content-revealed", "js-ready");
    document.querySelectorAll(".case-item, [data-aos], [data-reveal], #portfolio-horizontal > *").forEach((el) => {
        el.style.opacity = "1";
        el.style.visibility = "visible";
        el.classList.add("aos-animate", "is-revealed");
        if (gsap) gsap.set(el, { clearProps: "opacity,visibility,transform" });
    });
};

const initRevealAnimations = () => {
    document.documentElement.classList.add("js-ready");

    // Cases always solid — never faded.
    document.querySelectorAll(".case-item, #portfolio-horizontal > *").forEach((el) => {
        el.style.opacity = "1";
        el.style.visibility = "visible";
    });

    if (!gsap || !ScrollTrigger) {
        revealAnimatedContent();
        return;
    }

    const targets = gsap.utils.toArray(
        "[data-aos], [data-reveal], .service-card, .produto-card, .blog-card"
    );

    targets.forEach((el) => {
        if (el.classList.contains("case-item")) return;
        gsap.fromTo(
            el,
            { y: 36, opacity: 0 },
            {
                y: 0,
                opacity: 1,
                duration: 0.8,
                ease: "power3.out",
                overwrite: "auto",
                scrollTrigger: {
                    trigger: el,
                    start: "top 90%",
                    once: true
                },
                onComplete: () => {
                    el.style.opacity = "1";
                    el.style.visibility = "visible";
                    el.classList.add("is-revealed", "aos-animate");
                    gsap.set(el, { clearProps: "transform" });
                }
            }
        );
    });

    // Failsafe if any ScrollTrigger never fires.
    window.setTimeout(revealAnimatedContent, 2800);
};

const initCursor = () => {
    if (!gsap) return;
    const cursor = document.querySelector("#cursor");
    if (!cursor) return;

    window.addEventListener("mousemove", (e) => {
        gsap.to(cursor, {
            x: e.clientX,
            y: e.clientY,
            opacity: 1,
            duration: 0.35,
            ease: "power2.out"
        });
    });

    document.querySelectorAll("a, button, .case-item, .produto-card, .service-card, .blog-card").forEach((el) => {
        el.addEventListener("mouseenter", () => gsap.to(cursor, { scale: 6, opacity: 0.15, duration: 0.3 }));
        el.addEventListener("mouseleave", () => gsap.to(cursor, { scale: 1, opacity: 1, duration: 0.3 }));
    });
};

/**
 * Cases: pin + scrollLeft (sem transform no track).
 */
const initPortfolioAnimations = () => {
    if (!gsap || !ScrollTrigger) return;

    const section = document.querySelector("#portfolio");
    const rail = document.querySelector("#portfolio-rail");
    const track = document.querySelector("#portfolio-horizontal");
    const cases = gsap.utils.toArray(".case-item");
    if (!section || !rail || !track || !cases.length) return;

    gsap.set(cases, { clearProps: "opacity,visibility,transform" });
    cases.forEach((item) => {
        item.style.opacity = "1";
        item.style.visibility = "visible";
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
    const mm = gsap.matchMedia();

    mm.add("(min-width: 768px)", () => {
        const st = ScrollTrigger.create({
            trigger: section,
            start: "top top",
            end: () => `+=${Math.max(getMaxScroll(), window.innerHeight * 0.85)}`,
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
        requestAnimationFrame(() => ScrollTrigger.refresh());

        return () => {
            window.removeEventListener("resize", onResize);
            st.kill();
            rail.scrollLeft = 0;
        };
    });

    mm.add("(max-width: 767px)", () => {
        const onWheel = (e) => {
            if (Math.abs(e.deltaY) <= Math.abs(e.deltaX)) return;
            if (!getMaxScroll()) return;
            rail.scrollLeft += e.deltaY;
            e.preventDefault();
        };
        rail.addEventListener("wheel", onWheel, { passive: false });

        let isDown = false;
        let startX = 0;
        let startScroll = 0;

        const onDown = (e) => {
            if (e.pointerType === "mouse" && e.button !== 0) return;
            if (e.target.closest("a, button")) return;
            isDown = true;
            startX = e.clientX;
            startScroll = rail.scrollLeft;
            rail.classList.add("is-dragging");
        };
        const onMove = (e) => {
            if (!isDown) return;
            const dx = e.clientX - startX;
            if (Math.abs(dx) > 8) cases.forEach((c) => { c.dataset.dragged = "1"; });
            rail.scrollLeft = startScroll - dx;
        };
        const onUp = () => {
            isDown = false;
            rail.classList.remove("is-dragging");
            window.setTimeout(() => cases.forEach((c) => { c.dataset.dragged = "0"; }), 50);
        };

        rail.addEventListener("pointerdown", onDown);
        rail.addEventListener("pointermove", onMove);
        rail.addEventListener("pointerup", onUp);
        rail.addEventListener("pointercancel", onUp);

        return () => {
            rail.removeEventListener("wheel", onWheel);
            rail.removeEventListener("pointerdown", onDown);
            rail.removeEventListener("pointermove", onMove);
            rail.removeEventListener("pointerup", onUp);
            rail.removeEventListener("pointercancel", onUp);
        };
    });
};

const initHeaderAnim = () => {
    const header = document.querySelector("#main-header");
    if (!header) return;

    const setScrolled = (on) => header.classList.toggle("is-scrolled", Boolean(on));

    const sync = () => {
        const y = window.lenis ? window.lenis.scroll : window.scrollY || window.pageYOffset || 0;
        setScrolled(y > 80);
    };

    window.lenis?.on("scroll", sync);
    window.addEventListener("scroll", sync, { passive: true });
    sync();
};

const bootAnimations = () => {
    initRevealAnimations();
    initCursor();
    initPortfolioAnimations();
    initHeaderAnim();
    window.__wvAnimationsBooted = true;
    requestAnimationFrame(() => ScrollTrigger?.refresh?.());
};

if (document.readyState === "loading") {
    window.addEventListener("DOMContentLoaded", bootAnimations);
} else {
    bootAnimations();
}
