/**
 * Hero v2 — site-wide ambient glows + soft-circle particles + orb automation canvas.
 * Atmosphere: .site-ambient (glows) → #hero-canvas.site-particles → content.
 * Dor → Solução → Ações → Resultado. Curved wires + execution pulse.
 * Stories: Atendimento · RH · Operação. Respects prefers-reduced-motion.
 */
(function () {
  "use strict";

  var hero = document.getElementById("hero");
  if (!hero || !hero.classList.contains("hero-v2")) return;

  var reduced =
    window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  var rafIds = [];
  var intervals = [];
  var timeouts = [];
  var listeners = [];
  var storyCycleTimer = null;
  var storyResumeTimer = null;
  var pulseTimer = null;
  var edgeDrawTimer = null;
  var resizeTimer = null;
  var currentStory = "atendimento";
  var pulseIndex = 0;
  var storyChanging = false;
  var flowReady = false;
  var storyAutoPaused = false;

  var STORY_ORDER = ["atendimento", "rh", "operacao"];
  var PULSE_SEQUENCE = ["pain", "solution", "actionA", "actionB", "result"];

  var ICONS = {
    chat:
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M8 10h.01M12 10h.01M16 10h.01"/><path d="M21 11.5a8.4 8.4 0 0 1-1.7 5.1L21 21l-4.4-1.2A8.5 8.5 0 1 1 21 11.5z"/></svg>',
    bot:
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="9" width="14" height="10" rx="2"/><path d="M9 9V7a3 3 0 0 1 6 0v2"/><circle cx="9.5" cy="14" r="1" fill="currentColor" stroke="none"/><circle cx="14.5" cy="14" r="1" fill="currentColor" stroke="none"/><path d="M12 3v2"/></svg>',
    calendar:
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/></svg>',
    sync:
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h10v10H4z"/><path d="M14 10h6v7h-6z"/><path d="M10 12h4"/></svg>',
    check:
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>',
    clock:
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
    users:
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
    sheet:
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M8 13h8M8 17h5"/></svg>',
    flow:
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3v6a3 3 0 0 0 3 3h6a3 3 0 0 1 3 3v6"/><circle cx="6" cy="3" r="2"/><circle cx="18" cy="21" r="2"/><circle cx="12" cy="12" r="2"/></svg>',
    alert:
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M10.3 3.9 1.9 18a2 2 0 0 0 1.7 3h16.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/></svg>',
  };

  var STORIES = {
    atendimento: {
      tabId: "flowTabAtendimento",
      panelLabel: "Da demanda sem resposta à conversão contínua",
      chip: "Atendimento que gera receita",
      orbs: {
        pain: { title: "Demanda sem resposta", sub: "Cliente espera, receita some", icon: "chat" },
        solution: { title: "Agente que atende e vende", sub: "Qualifica e resolve sozinho", icon: "bot" },
        actionA: { title: "Agenda e prioriza", icon: "calendar" },
        actionB: { title: "Atualiza sistemas", icon: "sync" },
        result: {
          title: "Conversão 24/7",
          sub: "Canal ativo, time no fechamento",
          icon: "check",
          metricValue: "24/7",
          metricLabel: "resposta com venda",
        },
      },
    },
    rh: {
      tabId: "flowTabRh",
      panelLabel: "Da rotina manual ao RH estratégico",
      chip: "RH que escala com IA",
      orbs: {
        pain: { title: "Rotina manual no RH", sub: "Tempo perdido em tarefa", icon: "clock" },
        solution: { title: "Automação de pessoas", sub: "Processos sob controle", icon: "bot" },
        actionA: { title: "Folha e jornada", icon: "sheet" },
        actionB: { title: "Alertas e compliance", icon: "users" },
        result: {
          title: "RH estratégico",
          sub: "Foco em gente e resultado",
          icon: "check",
          metricValue: "−60%",
          metricLabel: "tempo em rotina",
        },
      },
    },
    operacao: {
      tabId: "flowTabOperacao",
      panelLabel: "Do processo fragmentado à operação única",
      chip: "Operação conectada com IA",
      orbs: {
        pain: { title: "Processo fragmentado", sub: "Dados e times soltos", icon: "sheet" },
        solution: { title: "Fluxo com IA", sub: "Um caminho ponta a ponta", icon: "flow" },
        actionA: { title: "Integra áreas", icon: "sync" },
        actionB: { title: "Visão em tempo real", icon: "alert" },
        result: {
          title: "Operação única",
          sub: "Menos atrito, mais entrega",
          icon: "check",
          metricValue: "1 fluxo",
          metricLabel: "da ponta à entrega",
        },
      },
    },
  };

  function on(target, type, fn, opts) {
    target.addEventListener(type, fn, opts);
    listeners.push([target, type, fn, opts]);
  }

  function raf(fn) {
    var id = requestAnimationFrame(fn);
    rafIds.push(id);
    return id;
  }

  function later(fn, ms) {
    var id = setTimeout(fn, ms);
    timeouts.push(id);
    return id;
  }

  function cleanup() {
    flowReady = false;
    rafIds.forEach(function (id) {
      cancelAnimationFrame(id);
    });
    rafIds = [];
    intervals.forEach(function (id) {
      clearInterval(id);
    });
    intervals = [];
    timeouts.forEach(function (id) {
      clearTimeout(id);
    });
    timeouts = [];
    if (storyCycleTimer) {
      clearInterval(storyCycleTimer);
      storyCycleTimer = null;
    }
    if (storyResumeTimer) {
      clearTimeout(storyResumeTimer);
      storyResumeTimer = null;
    }
    if (pulseTimer) {
      clearTimeout(pulseTimer);
      pulseTimer = null;
    }
    if (edgeDrawTimer) {
      clearTimeout(edgeDrawTimer);
      edgeDrawTimer = null;
    }
    if (resizeTimer) {
      clearTimeout(resizeTimer);
      resizeTimer = null;
    }
    listeners.forEach(function (entry) {
      entry[0].removeEventListener(entry[1], entry[2], entry[3]);
    });
    listeners = [];
  }

  on(window, "pagehide", cleanup);

  function isLightTheme() {
    return document.documentElement.getAttribute("data-theme") === "light";
  }

  function createCircleTexture() {
    var size = 64;
    var c = document.createElement("canvas");
    c.width = size;
    c.height = size;
    var ctx = c.getContext("2d");
    if (!ctx) return null;
    var g = ctx.createRadialGradient(size / 2, size / 2, 0, size / 2, size / 2, size / 2);
    g.addColorStop(0, "rgba(255,255,255,1)");
    g.addColorStop(0.28, "rgba(255,255,255,0.9)");
    g.addColorStop(0.55, "rgba(255,255,255,0.35)");
    g.addColorStop(1, "rgba(255,255,255,0)");
    ctx.fillStyle = g;
    ctx.fillRect(0, 0, size, size);
    var tex = new THREE.CanvasTexture(c);
    tex.needsUpdate = true;
    return tex;
  }

  function initThreeJS() {
    if (reduced || typeof THREE === "undefined") return;

    var canvas = document.getElementById("hero-canvas");
    if (!canvas) return;

    if (!canvas.classList.contains("site-particles")) {
      canvas.classList.add("site-particles");
    }

    var isMobile = window.innerWidth < 768;
    var isTablet = !isMobile && window.innerWidth < 1100;
    var particleCount = isMobile ? 180 : isTablet ? 360 : 520;
    var maxPixelRatio = isMobile ? 1.25 : 1.75;
    var frameSkip = isMobile ? 1 : 0; /* mobile: render every other raf (~30fps) */

    var scene = new THREE.Scene();
    var camera = new THREE.PerspectiveCamera(75, 1, 0.1, 1000);
    var renderer = new THREE.WebGLRenderer({
      canvas: canvas,
      alpha: true,
      antialias: !isMobile,
      powerPreference: "low-power",
    });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, maxPixelRatio));
    renderer.setClearColor(0x000000, 0);

    var positions = new Float32Array(particleCount * 3);
    var velocities = [];
    var i;

    for (i = 0; i < particleCount; i++) {
      positions[i * 3] = (Math.random() - 0.5) * 22;
      positions[i * 3 + 1] = (Math.random() - 0.5) * 22;
      positions[i * 3 + 2] = (Math.random() - 0.5) * 12;
      velocities.push({
        x: (Math.random() - 0.5) * 0.002,
        y: (Math.random() - 0.5) * 0.002,
        z: (Math.random() - 0.5) * 0.001,
      });
    }

    var geometry = new THREE.BufferGeometry();
    geometry.setAttribute("position", new THREE.BufferAttribute(positions, 3));

    var circleMap = createCircleTexture();
    var material = new THREE.PointsMaterial({
      color: 0xcfff04,
      size: 0.055,
      map: circleMap || undefined,
      transparent: true,
      opacity: 0.55,
      blending: THREE.AdditiveBlending,
      depthWrite: false,
      sizeAttenuation: true,
    });

    function applyParticleTheme() {
      if (isLightTheme()) {
        material.color.setHex(0x7a9a00);
        material.opacity = 0.22;
        material.size = 0.048;
        material.blending = THREE.NormalBlending;
      } else {
        material.color.setHex(0xcfff04);
        material.opacity = 0.48;
        material.size = 0.055;
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
    var visible = !document.hidden;
    var frame = 0;

    function resizeParticles() {
      var w = Math.max(1, window.innerWidth);
      var h = Math.max(1, window.innerHeight);
      camera.aspect = w / h;
      camera.updateProjectionMatrix();
      renderer.setSize(w, h, false);
    }

    resizeParticles();
    on(window, "resize", resizeParticles);
    on(document, "mousemove", function (e) {
      mouseX = (e.clientX / window.innerWidth - 0.5) * 2;
      mouseY = (e.clientY / window.innerHeight - 0.5) * 2;
    });

    on(document, "visibilitychange", function () {
      visible = !document.hidden;
      if (visible && running) animate();
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
      if (!running || !visible) return;
      raf(animate);

      frame += 1;
      if (frameSkip && frame % (frameSkip + 1) !== 0) return;

      var posArray = geometry.attributes.position.array;
      for (i = 0; i < particleCount; i++) {
        posArray[i * 3] += velocities[i].x;
        posArray[i * 3 + 1] += velocities[i].y;
        posArray[i * 3 + 2] += velocities[i].z;
        if (Math.abs(posArray[i * 3]) > 11) velocities[i].x *= -1;
        if (Math.abs(posArray[i * 3 + 1]) > 11) velocities[i].y *= -1;
        if (Math.abs(posArray[i * 3 + 2]) > 6) velocities[i].z *= -1;
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
        if (circleMap) circleMap.dispose();
        renderer.dispose();
      } catch (e) {
        /* noop */
      }
    });
  }

  /* ===== ORB AUTOMATION CANVAS ===== */
  function orbCenter(boardRect, el) {
    var circle = el.querySelector(".orb__circle") || el;
    var r = circle.getBoundingClientRect();
    return {
      x: r.left - boardRect.left + r.width / 2,
      y: r.top - boardRect.top + r.height / 2,
    };
  }

  function cubicPath(a, b) {
    var dx = b.x - a.x;
    var dy = b.y - a.y;
    var absDx = Math.abs(dx);
    var absDy = Math.abs(dy);
    var c1x;
    var c1y;
    var c2x;
    var c2y;

    if (absDy > absDx * 0.7 && absDy > 20) {
      var drop = Math.max(absDy * 0.4, 20);
      c1x = a.x;
      c1y = a.y + (dy >= 0 ? drop : -drop);
      c2x = b.x;
      c2y = b.y - (dy >= 0 ? drop * 0.5 : -drop * 0.5);
    } else {
      var c = Math.max(absDx * 0.4, 24);
      c1x = a.x + (dx >= 0 ? c : -c);
      c1y = a.y;
      c2x = b.x - (dx >= 0 ? c : -c);
      c2y = b.y;
    }

    return (
      "M " +
      a.x.toFixed(1) +
      " " +
      a.y.toFixed(1) +
      " C " +
      c1x.toFixed(1) +
      " " +
      c1y.toFixed(1) +
      ", " +
      c2x.toFixed(1) +
      " " +
      c2y.toFixed(1) +
      ", " +
      b.x.toFixed(1) +
      " " +
      b.y.toFixed(1)
    );
  }

  function scheduleWires() {
    if (edgeDrawTimer) clearTimeout(edgeDrawTimer);
    edgeDrawTimer = setTimeout(function () {
      edgeDrawTimer = null;
      drawWires();
    }, 32);
  }

  function drawWires() {
    var board = document.getElementById("heroOrbsBoard");
    var svg = document.getElementById("heroOrbsWires");
    var canvas = document.getElementById("heroOrbsCanvas");
    if (!board || !svg || !canvas) return;

    var canvasRect = canvas.getBoundingClientRect();
    var w = canvas.clientWidth;
    var h = canvas.clientHeight;
    if (w < 2 || h < 2) return;

    svg.setAttribute("viewBox", "0 0 " + w + " " + h);
    svg.setAttribute("width", String(w));
    svg.setAttribute("height", String(h));

    var byKey = {};
    board.querySelectorAll(".orb[data-orb]").forEach(function (orb) {
      byKey[orb.getAttribute("data-orb")] = orb;
    });

    // Spine + downward branches only — no crossing merge wires
    var pairs = [
      ["pain", "solution", true],
      ["solution", "result", true],
      ["solution", "actionA", false],
      ["solution", "actionB", false],
    ];

    var ns = "http://www.w3.org/2000/svg";
    while (svg.firstChild) svg.removeChild(svg.firstChild);

    pairs.forEach(function (pair) {
      var from = byKey[pair[0]];
      var to = byKey[pair[1]];
      if (!from || !to) return;
      var a = orbCenter(canvasRect, from);
      var b = orbCenter(canvasRect, to);
      var path = document.createElementNS(ns, "path");
      path.setAttribute("d", cubicPath(a, b));
      path.setAttribute("data-from", pair[0]);
      path.setAttribute("data-to", pair[1]);
      path.setAttribute("class", "hero-orbs__wire" + (pair[2] ? " hero-orbs__wire--spine" : ""));
      svg.appendChild(path);
    });

    updateWirePulse(PULSE_SEQUENCE[pulseIndex] || "pain");
  }

  function updateWirePulse(activeKey) {
    var svg = document.getElementById("heroOrbsWires");
    if (!svg) return;
    svg.querySelectorAll(".hero-orbs__wire").forEach(function (wire) {
      var lit =
        wire.getAttribute("data-from") === activeKey ||
        wire.getAttribute("data-to") === activeKey;
      wire.classList.toggle("is-lit", lit);
    });
  }

  function setRunningOrb(key) {
    var board = document.getElementById("heroOrbsBoard");
    if (!board) return;
    board.querySelectorAll(".orb").forEach(function (orb) {
      var match = orb.getAttribute("data-orb") === key;
      orb.classList.toggle("is-running", match);
      if (match && !reduced) {
        orb.classList.remove("is-pulse");
        void orb.offsetWidth;
        orb.classList.add("is-pulse");
      }
    });
    updateWirePulse(key);
  }

  function applyStoryContent(storyKey) {
    var story = STORIES[storyKey];
    if (!story) return;

    var flow = document.getElementById("heroFlow");
    var panel = document.getElementById("heroFlowPanel");
    var board = document.getElementById("heroOrbsBoard");
    var caption = document.getElementById("heroFlowChip");

    if (flow) flow.setAttribute("data-story", storyKey);
    if (panel) panel.setAttribute("aria-label", story.panelLabel);
    if (board) board.setAttribute("aria-labelledby", story.tabId);

    Object.keys(story.orbs).forEach(function (key) {
      var orb = board && board.querySelector('.orb[data-orb="' + key + '"]');
      if (!orb) return;
      var data = story.orbs[key];
      var title = orb.querySelector(".orb__title");
      var sub = orb.querySelector(".orb__sub");
      var icon = orb.querySelector(".orb__icon");
      if (title) title.textContent = data.title;
      if (sub) sub.textContent = data.sub || "";
      if (icon) icon.innerHTML = ICONS[data.icon] || ICONS.check;
      if (key === "result") {
        var mv = orb.querySelector(".orb__metric-value");
        var ml = orb.querySelector(".orb__metric-label");
        if (mv) mv.textContent = data.metricValue;
        if (ml) ml.textContent = data.metricLabel;
      }
    });

    if (caption) {
      var ct = caption.querySelector(".hero-orbs__caption-text");
      if (ct) ct.textContent = story.chip;
    }

    document.querySelectorAll(".hero-orbs__tab").forEach(function (tab) {
      var active = tab.getAttribute("data-story") === storyKey;
      tab.classList.toggle("is-active", active);
      tab.setAttribute("aria-selected", active ? "true" : "false");
      tab.tabIndex = active ? 0 : -1;
    });
  }

  function markOrbsReady(ready) {
    hero.querySelectorAll(".orb, .hero-orbs__caption").forEach(function (el) {
      el.classList.toggle("is-ready", !!ready);
      if (ready) {
        el.style.opacity = "";
        el.style.transform = "";
      }
    });
  }

  function clearOrbInlineMotion() {
    if (typeof gsap !== "undefined") {
      gsap.killTweensOf(".orb, .hero-orbs__caption");
      gsap.set(".orb, .hero-orbs__caption", { clearProps: "transform,opacity" });
    }
    markOrbsReady(true);
  }

  function revealOrbs(onDone) {
    var parts = hero.querySelectorAll(".orb, .hero-orbs__caption");
    var doneOnce = false;
    var finish = function () {
      if (doneOnce) return;
      doneOnce = true;
      clearOrbInlineMotion();
      drawWires();
      if (onDone) onDone();
    };

    if (!parts.length) {
      finish();
      return;
    }

    if (reduced || typeof gsap === "undefined") {
      finish();
      return;
    }

    markOrbsReady(false);
    gsap.killTweensOf(parts);
    gsap.set(parts, { opacity: 0, scale: 0.88 });
    gsap.to(parts, {
      opacity: 1,
      scale: 1,
      duration: 0.42,
      stagger: 0.07,
      ease: "power2.out",
      onComplete: finish,
    });
    later(finish, 1200);
  }

  function switchStory(storyKey, opts) {
    opts = opts || {};
    if (!STORIES[storyKey] || (storyKey === currentStory && !opts.force)) return;
    if (storyChanging) return;
    storyChanging = true;

    var canvas = document.getElementById("heroOrbsCanvas");
    var parts = hero.querySelectorAll(".orb, .hero-orbs__caption");
    var applied = false;
    stopPulseCycle();

    var finish = function () {
      if (applied) return;
      applied = true;
      applyStoryContent(storyKey);
      currentStory = storyKey;
      if (canvas) canvas.classList.remove("is-switching");
      revealOrbs(function () {
        storyChanging = false;
        if (!opts.skipRestartPulse) startPulseCycle();
      });
    };

    if (reduced || typeof gsap === "undefined") {
      finish();
      return;
    }

    if (canvas) canvas.classList.add("is-switching");
    gsap.killTweensOf(parts);
    gsap.to(parts, {
      opacity: 0,
      scale: 0.92,
      duration: 0.18,
      stagger: 0.02,
      ease: "power2.in",
      onComplete: finish,
    });
    later(finish, 700);
  }

  function startPulseCycle() {
    stopPulseCycle();
    pulseIndex = 0;
    flowReady = true;

    if (reduced) {
      setRunningOrb("solution");
      return;
    }

    var step = function () {
      if (!flowReady) return;
      setRunningOrb(PULSE_SEQUENCE[pulseIndex]);
      pulseIndex = (pulseIndex + 1) % PULSE_SEQUENCE.length;
      pulseTimer = setTimeout(step, 1300);
    };
    step();
  }

  function stopPulseCycle() {
    flowReady = false;
    if (pulseTimer) {
      clearTimeout(pulseTimer);
      pulseTimer = null;
    }
    hero.querySelectorAll(".orb.is-running, .orb.is-pulse").forEach(function (orb) {
      orb.classList.remove("is-running", "is-pulse");
    });
  }

  function stopStoryAutoCycle() {
    if (storyCycleTimer) {
      clearInterval(storyCycleTimer);
      storyCycleTimer = null;
    }
  }

  function startStoryAutoCycle() {
    stopStoryAutoCycle();
    if (reduced || storyAutoPaused) return;
    storyCycleTimer = setInterval(function () {
      if (storyChanging || storyAutoPaused) return;
      var idx = STORY_ORDER.indexOf(currentStory);
      switchStory(STORY_ORDER[(idx + 1) % STORY_ORDER.length]);
    }, 9000);
  }

  function pauseStoryAutoCycle(opts) {
    opts = opts || {};
    storyAutoPaused = true;
    stopStoryAutoCycle();
    if (storyResumeTimer) {
      clearTimeout(storyResumeTimer);
      storyResumeTimer = null;
    }
    if (opts.resumeMs) {
      storyResumeTimer = setTimeout(function () {
        storyResumeTimer = null;
        storyAutoPaused = false;
        startStoryAutoCycle();
      }, opts.resumeMs);
    }
  }

  function resetStoryAutoCycle() {
    /* Manual tab change: pause briefly so the chosen story can be read */
    pauseStoryAutoCycle({ resumeMs: 14000 });
  }

  function initTouchPause() {
    var flow = document.getElementById("heroFlow");
    if (!flow || reduced) return;

    var onInteract = function () {
      pauseStoryAutoCycle({ resumeMs: 16000 });
    };

    on(flow, "pointerdown", onInteract, { passive: true });
    on(flow, "touchstart", onInteract, { passive: true });
    on(flow, "focusin", onInteract);
  }

  function initOrbHover() {
    var board = document.getElementById("heroOrbsBoard");
    var svg = document.getElementById("heroOrbsWires");
    if (!board || !svg || reduced) return;

    board.querySelectorAll(".orb[data-orb]").forEach(function (orb) {
      on(orb, "mouseenter", function () {
        orb.classList.add("is-hovered");
        var key = orb.getAttribute("data-orb");
        svg.querySelectorAll(".hero-orbs__wire").forEach(function (wire) {
          if (wire.getAttribute("data-from") === key || wire.getAttribute("data-to") === key) {
            wire.classList.add("is-lit");
          }
        });
      });
      on(orb, "mouseleave", function () {
        orb.classList.remove("is-hovered");
        updateWirePulse(PULSE_SEQUENCE[pulseIndex] || "solution");
      });
    });
  }

  function initTabs() {
    var tabs = hero.querySelectorAll(".hero-orbs__tab");
    if (!tabs.length) return;

    tabs.forEach(function (tab) {
      on(tab, "click", function () {
        var key = tab.getAttribute("data-story");
        if (!key) return;
        switchStory(key);
        resetStoryAutoCycle();
      });
      on(tab, "keydown", function (e) {
        var keys = ["ArrowLeft", "ArrowRight", "Home", "End"];
        if (keys.indexOf(e.key) === -1) return;
        e.preventDefault();
        var list = Array.prototype.slice.call(tabs);
        var i = list.indexOf(tab);
        var next = i;
        if (e.key === "ArrowRight") next = (i + 1) % list.length;
        if (e.key === "ArrowLeft") next = (i - 1 + list.length) % list.length;
        if (e.key === "Home") next = 0;
        if (e.key === "End") next = list.length - 1;
        list[next].focus();
        switchStory(list[next].getAttribute("data-story"));
        resetStoryAutoCycle();
      });
    });
  }

  function initOrbs() {
    applyStoryContent(currentStory);
    scheduleWires();
    on(window, "resize", function () {
      if (resizeTimer) clearTimeout(resizeTimer);
      resizeTimer = setTimeout(drawWires, 100);
    });
    on(window, "orientationchange", function () {
      later(function () {
        drawWires();
      }, 180);
    });
    if (window.visualViewport) {
      on(window.visualViewport, "resize", function () {
        if (resizeTimer) clearTimeout(resizeTimer);
        resizeTimer = setTimeout(drawWires, 120);
      });
    }
    var canvas = document.getElementById("heroOrbsCanvas");
    if (canvas && typeof ResizeObserver !== "undefined") {
      var ro = new ResizeObserver(function () {
        scheduleWires();
      });
      ro.observe(canvas);
      on(window, "pagehide", function () {
        ro.disconnect();
      });
    }
    initOrbHover();
    initTabs();
    initTouchPause();
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

  function initEntrance() {
    var logos = hero.querySelector("#heroLogos");
    var scrollCue = hero.querySelector("#scrollCue");
    var flowRoot = hero.querySelector("#heroFlow");

    if (typeof gsap === "undefined") {
      hero
        .querySelectorAll(
          ".ai-badge, .hero-v2__eyebrow, .hero-v2__title-line, .hero-v2__subtitle, .hero-v2__stats, .hero-v2__ctas, #heroLogos, #heroFlow, .orb, .hero-orbs__caption, #scrollCue, .hero-v2__marquee, .hero-orbs__tabs"
        )
        .forEach(function (el) {
          el.style.opacity = "1";
          if (el.id !== "scrollCue") el.style.transform = "none";
        });
      document.querySelectorAll(".site-ambient .ambient-orb").forEach(function (el) {
        el.style.opacity = "1";
      });
      drawWires();
      animateStats();
      startPulseCycle();
      startStoryAutoCycle();
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
          "#heroFlow",
          ".orb",
          ".hero-orbs__caption",
          ".hero-v2__marquee",
          ".site-ambient .ambient-orb",
          ".hero-orbs__tabs",
        ],
        { opacity: 1, y: 0, clearProps: "transform" }
      );
      if (scrollCue) gsap.set(scrollCue, { opacity: 0.85, xPercent: -50 });
      drawWires();
      setRunningOrb("solution");
      return;
    }

    if (scrollCue) gsap.set(scrollCue, { xPercent: -50, opacity: 0 });
    if (logos) gsap.set(logos, { opacity: 0, y: 16 });
    if (flowRoot) gsap.set(flowRoot, { opacity: 0 });
    gsap.set(".orb, .hero-orbs__caption", { opacity: 0, scale: 0.88 });
    gsap.set(".hero-orbs__tabs", { opacity: 0, y: 8 });

    var tl = gsap.timeline({ defaults: { ease: "power3.out" } });

    tl.to(".site-ambient .ambient-orb", { opacity: 1, duration: 1.5, delay: 0.15 });
    tl.to("#aiBadge", { opacity: 1, y: 0, duration: 0.6 }, "-=0.6");
    tl.to("#heroEyebrow", { opacity: 1, duration: 0.6 }, "-=0.3");

    var titleLines = hero.querySelectorAll(".hero-v2__title-line");
    titleLines.forEach(function (line, i) {
      tl.to(line, { opacity: 1, y: 0, duration: 0.8, ease: "power4.out" }, i === 0 ? "-=0.2" : "-=0.5");
    });

    tl.to("#heroSubtitle", { opacity: 1, y: 0, duration: 0.7 }, "-=0.4");
    tl.to("#heroStats", { opacity: 1, duration: 0.6 }, "-=0.3");
    tl.to("#heroCtas", { opacity: 1, y: 0, duration: 0.6 }, "-=0.2");
    if (logos) {
      tl.to(logos, { opacity: 1, y: 0, duration: 0.7 }, "-=0.25");
    }

    if (flowRoot) {
      tl.to("#heroFlow", { opacity: 1, duration: 0.5, ease: "power2.out" }, "-=0.85");
      tl.to(".hero-orbs__tabs", { opacity: 1, y: 0, duration: 0.4 }, "-=0.35");
      tl.to(
        ".orb, .hero-orbs__caption",
        {
          opacity: 1,
          scale: 1,
          duration: 0.5,
          stagger: 0.08,
          ease: "power2.out",
          onComplete: function () {
            clearOrbInlineMotion();
            drawWires();
            startPulseCycle();
            startStoryAutoCycle();
          },
        },
        "-=0.2"
      );
      tl.add(scheduleWires, "-=0.1");
    }

    if (scrollCue) {
      tl.to(scrollCue, { opacity: 0.85, duration: 0.6 }, "-=0.5");
    }
    tl.to("#heroMarquee", { opacity: 1, duration: 0.8 }, "-=0.3");

    animateStats();
  }

  function boot() {
    initThreeJS();
    initOrbs();
    initEntrance();
    requestAnimationFrame(function () {
      requestAnimationFrame(drawWires);
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot);
  } else {
    boot();
  }
})();
