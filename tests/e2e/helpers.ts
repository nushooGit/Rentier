import { expect } from '@playwright/test';
import type { Page } from '@playwright/test';

export const e2eEmail = process.env.E2E_EMAIL ?? '';
export const e2ePassword = process.env.E2E_PASSWORD ?? '';

export function hasE2ECredentials() {
    return e2eEmail !== '' && e2ePassword !== '';
}

export async function login(page: Page) {
    await page.goto('/login');
    await page.getByLabel('Email address').fill(e2eEmail);
    await page.locator('input[name="password"]').fill(e2ePassword);
    await page.getByTestId('login-button').click();
    await expect(page).toHaveURL(/\/dashboard(?:\?|$)/);
    await expect(
        page.getByRole('heading', { name: 'Dashboard' }),
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

export async function selectOptionContaining(
    page: Page,
    selector: string,
    text: string,
) {
    const value = await page.locator(selector).evaluate(
        (select, optionText) => {
            const option = Array.from(
                (select as HTMLSelectElement).options,
            ).find((candidate) => candidate.textContent?.includes(optionText));

            return option?.value ?? null;
        },
        text,
    );

    if (value === null) {
        throw new Error(`No option containing "${text}" found for ${selector}`);
    }

    await page.locator(selector).selectOption(value);
}

export function todayParts() {
    const now = new Date();

    return {
        date: now.toISOString().slice(0, 10),
        month: String(now.getMonth() + 1),
        year: String(now.getFullYear()),
    };
}
