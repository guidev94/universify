import { test, expect } from "@playwright/test";

/** Hamburger below lg (1024); desktop chrome from notebooks up. */
const DESKTOP_NAV_MIN = 1024;

const VIEWPORTS = [
  { name: "390", width: 390, height: 844 },
  { name: "640", width: 640, height: 900 },
  { name: "768", width: 768, height: 1024 },
  { name: "900", width: 900, height: 1024 },
  { name: "1024", width: 1024, height: 768 },
  { name: "1100", width: 1100, height: 800 },
  { name: "1280", width: 1280, height: 800 },
  { name: "1366", width: 1366, height: 768 },
  { name: "1440", width: 1440, height: 900 }
];

/** Header + hero shell must not spill past the viewport (marquees/tracks excluded). */
async function headerHeroFit(page) {
  return page.evaluate(() => {
    const vw = document.documentElement.clientWidth;
    const header = document.querySelector("#main-header");
    const hero = document.querySelector("#hero.hero-v2");
    const content = document.querySelector("#hero.hero-v2 .hero-v2__content");
    const board = document.querySelector("#hero.hero-v2 .hero-orbs__canvas");
    const check = (el) => {
      if (!el) return null;
      const r = el.getBoundingClientRect();
      return {
        left: r.left,
        right: r.right,
        width: r.width,
        overflowRight: Math.max(0, r.right - vw),
        overflowLeft: Math.max(0, -r.left)
      };
    };
    return {
      vw,
      header: check(header),
      hero: check(hero),
      content: check(content),
      board: check(board)
    };
  });
}

