/**
 * Hero v3 — entrance + magnetic CTAs + open-flow sector canvas.
 * 3 tabs auto-cycle. Dor → Solução → Resultado + wire pulse.
 */
(function () {
  "use strict";

  var hero = document.getElementById("hero");
  if (!hero || !hero.classList.contains("hero-v3")) return;

  var reduced =
    window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  var coarse =
    window.matchMedia && window.matchMedia("(pointer: coarse)").matches;

  var PULSE = ["pain", "solve", "win"];
  var SECTOR_ORDER = ["comercio", "industria", "servicos"];
  var CYCLE_MS = 6000;

  var pulseIndex = 0;
  var pulseTimer = null;
  var wireFlowTween = null;
  var progressTween = null;
  var resizeTimer = null;
  var switching = false;
  var cyclePaused = false;
  var cycleArmed = false;

  var SECTORS = {
    comercio: {
      tabId: "hv3-tab-comercio",
      label: "Comércio — fila no WhatsApp vira venda",
      caseMark: "Facíleme",
      caseText: "Social commerce · catálogo + checkout",
      tags: ["Agente", "WhatsApp", "CRM"],
      nodes: {
        pain: {
          title: "Lead esfria na fila do WhatsApp",
          sub: "Pedido some enquanto o time responde — ou o concorrente fecha.",
        },
        solve: {
          title: "Agente + CRM no mesmo fluxo",
          sub: "Atende e qualifica 24/7; pedido e status entram sem planilha.",
        },
        win: {
          title: "Venda no ar onde o cliente já está",
          sub: "",
        },
      },
    },
    industria: {
      tabId: "hv3-tab-industria",
      label: "Indústria — chão de fábrica no ERP",
      caseMark: "WV",
      caseText: "Integração sob medida · operação única",
      tags: ["Automação", "ERP", "Estoque"],
      nodes: {
        pain: {
          title: "Chão de fábrica ainda aponta no papel",
          sub: "Produção, estoque e ERP não conversam — retrabalho vira rotina.",
        },
        solve: {
          title: "Automação + integração ERP",
          sub: "Apontamento vira fluxo: um caminho, uma verdade operacional.",
        },
        win: {
          title: "Operação única do chão ao sistema",
          sub: "",
        },
      },
    },
    servicos: {
      tabId: "hv3-tab-servicos",
      label: "Serviços & Saúde — agenda e jornada sob controle",
      caseMark: "Let's Work",
      caseText: "Facial · ponto · folha em dia",
      tags: ["Agente", "WhatsApp", "Folha"],
      nodes: {
        pain: {
          title: "Agenda caótica e jornada no improviso",
          sub: "Time apaga fila o dia todo — ponto e folha ainda vão na planilha.",
        },
        solve: {
          title: "Agente no atendimento + Let's Work na jornada",
          sub: "Tria e agenda no WhatsApp; facial e geo fecham a folha sem papel.",
        },
        win: {
          title: "Time no fechamento, folha em dia",
          sub: "",
        },
      },
    },
  };

  function q(sel) {
    return hero.querySelector(sel);
  }

  function qa(sel) {
    return hero.querySelectorAll(sel);
  }

  function currentSector() {
    var canvas = document.getElementById("hv3Canvas");
    return (canvas && canvas.getAttribute("data-sector")) || "comercio";
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

  function nodeAnchor(canvasRect, el) {
    var hub = el.querySelector(".hv3-node__hub") || el;
    var r = hub.getBoundingClientRect();
    return {
      x: r.left + r.width / 2 - canvasRect.left,
      y: r.top + r.height / 2 - canvasRect.top,
    };
  }

  function cubicPath(a, b) {
    var dx = b.x - a.x;
    var c1 = { x: a.x + dx * 0.42, y: a.y };
    var c2 = { x: b.x - dx * 0.42, y: b.y };
    return (
      "M" +
      a.x.toFixed(1) +
      "," +
      a.y.toFixed(1) +
      " C" +
      c1.x.toFixed(1) +
      "," +
      c1.y.toFixed(1) +
      " " +
      c2.x.toFixed(1) +
      "," +
      c2.y.toFixed(1) +
      " " +
      b.x.toFixed(1) +
      "," +
      b.y.toFixed(1)
    );
  }

  function drawWires() {
    var canvas = document.getElementById("hv3Canvas");
    var board = document.getElementById("hv3Board");
    var svg = document.getElementById("hv3Wires");
    if (!canvas || !board || !svg) return;

    var w = canvas.clientWidth;
    var h = canvas.clientHeight;
    if (w < 2 || h < 2) return;

    var canvasRect = canvas.getBoundingClientRect();
    svg.setAttribute("viewBox", "0 0 " + w + " " + h);
    svg.setAttribute("width", String(w));
    svg.setAttribute("height", String(h));

    var byKey = {};
    board.querySelectorAll(".hv3-node[data-node]").forEach(function (node) {
      byKey[node.getAttribute("data-node")] = node;
    });

    var pairs = [
      ["pain", "solve"],
      ["solve", "win"],
    ];

    var ns = "http://www.w3.org/2000/svg";
    while (svg.firstChild) svg.removeChild(svg.firstChild);

    pairs.forEach(function (pair) {
      var from = byKey[pair[0]];
      var to = byKey[pair[1]];
      if (!from || !to) return;
      var path = document.createElementNS(ns, "path");
      path.setAttribute("d", cubicPath(nodeAnchor(canvasRect, from), nodeAnchor(canvasRect, to)));
      path.setAttribute("data-from", pair[0]);
      path.setAttribute("data-to", pair[1]);
      path.setAttribute("class", "hv3-canvas__wire hv3-canvas__wire--spine");
      svg.appendChild(path);
    });

    startWireFlow();
    updateWirePulse(PULSE[pulseIndex] || "pain");
  }

  function startWireFlow() {
    var svg = document.getElementById("hv3Wires");
    if (!svg || typeof gsap === "undefined") return;

    var wires = svg.querySelectorAll(".hv3-canvas__wire");
    if (!wires.length) return;

    if (wireFlowTween) wireFlowTween.kill();

    if (reduced) {
      gsap.set(wires, { strokeDashoffset: 0 });
      return;
    }

    gsap.set(wires, { strokeDashoffset: 0 });
    wireFlowTween = gsap.to(wires, {
      strokeDashoffset: -24,
      duration: 1.15,
      ease: "none",
      repeat: -1,
    });
  }

  function updateWirePulse(activeKey) {
    var svg = document.getElementById("hv3Wires");
    if (!svg) return;
    svg.querySelectorAll(".hv3-canvas__wire").forEach(function (wire) {
      var lit =
        wire.getAttribute("data-from") === activeKey ||
        wire.getAttribute("data-to") === activeKey;
      wire.classList.toggle("is-lit", lit);
    });
  }

  function setRunningNode(key) {
    var board = document.getElementById("hv3Board");
    if (!board) return;
    board.querySelectorAll(".hv3-node").forEach(function (node) {
      var match = node.getAttribute("data-node") === key;
      node.classList.toggle("is-running", match);
      if (match && !reduced) {
        node.classList.remove("is-pulse");
        void node.offsetWidth;
        node.classList.add("is-pulse");
      }
    });
    updateWirePulse(key);
  }

  function stopPulse() {
    if (pulseTimer) {
      window.clearInterval(pulseTimer);
      pulseTimer = null;
    }
  }

  function startPulse() {
    stopPulse();
    if (reduced) {
      setRunningNode("win");
      return;
    }
    pulseIndex = 0;
    setRunningNode(PULSE[0]);
    pulseTimer = window.setInterval(function () {
      pulseIndex = (pulseIndex + 1) % PULSE.length;
      setRunningNode(PULSE[pulseIndex]);
    }, 1400);
  }

  function resetProgressBars() {
    qa(".hv3-tab__progress").forEach(function (bar) {
      if (typeof gsap !== "undefined") gsap.killTweensOf(bar);
      bar.style.transform = "scaleX(0)";
    });
    if (progressTween) {
      progressTween.kill();
      progressTween = null;
    }
  }

  function startTabProgress(sectorKey) {
    resetProgressBars();
    if (reduced || typeof gsap === "undefined" || cyclePaused) return;

    var tab = document.getElementById(SECTORS[sectorKey].tabId);
    var bar = tab && tab.querySelector(".hv3-tab__progress");
    if (!bar) return;

    gsap.set(bar, { scaleX: 0 });
    progressTween = gsap.to(bar, {
      scaleX: 1,
      duration: CYCLE_MS / 1000,
      ease: "none",
      onComplete: function () {
        if (cyclePaused || switching) return;
        var i = SECTOR_ORDER.indexOf(sectorKey);
        var next = SECTOR_ORDER[(i + 1) % SECTOR_ORDER.length];
        switchSector(next, false, true);
      },
    });
  }

  function pauseCycle() {
    cyclePaused = true;
    if (progressTween) progressTween.pause();
  }

  function resumeCycle() {
    cyclePaused = false;
    if (!cycleArmed) return;
    if (progressTween && progressTween.paused()) {
      progressTween.resume();
      return;
    }
    startTabProgress(currentSector());
  }

  function armAutoCycle() {
    if (reduced) return;
    cycleArmed = true;
    startTabProgress(currentSector());
  }

  function swapCopy(sectorKey) {
    var sector = SECTORS[sectorKey];
    if (!sector) return;

    var canvas = document.getElementById("hv3Canvas");
    var board = document.getElementById("hv3Board");
    var chip = document.getElementById("hv3CaseChip");
    var tagsEl = document.getElementById("hv3SolveTags");
    var stage = document.getElementById("hv3Stage");

    Object.keys(sector.nodes).forEach(function (key) {
      var node = board && board.querySelector('.hv3-node[data-node="' + key + '"]');
      if (!node) return;
      var data = sector.nodes[key];
      var title = node.querySelector(".hv3-node__title");
      var sub = node.querySelector(".hv3-node__sub");
      if (title) title.textContent = data.title;
      if (sub) {
        if (data.sub) {
          sub.textContent = data.sub;
          sub.hidden = false;
        } else {
          sub.textContent = "";
          sub.hidden = true;
        }
      }
    });

    if (tagsEl && sector.tags) {
      tagsEl.innerHTML = sector.tags
        .map(function (t) {
          return "<span>" + t + "</span>";
        })
        .join("");
    }

    if (chip) {
      var mark = chip.querySelector(".hv3-node__case-mark");
      var text = chip.querySelector(".hv3-node__case-text");
      if (mark) mark.textContent = sector.caseMark;
      if (text) text.textContent = sector.caseText;
    }

    if (canvas) canvas.setAttribute("data-sector", sectorKey);
    if (stage) stage.setAttribute("aria-label", sector.label);
    if (board) board.setAttribute("aria-labelledby", sector.tabId);

    qa(".hv3-tab").forEach(function (tab) {
      var on = tab.getAttribute("data-sector") === sectorKey;
      tab.classList.toggle("is-active", on);
      tab.setAttribute("aria-selected", on ? "true" : "false");
      tab.tabIndex = on ? 0 : -1;
    });
  }

  function switchSector(sectorKey, focusTab, fromAuto) {
    if (!SECTORS[sectorKey] || switching) return;
    if (currentSector() === sectorKey && !fromAuto) {
      if (fromAuto) startTabProgress(sectorKey);
      return;
    }
    if (currentSector() === sectorKey) {
      startTabProgress(sectorKey);
      return;
    }

    stopPulse();
    resetProgressBars();

    var sector = SECTORS[sectorKey];
    var board = document.getElementById("hv3Board");
    var chip = document.getElementById("hv3CaseChip");
    var tagsEl = document.getElementById("hv3SolveTags");

    function afterSwap() {
      switching = false;
      drawWires();
      startPulse();
      if (cycleArmed && !cyclePaused) startTabProgress(sectorKey);
      if (focusTab) {
        var t = document.getElementById(sector.tabId);
        if (t) t.focus();
      }
    }

    if (typeof gsap === "undefined" || reduced || !board) {
      swapCopy(sectorKey);
      afterSwap();
      return;
    }

    switching = true;
    var parts = board.querySelectorAll(".hv3-node");
    var extras = [chip, tagsEl].filter(Boolean);

    gsap
      .timeline({ onComplete: afterSwap })
      .to([parts, extras], {
        opacity: 0,
        y: 8,
        duration: 0.2,
        ease: "power2.in",
        stagger: 0.03,
      })
      .add(function () {
        swapCopy(sectorKey);
      })
      .fromTo(
        [parts, extras],
        { opacity: 0, y: 14 },
        { opacity: 1, y: 0, duration: 0.42, stagger: 0.05, ease: "power3.out" }
      );
  }

  function initSectorTabs() {
    var tabs = Array.prototype.slice.call(qa(".hv3-tab"));
    if (!tabs.length) return;

    tabs.forEach(function (tab) {
      tab.addEventListener("click", function () {
        var sector = tab.getAttribute("data-sector");
        if (sector) switchSector(sector, false, false);
      });

      tab.addEventListener("keydown", function (e) {
        var i = tabs.indexOf(tab);
        if (i < 0) return;
        var next = -1;
        if (e.key === "ArrowRight" || e.key === "ArrowDown") next = (i + 1) % tabs.length;
        else if (e.key === "ArrowLeft" || e.key === "ArrowUp")
          next = (i - 1 + tabs.length) % tabs.length;
        else if (e.key === "Home") next = 0;
        else if (e.key === "End") next = tabs.length - 1;
        if (next < 0) return;
        e.preventDefault();
        switchSector(tabs[next].getAttribute("data-sector"), true, false);
      });
    });
  }

  function initCyclePause() {
    var stage = document.getElementById("hv3Stage");
    if (!stage || reduced) return;

    stage.addEventListener("pointerenter", pauseCycle);
    stage.addEventListener("pointerleave", resumeCycle);
    stage.addEventListener("focusin", pauseCycle);
    stage.addEventListener("focusout", function (e) {
      if (!stage.contains(e.relatedTarget)) resumeCycle();
    });
  }

  function initNodeHover() {
    if (reduced || coarse || typeof gsap === "undefined") return;
    qa(".hv3-node").forEach(function (node) {
      node.addEventListener("pointerenter", function () {
        gsap.to(node, { y: -3, duration: 0.35, ease: "power3.out", overwrite: "auto" });
      });
      node.addEventListener("pointerleave", function () {
        gsap.to(node, { y: 0, duration: 0.4, ease: "power3.out", overwrite: "auto" });
      });
    });
  }

  function initResize() {
    window.addEventListener("resize", function () {
      window.clearTimeout(resizeTimer);
      resizeTimer = window.setTimeout(function () {
        drawWires();
      }, 120);
    });
  }

  function initEntrance() {
    if (typeof gsap === "undefined" || reduced) {
      drawWires();
      startPulse();
      armAutoCycle();
      return;
    }

    var brand = q(".hv3-brand");
    var lines = qa(".hv3-title__line");
    var subtitle = q(".hv3-subtitle");
    var ctas = q(".hv3-ctas");
    var stage = q(".hv3-stage-wrap");
    var head = q(".hv3-board__head");
    var tabs = q(".hv3-tabs");
    var nodes = qa(".hv3-node");
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
    if (nodes.length) gsap.set(nodes, { opacity: 0, y: 16, scale: 0.97 });
    if (scroll) gsap.set(scroll, { opacity: 0 });

    var tl = gsap.timeline({
      defaults: { ease: "power3.out" },
      onComplete: function () {
        gsap.set(parts, { opacity: 1, y: 0 });
        if (head) gsap.set(head, { opacity: 1, y: 0 });
        if (tabs) gsap.set(tabs, { opacity: 1, y: 0 });
        if (nodes.length) gsap.set(nodes, { opacity: 1, y: 0, scale: 1 });
        drawWires();
        startPulse();
        armAutoCycle();
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
    if (head) tl.to(head, { opacity: 1, y: 0, duration: 0.4 }, "-=0.4");
    if (tabs) tl.to(tabs, { opacity: 1, y: 0, duration: 0.4 }, "-=0.28");
    if (nodes.length) {
      tl.to(
        nodes,
        { opacity: 1, y: 0, scale: 1, duration: 0.5, stagger: 0.08, ease: "power3.out" },
        "-=0.2"
      );
    }
    if (scroll) tl.to(scroll, { opacity: 0.85, duration: 0.45 }, "-=0.2");
  }

  function boot() {
    initMagnetic();
    initSectorTabs();
    initCyclePause();
    initNodeHover();
    initResize();
    initEntrance();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot);
  } else {
    boot();
  }
})();
