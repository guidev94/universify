/**
 * Hero v3 — entrance + magnetic CTAs + sector scenario board.
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

  function animateSceneIn(scene) {
    if (!scene || typeof gsap === "undefined" || reduced) return;

    var parts = scene.querySelectorAll(
      ".hv3-scene__pain, .hv3-scene__move, .hv3-scene__win"
    );
    if (!parts.length) return;

    gsap.fromTo(
      parts,
      { opacity: 0, y: 12 },
      {
        opacity: 1,
        y: 0,
        duration: 0.4,
        stagger: 0.06,
        ease: "power3.out",
        overwrite: "auto",
      }
    );
  }

  function initSectorBoard() {
    var tabs = Array.prototype.slice.call(qa(".hv3-tab"));
    var panels = Array.prototype.slice.call(qa(".hv3-scene"));
    if (!tabs.length || !panels.length) return;

    function activate(sector, focusTab) {
      var nextTab = null;
      var nextPanel = null;

      tabs.forEach(function (tab) {
        var on = tab.getAttribute("data-sector") === sector;
        tab.classList.toggle("is-active", on);
        tab.setAttribute("aria-selected", on ? "true" : "false");
        tab.tabIndex = on ? 0 : -1;
        if (on) nextTab = tab;
      });

      panels.forEach(function (panel) {
        var on = panel.getAttribute("data-sector") === sector;
        panel.classList.toggle("is-active", on);
        if (on) {
          panel.removeAttribute("hidden");
          nextPanel = panel;
        } else {
          panel.setAttribute("hidden", "");
        }
      });

      if (focusTab && nextTab) nextTab.focus();
      animateSceneIn(nextPanel);
    }

    tabs.forEach(function (tab) {
      tab.addEventListener("click", function () {
        var sector = tab.getAttribute("data-sector");
        if (!sector || tab.classList.contains("is-active")) return;
        activate(sector, false);
      });

      tab.addEventListener("keydown", function (e) {
        var i = tabs.indexOf(tab);
        if (i < 0) return;

        var next = -1;
        if (e.key === "ArrowRight" || e.key === "ArrowDown") {
          next = (i + 1) % tabs.length;
        } else if (e.key === "ArrowLeft" || e.key === "ArrowUp") {
          next = (i - 1 + tabs.length) % tabs.length;
        } else if (e.key === "Home") {
          next = 0;
        } else if (e.key === "End") {
          next = tabs.length - 1;
        }

        if (next < 0) return;
        e.preventDefault();
        activate(tabs[next].getAttribute("data-sector"), true);
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
    var head = q(".hv3-board__head");
    var tabs = q(".hv3-tabs");
    var activeScene = q(".hv3-scene.is-active");
    var scroll = q(".hv3-scroll");

    var parts = [];
    if (brand) parts.push(brand);
    lines.forEach(function (el) {
      parts.push(el);
    });
    if (subtitle) parts.push(subtitle);
    if (ctas) parts.push(ctas);
    if (stage) parts.push(stage);

    gsap.set(parts, { opacity: 0, y: 18 });
    if (head) gsap.set(head, { opacity: 0, y: 10 });
    if (tabs) gsap.set(tabs, { opacity: 0, y: 10 });
    if (activeScene) gsap.set(activeScene, { opacity: 0, y: 14 });
    if (scroll) gsap.set(scroll, { opacity: 0 });

    var tl = gsap.timeline({
      defaults: { ease: "power3.out" },
      onComplete: function () {
        gsap.set(parts, { opacity: 1, y: 0 });
        if (head) gsap.set(head, { opacity: 1, y: 0 });
        if (tabs) gsap.set(tabs, { opacity: 1, y: 0 });
        if (activeScene) gsap.set(activeScene, { opacity: 1, y: 0 });
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
    if (head) tl.to(head, { opacity: 1, y: 0, duration: 0.45 }, "-=0.4");
    if (tabs) tl.to(tabs, { opacity: 1, y: 0, duration: 0.45 }, "-=0.3");
    if (activeScene) {
      tl.to(activeScene, { opacity: 1, y: 0, duration: 0.55 }, "-=0.25");
      tl.add(function () {
        animateSceneIn(activeScene);
      }, "-=0.35");
    }
    if (scroll) tl.to(scroll, { opacity: 0.85, duration: 0.45 }, "-=0.2");
  }

  function boot() {
    initMagnetic();
    initSectorBoard();
    initEntrance();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot);
  } else {
    boot();
  }
})();
