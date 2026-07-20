/**
 * Hero v3 — entrance + magnetic CTAs + sector automation canvas.
 * Dor → Solução → Ação → Resultado. Curved wires + sequential pulse.
 */
(function () {
  "use strict";

  var hero = document.getElementById("hero");
  if (!hero || !hero.classList.contains("hero-v3")) return;

  var reduced =
    window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  var coarse =
    window.matchMedia && window.matchMedia("(pointer: coarse)").matches;

  var PULSE = ["pain", "solve", "action", "win"];
  var pulseIndex = 0;
  var pulseTimer = null;
  var wireFlowTween = null;
  var resizeTimer = null;
  var switching = false;

  var SECTORS = {
    comercio: {
      tabId: "hv3-tab-comercio",
      label: "Comércio — fila no WhatsApp vira venda",
      caseMark: "Facíleme",
      caseText: "Social commerce · catálogo + checkout",
      nodes: {
        pain: {
          title: "Lead esfria na fila do WhatsApp",
          sub: "Pedido some enquanto o time responde",
        },
        solve: {
          title: "Agente + CRM no mesmo fluxo",
          sub: "Atende, qualifica e registra sem planilha",
        },
        action: {
          title: "Integra catálogo e checkout",
          sub: "Pedido entra onde a venda acontece",
        },
        win: {
          title: "Venda no ar onde o cliente já está",
          sub: "Canal ativo, conversão contínua",
        },
      },
    },
    industria: {
      tabId: "hv3-tab-industria",
      label: "Indústria — chão de fábrica no ERP",
      caseMark: "WV",
      caseText: "Integração sob medida · indústria",
      nodes: {
        pain: {
          title: "Chão de fábrica ainda aponta no papel",
          sub: "Produção e ERP não conversam",
        },
        solve: {
          title: "Automação + integração ERP",
          sub: "Um fluxo, uma verdade operacional",
        },
        action: {
          title: "Sincroniza estoque e apontamento",
          sub: "Do chão ao sistema sem retrabalho",
        },
        win: {
          title: "Operação única do chão ao sistema",
          sub: "Menos atraso, mais controle",
        },
      },
    },
    servicos: {
      tabId: "hv3-tab-servicos",
      label: "Serviços — time no fechamento",
      caseMark: "WV",
      caseText: "Fluxo comercial · serviços",
      nodes: {
        pain: {
          title: "Agenda e atendimento no modo incêndio",
          sub: "Fila come o dia; fechamento fica pra depois",
        },
        solve: {
          title: "Agente tria + fluxo de follow-up",
          sub: "Agenda e pós-atendimento no piloto",
        },
        action: {
          title: "Prioriza e alimenta o CRM",
          sub: "Comercial só pega o que fecha",
        },
        win: {
          title: "Time no fechamento, não na fila",
          sub: "Atendimento que gera receita",
        },
      },
    },
    saude: {
      tabId: "hv3-tab-saude",
      label: "Saúde / RH — jornada auditável",
      caseMark: "Let's Work",
      caseText: "Facial · ponto · RH/ops",
      nodes: {
        pain: {
          title: "Ponto e jornada manuais atrasam a folha",
          sub: "Planilha gera risco e retrabalho",
        },
        solve: {
          title: "Let's Work facial + geo",
          sub: "Registro biométrico sem papel",
        },
        action: {
          title: "Audita jornada em tempo real",
          sub: "Conformidade sem planilha",
        },
        win: {
          title: "Folha em dia, jornada auditável",
          sub: "RH opera com precisão",
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
    var dy = b.y - a.y;
    var absDx = Math.abs(dx);
    var absDy = Math.abs(dy);
    var c1;
    var c2;

    if (absDy > absDx * 0.7) {
      /* mostly vertical branch */
      c1 = { x: a.x, y: a.y + dy * 0.45 };
      c2 = { x: b.x, y: b.y - dy * 0.45 };
    } else {
      c1 = { x: a.x + dx * 0.42, y: a.y };
      c2 = { x: b.x - dx * 0.42, y: b.y };
    }

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
      ["pain", "solve", true],
      ["solve", "win", true],
      ["solve", "action", false],
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
      path.setAttribute(
        "class",
        "hv3-canvas__wire" + (pair[2] ? " hv3-canvas__wire--spine" : "")
      );
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

  function switchSector(sectorKey, focusTab) {
    if (!SECTORS[sectorKey] || switching) return;
    var canvas = document.getElementById("hv3Canvas");
    if (canvas && canvas.getAttribute("data-sector") === sectorKey) return;

    stopPulse();

    var sector = SECTORS[sectorKey];
    var board = document.getElementById("hv3Board");
    var chip = document.getElementById("hv3CaseChip");

    function swapCopy() {
      Object.keys(sector.nodes).forEach(function (key) {
        var node = board && board.querySelector('.hv3-node[data-node="' + key + '"]');
        if (!node) return;
        var data = sector.nodes[key];
        var title = node.querySelector(".hv3-node__title");
        var sub = node.querySelector(".hv3-node__sub");
        if (title) title.textContent = data.title;
        if (sub) sub.textContent = data.sub;
      });
      if (chip) {
        var mark = chip.querySelector(".hv3-canvas__case-mark");
        var text = chip.querySelector(".hv3-canvas__case-text");
        if (mark) mark.textContent = sector.caseMark;
        if (text) text.textContent = sector.caseText;
      }
      if (canvas) canvas.setAttribute("data-sector", sectorKey);
      var stage = document.getElementById("hv3Stage");
      if (stage) stage.setAttribute("aria-label", sector.label);
      if (board) board.setAttribute("aria-labelledby", sector.tabId);
      qa(".hv3-tab").forEach(function (tab) {
        var on = tab.getAttribute("data-sector") === sectorKey;
        tab.classList.toggle("is-active", on);
        tab.setAttribute("aria-selected", on ? "true" : "false");
        tab.tabIndex = on ? 0 : -1;
      });
    }

    if (typeof gsap === "undefined" || reduced || !board) {
      swapCopy();
      drawWires();
      startPulse();
      if (focusTab) {
        var t = document.getElementById(sector.tabId);
        if (t) t.focus();
      }
      return;
    }

    switching = true;
    var parts = board.querySelectorAll(".hv3-node");
    gsap
      .timeline({
        onComplete: function () {
          switching = false;
          drawWires();
          startPulse();
          if (focusTab) {
            var t = document.getElementById(sector.tabId);
            if (t) t.focus();
          }
        },
      })
      .to([parts, chip], {
        opacity: 0,
        y: 8,
        duration: 0.2,
        ease: "power2.in",
        stagger: 0.025,
      })
      .add(swapCopy)
      .fromTo(
        [parts, chip],
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
        if (sector) switchSector(sector, false);
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
        switchSector(tabs[next].getAttribute("data-sector"), true);
      });
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
    var chip = q(".hv3-canvas__case");
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
    if (chip) gsap.set(chip, { opacity: 0, y: 10 });
    if (scroll) gsap.set(scroll, { opacity: 0 });

    var tl = gsap.timeline({
      defaults: { ease: "power3.out" },
      onComplete: function () {
        gsap.set(parts, { opacity: 1, y: 0 });
        if (head) gsap.set(head, { opacity: 1, y: 0 });
        if (tabs) gsap.set(tabs, { opacity: 1, y: 0 });
        if (nodes.length) gsap.set(nodes, { opacity: 1, y: 0, scale: 1 });
        if (chip) gsap.set(chip, { opacity: 1, y: 0 });
        drawWires();
        startPulse();
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
    if (chip) tl.to(chip, { opacity: 1, y: 0, duration: 0.4 }, "-=0.25");
    if (scroll) tl.to(scroll, { opacity: 0.85, duration: 0.45 }, "-=0.2");
  }

  function boot() {
    initMagnetic();
    initSectorTabs();
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
