const { defineConfig } = require('@playwright/test');

const launchOptions = process.env.CHROME_PATH
    ? { executablePath: process.env.CHROME_PATH }
    : {};

module.exports = defineConfig({
    testDir: './tests/Playwright',
    testMatch: '**/*.spec.js',
    timeout: 30000,
    fullyParallel: false,
    workers: 1,
    reporter: [
        ['list'],
        ['json', { outputFile: 'preuves/semaine9/playwright-report.json' }],
    ],
    use: {
        baseURL: process.env.BASE_URL || 'http://127.0.0.1:8000',
        browserName: 'chromium',
        headless: true,
        launchOptions,
        screenshot: 'only-on-failure',
        trace: 'retain-on-failure',
    },
    webServer: {
        command: 'php -S 127.0.0.1:8000 -t public public/index.php',
        url: 'http://127.0.0.1:8000/health',
        env: { APP_ENV: 'test' },
        reuseExistingServer: !process.env.CI,
        timeout: 30000,
    },
});
