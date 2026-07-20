/**
 * Hero v2 — Three.js particles + GSAP entrance for value-stack panel.
 * Isolated resize/cleanup; respects prefers-reduced-motion.
 */
(function () {
  "use strict";

  var hero = document.getElementById("hero");
  if (!hero || !hero.classList.contains("hero-v2")) return;

  var reduced =
    window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  var rafIds = [];
  var intervals = [];
  var listeners = [];

  function on(target, type, fn, opts) {
    target.addEventListener(type, fn, opts);
    listeners.push([target, type, fn, opts]);
  }

  function raf(fn) {
    var id = requestAnimationFrame(fn);
    rafIds.push(id);
    return id;
  }

  function cleanup() {
    rafIds.forEach(function (id) {
      cancelAnimationFrame(id);
    });
    rafIds = [];
    intervals.forEach(function (id) {
      clearInterval(id);
    });
    intervals = [];
    listeners.forEach(function (entry) {
      entry[0].removeEventListener(entry[1], entry[2], entry[3]);
    });
    listeners = [];
  }

  on(window, "pagehide", cleanup);

  function isLightTheme() {
    return document.documentElement.getAttribute("data-theme") === "light";
  }

  /* ===== THREE.JS PARTICLE FIELD ===== */
  function initThreeJS() {
    if (reduced || typeof THREE === "undefined") return;

    var canvas = document.getElementById("hero-canvas");
    if (!canvas) return;

    var scene = new THREE.Scene();
    var camera = new THREE.PerspectiveCamera(75, 1, 0.1, 1000);
    var renderer = new THREE.WebGLRenderer({ canvas: canvas, alpha: true, antialias: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));

    var particleCount = window.innerWidth < 768 ? 280 : isLightTheme() ? 420 : 700;
    var positions = new Float32Array(particleCount * 3);
    var velocities = [];
    var i;

    for (i = 0; i < particleCount; i++) {
      positions[i * 3] = (Math.random() - 0.5) * 20;
      positions[i * 3 + 1] = (Math.random() - 0.5) * 20;
      positions[i * 3 + 2] = (Math.random() - 0.5) * 10;
      velocities.push({
        x: (Math.random() - 0.5) * 0.002,
        y: (Math.random() - 0.5) * 0.002,
        z: (Math.random() - 0.5) * 0.001,
      });
    }

    var geometry = new THREE.BufferGeometry();
    geometry.setAttribute("position", new THREE.BufferAttribute(positions, 3));

    var material = new THREE.PointsMaterial({
      color: 0xcfff04,
      size: 0.03,
      transparent: true,
      opacity: 0.55,
      blending: THREE.AdditiveBlending,
      sizeAttenuation: true,
    });

    function applyParticleTheme() {
      if (isLightTheme()) {
        material.color.setHex(0x6b8f00);
        material.opacity = 0.22;
        material.size = 0.032;
        material.blending = THREE.NormalBlending;
      } else {
        material.color.setHex(0xcfff04);
        material.opacity = 0.55;
        material.size = 0.03;
        material.blending = THREE.AdditiveBlending;
      }
      material.needsUpdate = true;
    }

    applyParticleTheme();

    var particles = new THREE.Points(geometry, material);
    scene.add(particles);
    camera.position.z = 5;

    var mouseX = 0;
    var mouseY = 0;
    var running = true;

    function resize() {
      var rect = hero.getBoundingClientRect();
      var w = Math.max(1, rect.width || window.innerWidth);
      var h = Math.max(1, rect.height || window.innerHeight);
      camera.aspect = w / h;
      camera.updateProjectionMatrix();
      renderer.setSize(w, h, false);
    }

    resize();
    on(window, "resize", resize);
    on(document, "mousemove", function (e) {
      mouseX = (e.clientX / window.innerWidth - 0.5) * 2;
      mouseY = (e.clientY / window.innerHeight - 0.5) * 2;
    });

    if (window.MutationObserver) {
      var themeObs = new MutationObserver(applyParticleTheme);
      themeObs.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ["data-theme"],
      });
      on(window, "pagehide", function () {
        themeObs.disconnect();
      });
    }

    function animate() {
      if (!running) return;
      raf(animate);

      var posArray = geometry.attributes.position.array;
      for (i = 0; i < particleCount; i++) {
        posArray[i * 3] += velocities[i].x;
        posArray[i * 3 + 1] += velocities[i].y;
        posArray[i * 3 + 2] += velocities[i].z;
        if (Math.abs(posArray[i * 3]) > 10) velocities[i].x *= -1;
        if (Math.abs(posArray[i * 3 + 1]) > 10) velocities[i].y *= -1;
        if (Math.abs(posArray[i * 3 + 2]) > 5) velocities[i].z *= -1;
      }
      geometry.attributes.position.needsUpdate = true;

      camera.position.x += (mouseX * 0.5 - camera.position.x) * 0.02;
      camera.position.y += (-mouseY * 0.5 - camera.position.y) * 0.02;
      camera.lookAt(scene.position);
      particles.rotation.y += 0.0003;
      renderer.render(scene, camera);
    }

    animate();

    on(window, "pagehide", function () {
      running = false;
      try {
        geometry.dispose();
        material.dispose();
        renderer.dispose();
      } catch (e) {
        /* noop */
      }
    });
  }

  function animateStats() {
    if (reduced) return;
    var stats = hero.querySelectorAll(".hero-v2__stat-num");
    stats.forEach(function (stat) {
      var finalText = stat.textContent || "";
      var hasPlus = finalText.indexOf("+") !== -1;
      var num = parseInt(finalText.replace(/\D/g, ""), 10);
      if (!num && num !== 0) return;
      var current = 0;
      var increment = num / 60;
      var interval = setInterval(function () {
        current += increment;
        if (current >= num) {
          current = num;
          clearInterval(interval);
        }
        stat.textContent = Math.floor(current) + (hasPlus ? "+" : "");
      }, 30);
      intervals.push(interval);
    });
  }

  function revealValuePanelFallback() {
    hero.querySelectorAll("#heroValue, .hero-value__card, .hero-value__pillar").forEach(function (el) {
      el.style.opacity = "1";
      el.style.transform = "none";
    });
  }

  function initEntrance() {
    var logos = hero.querySelector("#heroLogos");
    var scrollCue = hero.querySelector("#scrollCue");
    var valueRoot = hero.querySelector("#heroValue");

    if (typeof gsap === "undefined") {
      hero.querySelectorAll(
        ".ai-badge, .hero-v2__eyebrow, .hero-v2__title-line, .hero-v2__subtitle, .hero-v2__stats, .hero-v2__ctas, #heroLogos, #heroValue, .hero-value__card, .hero-value__pillar, #scrollCue, .hero-v2__marquee, .ambient-orb, .grid-overlay"
      ).forEach(function (el) {
        el.style.opacity = "1";
        if (el.id !== "scrollCue") el.style.transform = "none";
      });
      animateStats();
      return;
    }

    if (reduced) {
      gsap.set(
        [
          ".ai-badge",
          ".hero-v2__eyebrow",
          ".hero-v2__title-line",
          ".hero-v2__subtitle",
          ".hero-v2__stats",
          ".hero-v2__ctas",
          "#heroLogos",
          "#heroValue",
          ".hero-value__card",
          ".hero-value__pillar",
          ".hero-v2__marquee",
          ".ambient-orb",
          ".grid-overlay",
        ],
        { opacity: 1, y: 0, clearProps: "transform" }
      );
      if (scrollCue) gsap.set(scrollCue, { opacity: 0.85, xPercent: -50 });
      return;
    }

    if (scrollCue) gsap.set(scrollCue, { xPercent: -50, opacity: 0 });
    if (logos) gsap.set(logos, { opacity: 0, y: 16 });
    if (valueRoot) gsap.set(valueRoot, { opacity: 0 });
    gsap.set(".hero-value__card", { opacity: 0, y: 22 });
    gsap.set(".hero-value__pillar", { opacity: 0, y: 14 });

    var tl = gsap.timeline({ defaults: { ease: "power3.out" } });

    tl.to(".ambient-orb", { opacity: 1, duration: 1.5, delay: 0.15 });
    tl.to("#gridOverlay", { opacity: 1, duration: 1 }, "-=1");
    tl.to("#aiBadge", { opacity: 1, y: 0, duration: 0.6 }, "-=0.6");
    tl.to("#heroEyebrow", { opacity: 1, duration: 0.6 }, "-=0.3");

    var titleLines = hero.querySelectorAll(".hero-v2__title-line");
    titleLines.forEach(function (line, i) {
      tl.to(line, { opacity: 1, y: 0, duration: 0.8, ease: "power4.out" }, i === 0 ? "-=0.2" : "-=0.5");
    });

    tl.add(function () {
      var accentLine = hero.querySelector(".glitch-text");
      if (!accentLine) return;
      accentLine.classList.add("active");
      setTimeout(function () {
        accentLine.classList.remove("active");
      }, 400);
    }, "-=0.3");

    tl.to("#heroSubtitle", { opacity: 1, y: 0, duration: 0.7 }, "-=0.4");
    tl.to("#heroStats", { opacity: 1, duration: 0.6 }, "-=0.3");
    tl.to("#heroCtas", { opacity: 1, y: 0, duration: 0.6 }, "-=0.2");
    if (logos) {
      tl.to(logos, { opacity: 1, y: 0, duration: 0.7 }, "-=0.25");
    }

    if (valueRoot) {
      tl.to("#heroValue", { opacity: 1, duration: 0.5, ease: "power2.out" }, "-=0.85");
      tl.to(
        ".hero-value__card",
        { opacity: 1, y: 0, duration: 0.75, ease: "power3.out" },
        "-=0.35"
      );
      tl.to(
        ".hero-value__pillar",
        { opacity: 1, y: 0, duration: 0.55, stagger: 0.1, ease: "power2.out" },
        "-=0.4"
      );
    } else {
      revealValuePanelFallback();
    }

    if (scrollCue) {
      tl.to(scrollCue, { opacity: 0.85, duration: 0.6 }, "-=0.5");
    }
    tl.to("#heroMarquee", { opacity: 1, duration: 0.8 }, "-=0.3");

    animateStats();
  }

  function boot() {
    initThreeJS();
    initEntrance();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot);
  } else {
    boot();
  }
})();
