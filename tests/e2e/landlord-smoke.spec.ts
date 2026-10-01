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

    test('English locale is complete on landlord forms and document categories', async ({
        page,
    }) => {
        test.setTimeout(60_000);
        requireLocalBaseURL();

        const suffix = `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
        const propertyName = `E2E EN Property ${suffix}`;

        await login(page);
        await page.getByTestId('locale-switcher').click();
        await page.getByRole('menuitem', { name: /English/ }).click();
        await expect(
            page.getByRole('heading', { name: 'Dashboard' }),
        ).toBeVisible();

        const teamSlug = currentTeamSlug(page);

        await page.goto('/settings/teams');
        await expect(
            page.getByRole('heading', {
                level: 2,
                name: 'Workspaces',
                exact: true,
            }),
        ).toBeVisible();
        await expect(page.getByText('Teams', { exact: true })).toHaveCount(0);
        await expect(
            page.getByText('Manage your workspaces and member access.'),
        ).toBeVisible();

        await page.goto(`/${teamSlug}/properties/create`);
        await expect(page.getByText('Property name')).toBeVisible();
        await page.getByTestId('property-name-input').fill(propertyName);
        await page.getByTestId('property-type-select').selectOption('apartment');
        await page.getByTestId('property-status-select').selectOption('available');
        await page.getByTestId('property-city-input').fill('Bucharest');
        await page
            .getByTestId('property-address-input')
            .fill(`English Street ${suffix}`);
        await page.locator('#monthly_rent_amount').fill('1800');
        await page.getByTestId('property-save-button').click();
        await expect(page).toHaveURL(new RegExp(`/${teamSlug}/properties`));
        await expect(page.getByText(propertyName)).toBeVisible();

        await page.goto(`/${teamSlug}/leases/create`);
        await expect(page.getByText('Start date')).toBeVisible();
        await expect(page.getByText('End date')).toBeVisible();
        await expect(page.getByText('Renter phone')).toBeVisible();
        await expect(page.getByText('Monthly rent')).toBeVisible();
        await expect(page.getByText('Due day')).toBeVisible();
        await expect(page.getByText('Data început')).toHaveCount(0);
        await expect(page.getByText('Telefon chiriaș')).toHaveCount(0);

        await page.goto(`/${teamSlug}/leases`);
        await expect(
            page.getByRole('heading', {
                name: 'Leases',
                level: 2,
                exact: true,
            }),
        ).toBeVisible();
        await expect(page.getByText('Contracte', { exact: true })).toHaveCount(0);
        await expect(page.getByText('Editează contractul')).toHaveCount(0);
        await expect(page.getByText('Șterge contractul')).toHaveCount(0);
        await expect(page.getByText('Nu există contracte încă')).toHaveCount(0);

        await page.goto(`/${teamSlug}/payments/create`);
        await expect(page.getByTestId('payment-lease-select')).toContainText('Choose lease');
        await expect(page.getByText('Income type')).toBeVisible();
        await expect(page.getByText('Received date')).toBeVisible();
        await expect(page.getByText('Rent month')).toBeVisible();
        await expect(page.getByText('Rent year')).toBeVisible();
        await expect(page.getByText('Nesetat')).toHaveCount(0);

        await page.goto(`/${teamSlug}/expenses/create`);
        await expect(page.getByText('Property and lease')).toBeVisible();
        await expect(page.getByText('Amount and payment')).toBeVisible();
        await expect(page.getByText('Who bears the cost?')).toBeVisible();
        await expect(page.getByText('Decontare')).toHaveCount(0);

        await page.goto(`/${teamSlug}/calendar`);
        await expect(
            page.getByRole('heading', {
                level: 2,
                name: 'Calendar',
                exact: true,
            }),
        ).toBeVisible();
        await expect(
            page.getByText('Rent due dates and lease changes in a monthly view.'),
        ).toBeVisible();

        await page.goto(`/${teamSlug}/documents`);
        await expect(page.locator('#category')).toBeVisible();
        await expect(page.locator('#category')).toContainText('Lease contract');
        await expect(page.locator('#category')).toContainText('Addendum');
        await expect(page.locator('#category')).toContainText('Handover report');
        await expect(page.locator('#category')).not.toContainText(
            'Contract de închiriere',
        );
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
        const reminderTitle = `E2E Smoke Reminder ${suffix}`;
        const utilityProvider = `E2E Utility ${suffix}`;
        const secondUtilityProvider = `E2E Water ${suffix}`;
        const utilityInvoice = `E2E-INV-${suffix}`;
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

        await page.goto(
            `/${teamSlug}/calendar?month=${year}-${month.padStart(2, '0')}`,
        );
        await expect(page.getByText('Scadență chirie').first()).toBeVisible();
        await expect(page.getByText(propertyName).first()).toBeVisible();
        await expect(page.getByText(renterName).first()).toBeVisible();
        await expect(page.getByText('Achitată').first()).toBeVisible();

        await page.getByTestId('add-reminder-button').click();
        await page.getByTestId('reminder-title-input').fill(reminderTitle);
        await page.getByTestId('reminder-date-input').fill(date);
        await selectOptionContaining(
            page,
            '[data-test="reminder-property-select"]',
            propertyName,
        );
        await page.getByTestId('reminder-save-button').click();
        await expect(page.getByText(reminderTitle).first()).toBeVisible();

        await page.getByText(reminderTitle).first().click();
        await page.getByTestId('reminder-toggle-button').click();
        await expect(page.getByText('Făcut').first()).toBeVisible();

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

        await page.goto(`/${teamSlug}/utilities`);
        await page.getByTestId('utility-add-account-button').click();
        await selectOptionContaining(
            page,
            '[data-test="utility-account-property-select"]',
            propertyName,
        );
        await page.getByTestId('utility-provider-input').fill(utilityProvider);
        await page
            .getByTestId('utility-service-select')
            .selectOption('electricity');
        await page.getByTestId('utility-account-save-button').click();
        await expect(page.getByText(utilityProvider).first()).toBeVisible();

        await page.getByTestId('utility-add-account-button').click();
        await selectOptionContaining(
            page,
            '[data-test="utility-account-property-select"]',
            propertyName,
        );
        await page
            .getByTestId('utility-provider-input')
            .fill(secondUtilityProvider);
        await page
            .getByTestId('utility-service-select')
            .selectOption('water');
        await page.getByTestId('utility-account-save-button').click();
        await expect(page.getByText(secondUtilityProvider).first()).toBeVisible();

        const utilityAccountSection = page
            .getByTestId('utility-account-section')
            .filter({ hasText: utilityProvider });
        const secondUtilityAccountSection = page
            .getByTestId('utility-account-section')
            .filter({ hasText: secondUtilityProvider });

        await expect(
            utilityAccountSection.getByTestId('utility-account-dropzone'),
        ).toBeVisible();
        await utilityAccountSection
            .getByTestId('utility-account-upload-input')
            .setInputFiles({
                name: 'e2e-utility-invoice.pdf',
                mimeType: 'application/pdf',
                buffer: Buffer.from('%PDF-1.4\n% E2E utility invoice\n'),
            });
        await expect(
            page
                .getByTestId('utility-bill-account-select')
                .locator('option:checked'),
        ).toContainText(utilityProvider);
        await expect(
            page.getByText('e2e-utility-invoice.pdf', { exact: true }),
        ).toBeVisible();

        await page
            .getByTestId('utility-bill-number-input')
            .fill(utilityInvoice);
        await page.getByTestId('utility-bill-amount-input').fill('150.50');
        await page.getByTestId('utility-billing-start-input').fill(date);
        await page.getByTestId('utility-billing-end-input').fill(date);
        await page.getByTestId('utility-issue-date-input').fill(date);
        await page.getByTestId('utility-due-date-input').fill(date);
        await page.getByTestId('utility-bill-save-button').click();
        await expect(page.getByText(utilityInvoice).first()).toBeVisible();
        await expect(utilityAccountSection).toContainText(utilityInvoice);
        await expect(secondUtilityAccountSection).not.toContainText(
            utilityInvoice,
        );

        const utilityGroup = page
            .getByTestId('utility-property-group')
            .filter({ hasText: propertyName });
        await expect(utilityGroup).toBeVisible();
        await expect(
            utilityGroup.getByRole('heading', {
                name: utilityProvider,
                exact: true,
            }),
        ).toBeVisible();

        const utilityBillCard = utilityGroup
            .getByTestId('utility-bill-card')
            .filter({ hasText: utilityInvoice });
        await expect(utilityBillCard).toBeVisible();

        const [utilityDownload] = await Promise.all([
            page.waitForEvent('download'),
            utilityBillCard.getByTestId('utility-bill-download-link').click(),
        ]);
        expect(utilityDownload.suggestedFilename()).toBe(
            'e2e-utility-invoice.pdf',
        );

        await page.goto(`/${teamSlug}/dashboard`);
        await expect(
            page.getByRole('heading', { name: 'Panou de control' }),
        ).toBeVisible();
        await expect(page.getByText('Detalii financiare')).toBeVisible();
        await expect(page.getByText(propertyName).first()).toBeVisible();

        await page.setViewportSize({ width: 1440, height: 1000 });
        for (const [name, path] of [
            [
                'calendar',
                `/${teamSlug}/calendar?month=${year}-${month.padStart(2, '0')}`,
            ],
            ['properties', `/${teamSlug}/properties`],
            ['leases', `/${teamSlug}/leases`],
            ['payments', `/${teamSlug}/payments`],
            ['expenses', `/${teamSlug}/expenses`],
            ['utilities', `/${teamSlug}/utilities`],
            ['documents', `/${teamSlug}/documents`],
        ] as const) {
            await page.goto(path);
            await page.screenshot({
                path: `test-results/ui-05-review/${name}-desktop.png`,
                fullPage: true,
            });
        }

        await page.setViewportSize({ width: 390, height: 844 });
        for (const [name, path] of [
            [
                'calendar',
                `/${teamSlug}/calendar?month=${year}-${month.padStart(2, '0')}`,
            ],
            ['properties', `/${teamSlug}/properties`],
            ['leases', `/${teamSlug}/leases`],
            ['payments', `/${teamSlug}/payments`],
            ['expenses', `/${teamSlug}/expenses`],
            ['utilities', `/${teamSlug}/utilities`],
            ['documents', `/${teamSlug}/documents`],
        ] as const) {
            await page.goto(path);
            await expect
                .poll(() =>
                    page.evaluate(
                        () =>
                            document.documentElement.scrollWidth <=
                            window.innerWidth,
                    ),
                )
                .toBe(true);
            await page.screenshot({
                path: `test-results/ui-05-review/${name}-mobile.png`,
                fullPage: true,
            });
        }

        await page.setViewportSize({ width: 1280, height: 720 });
        await page.getByTestId('locale-switcher').click();
        await page.getByRole('menuitem', { name: /English/ }).click();
        await page.goto(`/${teamSlug}/utilities`);
        await expect(
            page.getByRole('heading', { name: 'Utilities', exact: true }),
        ).toBeVisible();
        await expect(page.getByText('Utilități', { exact: true })).toHaveCount(0);

        await page.goto(`/${teamSlug}/properties`);

        const propertyCard = page
            .getByTestId('property-card')
            .filter({ hasText: propertyName });

        await expect(propertyCard).toContainText('Paid this month');
        await expect(propertyCard).not.toContainText('Plătită');
        await expect(propertyCard).not.toContainText('Restanță');
        await expect(propertyCard).not.toContainText('luni restante');
    });
});
