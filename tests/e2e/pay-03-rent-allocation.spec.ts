import { expect, test } from '@playwright/test';
import type { Page } from '@playwright/test';
import {
    createScenarioTeam,
    hasE2ECredentials,
    login,
    monthParts,
    requireLocalBaseURL,
    selectOptionContaining,
} from './helpers';

test.describe('PAY-03 rent payment allocation', () => {
    test.skip(
        !hasE2ECredentials(),
        'Set E2E_EMAIL and E2E_PASSWORD for authenticated PAY-03 tests.',
    );

    test.beforeEach(() => {
        requireLocalBaseURL();
    });

    test('multiple payments roll forward across months and recalculate on edit/delete', async ({
        page,
    }) => {
        const suffix = uniqueSuffix();
        const current = monthParts(0);
        const next = monthParts(1);
        const following = monthParts(2);
        const propertyName = `PAY03 Roll Property ${suffix}`;
        const renterName = `PAY03 Roll Renter ${suffix}`;

        await login(page);
        const teamSlug = await createScenarioTeam(
            page,
            `PAY03 Roll Team ${suffix}`,
        );

        await createProperty(page, teamSlug, propertyName, '2500', '2500');
        await createLease(
            page,
            teamSlug,
            propertyName,
            renterName,
            current.date,
            following.date,
            '5',
            '2500',
        );
        await createRentPayment(page, teamSlug, renterName, '1500', current);
        await createRentPayment(page, teamSlug, renterName, '1000', current);
        await createRentPayment(page, teamSlug, renterName, '2500', current);

        await page.goto(`/${teamSlug}/properties`);
        const propertyCard = page
            .getByTestId('property-card')
            .filter({ hasText: propertyName });
        await expect(propertyCard).toContainText('Plătită luna aceasta');
        await expect(propertyCard).toContainText(
            `Plătită în avans până în ${next.inlineLabel}`,
        );

        await page.goto(`/${teamSlug}/dashboard`);
        await expect(rentCollectedCard(page)).toContainText('5.000 RON');
        await expect(
            page.getByRole('heading', { name: 'Chirii plătite în avans' }),
        ).toBeVisible();
        await expect(
            page
                .locator('section')
                .filter({ hasText: 'Chirii plătite în avans' }),
        ).toContainText(`Plătită în avans până în ${next.inlineLabel}`);

        await page.goto(`/${teamSlug}/payments`);
        await expect(paymentCard(page, renterName, '1.500 RON')).toContainText(
            'Chirie parțial achitată',
        );
        await expect(paymentCard(page, renterName, '1.500 RON')).toContainText(
            `${current.label} - 1.500 RON`,
        );
        await expect(paymentCard(page, renterName, '1.000 RON')).toContainText(
            'Chirie parțial achitată',
        );
        await expect(paymentCard(page, renterName, '1.000 RON')).toContainText(
            `${current.label} - 1.000 RON`,
        );
        await expect(paymentCard(page, renterName, '2.500 RON')).toContainText(
            'Chirie achitată integral',
        );
        await expect(paymentCard(page, renterName, '2.500 RON')).toContainText(
            `${next.label} - 2.500 RON`,
        );

        await paymentCard(page, renterName, '2.500 RON')
            .getByTestId('payment-card-link')
            .click();
        await expect(
            page.getByRole('heading', { name: 'Alocare chirie' }),
        ).toBeVisible();
        await expect(
            page.locator('section').filter({ hasText: 'Alocare chirie' }),
        ).toContainText(next.label);
        await expect(
            page.locator('section').filter({ hasText: 'Alocare chirie' }),
        ).toContainText('2.500 RON');

        await page.goto(`/${teamSlug}/payments`);
        await paymentCard(page, renterName, '2.500 RON')
            .getByTestId('payment-edit-link')
            .click();
        await page.getByTestId('payment-amount-input').fill('1250');
        await page.getByTestId('payment-save-button').click();
        await expect(page).toHaveURL(/\/payments\/[^/]+$/);

        await page.goto(`/${teamSlug}/dashboard`);
        await expect(rentCollectedCard(page)).toContainText('3.750 RON');
        await expect(
            page
                .locator('section')
                .filter({ hasText: 'Chirii plătite în avans' }),
        ).toContainText(
            `Avans pentru ${next.inlineLabel}: 1.250 RON / 2.500 RON`,
        );

        await page.goto(`/${teamSlug}/payments`);
        page.once('dialog', (dialog) => dialog.accept());
        await paymentCard(page, renterName, '1.250 RON')
            .getByTestId('payment-delete-button')
            .click();

        await page.goto(`/${teamSlug}/dashboard`);
        await expect(rentCollectedCard(page)).toContainText('2.500 RON');
        await expect(
            page
                .locator('section')
                .filter({ hasText: 'Chirii plătite în avans' }),
        ).not.toContainText(next.inlineLabel);

        await page.goto(`/${teamSlug}/payments`);
        await expect(
            page.getByTestId('payment-card').filter({ hasText: renterName }),
        ).toHaveCount(2);
    });

    test('partial third month advance is visible', async ({ page }) => {
        const suffix = uniqueSuffix();
        const current = monthParts(0);
        const next = monthParts(1);
        const following = monthParts(2);
        const propertyName = `PAY03 Partial Property ${suffix}`;
        const renterName = `PAY03 Partial Renter ${suffix}`;

        await login(page);
        const teamSlug = await createScenarioTeam(
            page,
            `PAY03 Partial Team ${suffix}`,
        );

        await createProperty(page, teamSlug, propertyName, '2500', '0');
        await createLease(
            page,
            teamSlug,
            propertyName,
            renterName,
            current.date,
            following.date,
            '5',
            '0',
        );
        await createRentPayment(page, teamSlug, renterName, '6000', current);

        await page.goto(`/${teamSlug}/properties`);
        await expect(
            page.getByTestId('property-card').filter({ hasText: propertyName }),
        ).toContainText(`Plătită în avans până în ${next.inlineLabel}`);
        await expect(
            page.getByTestId('property-card').filter({ hasText: propertyName }),
        ).toContainText(
            `Avans pentru ${following.inlineLabel}: 1.000 RON / 2.500 RON`,
        );

        await page.goto(`/${teamSlug}/dashboard`);
        await expect(
            page
                .locator('section')
                .filter({ hasText: 'Chirii plătite în avans' }),
        ).toContainText(`Plătită în avans până în ${next.inlineLabel}`);
        await expect(
            page
                .locator('section')
                .filter({ hasText: 'Chirii plătite în avans' }),
        ).toContainText(
            `Avans pentru ${following.inlineLabel}: 1.000 RON / 2.500 RON`,
        );

        await page.goto(`/${teamSlug}/payments`);
        const card = paymentCard(page, renterName, '6.000 RON');
        await expect(card).toContainText(`${current.label} - 2.500 RON`);
        await expect(card).toContainText(`${next.label} - 2.500 RON`);
        await expect(card).toContainText(`${following.label} - 1.000 RON`);
    });

    test('lease-end excess remains unallocated', async ({ page }) => {
        const suffix = uniqueSuffix();
        const current = monthParts(0);
        const propertyName = `PAY03 Credit Property ${suffix}`;
        const renterName = `PAY03 Credit Renter ${suffix}`;

        await login(page);
        const teamSlug = await createScenarioTeam(
            page,
            `PAY03 Credit Team ${suffix}`,
        );

        await createProperty(page, teamSlug, propertyName, '2500', '0');
        await createLease(
            page,
            teamSlug,
            propertyName,
            renterName,
            current.date,
            current.date,
            '5',
            '0',
        );
        await createRentPayment(page, teamSlug, renterName, '4000', current);

        await page.goto(`/${teamSlug}/payments`);
        const card = paymentCard(page, renterName, '4.000 RON');
        await expect(card).toContainText(`${current.label} - 2.500 RON`);
        await expect(card).toContainText('Sold nealocat: 1.500 RON');

        await card.getByTestId('payment-card-link').click();
        await expect(
            page.locator('section').filter({ hasText: 'Alocare chirie' }),
        ).toContainText('Sold nealocat');
        await expect(
            page.locator('section').filter({ hasText: 'Alocare chirie' }),
        ).toContainText('1.500 RON');
    });

    test('guarantee payments do not show rent allocation or advance notices', async ({
        page,
    }) => {
        const suffix = uniqueSuffix();
        const current = monthParts(0);
        const propertyName = `PAY03 Guarantee Property ${suffix}`;
        const renterName = `PAY03 Guarantee Renter ${suffix}`;

        await login(page);
        const teamSlug = await createScenarioTeam(
            page,
            `PAY03 Guarantee Team ${suffix}`,
        );

        await createProperty(page, teamSlug, propertyName, '2500', '2500');
        await createLease(
            page,
            teamSlug,
            propertyName,
            renterName,
            current.date,
            monthParts(1).date,
            '5',
            '2500',
        );
        await createGuaranteePayment(
            page,
            teamSlug,
            renterName,
            '2500',
            current,
        );

        await page.goto(`/${teamSlug}/payments`);
        const card = paymentCard(page, renterName, '2.500 RON');
        await expect(card).toContainText('Garanție achitată integral');
        await expect(card).not.toContainText('Alocare chirie');

        await page.goto(`/${teamSlug}/properties`);
        const propertyCard = page
            .getByTestId('property-card')
            .filter({ hasText: propertyName });
        await expect(propertyCard).not.toContainText('Plătită în avans');
    });
});

