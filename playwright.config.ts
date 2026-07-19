import { defineConfig } from '@playwright/test';

export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: false,
    forbidOnly: Boolean(process.env.CI),
    retries: process.env.CI ? 1 : 0,
    workers: 1,
    reporter: [['line']],
    use: {
        baseURL: process.env.E2E_BASE_URL ?? 'http://127.0.0.1:8019',
        browserName: 'chromium',
        colorScheme: 'light',
        screenshot: 'only-on-failure',
        trace: 'retain-on-failure',
    },
    webServer: process.env.E2E_BASE_URL
        ? undefined
        : {
              command:
                  'php artisan serve --host=127.0.0.1 --port=8019 --no-reload',
              url: 'http://127.0.0.1:8019/up',
              reuseExistingServer: true,
              timeout: 120_000,
          },
});
