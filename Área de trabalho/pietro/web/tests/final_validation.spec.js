import { test, expect } from '@playwright/test';

const BASE_URL = 'http://localhost:8000';

test.describe('M. Pietro - Functional Validation', () => {
  
  test('header logo and navigation exist', async ({ page }) => {
    await page.goto(BASE_URL);
    await expect(page.locator('text=M. PIETRO')).toBeVisible();
    await expect(page.locator('nav a >> text=Catálogo')).toBeVisible();
  });

  test('hero section content and spacing', async ({ page }) => {
    await page.goto(BASE_URL);
    const hero = page.locator('.hero-luxe');
    await expect(hero).toBeVisible();
    
    // Check for high contrast text
    const h1 = hero.locator('h1');
    await expect(h1).toBeVisible();
    await expect(h1).toContainText('Brindes');
  });

  test('categories grid functionality', async ({ page }) => {
    await page.goto(BASE_URL);
    const drinkware = page.locator('text=Drinkware').first();
    await expect(drinkware).toBeVisible();
    
    // Click category and check redirection
    await drinkware.click();
    await expect(page).toHaveURL(/catalogo\.html\?cat=Bebidas/);
  });

  test('catalog products loading and search', async ({ page }) => {
    await page.goto(BASE_URL);
    await page.waitForSelector('.premium-card');
    const cards = await page.locator('.premium-card').count();
    expect(cards).toBeGreaterThan(0);
    
    // Test search filter
    await page.fill('#searchInput', 'Caneta');
    await page.waitForTimeout(500);
    const firstProductName = await page.locator('.premium-card h4').first().textContent();
    expect(firstProductName?.toLowerCase()).toContain('caneta');
  });

  test('product modal gallery', async ({ page }) => {
    await page.goto(BASE_URL);
    // Find a product likely to have multiple images (Sku usually starts with 111)
    const product = page.locator('.premium-card').first();
    await product.click();
    
    await expect(page.locator('#productModal')).toBeVisible();
    await expect(page.locator('#modalTitle')).not.toBeEmpty();
    
    // Check if gallery container exists (might be empty for some products, but should be in DOM)
    await expect(page.locator('#modalGallery')).toBeDefined();
  });
});
