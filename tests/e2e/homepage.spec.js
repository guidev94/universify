import { test, expect } from "@playwright/test";
import { REQUIRED_SECTION_IDS, NAV_HASHES } from "../../assets/js/site-utils.mjs";

test.describe("WV homepage structure", () => {
  test.beforeEach(async ({ page }) => {
    await page.goto("/index.html", { waitUntil: "domcontentloaded" });
  });

  test("required sections exist", async ({ page }) => {
    for (const id of REQUIRED_SECTION_IDS) {
      await expect(page.locator(`#${id}`)).toHaveCount(1);
    }
  });

  test("nav hashes resolve to sections", async ({ page }) => {
    for (const hash of NAV_HASHES) {
      const id = hash.slice(1);
      await expect(page.locator(`#${id}`)).toBeAttached();
    }
  });

  test("hero has exactly 4 logos on one flex row", async ({ page }) => {
    const logos = page.locator(".hero-logos .hero-logo");
    await expect(logos).toHaveCount(4);

    const layout = await page.locator(".hero-logos").evaluate((el) => {
      const styles = getComputedStyle(el);
      const items = [...el.querySelectorAll(".hero-logo")];
      const tops = items.map((n) => n.getBoundingClientRect().top);
      const spread = Math.max(...tops) - Math.min(...tops);
      return {
        flexWrap: styles.flexWrap,
        singleRow: spread < 8,
        count: items.length
      };
    });

    expect(layout.count).toBe(4);
    expect(layout.flexWrap).toBe("nowrap");
    expect(layout.singleRow).toBe(true);
  });

  test("portfolio has 5 open-case cards", async ({ page }) => {
    await expect(page.locator("#portfolio .open-case")).toHaveCount(5);
  });

  test("produtos has 4 product cards with logos", async ({ page }) => {
    await expect(page.locator("#produtos .produto-card")).toHaveCount(4);
    await expect(page.locator("#produtos .produto-logo")).toHaveCount(4);
  });
});

test.describe("Contact modal", () => {
  test.beforeEach(async ({ page }) => {
    await page.goto("/index.html", { waitUntil: "networkidle" });
    await page.waitForFunction(() => window.__wvBooted === true);
  });

  test("opens from NOSSO ECOSSISTEMA and closes on backdrop", async ({ page }) => {
    const modal = page.locator("#contact-modal");
    await expect(modal).not.toHaveClass(/is-open/);

    await page.getByRole("button", { name: /NOSSO ECOSSISTEMA/i }).click();
    await expect(modal).toHaveClass(/is-open/);
    await expect(modal).toHaveAttribute("aria-hidden", "false");
    await expect(page.locator("html")).toHaveClass(/modal-open/);

    await page.locator("#contact-modal [data-modal-backdrop]").click({
      position: { x: 8, y: 8 },
      force: true
    });
    await expect(modal).not.toHaveClass(/is-open/, { timeout: 5000 });
    await expect(page.locator("html")).not.toHaveClass(/modal-open/);
  });

  test("opens from serviços CTA and closes with Escape", async ({ page }) => {
    await page.locator("#servicos").scrollIntoViewIfNeeded();
    await page.getByRole("button", { name: /Quero saber mais|Solicitar orçamento/i }).first().click();
    await expect(page.locator("#contact-modal")).toHaveClass(/is-open/);
    await page.keyboard.press("Escape");
    await expect(page.locator("#contact-modal")).not.toHaveClass(/is-open/, { timeout: 5000 });
  });

  test("does not leave ghost overlay after close", async ({ page }) => {
    await page.getByRole("button", { name: /NOSSO ECOSSISTEMA/i }).click();
    await expect(page.locator("#contact-modal")).toHaveClass(/is-open/);
    await page.locator("#close-modal").click();
    await expect(page.locator("#contact-modal")).not.toHaveClass(/is-open/, { timeout: 5000 });

    const blocking = await page.evaluate(() => {
      const modal = document.querySelector("#contact-modal");
      const cs = getComputedStyle(modal);
      return {
        pointerEvents: cs.pointerEvents,
        visibility: cs.visibility,
        isOpen: modal.classList.contains("is-open")
      };
    });
    expect(blocking.isOpen).toBe(false);
    expect(blocking.pointerEvents).toBe("none");
    expect(blocking.visibility).toBe("hidden");
  });
});

test.describe("Case modal", () => {
  test.beforeEach(async ({ page }) => {
    await page.goto("/index.html", { waitUntil: "networkidle" });
    await page.waitForFunction(() => window.__wvBooted === true);
  });

  test("opens first case and closes", async ({ page }) => {
    await page.locator("#portfolio").scrollIntoViewIfNeeded();
    await page.waitForTimeout(400);
    await page.locator("#portfolio .open-case").first().click();
    const modal = page.locator("#case-modal");
    await expect(modal).toHaveClass(/is-open/, { timeout: 8000 });
    await expect(page.locator("#case-modal-title")).not.toHaveText(/^\s*Título\s*$/);
    await page.locator("#close-case-modal").click();
    await expect(modal).not.toHaveClass(/is-open/, { timeout: 5000 });
  });

  test("each case card can open modal with its title", async ({ page }) => {
    test.setTimeout(120_000);
    await page.locator("#portfolio").scrollIntoViewIfNeeded();
    const cards = page.locator("#portfolio .open-case");
    const count = await cards.count();
    expect(count).toBe(5);

    for (let i = 0; i < count; i++) {
      const card = cards.nth(i);
      const expected = await card.getAttribute("data-case-title");
      await card.scrollIntoViewIfNeeded();
      await page.waitForTimeout(200);
      await card.click({ force: true });
      await expect(page.locator("#case-modal")).toHaveClass(/is-open/, { timeout: 8000 });
      await expect(page.locator("#case-modal-title")).toHaveText(expected);
      await page.locator("#close-case-modal").click();
      await expect(page.locator("#case-modal")).not.toHaveClass(/is-open/, { timeout: 5000 });
    }
  });
});

test.describe("Buttons and links smoke", () => {
  test("header WhatsApp CTA is external", async ({ page }) => {
    await page.goto("/index.html");
    const cta = page.locator("header a.magnetic").first();
    await expect(cta).toHaveAttribute("href", /wa\.me/);
  });

  test("blog cards link to blog-post", async ({ page }) => {
    await page.goto("/index.html");
    await page.locator("#blog").scrollIntoViewIfNeeded();
    const links = page.locator("#blog a.blog-card");
    await expect(links).toHaveCount(3);
    await expect(links.first()).toHaveAttribute("href", /blog-post/);
  });

  test("blog post page loads", async ({ page }) => {
    await page.goto("/blog-post.html");
    await expect(page).toHaveTitle(/./);
    await expect(page.locator("body")).toBeVisible();
  });

  test("no open-modal button throws", async ({ page }) => {
    await page.goto("/index.html", { waitUntil: "networkidle" });
    const errors = [];
    page.on("pageerror", (err) => errors.push(String(err)));

    const buttons = page.locator("button.open-modal");
    const n = await buttons.count();
    expect(n).toBeGreaterThan(3);

    // sample a few CTAs across sections
    const samples = [0, Math.floor(n / 2), n - 1];
    for (const idx of samples) {
      await buttons.nth(idx).scrollIntoViewIfNeeded();
      await buttons.nth(idx).click({ force: true });
      await expect(page.locator("#contact-modal")).toHaveClass(/is-open/);
      await page.keyboard.press("Escape");
      await expect(page.locator("#contact-modal")).not.toHaveClass(/is-open/, { timeout: 5000 });
    }
    expect(errors).toEqual([]);
  });
});