function uniqueSuffix() {
    return `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
}

function rentCollectedCard(page: Page) {
    return page.getByTestId('dashboard-rent-collected');
}

function paymentCard(page: Page, renterName: string, amount: string) {
    return page
        .getByTestId('payment-card')
        .filter({ hasText: renterName })
        .filter({ hasText: amount });
}

async function createProperty(
    page: Page,
    teamSlug: string,
    propertyName: string,
    rentAmount: string,
    guaranteeAmount: string,
) {
    await page.goto(`/${teamSlug}/properties/create`);
    await page.getByTestId('property-name-input').fill(propertyName);
    await page.getByTestId('property-type-select').selectOption('apartment');
    await page.getByTestId('property-status-select').selectOption('available');
    await page.getByTestId('property-city-input').fill('București');
    await page
        .getByTestId('property-address-input')
        .fill(`Strada PAY03 ${propertyName}`);
    await page.locator('#monthly_rent_amount').fill(rentAmount);
    await page.locator('#deposit_amount').fill(guaranteeAmount);
    await page.getByTestId('property-save-button').click();
    await expect(page).toHaveURL(new RegExp(`/${teamSlug}/properties`));
}

async function createLease(
    page: Page,
    teamSlug: string,
    propertyName: string,
    renterName: string,
    startDate: string,
    endDate: string,
    dueDay: string,
    guaranteeAmount: string,
) {
    await page.goto(`/${teamSlug}/leases/create`);
    await selectOptionContaining(
        page,
        '[data-test="lease-property-select"]',
        propertyName,
    );
    await page.getByTestId('lease-start-date-input').fill(startDate);
    await page.locator('#end_date').fill(endDate);
    await page.getByTestId('lease-renter-name-input').fill(renterName);
    await page
        .locator('#renter_email')
        .fill(`${renterName.replaceAll(' ', '-')}@rentier.test`);
    await page.locator('#rent_due_day').fill(dueDay);
    await page.locator('#deposit_amount').fill(guaranteeAmount);
    await page.getByTestId('lease-save-button').click();
    await expect(page).toHaveURL(new RegExp(`/${teamSlug}/leases`));
}

async function createRentPayment(
    page: Page,
    teamSlug: string,
    renterName: string,
    amount: string,
    period: ReturnType<typeof monthParts>,
) {
    await createPayment(page, teamSlug, renterName, amount, period, 'rent');
}

async function createGuaranteePayment(
    page: Page,
    teamSlug: string,
    renterName: string,
    amount: string,
    period: ReturnType<typeof monthParts>,
) {
    await createPayment(
        page,
        teamSlug,
        renterName,
        amount,
        period,
        'guarantee',
    );
}

async function createPayment(
    page: Page,
    teamSlug: string,
    renterName: string,
    amount: string,
    period: ReturnType<typeof monthParts>,
    paymentType: 'rent' | 'guarantee',
) {
    await page.goto(`/${teamSlug}/payments/create`);
    await selectOptionContaining(
        page,
        '[data-test="payment-lease-select"]',
        renterName,
    );
    await page.getByTestId('payment-type-select').selectOption(paymentType);
    await page.locator('#method').selectOption('bank_transfer');
    await page.getByTestId('payment-amount-input').fill(amount);
    await page.locator('#payment_date').fill(period.date);

    if (paymentType === 'rent') {
        await page.locator('#period_month').fill(period.month);
        await page.locator('#period_year').fill(period.year);
    }

    await page.getByTestId('payment-save-button').click();
    await expect(page).toHaveURL(new RegExp(`/${teamSlug}/payments`));
}
