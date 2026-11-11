const fs = require('node:fs');
const path = require('node:path');
const { defineConfig } = require('@playwright/test');

// Le mot de passe des comptes fictifs vit dans .env.test.local (ignoré par Git) : on le charge
// pour les scripts de test sans jamais l'écrire dans un fichier versionné.
const localEnv = path.join(__dirname, '.env.test.local');
if (fs.existsSync(localEnv)) {
    for (const line of fs.readFileSync(localEnv, 'utf8').split(/\r?\n/)) {
        const match = line.match(/^\s*([A-Z0-9_]+)\s*=\s*(.*)\s*$/);
        if (match && process.env[match[1]] === undefined) {
            process.env[match[1]] = match[2].replace(/^['"]|['"]$/g, '');
        }
    }
}

// Navigateur : Chrome déjà installé sur le poste (aucun téléchargement global de Chromium).
const defaultChrome = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const executablePath = process.env.CHROME_PATH || (fs.existsSync(defaultChrome) ? defaultChrome : undefined);

module.exports = defineConfig({
    testDir: './tests/Playwright',
    testMatch: '**/*.spec.js',
    timeout: 60000,
    fullyParallel: false,
    workers: 1,
    reporter: [['list']],
    globalSetup: require.resolve('./tests/Playwright/global-setup.js'),
    outputDir: 'test-results',
    use: {
        baseURL: process.env.BASE_URL || 'http://127.0.0.1:8000',
        browserName: 'chromium',
        headless: true,
        viewport: { width: 1366, height: 820 },
        locale: 'fr-FR',
        launchOptions: executablePath ? { executablePath } : {},
        screenshot: 'only-on-failure',
        trace: 'retain-on-failure',
    },
    // Serveur PHP intégré : le routeur force APP_ENV=test (base wassalni_test) ; voir scripts/serveur-test-router.php.
    webServer: {
        command: 'php -S 127.0.0.1:8000 -t public scripts/serveur-test-router.php',
        url: 'http://127.0.0.1:8000/health',
        reuseExistingServer: false,
        timeout: 60000,
    },
});
