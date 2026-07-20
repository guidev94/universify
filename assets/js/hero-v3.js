/**
 * Hero v3 — kinetic type entrance + magnetic CTAs + stage rails.
 * Transform/opacity only. Respects prefers-reduced-motion.
 */
(function () {
  "use strict";

  var hero = document.getElementById("hero");
  if (!hero || !hero.classList.contains("hero-v3")) return;

  var reduced =
    window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  var coarse =
    window.matchMedia && window.matchMedia("(pointer: coarse)").matches;

  var listeners = [];

  function on(target, type, fn, opts) {
    target.addEventListener(type, fn, opts);
    listeners.push([target, type, fn, opts]);
  }

  function revealStatic() {
    hero
      .querySelectorAll(
        ".hv3-brand, .hv3-title__word, .hv3-subtitle, .hv3-ctas, .hv3-stage-wrap, .hv3-rail, .hv3-proof, .hv3-scroll"
      )
      .forEach(function (el) {
        el.style.opacity = "1";
        el.style.transform = "none";
      });
  }

  function initMagnetic() {
    if (reduced || coarse || typeof gsap === "undefined") return;

    var ctas = hero.querySelectorAll(".hv3-cta[data-magnetic]");
    ctas.forEach(function (btn) {
      var xTo = gsap.quickTo(btn, "x", { duration: 0.45, ease: "power3.out" });
      var yTo = gsap.quickTo(btn, "y", { duration: 0.45, ease: "power3.out" });
      var scaleTo = gsap.quickTo(btn, "scale", { duration: 0.35, ease: "power3.out" });

      on(btn, "pointermove", function (e) {
        var rect = btn.getBoundingClientRect();
        xTo((e.clientX - (rect.left + rect.width / 2)) * 0.22);
        yTo((e.clientY - (rect.top + rect.height / 2)) * 0.28);
      });
      on(btn, "pointerenter", function () {
        scaleTo(1.035);
      });
      on(btn, "pointerleave", function () {
        xTo(0);
        yTo(0);
        scaleTo(1);
      });
      on(btn, "pointerdown", function () {
        scaleTo(0.97);
      });
      on(btn, "pointerup", function () {
        scaleTo(1.035);
      });
    });
  }

  function initRailTilt() {
    if (reduced || coarse || typeof gsap === "undefined") return;
    var rails = hero.querySelectorAll(".hv3-rail");
    rails.forEach(function (rail) {
      on(rail, "pointermove", function (e) {
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
      on(rail, "pointerleave", function () {
        gsap.to(rail, { x: 0, y: 0, duration: 0.5, ease: "power3.out" });
      });
    });
  }

  function initEntrance() {
    if (typeof gsap === "undefined" || reduced) {
      revealStatic();
      return;
    }

    var words = hero.querySelectorAll(".hv3-title__word");
    var rails = hero.querySelectorAll(".hv3-rail");
    var proof = hero.querySelector(".hv3-proof");
    var scroll = hero.querySelector(".hv3-scroll");

    gsap.set(words, { yPercent: 110, opacity: 0 });
    gsap.set(".hv3-brand", { opacity: 0, y: 16 });
    gsap.set(".hv3-subtitle", { opacity: 0, y: 18 });
    gsap.set(".hv3-ctas", { opacity: 0, y: 16 });
    gsap.set(".hv3-stage-wrap", { opacity: 0, y: 24 });
    gsap.set(rails, { opacity: 0, x: 28 });
    if (proof) gsap.set(proof, { opacity: 0, y: 16 });
    if (scroll) gsap.set(scroll, { opacity: 0 });

    var tl = gsap.timeline({ defaults: { ease: "power3.out" } });

    tl.to(".hv3-brand", { opacity: 1, y: 0, duration: 0.65, delay: 0.12 });
    tl.to(
      words,
      {
        yPercent: 0,
        opacity: 1,
        duration: 0.85,
        stagger: 0.09,
        ease: "power4.out",
      },
      "-=0.25"
    );
    tl.to(".hv3-subtitle", { opacity: 1, y: 0, duration: 0.7 }, "-=0.35");
    tl.to(".hv3-ctas", { opacity: 1, y: 0, duration: 0.65 }, "-=0.35");
    tl.to(".hv3-stage-wrap", { opacity: 1, y: 0, duration: 0.8 }, "-=0.55");
    tl.to(
      rails,
      {
        opacity: 1,
        x: 0,
        duration: 0.65,
        stagger: 0.12,
        ease: "power3.out",
      },
      "-=0.45"
    );
    if (proof) {
      tl.to(proof, { opacity: 1, y: 0, duration: 0.65, ease: "power3.out" }, "-=0.35");
    }
    if (scroll) {
      tl.to(scroll, { opacity: 0.85, duration: 0.55 }, "-=0.25");
    }
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
