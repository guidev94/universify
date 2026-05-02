import { test, expect } from '@playwright/test';

test.describe('Ranking', () => {

    test('página de ranking carrega', async ({ page }) => {
        await page.goto('/ranking');
        await expect(page).toHaveTitle(/Ranking/i);
        await expect(page.locator('h1')).toContainText(/ranking/i);
    });

    test('exibe tabela de usuários', async ({ page }) => {
        await page.goto('/ranking');
        await expect(page.locator('table, .ranking-row').first()).toBeVisible();
    });

    test('link para perfil público funciona', async ({ page }) => {
        await page.goto('/ranking');
        // Clica no primeiro link de usuário
        const firstUserLink = page.locator('a[href*="/user/"]').first();
        await expect(firstUserLink).toBeVisible();
        await firstUserLink.click();
        await expect(page).toHaveURL(/\/user\/\d+/);
    });

    test('página de perfil público carrega', async ({ page }) => {
        await page.goto('/ranking');
        const firstLink = page.locator('a[href*="/user/"]').first();
        const href = await firstLink.getAttribute('href');
        await page.goto(href);
        await expect(page.locator('.profile-header')).toBeVisible();
    });
});
