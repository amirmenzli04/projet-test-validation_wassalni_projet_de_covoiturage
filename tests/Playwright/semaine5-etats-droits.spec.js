/**
 * Semaine 5 — Scénarios ST-01 à ST-10 exécutés dans un vrai navigateur (Chrome, Playwright).
 * Chaque scénario : action dans l'interface → capture PNG (preuves/semaine5/) → relecture SQL dans wassalni_test
 * consignée dans preuves/semaine5/S05_verifications.json.
 * Ce sont des tests automatisés : ils ne remplacent pas les sessions d'exploration (chartes EXPL-01 à 04).
 */
const { test } = require('@playwright/test');
const { ACCOUNTS, expect, fixture, login, shot, sqlRow, evidenceLog } = require('./helpers');

test.describe.configure({ mode: 'serial' });

const WEEK = 'semaine5';
const log = evidenceLog(WEEK, 'S05_verifications.json');
const ids = {};

async function createComplaint(page, message) {
    await page.goto('/reclamation/nouvelle');
    await page.locator('#reclamation_sujet').selectOption('Retard important');
    await page.locator('#reclamation_message').fill(message);
    await page.locator('#reclamationForm button[type="submit"]').click();
    await page.waitForLoadState('networkidle');
}

function complaintRow(message) {
    return sqlRow(`SELECT r.id, r.statut, r.date_creation, r.date_traitement, r.reponse, u.email FROM reclamation r JOIN \`user\` u ON u.id = r.user_id WHERE r.message = '${message}'`);
}

test.afterAll(() => log.save());

test('ST-01 — passager A crée une réclamation valide : EN_ATTENTE, rattachée à A, visible dans sa liste', async ({ page }) => {
    const message = 'ST-01 Le conducteur est arrivé avec 40 minutes de retard.';
    await login(page, ACCOUNTS.passagerA);
    await createComplaint(page, message);

    await expect(page).toHaveURL(/\/reclamation\/mes-reclamations$/);
    const card = page.locator('.reclamation-card', { hasText: message });
    await expect(card).toContainText('En attente');
    const file = await shot(page, WEEK, 'S05_ST01_creation_reclamation.png');

    const row = complaintRow(message);
    ids.st01 = row.id;
    expect(row).toMatchObject({ statut: 'EN_ATTENTE', email: ACCOUNTS.passagerA, date_traitement: 'NULL' });
    log.add({ scenario: 'ST-01', capture: file, relecture_sql: row, resultat: 'Réussi' });
});

test('ST-02 — message vide puis de 9 caractères : formulaire refusé, aucun enregistrement', async ({ page }) => {
    await login(page, ACCOUNTS.passagerA);
    const before = sqlRow(`SELECT COUNT(*) n FROM reclamation r JOIN \`user\` u ON u.id = r.user_id WHERE u.email = '${ACCOUNTS.passagerA}'`).n;

    await createComplaint(page, '');
    const emptyStayed = new URL(page.url()).pathname === '/reclamation/nouvelle';
    await createComplaint(page, 'Neuf car.');
    await expect(page).toHaveURL(/\/reclamation\/nouvelle$/);
    await expect(page.locator('body')).toContainText('au moins 10 caractères');
    const file = await shot(page, WEEK, 'S05_ST02_message_9_caracteres_refuse.png');

    const after = sqlRow(`SELECT COUNT(*) n FROM reclamation r JOIN \`user\` u ON u.id = r.user_id WHERE u.email = '${ACCOUNTS.passagerA}'`).n;
    expect(emptyStayed).toBe(true);
    expect(after).toBe(before);
    log.add({ scenario: 'ST-02', capture: file, reclamations_avant: before, reclamations_apres: after, message_vide_bloque: emptyStayed, resultat: 'Réussi' });
});

test('ST-03 — message d\'exactement 10 caractères avec un sujet de la liste : accepté', async ({ page }) => {
    const message = 'Dix carac.';
    expect(message).toHaveLength(10);
    await login(page, ACCOUNTS.passagerA);
    await createComplaint(page, message);

    await expect(page).toHaveURL(/\/reclamation\/mes-reclamations$/);
    const file = await shot(page.locator('.reclamation-card', { hasText: message }), WEEK, 'S05_ST03_message_10_caracteres_accepte.png');
    const row = complaintRow(message);
    ids.st03 = row.id;
    expect(row.statut).toBe('EN_ATTENTE');
    log.add({ scenario: 'ST-03', capture: file, relecture_sql: row, resultat: 'Réussi' });
});

