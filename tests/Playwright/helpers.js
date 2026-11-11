const { execFileSync } = require('node:child_process');
const fs = require('node:fs');
const path = require('node:path');
const { expect } = require('@playwright/test');

const ROOT = path.resolve(__dirname, '..', '..');

/** Comptes fictifs créés par app:tests:prepare-week7-9 (nom « Tests W7-9 », domaine example.test). */
const ACCOUNTS = {
    passagerA: 'semaine7-9.passager@example.test',
    passagerB: 'semaine7-9.passager2@example.test',
    conducteur: 'semaine7-9.conducteur@example.test',
    admin: 'semaine7-9.admin@example.test',
};

function password() {
    const value = process.env.WASSALNI_TEST_PASSWORD;
    if (!value) {
        throw new Error('WASSALNI_TEST_PASSWORD absent : le définir dans .env.test.local');
    }
    return value;
}

function fixture() {
    return JSON.parse(fs.readFileSync(path.join(ROOT, 'test-results', 'fixture.json'), 'utf8'));
}

/** Connexion par le vrai formulaire ; le mot de passe n'apparaît jamais dans une capture (champ masqué). */
async function login(page, email) {
    await page.goto('/connexion');
    await page.locator('input[name="_username"]').fill(email);
    await page.locator('input[name="_password"]').fill(password());
    await Promise.all([page.waitForURL(url => !url.pathname.startsWith('/connexion')), page.getByRole('button', { name: 'Se connecter' }).click()]);
}

/** Capture PNG enregistrée directement dans preuves/semaineN/ (pleine page sauf indication contraire). */
async function shot(target, week, name, options = {}) {
    const directory = path.join(ROOT, 'preuves', week);
    fs.mkdirSync(directory, { recursive: true });
    const file = path.join(directory, name);
    await target.screenshot({ path: file, ...(target.goto ? { fullPage: true } : {}), ...options });
    return path.relative(ROOT, file);
}

/** Lecture SQL dans wassalni_test (la commande Symfony est forcée en --env=test). Retourne la première ligne. */
function sqlRow(query) {
    const output = execFileSync('php', ['bin/console', 'dbal:run-sql', query, '--env=test', '--no-ansi', '--force-fetch'], { cwd: ROOT, encoding: 'utf8' });
    const lines = output.split(/\r?\n/);
    const ruler = lines.find(line => /^\s*-+(\s+-+)*\s*$/.test(line));
    const rows = lines.filter(line => line.trim() !== '' && line !== ruler);
    if (!ruler || rows.length < 2) {
        return null;
    }
    // Colonnes délimitées par les groupes de tirets ; une cellule vide correspond à NULL.
    const spans = [...ruler.matchAll(/-+/g)].map(match => [match.index, match.index + match[0].length]);
    const cells = line => spans.map(([start, end]) => line.slice(start, end).trim());
    const headers = cells(rows[0]);
    const values = cells(rows[1]).map(value => (value === '' ? 'NULL' : value));
    return Object.fromEntries(headers.map((header, index) => [header, values[index]]));
}

/** Journal de preuve JSON (une entrée par vérification) écrit à côté des captures. */
function evidenceLog(week, file) {
    const entries = [];
    return {
        add(entry) {
            entries.push({ horodatage: new Date().toISOString(), ...entry });
        },
        save() {
            const directory = path.join(ROOT, 'preuves', week);
            fs.mkdirSync(directory, { recursive: true });
            fs.writeFileSync(path.join(directory, file), JSON.stringify(entries, null, 2) + '\n');
        },
    };
}

module.exports = { ACCOUNTS, ROOT, expect, fixture, login, password, shot, sqlRow, evidenceLog };
