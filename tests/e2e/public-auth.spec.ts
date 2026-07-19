import { expect, test } from '@playwright/test';

test.describe('public and auth smoke', () => {
    test('home and login pages load', async ({ page }) => {
        await page.goto('/');
        await expect(page).toHaveTitle(/Welcome|Rentier|Laravel/);
        await expect(page.getByRole('link', { name: 'Log in' })).toBeVisible();

        const registerLink = page.getByRole('link', { name: 'Register' });

        if (await registerLink.isVisible()) {
            await expect(registerLink).toHaveAttribute('href', '/register');
        }

        await page.getByRole('link', { name: 'Log in' }).click();
        await expect(page).toHaveURL(/\/login$/);
        await expect(
            page.getByRole('heading', { name: 'Log in to your account' }),
        ).toBeVisible();
    });

    test('registration page is either available or intentionally disabled', async ({
        page,
        request,
    }) => {
        const response = await request.get('/register');

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
            page.getByRole('heading', { name: 'Log in to your account' }),
        ).toBeVisible();
        await expect(page.getByTestId('register-link')).toHaveCount(0);
    });
});
