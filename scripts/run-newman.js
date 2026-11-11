/**
 * Semaine 8 — Exécution reproductible de la collection Postman avec Newman.
 *
 * 1. Lit WASSALNI_TEST_PASSWORD dans .env.test.local (jamais écrit dans la collection ni l'environnement).
 * 2. Démarre le serveur PHP de test (routeur qui force APP_ENV=test → base wassalni_test).
 * 3. Recrée les données fictives (app:tests:prepare-week7-9 --env=test) et vérifie la garde de base.
 * 4. Lance Newman (rapports cli, json, junit) puis masque le mot de passe dans les rapports écrits.
 *
 * Usage : node scripts/run-newman.js [dossier_de_sortie]   (défaut : preuves/semaine8)
 */
const { spawn, execFileSync } = require('node:child_process');
const fs = require('node:fs');
const path = require('node:path');
const newman = require('newman');

const root = path.resolve(__dirname, '..');
const outDir = path.resolve(root, process.argv[2] || 'preuves/semaine8');
const baseUrl = 'http://127.0.0.1:8000';

function readSecret() {
    const file = path.join(root, '.env.test.local');
    const line = fs.existsSync(file) ? fs.readFileSync(file, 'utf8').split(/\r?\n/).find(l => l.startsWith('WASSALNI_TEST_PASSWORD=')) : null;
    const value = process.env.WASSALNI_TEST_PASSWORD || (line ? line.split('=').slice(1).join('=').trim() : '');
    if (!value) {
        throw new Error('WASSALNI_TEST_PASSWORD absent (.env.test.local)');
    }
    return value;
}

async function waitForHealth(timeoutMs = 30000) {
    const start = Date.now();
    while (Date.now() - start < timeoutMs) {
        try {
            const response = await fetch(`${baseUrl}/health`);
            if (response.ok) {
                return;
            }
        } catch {
            // serveur pas encore prêt
        }
        await new Promise(resolve => setTimeout(resolve, 500));
    }
    throw new Error('Serveur de test indisponible');
}

async function main() {
    const secret = readSecret();
    fs.mkdirSync(outDir, { recursive: true });
    const server = spawn('php', ['-S', '127.0.0.1:8000', '-t', 'public', 'scripts/serveur-test-router.php'], { cwd: root, stdio: 'ignore' });
    try {
        await waitForHealth();
        const fixture = JSON.parse(execFileSync('php', ['bin/console', 'app:tests:prepare-week7-9', '--env=test', '--no-ansi'], { cwd: root, encoding: 'utf8' }).replace(/^[^{]*/, ''));
        const probe = await fetch(`${baseUrl}/trajet/recherche?villeDepart=Tunis&destination=Sfax&dateDepart=2099-12-01`);
        if (fixture.database !== 'wassalni_test' || probe.headers.get('x-debug-token') || !(await probe.text()).includes(`data-trajet-id="${fixture.tripId}"`)) {
            throw new Error('Garde-fou : la base servie n\'est pas wassalni_test. Arrêt.');
        }
        console.log(`# Base ${fixture.database}, trajet de fixture ${fixture.tripId}, date ${new Date().toISOString()}`);

        const summary = await new Promise((resolve, reject) => {
            newman.run({
                collection: require(path.join(root, 'postman/Wassalni-Semaine-8-API.postman_collection.json')),
                environment: require(path.join(root, 'postman/Wassalni-Test.postman_environment.json')),
                envVar: [{ key: 'passengerPassword', value: secret }],
                reporters: ['cli', 'json', 'junit'],
                reporter: {
                    json: { export: path.join(outDir, 'S08_newman-rapport.json') },
                    junit: { export: path.join(outDir, 'S08_newman-junit.xml') },
                },
                color: 'off',
            }, (error, result) => (error ? reject(error) : resolve(result)));
        });

        // Rapport JSON : on retire les corps de réponse (pages HTML volumineuses) et on masque les cookies de session.
        const jsonReport = path.join(outDir, 'S08_newman-rapport.json');
        if (fs.existsSync(jsonReport)) {
            const scrub = node => {
                if (Array.isArray(node)) {
                    node.forEach(scrub);
                } else if (node && typeof node === 'object') {
                    delete node.stream;
                    if (typeof node.key === 'string' && /^(set-)?cookie$/i.test(node.key)) {
                        node.value = '***MASQUE***';
                    }
                    Object.values(node).forEach(scrub);
                }
            };
            const report = JSON.parse(fs.readFileSync(jsonReport, 'utf8'));
            scrub(report);
            fs.writeFileSync(jsonReport, JSON.stringify(report, null, 2));
        }

        // Masquage du secret dans tous les rapports produits.
        for (const file of ['S08_newman-rapport.json', 'S08_newman-junit.xml']) {
            const target = path.join(outDir, file);
            if (fs.existsSync(target)) {
                fs.writeFileSync(target, fs.readFileSync(target, 'utf8').split(secret).join('***MASQUE***'));
            }
        }
        const stats = summary.run.stats;
        console.log(`# Requêtes : ${stats.requests.total} (échecs ${stats.requests.failed}) ; assertions : ${stats.assertions.total} (échecs ${stats.assertions.failed})`);
        process.exitCode = stats.assertions.failed > 0 ? 1 : 0;
    } finally {
        server.kill();
    }
}

main().catch(error => {
    console.error(error.message);
    process.exitCode = 2;
});
