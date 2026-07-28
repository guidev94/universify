import { attachToGlobal } from "./site-utils.mjs";

attachToGlobal(window);

try {
    await import("./main.js");
    await import("./animations.js");
    await import("./components.js");
    document.documentElement.classList.add("content-revealed");
} catch (err) {
    console.error("[WV] Boot failed:", err);
    // Last-resort: force content visible even if modules throw.
    document.documentElement.classList.add("content-revealed");
    document.querySelectorAll("[data-aos], .case-item").forEach((el) => {
        el.style.opacity = "1";
        el.style.visibility = "visible";
        el.classList.add("aos-animate");
    });
}
