import { expect, test } from '@playwright/test';
import {
    currentTeamSlug,
    hasE2ECredentials,
    login,
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

    test('creates property, lease, payments, expense, and returns to dashboard', async ({
        page,
    }) => {
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
        await page.getByTestId('lease-start-date-input').fill(date);
        await page.getByTestId('lease-renter-name-input').fill(renterName);
        await page.locator('#renter_email').fill(`e2e-${suffix}@rentier.test`);
        await page.locator('#rent_due_day').fill('1');
        await page.locator('#deposit_amount').fill('500');
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
            page.getByRole('heading', { name: 'Dashboard' }),
        ).toBeVisible();
        await expect(page.getByText('Rezumat financiar')).toBeVisible();
        await expect(page.getByText(propertyName).first()).toBeVisible();
    });
});
