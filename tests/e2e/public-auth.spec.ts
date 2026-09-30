import { expect, test } from '@playwright/test';

test.describe('public and auth smoke', () => {
    test('home and login pages load', async ({ page }) => {
        await page.goto('/');
        await expect(page).toHaveTitle(
            /Administrare chirii pentru proprietari.*Rentier/,
        );
        await expect(
            page.getByRole('heading', {
                name: /Ai grijă de proprietăți\. Rentier ține evidența\./,
            }),
        ).toBeVisible();
        await expect(
            page.getByText('Contracte, chirii, garanții și cheltuieli', {
                exact: false,
            }),
        ).toBeVisible();

        const loginLink = page.getByRole('link', { name: 'Intră în cont' });
        await expect(loginLink).toBeVisible();
        await expect(loginLink).toHaveAttribute(
            'href',
            'http://127.0.0.1:8010/login',
        );

        await loginLink.click();
        await expect(page).toHaveURL(/\/login$/);
        await expect(
            page.getByRole('heading', { name: 'Bine ai revenit' }),
        ).toBeVisible();
    });

    test('captures WEB-01 desktop and mobile review screenshots', async ({ page }) => {
        await page.setViewportSize({ width: 1440, height: 1000 });
        await page.goto('/');
        await expect(
            page.getByRole('heading', {
                name: /Ai grijă de proprietăți\. Rentier ține evidența\./,
            }),
        ).toBeVisible();
        await page.screenshot({
            path: 'test-results/web-01-review/desktop.png',
            fullPage: true,
        });

        await page.setViewportSize({ width: 390, height: 844 });
        await page.reload();
        await expect(
            page.getByRole('link', { name: 'Intră în cont' }),
        ).toBeVisible();
        await page.screenshot({
            path: 'test-results/web-01-review/mobile.png',
            fullPage: true,
        });
    });

    test('login theme toggle persists and auth UI is reviewable', async ({ page }) => {
        await page.addInitScript(() => {
            if (!localStorage.getItem('appearance')) {
                localStorage.setItem('appearance', 'light');
            }
        });

        await page.setViewportSize({ width: 1440, height: 1000 });
        await page.goto('/login');
        await expect(
            page.getByRole('heading', { name: 'Bine ai revenit' }),
        ).toBeVisible();
        await expect(page.locator('html')).not.toHaveClass(/dark/);
        await page.screenshot({
            path: 'test-results/ui-03-auth-review/login-light-desktop.png',
            fullPage: true,
        });

        await page.getByTestId('theme-toggle').click();
        await expect(page.locator('html')).toHaveClass(/dark/);
        await expect
            .poll(() => page.evaluate(() => localStorage.getItem('appearance')))
            .toBe('dark');
        await page.screenshot({
            path: 'test-results/ui-03-auth-review/login-dark-desktop.png',
            fullPage: true,
        });

        await page.setViewportSize({ width: 390, height: 844 });
        await page.reload();
        await expect(page.locator('html')).toHaveClass(/dark/);
        await page.screenshot({
            path: 'test-results/ui-03-auth-review/login-dark-mobile.png',
            fullPage: true,
        });
    });

    test('language switcher changes the authentication UI to English', async ({
        page,
    }) => {
        await page.goto('/login');
        await expect(
            page.getByRole('heading', { name: 'Bine ai revenit' }),
        ).toBeVisible();

        await page.getByTestId('locale-switcher').click();
        await page.getByRole('menuitem', { name: /English/ }).click();

        await expect(
            page.getByRole('heading', { name: 'Welcome back' }),
        ).toBeVisible();
        await expect(page.getByLabel('Email address')).toBeVisible();
        await expect(page.getByLabel('Password')).toBeVisible();
        await expect(page.getByRole('button', { name: 'Log in' })).toBeVisible();
    });

    test('registration page is either available or intentionally disabled', async ({
        page,
        request,
    }) => {
        const response = await request.get('/register');

        if (process.env.E2E_ISOLATED === '1') {
            expect(response.status()).toBe(404);

            await page.goto('/login');
            await expect(
                page.getByRole('heading', { name: 'Bine ai revenit' }),
            ).toBeVisible();
            await expect(page.getByTestId('register-link')).toHaveCount(0);

            return;
        }

        expect([200, 404]).toContain(response.status());

        if (response.status() === 200) {
            await page.goto('/register');
            await expect(
                page.getByRole('heading', { name: 'Create an account' }),
            ).toBeVisible();
            await expect(page.locator('form[action="/register"]')).toBeVisible();

            return;
        }

        await page.goto('/login');
        await expect(
            page.getByRole('heading', { name: 'Bine ai revenit' }),
        ).toBeVisible();
        await expect(page.getByTestId('register-link')).toHaveCount(0);
    });
});
