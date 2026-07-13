gsap.registerPlugin(ScrollTrigger);

const initAOS = () => {
    if (!window.AOS) return;

    AOS.init({
        duration: 1000,
        once: true,
        easing: "ease-out-expo",
        mirror: false
    });

    // Lenis + late layout can leave fade nodes stuck at opacity 0 (aos.css).
    const refresh = () => {
        if (typeof AOS.refreshHard === "function") AOS.refreshHard();
        else AOS.refresh();
    };
    requestAnimationFrame(refresh);
    window.addEventListener("load", refresh, { once: true });
    // One delayed pass after fonts/images/pin spacer settle.
    window.setTimeout(refresh, 600);
};

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
 *
 * Entrada: fromTo + immediateRender:false — gsap.from(autoAlpha:0) deixa cards
 * invisíveis se o ScrollTrigger do pin invalidar o tween antes do play.
 */
const initPortfolioAnimations = () => {
    const section = document.querySelector("#portfolio");
    const rail = document.querySelector("#portfolio-rail");
    const track = document.querySelector("#portfolio-horizontal");
    const cases = gsap.utils.toArray(".case-item");
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

    const ensureCasesVisible = () => {
        gsap.set(cases, { clearProps: "opacity,visibility,transform" });
    };

    const entrance = gsap.fromTo(
        cases,
        { opacity: 0, y: 48, scale: 0.96 },
        {
            opacity: 1,
            y: 0,
            scale: 1,
            duration: 0.9,
            stagger: 0.12,
            ease: "power3.out",
            immediateRender: false,
            paused: true,
            clearProps: "transform",
            onComplete: () => {
                gsap.set(cases, { clearProps: "opacity,visibility" });
            }
        }
    );

    let entrancePlayed = false;
    const playEntrance = () => {
        if (entrancePlayed) return;
        entrancePlayed = true;
        entrance.play(0);
    };

    ScrollTrigger.create({
        trigger: section,
        start: "top 80%",
        once: true,
        onEnter: playEntrance,
        onRefresh: (self) => {
            // Já passou do start (ex.: deep-link / refresh com pin) — garante play.
            if (self.isActive || self.progress > 0 || self.scroll() > self.start) {
                playEntrance();
            }
        }
    });

    // Failsafe: nunca deixar cases presos em opacity 0.
    window.setTimeout(() => {
        const stuck = cases.some((el) => getComputedStyle(el).opacity === "0");
        if (stuck) {
            entrancePlayed = true;
            entrance.progress(1).pause();
            ensureCasesVisible();
        }
    }, 2200);

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
        // Refresh after pin layout so entrance ST + AOS recalculate.
        requestAnimationFrame(() => ScrollTrigger.refresh());

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

const bootAnimations = () => {
    initAOS();
    initCursor();
    initPortfolioAnimations();
    initHeaderAnim();
    initBlogAnimations();
    window.__wvAnimationsBooted = true;
};

if (document.readyState === "loading") {
    window.addEventListener("DOMContentLoaded", bootAnimations);
} else {
    bootAnimations();
}