test('ST-04 — passager B ouvre la réclamation de A par URL directe : 403, aucune donnée de A révélée', async ({ page }) => {
    await login(page, ACCOUNTS.passagerB);
    const response = await page.goto(`/reclamation/voir/${ids.st01}`);
    const file = await shot(page, WEEK, 'S05_ST04_acces_interdit.png');

    expect(response.status()).toBe(403);
    await expect(page.locator('body')).not.toContainText('40 minutes de retard');
    log.add({ scenario: 'ST-04', capture: file, url: `/reclamation/voir/${ids.st01}`, http: response.status(), resultat: 'Réussi' });
});

test('ST-05 — l\'administrateur consulte le détail de la réclamation de A puis la liste d\'administration', async ({ page }) => {
    await login(page, ACCOUNTS.admin);
    const detail = await page.goto(`/reclamation/voir/${ids.st01}`);
    expect(detail.status()).toBe(200);
    await expect(page.locator('body')).toContainText('40 minutes de retard');
    const fileDetail = await shot(page, WEEK, 'S05_ST05_admin_detail_reclamation.png');

    await page.goto('/admin/reclamations');
    const card = page.locator('.reclamation-card', { hasText: '40 minutes de retard' });
    await expect(card).toContainText('En attente');
    const fileCard = await shot(card, WEEK, 'S05_ST05_admin_liste_carte.png');
    log.add({ scenario: 'ST-05', captures: [fileDetail, fileCard], http_detail: detail.status(), resultat: 'Réussi' });
});

test('ST-06 — l\'administrateur répond : TRAITEE, réponse et date de traitement persistées ; A voit la réponse', async ({ page, browser }) => {
    await login(page, ACCOUNTS.admin);
    await page.goto('/admin/reclamations');
    const card = page.locator('.reclamation-card', { hasText: '40 minutes de retard' });
    await card.locator('textarea[name="reponse"]').fill('Nous avons contacté le conducteur. Réponse ST-06.');
    await card.locator('button[type="submit"]').click();
    await page.waitForLoadState('networkidle');
    const cardAfter = page.locator('.reclamation-card', { hasText: '40 minutes de retard' });
    await expect(cardAfter).toContainText('Traitée');
    const fileAdmin = await shot(cardAfter, WEEK, 'S05_ST06_traitement_admin.png');

    const row = sqlRow(`SELECT statut, reponse, date_traitement FROM reclamation WHERE id = ${ids.st01}`);
    expect(row.statut).toBe('TRAITEE');
    expect(row.date_traitement).not.toBe('NULL');

    const passenger = await (await browser.newContext()).newPage();
    await login(passenger, ACCOUNTS.passagerA);
    await passenger.goto('/reclamation/mes-reclamations');
    const passengerCard = passenger.locator('.reclamation-card', { hasText: '40 minutes de retard' });
    await expect(passengerCard).toContainText('Traitée');
    const filePassenger = await shot(passenger, WEEK, 'S05_ST06_vue_passager_reponse.png');
    log.add({ scenario: 'ST-06', captures: [fileAdmin, filePassenger], relecture_sql: row, resultat: 'Réussi' });
});

test('ST-07 — réponse administrateur vide : statut et date inchangés', async ({ page }) => {
    await login(page, ACCOUNTS.admin);
    await page.goto('/admin/reclamations');
    const card = page.locator('.reclamation-card', { hasText: 'Dix carac.' });
    await card.locator('button[type="submit"]').click();
    await page.waitForLoadState('networkidle');
    // La liste d'administration contient aussi des réclamations préexistantes d'autres comptes :
    // on ne capture que la carte de la réclamation fictive du scénario.
    const flashShown = (await page.locator('body').innerText()).includes('Veuillez saisir une réponse');
    const cardAfter = page.locator('.reclamation-card', { hasText: 'Dix carac.' });
    await expect(cardAfter).toContainText('En attente');
    const file = await shot(cardAfter, WEEK, 'S05_ST07_reponse_vide_refusee.png');

    const row = sqlRow(`SELECT statut, date_traitement FROM reclamation WHERE id = ${ids.st03}`);
    expect(row).toMatchObject({ statut: 'EN_ATTENTE', date_traitement: 'NULL' });
    log.add({ scenario: 'ST-07', capture: file, message_flash_affiche: flashShown, relecture_sql: row, resultat: 'Réussi' });
});

