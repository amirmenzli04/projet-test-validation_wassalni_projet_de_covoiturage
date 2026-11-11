/**
 * Semaine 9 — Tests non fonctionnels exécutés dans Chrome :
 *  - accessibilité automatisée (axe-core 4.x, règles WCAG 2.0/2.1 niveaux A et AA) sur 4 pages clés
 *    + navigation au clavier sur la connexion ;
 *  - sécurité élémentaire : en-têtes HTTP de protection, attributs du cookie de session,
 *    page d'erreur sans fuite d'information technique.
 * Un audit automatique ne couvre qu'une partie des critères WCAG : aucun niveau de conformité n'est revendiqué.
 */
const { test } = require('@playwright/test');
const AxeBuilder = require('@axe-core/playwright').default;
const { ACCOUNTS, expect, login, shot, evidenceLog } = require('./helpers');

test.describe.configure({ mode: 'default' }); // tests indépendants : un échec n'interrompt pas les suivants
const WEEK = 'semaine9';
const a11y = evidenceLog(WEEK, 'S09_accessibilite-axe.json');
const security = evidenceLog(WEEK, 'S09_securite-elementaire.json');
test.afterAll(() => {
    a11y.save();
    security.save();
});

const PAGES = [
    { name: 'connexion', url: '/connexion', account: null },
    { name: 'recherche', url: '/trajet/recherche?villeDepart=Tunis&destination=Sfax&dateDepart=2099-12-01', account: ACCOUNTS.passagerA },
    { name: 'nouvelle-reclamation', url: '/reclamation/nouvelle', account: ACCOUNTS.passagerA },
    { name: 'mes-reservations', url: '/reservation/mes-reservations', account: ACCOUNTS.passagerA },
];

for (const target of PAGES) {
    test(`Accessibilité (axe, WCAG A/AA) — page ${target.name}`, async ({ page }) => {
        if (target.account) {
            await login(page, target.account);
        }
        await page.goto(target.url);
        const results = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa']).analyze();
        const byImpact = { critical: 0, serious: 0, moderate: 0, minor: 0 };
        const violations = results.violations.map(v => {
            byImpact[v.impact] = (byImpact[v.impact] || 0) + 1;
            return { regle: v.id, impact: v.impact, description: v.help, elements: v.nodes.length, exemple: v.nodes[0]?.target?.join(' ') };
        });
        const file = await shot(page, WEEK, `S09_A11Y_${target.name}.png`, { fullPage: false });
        a11y.add({ page: target.name, url: target.url.split('?')[0], regles_verifiees: results.passes.length + results.violations.length, conformes: results.passes.length, violations_par_impact: byImpact, violations, capture: file });
        // Constat consigné ; les violations critiques sont signalées comme échec (soft) sans arrêter la campagne.
        expect.soft(byImpact.critical, `Violations critiques sur ${target.name}`).toBe(0);
    });
}

test('Accessibilité — connexion entièrement au clavier (Tab, saisie, Entrée)', async ({ page }) => {
    await page.goto('/connexion');
    const activeName = () => page.evaluate(() => {
        const el = document.activeElement;
        return el?.getAttribute('name') || `${el?.tagName}${el?.textContent ? ':' + el.textContent.trim().slice(0, 20) : ''}`;
    });
    // L'utilisateur appuie sur Tab jusqu'au champ e-mail (10 appuis au plus).
    const focusOrder = [];
    for (let i = 0; i < 10 && (focusOrder.at(-1) !== '_username'); i++) {
        await page.keyboard.press('Tab');
        focusOrder.push(await activeName());
    }
    await page.keyboard.type(ACCOUNTS.passagerA);
    await page.keyboard.press('Tab');
    const afterEmail = await activeName();
    await page.keyboard.type(process.env.WASSALNI_TEST_PASSWORD);
    await Promise.all([page.waitForURL(url => !url.pathname.startsWith('/connexion'), { timeout: 15000 }), page.keyboard.press('Enter')]);
    a11y.add({ page: 'connexion', sonde: 'navigation clavier', appuis_tab_avant_email: focusOrder.length, ordre_du_focus: focusOrder, focus_apres_email: afterEmail, page_apres_entree: new URL(page.url()).pathname });
    expect(focusOrder.at(-1)).toBe('_username');
    expect(afterEmail).toBe('_password');
    expect(page.url()).toContain('/trajet/recherche');
});

test('Sécurité élémentaire — en-têtes de protection et cookie de session', async ({ page }) => {
    const headersOf = async (url) => {
        const response = await page.request.get(url, { maxRedirects: 0 });
        return response.headers();
    };
    const wanted = ['x-frame-options', 'content-security-policy', 'x-content-type-options', 'referrer-policy', 'strict-transport-security', 'permissions-policy'];
    const report = {};
    for (const url of ['/connexion', '/trajet/recherche', '/health']) {
        const headers = await headersOf(url);
        report[url] = Object.fromEntries(wanted.map(name => [name, headers[name] ?? 'ABSENT']));
        report[url]['x-powered-by'] = headers['x-powered-by'] ?? 'ABSENT';
    }
    await login(page, ACCOUNTS.passagerA);
    const cookies = (await page.context().cookies()).map(c => ({ nom: c.name, httpOnly: c.httpOnly, secure: c.secure, sameSite: c.sameSite }));
    security.add({ sonde: 'en-têtes HTTP de protection', resultats: report });
    security.add({ sonde: 'attributs des cookies après connexion (valeurs non enregistrées)', cookies });
    expect(cookies.length).toBeGreaterThan(0);
    for (const cookie of cookies) {
        expect.soft(cookie.httpOnly, `${cookie.nom} doit être HttpOnly`).toBe(true);
    }
});

test('Sécurité élémentaire — une erreur serveur ne divulgue ni trace ni chemin de fichier', async ({ page }) => {
    await login(page, ACCOUNTS.passagerA);
    const response = await page.goto('/passager/dashboard'); // erreur 500 connue (ANO-34)
    const text = await page.locator('body').innerText();
    const leaks = ['Stack Trace', 'vendor/', 'Twig\\Error', '.php', 'C:\\'].filter(token => text.includes(token));
    const file = await shot(page, WEEK, 'S09_SECU_page_erreur_500.png', { fullPage: false });
    security.add({ sonde: 'page d\'erreur en environnement non-debug', http: response.status(), fuites_detectees: leaks, capture: file });
    expect(response.status()).toBe(500);
    expect(leaks).toEqual([]);
});
