import { expect, test } from '@playwright/test';
import {
    currentTeamSlug,
    hasE2ECredentials,
    login,
    requireLocalBaseURL,
    selectOptionContaining,
    todayParts,
} from './helpers';

test.describe('authenticated landlord smoke', () => {
    test.skip(
        !hasE2ECredentials(),
        'Set E2E_EMAIL and E2E_PASSWORD for authenticated smoke tests.',
    );

    test('login reaches dashboard', async ({ page }) => {
        await login(page);
    });

    test('theme toggle switches the authenticated shell to dark mode', async ({ page }) => {
        await page.addInitScript(() => {
            if (!localStorage.getItem('appearance')) {
                localStorage.setItem('appearance', 'light');
            }
        });
        await login(page);
        await expect(page.locator('html')).not.toHaveClass(/dark/);

        await page.getByTestId('theme-toggle').click();
        await expect(page.locator('html')).toHaveClass(/dark/);
        await expect
            .poll(() => page.evaluate(() => localStorage.getItem('appearance')))
            .toBe('dark');

        await page.screenshot({
            path: 'test-results/ui-06-theme-review/dashboard-dark-desktop.png',
            fullPage: true,
        });
    });

    test('captures UI-05 dashboard review screenshots', async ({ page }) => {
        await page.setViewportSize({ width: 1440, height: 1000 });
        await login(page);
        await expect(
            page.getByRole('heading', {
                name: 'Panou de control',
            }),
        ).toBeVisible();
        await page.screenshot({
            path: 'test-results/ui-05-review/dashboard-desktop.png',
            fullPage: true,
        });

        await page.setViewportSize({ width: 390, height: 844 });
        await page.reload();
        await expect(
            page.getByText('Necesită atenția ta'),
        ).toBeVisible();
        await page.screenshot({
            path: 'test-results/ui-05-review/dashboard-mobile.png',
            fullPage: true,
        });
    });

    test('English locale stays consistent across landlord modules', async ({ page }) => {
        await login(page);
        const teamSlug = currentTeamSlug(page);

        await page.getByTestId('locale-switcher').click();
        await page.getByRole('menuitem', { name: /English/ }).click();
        await expect(
            page.getByRole('heading', { name: 'Dashboard' }),
        ).toBeVisible();

        await page.goto(`/${teamSlug}/properties/create`);
        await expect(page.getByText('Main details')).toBeVisible();
        await expect(page.getByText('Usable area (m²)')).toBeVisible();
        await expect(page.getByText('Total area (m²)')).toBeVisible();

        await page.goto(`/${teamSlug}/leases/create`);
        await expect(page.getByText('Lease details')).toBeVisible();
        await expect(page.getByText('Start date')).toBeVisible();
        await expect(page.getByText('End date')).toBeVisible();
        await expect(page.getByText('Renter phone')).toBeVisible();
        await expect(page.getByText('Monthly rent')).toBeVisible();
        await expect(page.getByText('Due day')).toBeVisible();

        await page.goto(`/${teamSlug}/payments/create`);
        await expect(page.getByText('Income details')).toBeVisible();
        await expect(page.getByText('Choose lease')).toBeVisible();
        await expect(page.getByText('Received date')).toBeVisible();

        await page.goto(`/${teamSlug}/expenses/create`);
        await expect(page.getByText('Cost details')).toBeVisible();
        await expect(page.getByText('Property and lease')).toBeVisible();
        await expect(page.getByText('Who bears this cost?')).toBeVisible();

        await page.goto(`/${teamSlug}/documents`);
        await expect(page.getByText('Upload document')).toBeVisible();
        await expect(
            page.locator('#category').getByRole('option', {
                name: 'Lease agreement',
            }),
        ).toHaveCount(1);
    });

    test('creates property, lease, payments, expense, and returns to dashboard', async ({
        page,
    }) => {
        test.setTimeout(60_000);

        requireLocalBaseURL();

        const suffix = `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
        const propertyName = `E2E Smoke Property ${suffix}`;
        const renterName = `E2E Smoke Renter ${suffix}`;
        const expenseTitle = `E2E Smoke Expense ${suffix}`;
        const { date, month, year } = todayParts();

        await login(page);
        const teamSlug = currentTeamSlug(page);

        await page.goto(`/${teamSlug}/properties/create`);
        await expect(page.getByTestId('property-name-input')).toBeVisible();
        await page.getByTestId('property-name-input').fill(propertyName);
        await page.getByTestId('property-type-select').selectOption('apartment');
        await page.getByTestId('property-status-select').selectOption('available');
        await page.getByTestId('property-city-input').fill('Bucuresti');
        await page
            .getByTestId('property-address-input')
            .fill(`Strada E2E ${suffix}`);
        await page.locator('#monthly_rent_amount').fill('2500');
        await page.locator('#deposit_amount').fill('500');
        await page.getByTestId('property-save-button').click();
        await expect(page).toHaveURL(new RegExp(`/${teamSlug}/properties`));
        await expect(page.getByText(propertyName)).toBeVisible();

        await page.goto(`/${teamSlug}/leases/create`);
        await expect(page.getByTestId('lease-property-select')).toBeVisible();
        await selectOptionContaining(
            page,
            '[data-test="lease-property-select"]',
            propertyName,
        );
        await page.getByTestId('lease-renter-name-input').fill(renterName);
        await page.locator('#renter_email').fill(`e2e-${suffix}@rentier.test`);
        await page.locator('#rent_due_day').fill('1');
        await page.locator('#deposit_amount').fill('500');
        await expect(page.getByTestId('lease-start-date-input')).toHaveAttribute(
            'aria-required',
            'true',
        );
        await expect(
            page.getByTestId('lease-start-date-input'),
        ).not.toHaveAttribute('required', '');
        await expect(page.locator('#end_date')).not.toHaveAttribute(
            'aria-required',
            'true',
        );
        await page.getByTestId('lease-save-button').click();
        await expect(
            page.getByText('Data de început este obligatorie.'),
        ).toHaveCount(1);
        await expect(
            page.getByText('The start date is required.'),
        ).toHaveCount(0);
        await expect(
            page.getByText('Please fill out this field.'),
        ).toHaveCount(0);

        await page
            .getByTestId('lease-start-date-input')
            .fill('35.09.2026');
        await page.getByTestId('lease-save-button').click();
        await expect(page.getByTestId('lease-start-date-input')).toHaveAttribute(
            'aria-invalid',
            'true',
        );
        await expect(page.locator('#start_date-format-hint')).toHaveText(
            'Data trebuie să fie în formatul ZZ.LL.AAAA.',
        );
        await expect(
            page.getByText('The start date field is required.'),
        ).toHaveCount(0);
        await expect(
            page.getByText('Data de început este obligatorie.'),
        ).toHaveCount(0);

        await page.getByTestId('lease-start-date-input').fill(date);
        await page.getByTestId('lease-save-button').click();
        await expect(page).toHaveURL(new RegExp(`/${teamSlug}/leases`));
        await expect(page.getByText(renterName)).toBeVisible();
        await expect(page.getByText(propertyName)).toBeVisible();

        await page.goto(`/${teamSlug}/payments/create`);
        await expect(page.getByTestId('payment-lease-select')).toBeVisible();
        await selectOptionContaining(
            page,
            '[data-test="payment-lease-select"]',
            renterName,
        );
        await page.getByTestId('payment-type-select').selectOption('rent');
        await page.locator('#method').selectOption('bank_transfer');
        await page.getByTestId('payment-amount-input').fill('2500');
        await page.locator('#payment_date').fill(date);
        await page.locator('#period_month').fill(month);
        await page.locator('#period_year').fill(year);
        await page.getByTestId('payment-save-button').click();
        await expect(page).toHaveURL(new RegExp(`/${teamSlug}/payments`));
        await expect(page.getByText(renterName).first()).toBeVisible();

        await page.goto(`/${teamSlug}/payments/create`);
        await selectOptionContaining(
            page,
            '[data-test="payment-lease-select"]',
            renterName,
        );
        await page.getByTestId('payment-type-select').selectOption('guarantee');
        await page.locator('#method').selectOption('bank_transfer');
        await page.getByTestId('payment-amount-input').fill('500');
        await page.locator('#payment_date').fill(date);
        await page.getByTestId('payment-save-button').click();
        await expect(page).toHaveURL(new RegExp(`/${teamSlug}/payments`));
        await expect(page.getByText(renterName).first()).toBeVisible();

        await page.goto(`/${teamSlug}/expenses/create`);
        await expect(page.getByTestId('expense-title-input')).toBeVisible();
        await page.getByTestId('expense-title-input').fill(expenseTitle);
        await page.getByTestId('expense-category-select').selectOption('repairs');
        await selectOptionContaining(
            page,
            '[data-test="expense-property-select"]',
            propertyName,
        );
        await page.getByTestId('expense-amount-input').fill('120');
        await page.locator('#expense_date').fill(date);
        await page.getByTestId('expense-save-button').click();
        await expect(page).toHaveURL(new RegExp(`/${teamSlug}/expenses`));
        await expect(page.getByText(expenseTitle)).toBeVisible();

        await page.goto(`/${teamSlug}/dashboard`);
        await expect(
            page.getByRole('heading', { name: 'Panou de control' }),
        ).toBeVisible();
        await expect(page.getByText('Detalii financiare')).toBeVisible();
        await expect(page.getByText(propertyName).first()).toBeVisible();
    });
});
