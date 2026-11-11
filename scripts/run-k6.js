/**
 * Semaine 9 — Lance le test de charge k6 contre le serveur de test (APP_ENV=test forcé → wassalni_test).
 * k6 est un binaire local (tools/k6, non versionné). Usage : node scripts/run-k6.js [dossier_de_sortie]
 */
const { spawn, spawnSync, execFileSync } = require('node:child_process');
const fs = require('node:fs');
const path = require('node:path');

const root = path.resolve(__dirname, '..');
const outDir = path.resolve(root, process.argv[2] || 'preuves/semaine9');
const k6 = path.join(root, 'tools/k6/k6-v2.3.0-windows-amd64/k6.exe');
const baseUrl = 'http://127.0.0.1:8000';

async function waitForHealth() {
    for (let i = 0; i < 60; i++) {
        try {
            if ((await fetch(`${baseUrl}/health`)).ok) {
                return;
            }
        } catch {
            // pas encore prêt
        }
        await new Promise(resolve => setTimeout(resolve, 500));
    }
    throw new Error('Serveur de test indisponible');
}

async function main() {
    if (!fs.existsSync(k6)) {
        throw new Error(`k6 introuvable : ${k6}`);
    }
    fs.mkdirSync(outDir, { recursive: true });
    const server = spawn('php', ['-S', '127.0.0.1:8000', '-t', 'public', 'scripts/serveur-test-router.php'], { cwd: root, stdio: 'ignore' });
    try {
        await waitForHealth();
        const fixture = JSON.parse(execFileSync('php', ['bin/console', 'app:tests:prepare-week7-9', '--env=test', '--no-ansi'], { cwd: root, encoding: 'utf8' }).replace(/^[^{]*/, ''));
        const probe = await fetch(`${baseUrl}/trajet/recherche?villeDepart=Tunis&destination=Sfax&dateDepart=2099-12-01`);
        if (fixture.database !== 'wassalni_test' || probe.headers.get('x-debug-token') || !(await probe.text()).includes(`data-trajet-id="${fixture.tripId}"`)) {
            throw new Error('Garde-fou : la base servie n\'est pas wassalni_test. Arrêt.');
        }
        console.log(`# ${new Date().toISOString()} | base ${fixture.database} | trajet ${fixture.tripId} | ${execFileSync(k6, ['version'], { encoding: 'utf8' }).trim()}`);
        const result = spawnSync(k6, ['run', '--no-color', '--quiet', 'tests/k6/recherche-charge.js',
            '-e', `TRIP_ID=${fixture.tripId}`, '-e', `SUMMARY_JSON=${path.join(outDir, 'S09_k6-resume.json')}`], { cwd: root, encoding: 'utf8' });
        process.stdout.write(result.stdout);
        process.stderr.write(result.stderr);
        process.exitCode = result.status;
    } finally {
        server.kill();
    }
}

main().catch(error => {
    console.error(error.message);
    process.exitCode = 2;
});
