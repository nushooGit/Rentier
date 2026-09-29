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
            page.getByRole('heading', { name: 'Log in to your account' }),
        ).toBeVisible();
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
                page.getByRole('heading', { name: 'Log in to your account' }),
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
            page.getByRole('heading', { name: 'Log in to your account' }),
        ).toBeVisible();
        await expect(page.getByTestId('register-link')).toHaveCount(0);
    });
});
