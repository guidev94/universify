/**
 * Hero v2 — Three.js particle field + GSAP entrance + multi-pain workflow canvas.
 * Stories: Atendimento · RH · Operação. Lime particles (additive, mouse parallax).
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
  var resizeTimer = null;
  var storyCycleTimer = null;
  var pulseTimer = null;
  var edgeDrawTimer = null;
  var currentStory = "atendimento";
  var pulseIndex = 0;
  var storyChanging = false;
  var flowReady = false;

  var STORY_ORDER = ["atendimento", "rh", "operacao"];

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
      label: "Atendimento",
      tabId: "flowTabAtendimento",
      panelLabel: "Do WhatsApp lotado à venda no ar",
      metricValue: "24/7",
      metricLabel: "sem fila no atendimento",
      chip: "Operação comercial com IA",
      nodes: {
        lead: { title: "WhatsApp lotado", sub: "Lead esfria à noite", icon: "chat" },
        ai: { title: "Agente atende 24/7", sub: "Qualifica e resolve", icon: "bot" },
        auto: { title: "Agenda o pedido", sub: "Sem planilha no meio", icon: "calendar" },
        integ: { title: "Atualiza o CRM", sub: "Sem retrabalho manual", icon: "sync" },
        result: { title: "Venda no ar", sub: "Time livre pra vender", icon: "check" },
      },
    },
    rh: {
      label: "RH",
      tabId: "flowTabRh",
      panelLabel: "Do ponto manual à folha e gestão em dia",
      metricValue: "Em dia",
      metricLabel: "folha e jornada sem caos",
      chip: "RH e pessoas com IA",
      nodes: {
        lead: { title: "Ponto e jornada manuais", sub: "Planilha e WhatsApp do RH", icon: "clock" },
        ai: { title: "Automação de ponto", sub: "Marca, valida e alerta", icon: "bot" },
        auto: { title: "Integra folha e sistemas", sub: "Dados no lugar certo", icon: "sync" },
        integ: { title: "Visão única do time", sub: "Gestão sem retrabalho", icon: "users" },
        result: { title: "Folha e gestão em dia", sub: "RH focado em gente", icon: "check" },
      },
    },
    operacao: {
      label: "Operação",
      tabId: "flowTabOperacao",
      panelLabel: "Do Excel desalinhado à operação única",
      metricValue: "1 fluxo",
      metricLabel: "times na mesma página",
      chip: "Negócios e ops com IA",
      nodes: {
        lead: { title: "Processo no Excel", sub: "Times desalinhados", icon: "sheet" },
        ai: { title: "Automação do fluxo", sub: "Um caminho só", icon: "flow" },
        auto: { title: "Integra sistemas e times", sub: "Sem cópia manual", icon: "sync" },
        integ: { title: "Status em tempo real", sub: "Todos veem o mesmo", icon: "alert" },
        result: { title: "Operação única", sub: "Menos retrabalho, mais entrega", icon: "check" },
      },
    },
  };

  // Story order matches the canvas read path: Dor → Solução → Ações → Resultado
  var PULSE_SEQUENCE = ["lead", "ai", "auto", "integ", "result"];

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
    if (edgeDrawTimer) {
      clearTimeout(edgeDrawTimer);
      edgeDrawTimer = null;
    }
    listeners.forEach(function (entry) {
      entry[0].removeEventListener(entry[1], entry[2], entry[3]);
    });
    listeners = [];
    if (resizeTimer) {
      clearTimeout(resizeTimer);
      resizeTimer = null;
    }
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

    function applyParticleTheme() {
      if (isLightTheme()) {
        material.color.setHex(0x6b8f00);
        material.opacity = 0.32;
        material.size = 0.032;
        material.blending = THREE.NormalBlending;
      } else {
        material.color.setHex(0xcfff04);
        material.opacity = 0.6;
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
        renderer.dispose();
      } catch (e) {
        /* noop */
      }
    });
  }

  function nodeCenter(boardRect, el) {
    var r = el.getBoundingClientRect();
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

    // Vertical branch (Solução → Ação): drop then settle — avoids tangled diagonals
    if (absDy > absDx * 0.85 && absDy > 24) {
      var drop = Math.max(absDy * 0.45, 24);
      c1x = a.x;
      c1y = a.y + (dy >= 0 ? drop : -drop);
      c2x = b.x;
      c2y = b.y - (dy >= 0 ? drop * 0.55 : -drop * 0.55);
    } else if (absDy > 18 && absDx > 18) {
      // Diagonal feed into Resultado: soft elbow, no cross over the spine
      c1x = a.x + dx * 0.15;
      c1y = a.y + dy * 0.65;
      c2x = b.x - dx * 0.35;
      c2y = b.y;
    } else {
      // Horizontal spine (Dor → Solução → Resultado)
      var c = Math.max(absDx * 0.42, 28);
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

  function scheduleEdges() {
    if (edgeDrawTimer) clearTimeout(edgeDrawTimer);
    edgeDrawTimer = setTimeout(function () {
      edgeDrawTimer = null;
      drawEdges();
    }, 32);
  }

  function drawEdges() {
    var board = document.getElementById("heroFlowBoard");
    var svg = document.getElementById("heroFlowEdges");
    var canvas = board && board.closest(".hero-flow__canvas");
    if (!board || !svg || !canvas) return;

    var canvasRect = canvas.getBoundingClientRect();
    var w = canvas.clientWidth;
    var h = canvas.clientHeight;
    if (w < 2 || h < 2) return;

    svg.setAttribute("viewBox", "0 0 " + w + " " + h);
    svg.setAttribute("width", String(w));
    svg.setAttribute("height", String(h));

    var byKey = {};
    board.querySelectorAll(".flow-node[data-node]").forEach(function (node) {
      byKey[node.getAttribute("data-node")] = node;
    });

    // Spine Dor → Solução → Resultado; Ações branch under Solução then feed Resultado
    var pairs = [
      ["lead", "ai"],
      ["ai", "result"],
      ["ai", "auto"],
      ["ai", "integ"],
      ["auto", "result"],
      ["integ", "result"],
    ];

    var ns = "http://www.w3.org/2000/svg";
    while (svg.firstChild) svg.removeChild(svg.firstChild);

    pairs.forEach(function (pair) {
      var from = byKey[pair[0]];
      var to = byKey[pair[1]];
      if (!from || !to) return;
      var a = nodeCenter(canvasRect, from);
      var b = nodeCenter(canvasRect, to);
      var path = document.createElementNS(ns, "path");
      path.setAttribute("d", cubicPath(a, b));
      path.setAttribute("data-from", pair[0]);
      path.setAttribute("data-to", pair[1]);
      var isSpine =
        (pair[0] === "lead" && pair[1] === "ai") ||
        (pair[0] === "ai" && pair[1] === "result");
      path.setAttribute(
        "class",
        "hero-flow__edge" + (isSpine ? " hero-flow__edge--spine" : "")
      );
      svg.appendChild(path);
    });

    updateEdgePulse(PULSE_SEQUENCE[pulseIndex] || "lead");
  }

  function updateEdgePulse(activeKey) {
    var svg = document.getElementById("heroFlowEdges");
    if (!svg) return;
    svg.querySelectorAll(".hero-flow__edge").forEach(function (edge) {
      var lit =
        edge.getAttribute("data-from") === activeKey ||
        edge.getAttribute("data-to") === activeKey;
      edge.classList.toggle("hero-flow__edge--active", lit);
      edge.classList.toggle("is-lit", lit);
    });
  }

  function restartNodePulse(node) {
    if (!node || reduced) return;
    node.classList.remove("is-running");
    // Force style recalc so CSS animation restarts cleanly on each step
    void node.offsetWidth;
    node.classList.add("is-running");
  }

  function setRunningNode(key) {
    var board = document.getElementById("heroFlowBoard");
    if (!board) return;
    board.querySelectorAll(".flow-node").forEach(function (node) {
      var match = node.getAttribute("data-node") === key;
      if (match) {
        restartNodePulse(node);
      } else {
        node.classList.remove("is-running");
      }
    });
    updateEdgePulse(key);
  }

  function markNodesReady(ready) {
    hero.querySelectorAll("#heroFlowBoard .flow-node").forEach(function (node) {
      node.classList.toggle("is-ready", !!ready);
      if (ready) {
        node.style.opacity = "";
        node.style.transform = "";
      }
    });
  }

  function clearNodeInlineMotion() {
    if (typeof gsap !== "undefined") {
      gsap.killTweensOf("#heroFlowBoard .flow-node");
      gsap.set("#heroFlowBoard .flow-node", { clearProps: "transform,opacity" });
    }
    markNodesReady(true);
  }

  function applyStoryContent(storyKey) {
    var story = STORIES[storyKey];
    if (!story) return;

    var board = document.getElementById("heroFlowBoard");
    var flow = document.getElementById("heroFlow");
    var panel = document.getElementById("heroFlowPanel");
    var metric = document.getElementById("heroFlowMetric");
    var chip = document.getElementById("heroFlowChip");

    if (flow) flow.setAttribute("data-story", storyKey);
    if (panel) panel.setAttribute("aria-label", story.panelLabel);
    if (board) board.setAttribute("aria-labelledby", story.tabId);

    Object.keys(story.nodes).forEach(function (key) {
      var node = board && board.querySelector('.flow-node[data-node="' + key + '"]');
      if (!node) return;
      var data = story.nodes[key];
      var title = node.querySelector(".flow-node__title");
      var sub = node.querySelector(".flow-node__sub");
      var icon = node.querySelector(".flow-node__icon");
      if (title) title.textContent = data.title;
      if (sub) sub.textContent = data.sub;
      if (icon) icon.innerHTML = ICONS[data.icon] || ICONS.check;
    });

    if (metric) {
      var mv = metric.querySelector(".hero-flow__metric-value");
      var ml = metric.querySelector(".hero-flow__metric-label");
      if (mv) mv.textContent = story.metricValue;
      if (ml) ml.textContent = story.metricLabel;
    }
    if (chip) {
      var ct = chip.querySelector(".hero-flow__chip-text");
      if (ct) ct.textContent = story.chip;
    }

    document.querySelectorAll(".hero-flow__tab").forEach(function (tab) {
      var active = tab.getAttribute("data-story") === storyKey;
      tab.classList.toggle("is-active", active);
      tab.setAttribute("aria-selected", active ? "true" : "false");
      tab.tabIndex = active ? 0 : -1;
    });
  }

  function revealStoryNodes(onDone) {
    var nodes = hero.querySelectorAll("#heroFlowBoard .flow-node");
    var doneOnce = false;
    var finish = function () {
      if (doneOnce) return;
      doneOnce = true;
      clearNodeInlineMotion();
      drawEdges();
      if (onDone) onDone();
    };

    if (!nodes.length) {
      finish();
      return;
    }

    if (reduced || typeof gsap === "undefined") {
      finish();
      return;
    }

    markNodesReady(false);
    gsap.killTweensOf(nodes);
    gsap.set(nodes, { opacity: 0, y: 14 });
    gsap.to(nodes, {
      opacity: 1,
      y: 0,
      duration: 0.42,
      stagger: 0.09,
      ease: "power2.out",
      onComplete: finish,
    });
    // Safety: never leave storyChanging true if GSAP stalls
    later(finish, 1200);
  }

  function switchStory(storyKey, opts) {
    opts = opts || {};
    if (!STORIES[storyKey] || (storyKey === currentStory && !opts.force)) return;
    if (storyChanging) return;
    storyChanging = true;

    var canvas = hero.querySelector(".hero-flow__canvas");
    var nodes = hero.querySelectorAll("#heroFlowBoard .flow-node");
    var applied = false;
    stopPulseCycle();

    var finish = function () {
      if (applied) return;
      applied = true;
      applyStoryContent(storyKey);
      currentStory = storyKey;
      if (canvas) canvas.classList.remove("is-switching");
      revealStoryNodes(function () {
        storyChanging = false;
        if (!opts.skipRestartPulse) startPulseCycle();
      });
    };

    if (reduced || typeof gsap === "undefined") {
      finish();
      return;
    }

    if (canvas) canvas.classList.add("is-switching");
    gsap.killTweensOf(nodes);
    gsap.to(nodes, {
      opacity: 0,
      y: -8,
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
      setRunningNode("ai");
      return;
    }

    var step = function () {
      if (!flowReady) return;
      var key = PULSE_SEQUENCE[pulseIndex];
      setRunningNode(key);
      pulseIndex = (pulseIndex + 1) % PULSE_SEQUENCE.length;
      pulseTimer = setTimeout(step, 1400);
    };
    step();
  }

  function stopPulseCycle() {
    flowReady = false;
    if (pulseTimer) {
      clearTimeout(pulseTimer);
      pulseTimer = null;
    }
    hero.querySelectorAll("#heroFlowBoard .flow-node.is-running").forEach(function (node) {
      node.classList.remove("is-running");
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

  function initNodeHover() {
    var board = document.getElementById("heroFlowBoard");
    var svg = document.getElementById("heroFlowEdges");
    if (!board || !svg || reduced) return;

    board.querySelectorAll(".flow-node[data-node]").forEach(function (node) {
      on(node, "mouseenter", function () {
        node.classList.add("is-hovered");
        var key = node.getAttribute("data-node");
        svg.querySelectorAll(".hero-flow__edge").forEach(function (edge) {
          if (edge.getAttribute("data-from") === key || edge.getAttribute("data-to") === key) {
            edge.classList.add("is-lit");
          }
        });
      });
      on(node, "mouseleave", function () {
        node.classList.remove("is-hovered");
        var active = PULSE_SEQUENCE[pulseIndex] || "ai";
        updateEdgePulse(active);
      });
    });
  }

  function initTabs() {
    var tabs = hero.querySelectorAll(".hero-flow__tab");
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

  function initFlowCanvas() {
    applyStoryContent(currentStory);
    scheduleEdges();
    on(window, "resize", function () {
      if (resizeTimer) clearTimeout(resizeTimer);
      resizeTimer = setTimeout(drawEdges, 100);
    });
    initNodeHover();
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

  function revealFlowFallback() {
    hero.querySelectorAll("#heroFlow, .flow-node").forEach(function (el) {
      el.style.opacity = "1";
      el.style.transform = "none";
    });
    drawEdges();
    startPulseCycle();
  }

  function initEntrance() {
    var logos = hero.querySelector("#heroLogos");
    var scrollCue = hero.querySelector("#scrollCue");
    var flowRoot = hero.querySelector("#heroFlow");

    if (typeof gsap === "undefined") {
      hero
        .querySelectorAll(
          ".ai-badge, .hero-v2__eyebrow, .hero-v2__title-line, .hero-v2__subtitle, .hero-v2__stats, .hero-v2__ctas, #heroLogos, #heroFlow, .flow-node, #scrollCue, .hero-v2__marquee, .ambient-orb, .grid-overlay, .hero-flow__tabs"
        )
        .forEach(function (el) {
          el.style.opacity = "1";
          if (el.id !== "scrollCue") el.style.transform = "none";
        });
      drawEdges();
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
          ".flow-node",
          ".hero-v2__marquee",
          ".ambient-orb",
          ".grid-overlay",
          ".hero-flow__tabs",
        ],
        { opacity: 1, y: 0, clearProps: "transform" }
      );
      if (scrollCue) gsap.set(scrollCue, { opacity: 0.85, xPercent: -50 });
      drawEdges();
      setRunningNode("ai");
      return;
    }

    if (scrollCue) gsap.set(scrollCue, { xPercent: -50, opacity: 0 });
    if (logos) gsap.set(logos, { opacity: 0, y: 16 });
    if (flowRoot) gsap.set(flowRoot, { opacity: 0 });
    gsap.set(".flow-node", { opacity: 0, y: 16 });
    gsap.set(".hero-flow__tabs", { opacity: 0, y: 8 });

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
      tl.to(".hero-flow__tabs", { opacity: 1, y: 0, duration: 0.4 }, "-=0.35");
      tl.to(
        ".flow-node",
        {
          opacity: 1,
          y: 0,
          duration: 0.55,
          stagger: 0.1,
          ease: "power2.out",
          onComplete: function () {
            clearNodeInlineMotion();
            hero.querySelectorAll("#heroFlowBoard .flow-node").forEach(function (n) {
              n.style.opacity = "1";
            });
            drawEdges();
            startPulseCycle();
            startStoryAutoCycle();
          },
        },
        "-=0.25"
      );
      tl.add(scheduleEdges, "-=0.15");
    } else {
      revealFlowFallback();
    }

    if (scrollCue) {
      tl.to(scrollCue, { opacity: 0.85, duration: 0.6 }, "-=0.5");
    }
    tl.to("#heroMarquee", { opacity: 1, duration: 0.8 }, "-=0.3");

    animateStats();
  }

  function boot() {
    initThreeJS();
    initFlowCanvas();
    initEntrance();
    requestAnimationFrame(function () {
      requestAnimationFrame(drawEdges);
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot);
  } else {
    boot();
  }
})();
