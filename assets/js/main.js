const gsap = window.gsap;
const ScrollTrigger = window.ScrollTrigger;
const Lenis = window.Lenis;

if (!gsap || !ScrollTrigger || !Lenis) {
    console.error("[WV] Missing globals (gsap/ScrollTrigger/Lenis). Scripts did not boot.");
} else {
    gsap.registerPlugin(ScrollTrigger);

    const lenis = new Lenis({
        duration: 1.2,
        easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
        smoothWheel: true,
        orientation: "vertical"
    });

    window.lenis = lenis;

    // Keep ScrollTrigger in sync — avoid scrollerProxy on body (breaks pins/modals).
    lenis.on("scroll", () => {
        ScrollTrigger.update();
    });

    gsap.ticker.add((time) => {
        lenis.raf(time * 1000);
    });
    gsap.ticker.lagSmoothing(0);

    window.addEventListener("resize", () => {
        lenis.resize();
        ScrollTrigger.refresh();
    });

    requestAnimationFrame(() => ScrollTrigger.refresh());
}