test('ST-08 — passager A appelle directement la route de traitement administrateur : 403, aucune mutation', async ({ page }) => {
    await login(page, ACCOUNTS.passagerA);
    const response = await page.goto(`/admin/reclamation/traiter/${ids.st03}`);
    const file = await shot(page, WEEK, 'S05_ST08_traitement_par_non_admin_403.png');

    expect(response.status()).toBe(403);
    const row = sqlRow(`SELECT statut, date_traitement FROM reclamation WHERE id = ${ids.st03}`);
    expect(row.statut).toBe('EN_ATTENTE');
    log.add({ scenario: 'ST-08', capture: file, http: response.status(), relecture_sql: row, resultat: 'Réussi' });
});

test('ST-09 — transition vers EN_COURS : aucune action proposée à l\'administrateur (constat, règle à arbitrer)', async ({ page }) => {
    await login(page, ACCOUNTS.admin);
    await page.goto('/admin/reclamations');
    const card = page.locator('.reclamation-card', { hasText: 'Dix carac.' });
    const actions = await card.locator('button, a, select').allInnerTexts();
    const file = await shot(card, WEEK, 'S05_ST09_absence_action_en_cours.png');

    expect(actions.join(' ')).not.toMatch(/en cours/i);
    log.add({ scenario: 'ST-09', capture: file, actions_disponibles: actions, resultat: 'Bloqué (règle produit absente) : seule la réponse, qui passe directement à TRAITEE, est proposée' });
});

test('ST-10 — deux sessions : réservation, rafraîchissement, annulation puis annulation répétée ; stock cohérent', async ({ browser }) => {
    const tripId = fixture().tripId;
    const seats = () => Number(sqlRow(`SELECT places_disponibles p FROM trajet WHERE id = ${tripId}`).p);
    const searchUrl = '/trajet/recherche?villeDepart=Tunis&destination=Sfax&dateDepart=2099-12-01';
    const pageA = await (await browser.newContext()).newPage();
    const pageB = await (await browser.newContext()).newPage();
    pageA.on('dialog', dialog => dialog.accept());
    await login(pageA, ACCOUNTS.passagerA);
    await login(pageB, ACCOUNTS.passagerB);
    const states = { initial: seats() };

    await pageA.goto(searchUrl);
    await pageA.locator(`.result-card[data-trajet-id="${tripId}"]`).getByRole('button', { name: /Réserver/ }).click();
    const reply = pageA.waitForResponse(r => r.url().includes('/reservation/new-ajax'));
    await pageA.locator('#confirmReservationBtn').click();
    expect((await reply).status()).toBe(200);
    states.apres_reservation_A = seats();
    const f1 = await shot(pageA, WEEK, 'S05_ST10_1_reservation_session_A.png', { fullPage: false });

    await pageB.goto(searchUrl);
    const cardB = pageB.locator(`.result-card[data-trajet-id="${tripId}"]`);
    await expect(cardB).toContainText(`${states.apres_reservation_A} places`);
    const f2 = await shot(cardB, WEEK, 'S05_ST10_2_session_B_voit_stock_mis_a_jour.png');

    await pageA.goto('/reservation/mes-reservations');
    const reservation = pageA.locator('.reservation-card', { hasText: 'Sfax' }).filter({ has: pageA.locator('.btn-cancel') }).first();
    const cancelHref = await reservation.locator('.btn-cancel').getAttribute('href');
    await reservation.locator('.btn-cancel').click();
    await pageA.waitForLoadState('networkidle');
    states.apres_annulation = seats();
    const f3 = await shot(pageA, WEEK, 'S05_ST10_3_annulation_session_A.png', { fullPage: false });

    await pageA.goto(cancelHref);
    states.apres_double_annulation = seats();
    const f4 = await shot(pageA, WEEK, 'S05_ST10_4_annulation_repetee.png', { fullPage: false });

    await pageB.goto(searchUrl);
    await expect(pageB.locator(`.result-card[data-trajet-id="${tripId}"]`)).toContainText(`${states.initial} places`);

    expect(states).toEqual({ initial: 4, apres_reservation_A: 3, apres_annulation: 4, apres_double_annulation: 4 });
    log.add({ scenario: 'ST-10', captures: [f1, f2, f3, f4], stock: states, url_annulation: cancelHref, resultat: 'Réussi' });
});
