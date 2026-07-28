import { defineConfig, devices } from '@playwright/test';
import type { PlaywrightTestConfig } from '@playwright/test';

const baseURL = 'http://127.0.0.1:8010';

process.env.E2E_BASE_URL = baseURL;
process.env.E2E_EMAIL = process.env.E2E_EMAIL || 'e2e@rentier.test';
process.env.E2E_PASSWORD = process.env.E2E_PASSWORD || 'password';
process.env.E2E_ISOLATED = '1';

const webServer: PlaywrightTestConfig['webServer'] = {
    command:
        'powershell -NoProfile -ExecutionPolicy Bypass -File scripts/e2e/start-isolated.ps1',
    url: `${baseURL}/up`,
    reuseExistingServer: false,
    timeout: 180_000,
};

export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: false,
    workers: 1,
    retries: process.env.CI ? 1 : 0,
    reporter: process.env.CI ? [['list'], ['html', { open: 'never' }]] : 'list',
    webServer,
    use: {
        baseURL,
        testIdAttribute: 'data-test',
        screenshot: 'only-on-failure',
        trace: 'retain-on-failure',
        video: 'retain-on-failure',
    },
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],
});
