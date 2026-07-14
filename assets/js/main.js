// Lenis + GSAP — classic script (same pattern as commit 8c3fdb9)
gsap.registerPlugin(ScrollTrigger);

const lenis = new Lenis({
    duration: 1.2,
    easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
    smoothWheel: true,
    orientation: "vertical"
});

window.lenis = lenis;
window.__wvScroll = 0;

lenis.on("scroll", ({ scroll }) => {
    window.__wvScroll = scroll;
    ScrollTrigger.update();
    // Do NOT dispatch a native `scroll` event here — Lenis listens to window scroll
    // (onNativeScroll) and would recurse until "Maximum call stack size exceeded".
    // AOS is driven by driveAosWithLenis() via lenis.on("scroll") in animations.js.
});

// Single RAF loop (avoid double-driving Lenis).
gsap.ticker.add((time) => {
    lenis.raf(time * 1000);
});
gsap.ticker.lagSmoothing(0);
