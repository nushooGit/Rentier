import { expect } from '@playwright/test';
import type { Page } from '@playwright/test';

export const e2eEmail = process.env.E2E_EMAIL ?? '';
export const e2ePassword = process.env.E2E_PASSWORD ?? '';

export function hasE2ECredentials() {
    return e2eEmail !== '' && e2ePassword !== '';
}

export function requireLocalBaseURL() {
    const baseURL = process.env.E2E_BASE_URL ?? 'http://127.0.0.1:8000';
    const host = new URL(baseURL).hostname;

    if (!['127.0.0.1', 'localhost'].includes(host)) {
        throw new Error(
            `Refusing write-capable E2E test against non-local base URL: ${baseURL}`,
        );
    }

    if (baseURL.toLowerCase().includes('rentier.ro')) {
        throw new Error(`Refusing write-capable E2E test against ${baseURL}`);
    }
}

export async function login(page: Page) {
    await page.goto('/login');
    await page.getByLabel('Adresă de email').fill(e2eEmail);
    await page.locator('input[name="password"]').fill(e2ePassword);
    await page.getByTestId('login-button').click();
    await expect(page).toHaveURL(/\/dashboard(?:\?|$)/);
    await expect(
        page.getByRole('heading', { name: 'Panou de control' }),
    ).toBeVisible();
}

export function currentTeamSlug(page: Page) {
    const [, slug] = new URL(page.url()).pathname.split('/');

    if (!slug) {
        throw new Error(
            `Could not determine current team slug from ${page.url()}`,
        );
    }

    return slug;
}

export async function createScenarioTeam(page: Page, teamName: string) {
    await page.getByTestId('team-switcher-trigger').click();
    await page.getByTestId('team-switcher-new-team').click();
    await page.getByTestId('create-team-name').fill(teamName);
    await page.getByTestId('create-team-submit').click();
    await expect(page).toHaveURL(/\/settings\/teams\/[^/]+(?:\?|$)/);

    const slug = new URL(page.url()).pathname.split('/').filter(Boolean).pop();

    if (!slug || slug === 'teams') {
        throw new Error(
            `Could not determine created team slug from ${page.url()}`,
        );
    }

    await page.goto(`/${slug}/dashboard`);
    await expect(
        page.getByRole('heading', { name: 'Panou de control' }),
    ).toBeVisible();

    return slug;
}

export async function selectOptionContaining(
    page: Page,
    selector: string,
    text: string,
) {
    const value = await page
        .locator(selector)
        .evaluate((select, optionText) => {
            const option = Array.from(
                (select as HTMLSelectElement).options,
            ).find((candidate) => candidate.textContent?.includes(optionText));

            return option?.value ?? null;
        }, text);

    if (value === null) {
        throw new Error(`No option containing "${text}" found for ${selector}`);
    }

    await page.locator(selector).selectOption(value);
}

const appTimeZone = 'Europe/Bucharest';

function appCalendarParts(date = new Date()) {
    const parts = new Intl.DateTimeFormat('en-CA', {
        timeZone: appTimeZone,
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    }).formatToParts(date);

    return Object.fromEntries(
        parts
            .filter((part) => part.type !== 'literal')
            .map((part) => [part.type, part.value]),
    ) as Record<'year' | 'month' | 'day', string>;
}

export function todayParts() {
    const { year, month, day } = appCalendarParts();

    return {
        date: `${year}-${month}-${day}`,
        day,
        month: String(Number(month)),
        year,
    };
}

export function monthParts(offset = 0) {
    const current = appCalendarParts();
    const target = new Date(
        Date.UTC(
            Number(current.year),
            Number(current.month) - 1 + offset,
            1,
            12,
        ),
    );
    const year = String(target.getUTCFullYear());
    const month = String(target.getUTCMonth() + 1);
    const date = `${year}-${month.padStart(2, '0')}-01`;
    const inlineLabel = new Intl.DateTimeFormat('ro-RO', {
        timeZone: appTimeZone,
        month: 'long',
        year: 'numeric',
    }).format(target);

    return {
        date,
        month,
        year,
        inlineLabel,
        label: inlineLabel.charAt(0).toUpperCase() + inlineLabel.slice(1),
    };
}
