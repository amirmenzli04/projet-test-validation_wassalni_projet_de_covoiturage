/**
 * Semaine 5 — Sessions exploratoires OUTILLÉES (chartes EXPL-01 à EXPL-04).
 * Les sondes ci-dessous ont été choisies au fil de l'exploration ; elles consignent ce qui est observé
 * (preuves/semaine5/S05_exploration-journal.json) au lieu d'imposer un résultat. Seules les règles
 * déjà établies (CA-xx) sont vérifiées par expect(). Ce n'est pas une exploration manuelle humaine.
 */
const { test } = require('@playwright/test');
const { ACCOUNTS, expect, fixture, login, resetFixture, shot, sqlRow, evidenceLog } = require('./helpers');

test.beforeAll(() => resetFixture()); // isolation entre fichiers (ANO-T05)

test.describe.configure({ mode: 'serial' });
const WEEK = 'semaine5';
const journal = evidenceLog(WEEK, 'S05_exploration-journal.json');
test.afterAll(() => journal.save());

const reservationsOf = (email, tripId) => Number(sqlRow(`SELECT COUNT(*) n FROM reservation r JOIN \`user\` u ON u.id = r.passager_id WHERE u.email = '${email}' AND r.trajet_id = ${tripId} AND r.statut = 'CONFIRMEE'`).n);

test('EXPL-02 — accès directs par URL pour chaque rôle (statut HTTP observé)', async ({ browser }) => {
    const urls = ['/admin/reclamations', '/admin/utilisateurs', '/conducteur/demandes', '/vehicule/mes-vehicules', '/reservation/mes-reservations', '/reclamation/nouvelle', '/passager/dashboard'];
    const observed = {};
    for (const [role, email] of Object.entries({ anonyme: null, passagerA: ACCOUNTS.passagerA, conducteur: ACCOUNTS.conducteur, admin: ACCOUNTS.admin })) {
        const page = await (await browser.newContext()).newPage();
        if (email) {
            await login(page, email);
        }
        observed[role] = {};
        for (const url of urls) {
            const response = await page.goto(url);
            const finalPath = new URL(page.url()).pathname;
            observed[role][url] = finalPath === url ? response.status() : `${response.status()} → ${finalPath}`;
        }
    }
    journal.add({ charte: 'EXPL-02', sonde: 'matrice des accès directs', observe: observed });

    // Règles établies (CA-12) : anonyme redirigé vers /connexion ; passager et conducteur refusés sur l'administration.
    for (const url of urls) {
        expect(String(observed.anonyme[url])).toContain('/connexion');
    }
    expect(observed.passagerA['/admin/reclamations']).toBe(403);
    expect(observed.conducteur['/admin/utilisateurs']).toBe(403);
    expect(observed.passagerA['/conducteur/demandes']).toBe(403);
});

test('EXPL-04 — double clic sur « Confirmer la réservation » (voie JSON)', async ({ page }) => {
    const tripId = fixture().tripId;
    await login(page, ACCOUNTS.passagerB);
    await page.goto('/trajet/recherche?villeDepart=Tunis&destination=Sfax&dateDepart=2099-12-01');
    await page.locator(`.result-card[data-trajet-id="${tripId}"]`).getByRole('button', { name: /Réserver/ }).click();
    const statuses = [];
    page.on('response', response => {
        if (response.url().includes('/reservation/new-ajax')) {
            statuses.push(response.status());
        }
    });
    await page.locator('#confirmReservationBtn').dblclick();
    await page.waitForTimeout(2500);
    const created = reservationsOf(ACCOUNTS.passagerB, tripId);
    const file = await shot(page, WEEK, 'S05_EXPL04_double_clic_reservation.png', { fullPage: false });
    journal.add({ charte: 'EXPL-04', sonde: 'double clic sur la confirmation', reponses_http: statuses, reservations_confirmees: created, capture: file });
    expect(created).toBe(1); // CA-09 : une seule réservation confirmée
});

test('EXPL-04 — session expirée (cookies effacés) puis envoi du formulaire de réclamation', async ({ page, context }) => {
    await login(page, ACCOUNTS.passagerA);
    await page.goto('/reclamation/nouvelle');
    await page.locator('#reclamation_sujet').selectOption('Autre problème');
    await page.locator('#reclamation_message').fill('EXPL-04 envoi après expiration de session');
    await context.clearCookies();
    await page.locator('#reclamationForm button[type="submit"]').click();
    await page.waitForLoadState('networkidle');
    const stored = Number(sqlRow("SELECT COUNT(*) n FROM reclamation WHERE message = 'EXPL-04 envoi après expiration de session'").n);
    const file = await shot(page, WEEK, 'S05_EXPL04_session_expiree.png', { fullPage: false });
    journal.add({ charte: 'EXPL-04', sonde: 'envoi après expiration de session', page_finale: new URL(page.url()).pathname, reclamations_enregistrees: stored, capture: file });
    expect(stored).toBe(0);
    expect(page.url()).toContain('/connexion');
});

test('EXPL-01 — rafraîchissement après création d\'une réclamation (doublon ?)', async ({ page }) => {
    const message = 'EXPL-01 rafraîchissement après envoi du formulaire';
    await login(page, ACCOUNTS.passagerA);
    await page.goto('/reclamation/nouvelle');
    await page.locator('#reclamation_sujet').selectOption('Autre problème');
    await page.locator('#reclamation_message').fill(message);
    await page.locator('#reclamationForm button[type="submit"]').click();
    await page.waitForURL(/mes-reclamations/);
    await page.reload();
    await page.goBack();
    await page.waitForLoadState('networkidle');
    const stored = Number(sqlRow(`SELECT COUNT(*) n FROM reclamation WHERE message = '${message}'`).n);
    journal.add({ charte: 'EXPL-01', sonde: 'rafraîchissement puis retour arrière après envoi', reclamations_enregistrees: stored, page_apres_retour: new URL(page.url()).pathname });
    expect(stored).toBe(1); // le modèle POST-redirection-GET évite le doublon
});

test('EXPL-03 — fenêtre de réservation : la quantité ne dépasse pas les places disponibles', async ({ page }) => {
    const tripId = fixture().tripId;
    await login(page, ACCOUNTS.passagerA);
    await page.goto('/trajet/recherche?villeDepart=Tunis&destination=Sfax&dateDepart=2099-12-01');
    const card = page.locator(`.result-card[data-trajet-id="${tripId}"]`);
    const max = Number(await card.getAttribute('data-places-max'));
    await card.getByRole('button', { name: /Réserver/ }).click();
    for (let i = 0; i < max + 3; i++) {
        await page.locator('#incrementBtn').click({ force: true });
    }
    const selected = Number(await page.locator('#selectedPlaces').innerText());
    const total = Number(await page.locator('#totalPrice').innerText());
    const file = await shot(page.locator('#reservationModal .modal-content, #reservationModal').first(), WEEK, 'S05_EXPL03_quantite_plafonnee.png');
    journal.add({ charte: 'EXPL-03', sonde: `${max + 3} clics sur « + »`, places_disponibles: max, quantite_affichee: selected, total_affiche: total, capture: file });
    expect(selected).toBeLessThanOrEqual(max);
});
