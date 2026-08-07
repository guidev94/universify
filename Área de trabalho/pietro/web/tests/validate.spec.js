import { test, expect } from '@playwright/test';

const BASE_URL = 'http://localhost:8000';

test.describe('M. Pietro - Web Validation', () => {
  
  test('home page loads correctly', async ({ page }) => {
    await page.goto(BASE_URL);
    
    // Check Title
    await expect(page).toHaveTitle(/M. PIETRO/);
    
    // Check Hero Section
    await expect(page.locator('h1')).toContainText('Sua marca em');
    
    // Check if products load
    await page.waitForSelector('.product-card-hover');
    const productsCount = await page.locator('.product-card-hover').count();
    expect(productsCount).toBeGreaterThan(0);
  });

  test('catalog page and filters', async ({ page }) => {
    await page.goto(`${BASE_URL}/catalogo.html`);
    
    // Check if categories load
    await page.waitForSelector('#categoriesList button');
    const categoriesCount = await page.locator('#categoriesList button').count();
    expect(categoriesCount).toBeGreaterThan(1);
    
    // Test search
    await page.fill('#catalogSearch', 'Caneta');
    await page.waitForTimeout(1000); // Wait for debounce/search
    const searchResults = await page.locator('.product-card-hover').count();
    expect(searchResults).toBeGreaterThan(0);
  });

  test('product detail page and cart', async ({ page }) => {
    await page.goto(`${BASE_URL}/catalogo.html`);
    
    // Click on first product
    const firstProduct = page.locator('.product-card-hover').first();
    const productName = await firstProduct.locator('h4').textContent();
    await firstProduct.click();
    
    // Check detail page
    await expect(page).toHaveURL(/sku=/);
    await expect(page.locator('h1')).toContainText(productName || '');
    
    // Add to cart
    await page.click('#addBtn');
    
    // Verify cart sidebar is open and has item
    await expect(page.locator('#cartSidebar')).toHaveClass(/translate-x-0/);
    await expect(page.locator('#cartItems')).toContainText(productName || '');
  });
});
