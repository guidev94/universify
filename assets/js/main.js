// Initialize Lenis Smooth Scroll
const lenis = new Lenis({
    duration: 1.2,
    easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
    smoothWheel: true,
    orientation: "vertical"
});

window.lenis = lenis;

gsap.registerPlugin(ScrollTrigger);

lenis.on("scroll", ScrollTrigger.update);

// Single RAF owner — avoid double-driving Lenis (was gsap.ticker + rAF).
gsap.ticker.add((time) => {
    lenis.raf(time * 1000);
});
gsap.ticker.lagSmoothing(0);
