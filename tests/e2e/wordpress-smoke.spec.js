import { test, expect } from "@playwright/test";

const WP = process.env.WP_URL || "http://localhost:8080";

async function wpReachable(request) {
  try {
    const res = await request.get(WP + "/", { timeout: 8_000 });
    return res.ok();
  } catch {
    return false;
  }
}

test.describe("WordPress Docker smoke", () => {
  test.beforeEach(async ({ request }, testInfo) => {
    if (!(await wpReachable(request))) {
      testInfo.skip(true, "WordPress at " + WP + " not reachable");
    }
  });

  test("home loads with Insights section", async ({ page }) => {
    await page.goto(WP + "/", { waitUntil: "domcontentloaded" });
    await expect(page.locator("#blog")).toBeAttached();
    await expect(page.locator("footer a", { hasText: /Contato/i }).first()).toBeVisible();
  });

  test("blog index and single match blog-post layout cues", async ({ page }) => {
    await page.goto(WP + "/insights/", { waitUntil: "domcontentloaded" });
    await expect(page.locator("main .blog-card").first()).toBeVisible({ timeout: 15_000 });

    await page.locator("main .blog-card").first().click();
    await expect(page.locator("main article")).toBeVisible();
    await expect(page.locator("main nav", { hasText: /Insights/i })).toBeVisible();
    await expect(page.locator("aside")).toBeVisible();
    await expect(page.locator(".prose-blog, .prose")).toBeVisible();
  });

  test("contato page form saves a lead", async ({ page }) => {
    await page.goto(WP + "/contato/", { waitUntil: "networkidle" });
    await expect(page.locator("#contato-page-form")).toBeVisible();

    const stamp = Date.now();
    await page.fill("#wv-page-nome", `E2E Contato ${stamp}`);
    await page.fill("#wv-page-email", `e2e.contato.${stamp}@example.com`);
    await page.fill("#wv-page-phone", "11999990000");
    await page.fill("#wv-page-msg", "Mensagem de teste e2e do formulário Contato.");

    page.once("popup", async (popup) => {
      await popup.close().catch(() => {});
    });

    await page.locator("#contato-page-form [type=submit]").click();
    await expect(page.locator("#contato-page-form .wv-lead-feedback")).toBeVisible({ timeout: 15_000 });
    await expect(page.locator("#contato-page-form .wv-lead-feedback")).toContainText(/sucesso|Recebemos|enviada/i);
  });

  test("admin sidebar hides CPT top-level menus", async ({ page }) => {
    // WP admin auto-folds #adminmenu on narrow viewports — use desktop chrome for this check.
    await page.setViewportSize({ width: 1440, height: 900 });
    await page.goto(WP + "/wp-login.php", { waitUntil: "networkidle" });
    await page.locator("#user_login").fill("admin");
    await page.locator("#user_pass").fill("admin");
    await Promise.all([
      page.waitForURL(/\/wp-admin/, { timeout: 30_000 }),
      page.locator("#wp-submit").click()
    ]);

    await expect(page.locator("#adminmenu #toplevel_page_wvtodoz")).toBeAttached({ timeout: 15_000 });
    await expect(page.locator("#adminmenu > li#menu-posts-wv_case")).toHaveCount(0);
    await expect(page.locator("#adminmenu > li#menu-posts-wv_produto")).toHaveCount(0);
    await expect(page.locator("#adminmenu > li#menu-posts-wv_cliente")).toHaveCount(0);
    await expect(page.locator("#adminmenu > li#menu-posts-wv_servico")).toHaveCount(0);
    await expect(page.locator("#adminmenu a[href*='page=wv-seed']")).toHaveCount(0);

    await expect(page.locator("#adminmenu #menu-posts")).toBeAttached();
    await expect(page.locator("#adminmenu #menu-pages")).toBeAttached();
  });
});
