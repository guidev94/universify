/**
 * Hero v2 — Three.js particles, neural orbit, GSAP entrance.
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

  /* ===== THREE.JS PARTICLE FIELD ===== */
  function initThreeJS() {
    if (reduced || typeof THREE === "undefined") return;

    var canvas = document.getElementById("hero-canvas");
    if (!canvas) return;

    var scene = new THREE.Scene();
    var camera = new THREE.PerspectiveCamera(75, 1, 0.1, 1000);
    var renderer = new THREE.WebGLRenderer({ canvas: canvas, alpha: true, antialias: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));

    var particleCount = window.innerWidth < 768 ? 400 : 800;
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
      opacity: 0.6,
      blending: THREE.AdditiveBlending,
      sizeAttenuation: true,
    });

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

  function drawConnections() {
    var svg = document.getElementById("connectionsSvg");
    var centralOrb = document.getElementById("centralOrb");
    var nodes = hero.querySelectorAll(".satellite-node");
    var container = document.getElementById("neuralContainer");
    if (!svg || !centralOrb || !container || !nodes.length) return;

    var angle = 0;
    var orbitRadius = window.innerWidth >= 1024 ? 140 : 110;
    var orbiting = !reduced && window.innerWidth >= 1024;

    function updateConnections() {
      var containerRect = container.getBoundingClientRect();
      var centerX = containerRect.width / 2;
      var centerY = containerRect.height / 2;
      var paths = "";
      nodes.forEach(function (node) {
        var nodeRect = node.getBoundingClientRect();
        var nodeX = nodeRect.left + nodeRect.width / 2 - containerRect.left;
        var nodeY = nodeRect.top + nodeRect.height / 2 - containerRect.top;
        var midX = (centerX + nodeX) / 2;
        var midY = (centerY + nodeY) / 2;
        var offset = 20;
        paths +=
          '<path class="connection-line" d="M' +
          centerX +
          "," +
          centerY +
          " Q" +
          (midX + offset) +
          "," +
          (midY - offset) +
          " " +
          nodeX +
          "," +
          nodeY +
          '"/>';
      });
      svg.innerHTML = paths;
    }

    function placeStatic() {
      nodes.forEach(function (node, i) {
        var nodeAngle = i * ((Math.PI * 2) / nodes.length) - Math.PI / 2;
        var x = Math.cos(nodeAngle) * orbitRadius;
        var y = Math.sin(nodeAngle) * orbitRadius;
        node.style.transform = "translate(" + x + "px, " + y + "px)";
      });
      updateConnections();
    }

    function orbitNodes() {
      if (!orbiting) return;
      angle += 0.003;
      nodes.forEach(function (node, i) {
        var nodeAngle = angle + i * ((Math.PI * 2) / nodes.length);
        var x = Math.cos(nodeAngle) * orbitRadius;
        var y = Math.sin(nodeAngle) * orbitRadius;
        node.style.transform = "translate(" + x + "px, " + y + "px)";
      });
      updateConnections();
      raf(orbitNodes);
    }

    placeStatic();
    on(window, "resize", function () {
      orbitRadius = window.innerWidth >= 1024 ? 140 : 110;
      if (!orbiting) placeStatic();
      else updateConnections();
    });

    if (orbiting) orbitNodes();
  }

  function animateDataPackets() {
    if (reduced) return;
    var container = document.getElementById("neuralContainer");
    if (!container) return;

    function createPacket() {
      if (window.innerWidth < 1024) return;
      var packet = document.createElement("div");
      packet.className = "data-packet";
      container.appendChild(packet);
      var startAngle = Math.random() * Math.PI * 2;
      var startRadius = 140;
      var endRadius = 0;
      var duration = 2000 + Math.random() * 1000;
      var startTime = Date.now();
      var centerX = container.offsetWidth / 2;
      var centerY = container.offsetHeight / 2;

      function updatePacket() {
        var elapsed = Date.now() - startTime;
        var progress = Math.min(elapsed / duration, 1);
        var easeProgress = 1 - Math.pow(1 - progress, 3);
        var currentRadius = startRadius + (endRadius - startRadius) * easeProgress;
        var x = centerX + Math.cos(startAngle) * currentRadius - 2;
        var y = centerY + Math.sin(startAngle) * currentRadius - 2;
        packet.style.left = x + "px";
        packet.style.top = y + "px";
        packet.style.opacity = String(1 - easeProgress);
        if (progress < 1) raf(updatePacket);
        else packet.remove();
      }
      raf(updatePacket);
    }

    intervals.push(setInterval(createPacket, 800));
  }

  function initParallax() {
    if (reduced) return;
    var neuralContainer = document.getElementById("neuralContainer");
    var centralOrb = document.getElementById("centralOrb");

    on(document, "mousemove", function (e) {
      var x = (e.clientX / window.innerWidth - 0.5) * 2;
      var y = (e.clientY / window.innerHeight - 0.5) * 2;
      if (neuralContainer) {
        neuralContainer.style.transform = "translate(" + x * 10 + "px, " + y * 10 + "px)";
      }
      if (centralOrb) {
        centralOrb.style.transform =
          "translate(calc(-50% + " + x * 5 + "px), calc(-50% + " + y * 5 + "px))";
      }
    });
  }

  function initEntrance() {
    if (typeof gsap === "undefined") {
      hero.querySelectorAll(
        ".ai-badge, .hero-v2__eyebrow, .hero-v2__title-line, .hero-v2__subtitle, .hero-v2__stats, .hero-v2__ctas, .neural-container, .scroll-cue, .hero-v2__marquee, .ambient-orb, .grid-overlay"
      ).forEach(function (el) {
        el.style.opacity = "1";
        el.style.transform = "none";
      });
      animateStats();
      drawConnections();
      animateDataPackets();
      initParallax();
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
          ".neural-container",
          ".scroll-cue",
          ".hero-v2__marquee",
          ".ambient-orb",
          ".grid-overlay",
        ],
        { opacity: 1, y: 0, clearProps: "transform" }
      );
      drawConnections();
      return;
    }

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
    tl.to("#neuralContainer", { opacity: 1, duration: 1.2, ease: "power2.out" }, "-=1");
    tl.to("#scrollCue", { opacity: 1, duration: 0.6 }, "-=0.5");
    tl.to("#heroMarquee", { opacity: 1, duration: 0.8 }, "-=0.3");

    animateStats();
    drawConnections();
    animateDataPackets();
    initParallax();
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
