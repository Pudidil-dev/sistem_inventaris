// Konfigurasi E2E: server PHP bawaan + database *_test yang di-reset sebelum test.
// E2E_PORT & E2E_DB_PATH dapat ditimpa agar beberapa agent berjalan paralel tanpa bentrok.
import { defineConfig, devices } from '@playwright/test';

const PORT = Number(process.env.E2E_PORT ?? 8080);
const DB_PATH = process.env.E2E_DB_PATH ?? 'storage/database/inventaris_test.sqlite';
const BASE_URL = `http://127.0.0.1:${PORT}`;

export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: false,
    workers: 1,
    retries: 0,
    reporter: [['list'], ['html', { open: 'never' }]],
    globalSetup: './tests/e2e/global-setup.js',
    use: {
        baseURL: BASE_URL,
        locale: 'id-ID',
        timezoneId: 'Asia/Jakarta',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },
    projects: [
        {
            name: 'desktop',
            use: { ...devices['Desktop Chrome'] },
            testIgnore: /responsive\.spec\.js/,
        },
        {
            name: 'mobile',
            use: { ...devices['Pixel 7'] },
            testMatch: /responsive\.spec\.js/,
        },
    ],
    webServer: {
        command: `php -S 127.0.0.1:${PORT} -t public`,
        url: `${BASE_URL}/login.php`,
        reuseExistingServer: false,
        timeout: 30_000,
        env: {
            APP_ENV: 'testing',
            APP_DEBUG: 'true',
            APP_BASE_URL: BASE_URL,
            DB_PATH,
        },
    },
});
