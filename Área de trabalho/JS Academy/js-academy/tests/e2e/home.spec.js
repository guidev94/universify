import { test, expect } from '@playwright/test';

test.describe('Homepage — JS Academy', () => {

    test('carrega corretamente com título e conteúdo principal', async ({ page }) => {
        await page.goto('/');
        await expect(page).toHaveTitle(/JS Academy/i);
        await expect(page.locator('nav')).toBeVisible();
        await expect(page.locator('.hero-headline')).toBeVisible();
    });

    test('exibe trilhas de aprendizado', async ({ page }) => {
        await page.goto('/');
        await expect(page.locator('.track-card').first()).toBeVisible();
        // Deve ter pelo menos 1 trilha
        const tracks = page.locator('.track-card');
        await expect(tracks).toHaveCountGreaterThan(0);
    });

    test('clicar em lição do primeiro módulo carrega conteúdo no editor', async ({ page }) => {
        await page.goto('/');
        // Clica na primeira lição não bloqueada
        const firstLesson = page.locator('.lesson-btn').first();
        await firstLesson.click();
        // Editor deve aparecer e título deve mudar
        await expect(page.locator('#lesson-title-display')).not.toContainText('Bem-vindo à Arena');
        await expect(page.locator('#monaco-editor')).toBeVisible();
    });

    test('dark/light mode toggle funciona', async ({ page }) => {
        await page.goto('/');
        // Estado inicial — dark mode (limpa localStorage para garantir)
        await page.evaluate(() => localStorage.removeItem('jsacademy_theme'));
        await page.reload();

        const body = page.locator('body');
        await expect(body).not.toHaveClass(/light-mode/);

        // Clicar no toggle
        await page.locator('#theme-toggle').click();
        await page.waitForTimeout(200);

        // Agora deve estar em light mode
        await expect(body).toHaveClass(/light-mode/);

        // Clicar novamente para voltar ao dark
        await page.locator('#theme-toggle').click();
        await page.waitForTimeout(200);
        await expect(body).not.toHaveClass(/light-mode/);
    });

    test('light mode persiste ao navegar entre páginas', async ({ page }) => {
        await page.goto('/');
        // Ativa light mode
        await page.locator('#theme-toggle').click();
        await page.waitForTimeout(200);
        await expect(page.locator('body')).toHaveClass(/light-mode/);

        // Navega para ranking
        await page.goto('/ranking');
        // Body deve continuar com light-mode (via pre-Alpine script)
        await expect(page.locator('body')).toHaveClass(/light-mode/);
    });

    test('navegação entre páginas funciona', async ({ page }) => {
        await page.goto('/');

        // Ir para Ranking
        await page.click('a[href*="ranking"]');
        await expect(page).toHaveURL(/\/ranking/);
        await expect(page.locator('h1')).toContainText(/ranking/i);

        // Voltar
        await page.goBack();
        await expect(page).toHaveURL('/');
    });

    test('botão Início no nav leva para home', async ({ page }) => {
        await page.goto('/ranking');
        await page.click('a[href="/"]');
        await expect(page).toHaveURL('http://localhost:8765/');
    });
});
