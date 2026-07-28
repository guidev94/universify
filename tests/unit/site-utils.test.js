import {
  REQUIRED_SECTION_IDS,
  NAV_HASHES,
  buildWhatsAppMessage,
  buildWhatsAppUrl,
  getCaseDataFromElement,
  isModalOpen,
  shouldIgnoreCaseClick,
  heroLogosOnSingleRow,
  attachToGlobal
} from "../../assets/js/site-utils.mjs";
import { describe, it } from "node:test";
import assert from "node:assert/strict";

describe("buildWhatsAppMessage", () => {
  it("formats contact payload", () => {
    const msg = buildWhatsAppMessage({
      nome: " Ana ",
      email: "ana@wv.com",
      mensagem: "Quero um app"
    });
    assert.match(msg, /Ana/);
    assert.match(msg, /ana@wv.com/);
    assert.match(msg, /Quero um app/);
  });

  it("handles empty fields", () => {
    const msg = buildWhatsAppMessage();
    assert.equal(typeof msg, "string");
    assert.match(msg, /WV_Todoz/);
  });
});

describe("buildWhatsAppUrl", () => {
  it("encodes message", () => {
    const url = buildWhatsAppUrl("olá mundo");
    assert.ok(url.startsWith("https://wa.me/551155311779?text="));
    assert.ok(url.includes(encodeURIComponent("olá mundo")));
  });
});

describe("getCaseDataFromElement", () => {
  it("returns null without title", () => {
    assert.equal(getCaseDataFromElement({ getAttribute: () => null }), null);
    assert.equal(getCaseDataFromElement(null), null);
  });

  it("reads data attributes", () => {
    const el = {
      getAttribute(name) {
        const map = {
          "data-case-title": "Pullman App",
          "data-case-category": "Marketing",
          "data-case-img": "https://example.com/a.jpg",
          "data-case-desc": "Desc"
        };
        return map[name] ?? null;
      }
    };
    assert.deepEqual(getCaseDataFromElement(el), {
      title: "Pullman App",
      category: "Marketing",
      img: "https://example.com/a.jpg",
      desc: "Desc"
    });
  });
});

describe("modal helpers", () => {
  it("detects open state", () => {
    assert.equal(isModalOpen({ classList: { contains: (c) => c === "is-open" } }), true);
    assert.equal(isModalOpen({ classList: { contains: () => false } }), false);
    assert.equal(isModalOpen(null), false);
  });

  it("ignores nested CTA clicks on cases", () => {
    assert.equal(
      shouldIgnoreCaseClick({ closest: (sel) => (sel.includes("a") ? {} : null) }),
      true
    );
    assert.equal(shouldIgnoreCaseClick({ closest: () => null }), false);
  });
});

describe("site structure constants", () => {
  it("lists required sections and nav hashes", () => {
    assert.ok(REQUIRED_SECTION_IDS.includes("portfolio"));
    assert.ok(REQUIRED_SECTION_IDS.includes("produtos"));
    assert.ok(REQUIRED_SECTION_IDS.includes("aplicacoes"));
    assert.ok(!REQUIRED_SECTION_IDS.includes("marketing"));
    assert.ok(!NAV_HASHES.includes("#aplicacoes"));
    assert.ok(NAV_HASHES.includes("#portfolio"));
    assert.ok(NAV_HASHES.every((h) => h.startsWith("#")));
  });
});

describe("heroLogosOnSingleRow", () => {
  it("true when logos fit", () => {
    assert.equal(heroLogosOnSingleRow(800, [100, 100, 100, 100], 20), true);
  });

  it("false when logos overflow", () => {
    assert.equal(heroLogosOnSingleRow(300, [120, 120, 120, 120], 24), false);
  });
});

describe("attachToGlobal", () => {
  it("exposes API", () => {
    const g = {};
    const api = attachToGlobal(g);
    assert.equal(g.WVSiteUtils, api);
    assert.equal(typeof api.buildWhatsAppMessage, "function");
  });
});