test.describe("Header + Hero v2 responsive", () => {
  for (const vp of VIEWPORTS) {
    test(`index2 @ ${vp.name}px — header/hero fit, chrome ok`, async ({ page }) => {
      await page.setViewportSize({ width: vp.width, height: vp.height });
      await page.goto("/index2.html", { waitUntil: "domcontentloaded" });
      await page.waitForTimeout(400);

      const fit = await headerHeroFit(page);
      expect(fit.header.overflowRight, "header right").toBeLessThanOrEqual(1);
      expect(fit.header.overflowLeft, "header left").toBeLessThanOrEqual(1);
      expect(fit.content.overflowRight, "hero content right").toBeLessThanOrEqual(2);
      expect(fit.board.overflowRight, "orb board right").toBeLessThanOrEqual(2);

      const header = page.locator("#main-header");
      await expect(header).toBeVisible();

      const desktopNav = page.locator('#main-header .site-container > nav[aria-label="Principal"]');
      const mobileActions = page.locator("#main-header .header-actions--mobile");
      const hamburger = page.locator("#mobile-menu-trigger");

      // Aplicações removed from nav
      await expect(page.locator('#main-header a[href="#aplicacoes"]')).toHaveCount(0);
      await expect(page.locator('#mobile-menu a[href="#aplicacoes"]')).toHaveCount(0);

      if (vp.width < DESKTOP_NAV_MIN) {
        await expect(desktopNav).toBeHidden();
        await expect(mobileActions).toBeVisible();
        await expect(hamburger).toBeVisible();
        await expect(mobileActions.locator("[data-theme-toggle]")).toBeVisible();

        const logoBox = await page.locator("#main-header .site-logo-link").boundingBox();
        const actionsBox = await mobileActions.boundingBox();
        expect(logoBox).toBeTruthy();
        expect(actionsBox).toBeTruthy();
        expect(logoBox.width).toBeGreaterThan(80);
        expect(logoBox.width).toBeLessThanOrEqual(vp.width * 0.5);
        expect(logoBox.x + logoBox.width).toBeLessThanOrEqual(actionsBox.x + 1);
      } else {
        await expect(desktopNav).toBeVisible();
        await expect(mobileActions).toBeHidden();
        await expect(page.locator("#main-header .header-actions--desktop")).toBeVisible();
        await expect(
          page.locator("#main-header .header-actions--desktop a.magnetic.bg-todoz")
        ).toBeVisible();
        await expect(
          page.locator("#main-header .header-actions--desktop [data-theme-toggle]")
        ).toBeVisible();

        const chrome = await desktopNav.evaluate((nav) => {
          const links = [...nav.querySelectorAll(":scope > a")].map((a) => {
            const cs = getComputedStyle(a);
            const h = a.getBoundingClientRect().height;
            const lh = parseFloat(cs.lineHeight) || 0;
            return {
              text: a.textContent.trim(),
              whiteSpace: cs.whiteSpace,
              height: h,
              lineHeight: lh,
              fontSize: parseFloat(cs.fontSize) || 0
            };
          });
          const logo = document.querySelector("#main-header .site-logo-link");
          return {
            links,
            logoW: logo ? logo.getBoundingClientRect().width : 0,
            navGap: getComputedStyle(nav).gap
          };
        });

        for (const item of chrome.links) {
          expect(item.whiteSpace, item.text).toBe("nowrap");
          if (item.lineHeight > 0) {
            expect(item.height, item.text).toBeLessThanOrEqual(item.lineHeight * 1.65 + 4);
          }
          // Notebook+ must stay near the large-desktop type scale (no 10px crush)
          expect(item.fontSize, item.text).toBeGreaterThanOrEqual(10.75);
        }

        expect(chrome.logoW).toBeGreaterThanOrEqual(160);

        // Logo left · nav centered · actions right
        const layout = await page.evaluate(() => {
          const container = document.querySelector("#main-header .site-container");
          const logo = document.querySelector("#main-header .site-logo-link");
          const nav = document.querySelector('#main-header .site-container > nav[aria-label="Principal"]');
          const actions = document.querySelector("#main-header .header-actions--desktop");
          const cr = container.getBoundingClientRect();
          const lr = logo.getBoundingClientRect();
          const nr = nav.getBoundingClientRect();
          const ar = actions.getBoundingClientRect();
          const mid = cr.left + cr.width / 2;
          const navMid = nr.left + nr.width / 2;
          return {
            grid: getComputedStyle(container).gridTemplateColumns,
            logoLeftOfNav: lr.right <= nr.left + 1,
            navLeftOfActions: nr.right <= ar.left + 1,
            navCenterOffset: Math.abs(navMid - mid)
          };
        });
        expect(layout.grid.split(" ").length).toBeGreaterThanOrEqual(3);
        expect(layout.logoLeftOfNav).toBe(true);
        expect(layout.navLeftOfActions).toBe(true);
        expect(layout.navCenterOffset).toBeLessThanOrEqual(24);

        if (vp.width >= 1280) {
          for (const item of chrome.links) {
            expect(item.fontSize, item.text).toBeGreaterThanOrEqual(11.5);
          }
        }
      }

      const gridCols = await page.locator("#hero.hero-v2 .hero-v2__content").evaluate((el) => {
        return getComputedStyle(el).gridTemplateColumns;
      });

      if (vp.width < 1280) {
        expect(gridCols.split(" ").length).toBe(1);
      } else {
        expect(gridCols.split(" ").length).toBeGreaterThanOrEqual(2);
      }

      await expect(page.locator("#hero.hero-v2 .hero-orbs__canvas")).toBeVisible();
    });
  }

  test("shared header desktop nav on index.html @ 1024", async ({ page }) => {
    await page.setViewportSize({ width: 1024, height: 768 });
    await page.goto("/index.html", { waitUntil: "domcontentloaded" });
    await expect(page.locator('#main-header .site-container > nav[aria-label="Principal"]')).toBeVisible();
    await expect(page.locator("#main-header .header-actions--mobile")).toBeHidden();
    await expect(page.locator('#main-header a[href="#aplicacoes"]')).toHaveCount(0);
    await expect(page.locator('#mobile-menu a[href="#aplicacoes"]')).toHaveCount(0);
  });

  test("shared header hamburger on index3.html @ 900", async ({ page }) => {
    await page.setViewportSize({ width: 900, height: 1024 });
    await page.goto("/index3.html", { waitUntil: "domcontentloaded" });
    await expect(page.locator("#mobile-menu-trigger")).toBeVisible();
    await expect(page.locator('#main-header .site-container > nav[aria-label="Principal"]')).toBeHidden();
  });
});
