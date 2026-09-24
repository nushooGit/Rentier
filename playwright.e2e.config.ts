import { defineConfig, devices } from '@playwright/test';
import type { PlaywrightTestConfig } from '@playwright/test';

const baseURL = 'http://127.0.0.1:8010';

process.env.E2E_BASE_URL = baseURL;
process.env.E2E_EMAIL = process.env.E2E_EMAIL || 'e2e@rentier.test';
process.env.E2E_PASSWORD = process.env.E2E_PASSWORD || 'password';
process.env.E2E_ISOLATED = '1';

const webServer: PlaywrightTestConfig['webServer'] = [
    {
        name: 'Laravel E2E app',
        command:
            process.platform === 'win32'
                ? 'powershell -NoProfile -ExecutionPolicy Bypass -File scripts/e2e/start-isolated.ps1'
                : 'bash scripts/e2e/start-isolated.sh',
        url: `${baseURL}/login`,
        reuseExistingServer: false,
        timeout: 180_000,
    },
    ...(process.env.CI
        ? []
        : [
              {
                  name: 'Vite E2E assets',
                  command: 'npm run dev -- --host 127.0.0.1 --port 5174',
                  url: 'http://127.0.0.1:5174/@vite/client',
                  reuseExistingServer: false,
                  timeout: 180_000,
              },
          ]),
];

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
