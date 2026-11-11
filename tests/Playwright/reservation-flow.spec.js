/**
 * Semaine 9 — Parcours critique de bout en bout dans Chrome (CT-29) :
 * connexion → recherche (saisie réelle des filtres) → fenêtre de réservation → confirmation →
 * « Mes réservations » → vérification de la persistance en base (wassalni_test).
 * Version initiale (non exécutable) corrigée le 11/11/2026 : la recherche se soumet automatiquement
 * quand les deux villes sont choisies, la date doit donc être saisie avant.
 */
const { test } = require('@playwright/test');
const { ACCOUNTS, expect, fixture, login, resetFixture, shot, sqlRow, evidenceLog } = require('./helpers');

test.beforeAll(() => resetFixture()); // isolation entre fichiers (ANO-T05)

const WEEK = 'semaine9';
const log = evidenceLog(WEEK, 'S09_parcours-verifications.json');
test.afterAll(() => log.save());

test('CT-29 — un passager se connecte, recherche, réserve 2 places et retrouve sa réservation', async ({ page }) => {
    const tripId = fixture().tripId;
    const seats = () => Number(sqlRow(`SELECT places_disponibles p FROM trajet WHERE id = ${tripId}`).p);
    const initialSeats = seats();

    // 1. Connexion par le formulaire
    await login(page, ACCOUNTS.passagerA);
    await expect(page).toHaveURL(/\/trajet\/recherche/);

    // 2. Recherche avec les vrais contrôles de l'interface
    await page.locator('#dateDepart').fill('2099-12-01');
    await page.locator('#villeDepart').selectOption('Tunis');
    await Promise.all([page.waitForURL(/destination=Sfax/), page.locator('#destination').selectOption('Sfax')]);
    const card = page.locator(`.result-card[data-trajet-id="${tripId}"]`);
    await expect(card).toContainText('Tunis');
    await expect(card).toContainText('Sfax');
    await expect(card).toContainText(`${initialSeats} places`);
    const f1 = await shot(page, WEEK, 'S09_PARCOURS_1_recherche.png', { fullPage: false });

    // 3. Fenêtre de réservation : 2 places, total recalculé
    await card.getByRole('button', { name: /Réserver/ }).click();
    await expect(page.locator('#reservationModal')).toHaveClass(/active/);
    await page.locator('#incrementBtn').click();
    await expect(page.locator('#selectedPlaces')).toHaveText('2');
    await expect(page.locator('#totalPrice')).toHaveText('50');
    const f2 = await shot(page, WEEK, 'S09_PARCOURS_2_fenetre_reservation.png', { fullPage: false });

    // 4. Confirmation : réponse JSON et message à l'écran
    const reply = page.waitForResponse(r => r.url().includes('/reservation/new-ajax') && r.request().method() === 'POST');
    await page.locator('#confirmReservationBtn').click();
    const response = await reply;
    const body = await response.json();
    expect(response.status()).toBe(200);
    expect(body).toMatchObject({ success: true, prixTotal: 50 });
    await expect(page.locator('#toast-container')).toContainText('Réservation confirmée');
    const f3 = await shot(page, WEEK, 'S09_PARCOURS_3_confirmation.png', { fullPage: false });

    // 5. « Mes réservations »
    await page.goto('/reservation/mes-reservations');
    const reservation = page.locator('.reservation-card', { hasText: 'Sfax' }).first();
    await expect(reservation).toContainText('2 place(s) réservée(s)');
    await expect(reservation).toContainText('50');
    const f4 = await shot(page, WEEK, 'S09_PARCOURS_4_mes_reservations.png', { fullPage: false });

    // 6. Persistance vérifiée en base
    const row = sqlRow(`SELECT r.id, r.statut, r.nb_places, r.prix_total, t.places_disponibles FROM reservation r JOIN trajet t ON t.id = r.trajet_id WHERE r.id = ${body.reservationId}`);
    expect(row).toMatchObject({ statut: 'CONFIRMEE', nb_places: '2', prix_total: '50', places_disponibles: String(initialSeats - 2) });
    log.add({ scenario: 'CT-29 parcours critique', captures: [f1, f2, f3, f4], reponse_json: body, relecture_sql: row, stock_initial: initialSeats, resultat: 'Réussi' });
});
