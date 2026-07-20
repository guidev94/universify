/**
 * Hero v3 — entrance + magnetic CTAs + stage rails.
 * Transform/opacity only. Content stays visible if GSAP missing / reduced-motion.
 */
(function () {
  "use strict";

  var hero = document.getElementById("hero");
  if (!hero || !hero.classList.contains("hero-v3")) return;

  var reduced =
    window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  var coarse =
    window.matchMedia && window.matchMedia("(pointer: coarse)").matches;

  function q(sel) {
    return hero.querySelector(sel);
  }

  function qa(sel) {
    return hero.querySelectorAll(sel);
  }

  function initMagnetic() {
    if (reduced || coarse || typeof gsap === "undefined") return;

    qa(".hv3-cta[data-magnetic]").forEach(function (btn) {
      var xTo = gsap.quickTo(btn, "x", { duration: 0.45, ease: "power3.out" });
      var yTo = gsap.quickTo(btn, "y", { duration: 0.45, ease: "power3.out" });
      var scaleTo = gsap.quickTo(btn, "scale", { duration: 0.35, ease: "power3.out" });

      btn.addEventListener("pointermove", function (e) {
        var rect = btn.getBoundingClientRect();
        xTo((e.clientX - (rect.left + rect.width / 2)) * 0.22);
        yTo((e.clientY - (rect.top + rect.height / 2)) * 0.28);
      });
      btn.addEventListener("pointerenter", function () {
        scaleTo(1.035);
      });
      btn.addEventListener("pointerleave", function () {
        xTo(0);
        yTo(0);
        scaleTo(1);
      });
      btn.addEventListener("pointerdown", function () {
        scaleTo(0.97);
      });
      btn.addEventListener("pointerup", function () {
        scaleTo(1.035);
      });
    });
  }

  function initRailTilt() {
    if (reduced || coarse || typeof gsap === "undefined") return;

    qa(".hv3-rail").forEach(function (rail) {
      rail.addEventListener("pointermove", function (e) {
        var rect = rail.getBoundingClientRect();
        var dx = (e.clientX - (rect.left + rect.width / 2)) / rect.width;
        var dy = (e.clientY - (rect.top + rect.height / 2)) / rect.height;
        gsap.to(rail, {
          x: dx * 6,
          y: dy * 4,
          duration: 0.4,
          ease: "power3.out",
          overwrite: "auto",
        });
      });
      rail.addEventListener("pointerleave", function () {
        gsap.to(rail, { x: 0, y: 0, duration: 0.5, ease: "power3.out" });
      });
    });
  }

  function initEntrance() {
    /* Default CSS is fully visible — never hide title via CSS opacity/clip. */
    if (typeof gsap === "undefined" || reduced) return;

    var brand = q(".hv3-brand");
    var lines = qa(".hv3-title__line");
    var subtitle = q(".hv3-subtitle");
    var ctas = q(".hv3-ctas");
    var stage = q(".hv3-stage-wrap");
    var rails = qa(".hv3-rail");
    var proof = q(".hv3-proof");
    var scroll = q(".hv3-scroll");

    var parts = [];
    if (brand) parts.push(brand);
    lines.forEach(function (el) {
      parts.push(el);
    });
    if (subtitle) parts.push(subtitle);
    if (ctas) parts.push(ctas);
    if (stage) parts.push(stage);
    rails.forEach(function (el) {
      parts.push(el);
    });
    if (proof) parts.push(proof);

    gsap.set(parts, { opacity: 0, y: 18 });
    if (scroll) gsap.set(scroll, { opacity: 0 });

    var tl = gsap.timeline({
      defaults: { ease: "power3.out" },
      onComplete: function () {
        /* Keep CSS transforms (e.g. proof tilt); only release GSAP y */
        gsap.set(parts, { opacity: 1, y: 0 });
      },
    });

    if (brand) tl.to(brand, { opacity: 1, y: 0, duration: 0.55, delay: 0.08 });
    if (lines.length) {
      tl.to(
        lines,
        { opacity: 1, y: 0, duration: 0.7, stagger: 0.1, ease: "power4.out" },
        brand ? "-=0.2" : 0.1
      );
    }
    if (subtitle) tl.to(subtitle, { opacity: 1, y: 0, duration: 0.55 }, "-=0.3");
    if (ctas) tl.to(ctas, { opacity: 1, y: 0, duration: 0.55 }, "-=0.3");
    if (stage) tl.to(stage, { opacity: 1, y: 0, duration: 0.65 }, "-=0.45");
    if (rails.length) {
      tl.to(rails, { opacity: 1, y: 0, duration: 0.55, stagger: 0.08 }, "-=0.4");
    }
    if (proof) tl.to(proof, { opacity: 1, y: 0, duration: 0.55 }, "-=0.3");
    if (scroll) tl.to(scroll, { opacity: 0.85, duration: 0.45 }, "-=0.2");
  }

  function boot() {
    initMagnetic();
    initRailTilt();
    initEntrance();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot);
  } else {
    boot();
  }
})();
