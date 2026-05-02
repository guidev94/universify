import { test, expect } from '@playwright/test';

test.describe('Autenticação', () => {

    test('página de login renderiza corretamente', async ({ page }) => {
        await page.goto('/login');
        await expect(page.locator('input[name="email"]')).toBeVisible();
        await expect(page.locator('input[name="password"]')).toBeVisible();
        await expect(page.locator('button[type="submit"]')).toBeVisible();
    });

    test('página de registro renderiza corretamente', async ({ page }) => {
        await page.goto('/register');
        await expect(page.locator('input[name="name"]')).toBeVisible();
        await expect(page.locator('input[name="email"]')).toBeVisible();
        await expect(page.locator('input[name="password"]')).toBeVisible();
    });

    test('página de recuperar senha renderiza', async ({ page }) => {
        await page.goto('/forgot-password');
        await expect(page.locator('input[name="email"]')).toBeVisible();
        await expect(page.locator('button[type="submit"]')).toBeVisible();
    });

    test('login com credenciais inválidas mostra erro', async ({ page }) => {
        await page.goto('/login');
        await page.fill('input[name="email"]', 'invalido@teste.com');
        await page.fill('input[name="password"]', 'senhaerrada');
        await page.click('button[type="submit"]');
        await expect(page.locator('.text-red-400, [class*="text-red"]').first()).toBeVisible();
    });

    test('login com credenciais do admin funciona', async ({ page }) => {
        await page.goto('/login');
        await page.fill('input[name="email"]', 'admin@jsacademy.com');
        await page.fill('input[name="password"]', 'password');
        await page.click('button[type="submit"]');
        // Após login deve redirecionar para home
        await expect(page).toHaveURL('http://localhost:8765/');
        // Nav deve mostrar nome do usuário
        await expect(page.locator('nav')).toContainText(/Admin/i);
    });

    test('módulos além do primeiro bloqueados para visitantes', async ({ page }) => {
        await page.goto('/');
        // Segundo track-card deve ter o overlay de bloqueado
        const secondTrack = page.locator('.track-card').nth(1);
        await expect(secondTrack.locator('[data-requires-auth], .backdrop-blur-\\[3px\\]')).toBeVisible();
    });
});
