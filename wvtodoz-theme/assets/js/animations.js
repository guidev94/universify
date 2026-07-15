/**
 * Restored animation stack from classic era (8c3fdb9):
 * AOS entrances + GSAP portfolio pin / header.
 * Plus Lenis-compatible AOS (getBoundingClientRect driver).
 */
gsap.registerPlugin(ScrollTrigger);

const driveAosWithLenis = () => {
    const nodes = document.querySelectorAll("[data-aos]");
    if (!nodes.length) return;

    const mark = () => {
        const vh = window.innerHeight || 800;
        nodes.forEach((el) => {
            if (el.classList.contains("aos-animate")) return;
            const top = el.getBoundingClientRect().top;
            if (top < vh * 0.92) el.classList.add("aos-animate");
        });
    };

    mark();
    window.lenis?.on("scroll", mark);
    window.addEventListener("resize", mark);
    window.setTimeout(mark, 400);
    window.setTimeout(mark, 1200);
};

AOS.init({
    duration: 1000,
    once: true,
    easing: "ease-out-expo",
    // Disable native scroll watcher — Lenis drives visibility via driveAosWithLenis.
    disableMutationObserver: false
});

driveAosWithLenis();

const initBlogAnimations = () => {};

const initPortfolioAnimations = () => {
    const section = document.querySelector("#portfolio");
    const rail = document.querySelector("#portfolio-rail");
    const track = document.querySelector("#portfolio-horizontal");
    const cases = gsap.utils.toArray(".case-item");
    if (!section || !rail || !track || !cases.length) return;

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

    // Entrada dos cards (como antes)
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
        },
        onComplete: () => {
            cases.forEach((c) => {
                c.style.opacity = "1";
                c.style.visibility = "visible";
            });
        }
    });

    // Failsafe se o pin/refresh atrapalhar a entrada
    window.setTimeout(() => {
        cases.forEach((c) => {
            c.style.opacity = "1";
            c.style.visibility = "visible";
            gsap.set(c, { clearProps: "opacity,visibility" });
        });
    }, 2000);

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
        requestAnimationFrame(() => ScrollTrigger.refresh());

        return () => {
            window.removeEventListener("resize", onResize);
            st.kill();
            rail.scrollLeft = 0;
        };
    });

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
            if (Math.abs(dx) > 8) {
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
    const header = document.querySelector("#main-header");
    if (!header) return;

    // Keep CSS class path (readable) + sync with Lenis/GSAP scroll
    const setScrolled = (on) => header.classList.toggle("is-scrolled", Boolean(on));

    ScrollTrigger.create({
        start: "top -100",
        onEnter: () => setScrolled(true),
        onLeaveBack: () => setScrolled(false)
    });

    window.lenis?.on("scroll", ({ scroll }) => setScrolled(scroll > 80));
};

const initMissaoStats = () => {
    const section = document.querySelector("#missao");
    const counters = gsap.utils.toArray("#missao [data-count]");
    if (!section || !counters.length) return;

    const render = (el, value) => {
        const suffix = el.dataset.suffix || "";
        el.textContent = `${Math.round(value)}${suffix}`;
    };

    const prefersReduced = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    if (prefersReduced) {
        counters.forEach((el) => render(el, Number(el.dataset.count) || 0));
        return;
    }

    counters.forEach((el) => render(el, 0));

    let played = false;
    const play = () => {
        if (played) return;
        played = true;

        counters.forEach((el, i) => {
            const end = Number(el.dataset.count) || 0;
            const state = { val: 0 };

            gsap.to(state, {
                val: end,
                duration: 1.65,
                delay: i * 0.14,
                ease: "power2.out",
                onUpdate: () => render(el, state.val),
                onComplete: () => render(el, end)
            });
        });
    };

    const trigger = section.querySelector(".missao-stats") || section;

    ScrollTrigger.create({
        trigger,
        start: "top 82%",
        once: true,
        onEnter: play
    });

    // Lenis / late layout: if already in view, play without waiting for scroll
    const maybePlay = () => {
        const rect = trigger.getBoundingClientRect();
        const vh = window.innerHeight || 800;
        if (rect.top < vh * 0.88 && rect.bottom > 0) play();
    };

    requestAnimationFrame(maybePlay);
    window.setTimeout(maybePlay, 600);
    window.lenis?.on("scroll", maybePlay);
};

window.addEventListener("DOMContentLoaded", () => {
    document.documentElement.classList.add("js-ready");
    initPortfolioAnimations();
    initHeaderAnim();
    initMissaoStats();
    initBlogAnimations();
    window.__wvAnimationsBooted = true;
    requestAnimationFrame(() => ScrollTrigger.refresh());

    // Last-resort only if boot didn't finish (CDN/JS error).
    window.setTimeout(() => {
        if (window.__wvAnimationsBooted && window.__wvBooted) return;
        document.documentElement.classList.add("content-revealed");
        document.querySelectorAll("[data-aos]").forEach((el) => el.classList.add("aos-animate"));
    }, 2500);
});
