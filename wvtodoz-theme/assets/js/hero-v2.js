/**
 * Hero v2 — soft-circle particles + editorial story strip (Dor → Solução → Resultado).
 * Stories: Atendimento · RH · Operação. No flowchart edges.
 * Respects prefers-reduced-motion.
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
  var pulseTimer = null;
  var currentStory = "atendimento";
  var pulseIndex = 0;
  var storyChanging = false;
  var flowReady = false;

  var STORY_ORDER = ["atendimento", "rh", "operacao"];
  var STEP_SEQUENCE = ["pain", "solution", "result"];

  var STORIES = {
    atendimento: {
      label: "Atendimento",
      tabId: "flowTabAtendimento",
      panelLabel: "Do WhatsApp lotado à venda no ar",
      chip: "Atendimento que converte",
      pain: { title: "WhatsApp sem resposta", text: "Lead esfria à noite — e a venda vai embora" },
      solution: { title: "Agente que vende 24/7", text: "Qualifica, responde e encaminha sem fila" },
      chips: ["Agenda e confirma", "CRM atualizado"],
      result: {
        title: "Mais vendas no ar",
        text: "Time livre pra fechar o que importa",
        metricValue: "24/7",
        metricLabel: "sem fila, com venda",
      },
      panel: {
        title: "Atendimento que vende enquanto o time descansa",
        proof: ["Lead qualificado a qualquer hora", "Vendedor foca no fechamento"],
      },
    },
    rh: {
      label: "RH",
      tabId: "flowTabRh",
      panelLabel: "Do ponto manual à folha e gestão em dia",
      chip: "RH que escala com IA",
      pain: { title: "Ponto na planilha", text: "RH apaga incêndio no Zap todo dia" },
      solution: { title: "Ponto inteligente", text: "Marca, valida e alerta sem retrabalho" },
      chips: ["Folha integrada", "Time sob controle"],
      result: {
        title: "Folha em dia, RH livre",
        text: "Foco em gente — não em planilha",
        metricValue: "100%",
        metricLabel: "folha e jornada em dia",
      },
      panel: {
        title: "RH que escala sem virar caos operacional",
        proof: ["Folha e jornada sob controle", "Menos apagar incêndio, mais gestão"],
      },
    },
    operacao: {
      label: "Operação",
      tabId: "flowTabOperacao",
      panelLabel: "Do Excel desalinhado à operação única",
      chip: "Operação sem planilha",
      pain: { title: "Tudo no Excel", text: "Times desalinhados, status em cópia" },
      solution: { title: "Fluxo automatizado", text: "Um caminho só — do pedido à entrega" },
      chips: ["Sistemas conectados", "Visão em tempo real"],
      result: {
        title: "Operação que entrega",
        text: "Menos caos, mais resultado no prazo",
        metricValue: "1 fluxo",
        metricLabel: "do lead à operação",
      },
      panel: {
        title: "Operação única — sem planilha no meio do caminho",
        proof: ["Times na mesma página", "Menos retrabalho, mais entrega"],
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
    if (pulseTimer) {
      clearTimeout(pulseTimer);
      pulseTimer = null;
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

    var scene = new THREE.Scene();
    var camera = new THREE.PerspectiveCamera(75, 1, 0.1, 1000);
    var renderer = new THREE.WebGLRenderer({ canvas: canvas, alpha: true, antialias: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));

    var particleCount = window.innerWidth < 768 ? 320 : 620;
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
        material.opacity = 0.28;
        material.size = 0.05;
        material.blending = THREE.NormalBlending;
      } else {
        material.color.setHex(0xcfff04);
        material.opacity = 0.55;
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

    function resizeParticles() {
      var rect = hero.getBoundingClientRect();
      var w = Math.max(1, rect.width || window.innerWidth);
      var h = Math.max(1, rect.height || window.innerHeight);
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
        if (circleMap) circleMap.dispose();
        renderer.dispose();
      } catch (e) {
        /* noop */
      }
    });
  }

  /* ===== EDITORIAL STORY STRIP ===== */
  function setActiveStep(key) {
    hero.querySelectorAll(".hero-step").forEach(function (step) {
      step.classList.toggle("is-active", step.getAttribute("data-step") === key);
    });
  }

  function applyStoryContent(storyKey) {
    var story = STORIES[storyKey];
    if (!story) return;

    var flow = document.getElementById("heroFlow");
    var panel = document.getElementById("heroFlowPanel");
    var stage = document.getElementById("heroStoryStage");
    var caption = document.getElementById("heroFlowChip");

    if (flow) flow.setAttribute("data-story", storyKey);
    if (panel) panel.setAttribute("aria-label", story.panelLabel);
    if (stage) stage.setAttribute("aria-labelledby", story.tabId);

    var pain = hero.querySelector('.hero-step--pain');
    var solution = hero.querySelector('.hero-step--solution');
    var result = hero.querySelector('.hero-step--result');

    if (pain) {
      var pt = pain.querySelector(".hero-step__title");
      var px = pain.querySelector(".hero-step__text");
      if (pt) pt.textContent = story.pain.title;
      if (px) px.textContent = story.pain.text;
    }
    if (solution) {
      var st = solution.querySelector(".hero-step__title");
      var sx = solution.querySelector(".hero-step__text");
      if (st) st.textContent = story.solution.title;
      if (sx) sx.textContent = story.solution.text;
      solution.querySelectorAll(".hero-step__chip").forEach(function (chip, i) {
        chip.textContent = story.chips[i] || "";
        chip.hidden = !story.chips[i];
      });
    }
    if (result) {
      var rt = result.querySelector(".hero-step__title");
      var rx = result.querySelector(".hero-step__text");
      var mv = result.querySelector(".hero-step__metric-value");
      var ml = result.querySelector(".hero-step__metric-label");
      if (rt) rt.textContent = story.result.title;
      if (rx) rx.textContent = story.result.text;
      if (mv) mv.textContent = story.result.metricValue;
      if (ml) ml.textContent = story.result.metricLabel;
    }

    var panelTitle = hero.querySelector(".hero-story__panel-title");
    if (panelTitle) panelTitle.textContent = story.panel.title;
    hero.querySelectorAll(".hero-story__proof li").forEach(function (li, i) {
      li.textContent = story.panel.proof[i] || "";
      li.hidden = !story.panel.proof[i];
    });

    if (caption) {
      var ct = caption.querySelector(".hero-story__caption-text");
      if (ct) ct.textContent = story.chip;
    }

    document.querySelectorAll(".hero-story__tab").forEach(function (tab) {
      var active = tab.getAttribute("data-story") === storyKey;
      tab.classList.toggle("is-active", active);
      tab.setAttribute("aria-selected", active ? "true" : "false");
      tab.tabIndex = active ? 0 : -1;
    });
  }

  function markStoryReady(ready) {
    hero.querySelectorAll(".hero-step, .hero-story__panel").forEach(function (el) {
      el.classList.toggle("is-ready", !!ready);
      if (ready) {
        el.style.opacity = "";
        el.style.transform = "";
      }
    });
  }

  function clearStoryInlineMotion() {
    if (typeof gsap !== "undefined") {
      gsap.killTweensOf(".hero-step, .hero-story__panel, .hero-story__caption");
      gsap.set(".hero-step, .hero-story__panel, .hero-story__caption", {
        clearProps: "transform,opacity",
      });
    }
    markStoryReady(true);
  }

  function revealStory(onDone) {
    var parts = hero.querySelectorAll(".hero-step, .hero-story__panel, .hero-story__caption");
    var doneOnce = false;
    var finish = function () {
      if (doneOnce) return;
      doneOnce = true;
      clearStoryInlineMotion();
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

    markStoryReady(false);
    gsap.killTweensOf(parts);
    gsap.set(parts, { opacity: 0, y: 16 });
    gsap.to(parts, {
      opacity: 1,
      y: 0,
      duration: 0.45,
      stagger: 0.08,
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

    var stage = document.getElementById("heroStoryStage");
    var parts = hero.querySelectorAll(".hero-step, .hero-story__panel, .hero-story__caption");
    var applied = false;
    stopPulseCycle();

    var finish = function () {
      if (applied) return;
      applied = true;
      applyStoryContent(storyKey);
      currentStory = storyKey;
      if (stage) stage.classList.remove("is-switching");
      revealStory(function () {
        storyChanging = false;
        if (!opts.skipRestartPulse) startPulseCycle();
      });
    };

    if (reduced || typeof gsap === "undefined") {
      finish();
      return;
    }

    if (stage) stage.classList.add("is-switching");
    gsap.killTweensOf(parts);
    gsap.to(parts, {
      opacity: 0,
      y: -10,
      duration: 0.2,
      stagger: 0.03,
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
      setActiveStep("solution");
      return;
    }

    var step = function () {
      if (!flowReady) return;
      setActiveStep(STEP_SEQUENCE[pulseIndex]);
      pulseIndex = (pulseIndex + 1) % STEP_SEQUENCE.length;
      pulseTimer = setTimeout(step, 1600);
    };
    step();
  }

  function stopPulseCycle() {
    flowReady = false;
    if (pulseTimer) {
      clearTimeout(pulseTimer);
      pulseTimer = null;
    }
    hero.querySelectorAll(".hero-step.is-active").forEach(function (el) {
      el.classList.remove("is-active");
    });
  }

  function startStoryAutoCycle() {
    if (storyCycleTimer) {
      clearInterval(storyCycleTimer);
      storyCycleTimer = null;
    }
    if (reduced) return;
    storyCycleTimer = setInterval(function () {
      if (storyChanging) return;
      var idx = STORY_ORDER.indexOf(currentStory);
      var next = STORY_ORDER[(idx + 1) % STORY_ORDER.length];
      switchStory(next);
    }, 9000);
  }

  function resetStoryAutoCycle() {
    startStoryAutoCycle();
  }

  function initTabs() {
    var tabs = hero.querySelectorAll(".hero-story__tab");
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

  function initStory() {
    applyStoryContent(currentStory);
    initTabs();
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
          ".ai-badge, .hero-v2__eyebrow, .hero-v2__title-line, .hero-v2__subtitle, .hero-v2__stats, .hero-v2__ctas, #heroLogos, #heroFlow, .hero-step, .hero-story__panel, .hero-story__caption, #scrollCue, .hero-v2__marquee, .ambient-orb, .grid-overlay, .hero-story__tabs"
        )
        .forEach(function (el) {
          el.style.opacity = "1";
          if (el.id !== "scrollCue") el.style.transform = "none";
        });
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
          ".hero-step",
          ".hero-story__panel",
          ".hero-story__caption",
          ".hero-v2__marquee",
          ".ambient-orb",
          ".grid-overlay",
          ".hero-story__tabs",
        ],
        { opacity: 1, y: 0, clearProps: "transform" }
      );
      if (scrollCue) gsap.set(scrollCue, { opacity: 0.85, xPercent: -50 });
      setActiveStep("solution");
      return;
    }

    if (scrollCue) gsap.set(scrollCue, { xPercent: -50, opacity: 0 });
    if (logos) gsap.set(logos, { opacity: 0, y: 16 });
    if (flowRoot) gsap.set(flowRoot, { opacity: 0 });
    gsap.set(".hero-step, .hero-story__panel, .hero-story__caption", { opacity: 0, y: 18 });
    gsap.set(".hero-story__tabs", { opacity: 0, y: 8 });

    var tl = gsap.timeline({ defaults: { ease: "power3.out" } });

    tl.to(".ambient-orb", { opacity: 1, duration: 1.5, delay: 0.15 });
    tl.to("#gridOverlay", { opacity: 1, duration: 1 }, "-=1");
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
      tl.to(".hero-story__tabs", { opacity: 1, y: 0, duration: 0.4 }, "-=0.35");
      tl.to(
        ".hero-step, .hero-story__panel, .hero-story__caption",
        {
          opacity: 1,
          y: 0,
          duration: 0.5,
          stagger: 0.09,
          ease: "power2.out",
          onComplete: function () {
            clearStoryInlineMotion();
            startPulseCycle();
            startStoryAutoCycle();
          },
        },
        "-=0.2"
      );
    }

    if (scrollCue) {
      tl.to(scrollCue, { opacity: 0.85, duration: 0.6 }, "-=0.5");
    }
    tl.to("#heroMarquee", { opacity: 1, duration: 0.8 }, "-=0.3");

    animateStats();
  }

  function boot() {
    initThreeJS();
    initStory();
    initEntrance();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot);
  } else {
    boot();
  }
})();
