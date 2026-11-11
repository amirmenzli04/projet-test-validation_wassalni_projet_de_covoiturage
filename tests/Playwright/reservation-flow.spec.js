const fs = require('node:fs');
const path = require('node:path');
const { test, expect } = require('@playwright/test');

const passengerEmail = 'semaine7-9.passager@example.test';
// Mot de passe du compte fictif : lu depuis l'environnement (défini dans .env.test.local, non versionné).
const passengerPassword = process.env.WASSALNI_TEST_PASSWORD;
if (!passengerPassword) {
    throw new Error('WASSALNI_TEST_PASSWORD doit être défini (voir scripts/ et .env.test.local).');
}
const fixtureDate = '2099-12-01';

test('passenger signs in, books seats and verifies persisted availability', async ({ page }) => {
    await page.goto('/connexion');
    await page.locator('input[name="_username"]').fill(passengerEmail);
    await page.locator('input[name="_password"]').fill(passengerPassword);
    await page.getByRole('button', { name: 'Se connecter' }).click();
    await expect(page).toHaveURL(/\/trajet\/recherche$/);

    await page.locator('#villeDepart').selectOption('Tunis');
    await Promise.all([
        page.waitForNavigation(),
        page.locator('#destination').selectOption('Sfax'),
    ]);
    await expect(page).toHaveURL(/dateDepart=2099-12-01/);

    const result = page.locator('.result-card');
    await expect(result).toHaveCount(1);
    await expect(result).toContainText('Tunis');
    await expect(result).toContainText('Sfax');
    await expect(result).toContainText('4 places');

    const evidenceDirectory = path.resolve('preuves/semaine9');
    fs.mkdirSync(evidenceDirectory, { recursive: true });

    await result.getByRole('button', { name: /Réserver/ }).click();
    await expect(page.locator('#reservationModal')).toHaveClass(/active/);
    await page.locator('#incrementBtn').click();
    await expect(page.locator('#selectedPlaces')).toHaveText('2');
    await expect(page.locator('#totalPrice')).toHaveText('50');

    const responsePromise = page.waitForResponse(response =>
        response.url().includes('/reservation/new-ajax') && response.request().method() === 'POST'
    );
    await page.locator('#confirmReservationBtn').click();
    const response = await responsePromise;
    expect(response.status()).toBe(200);
    expect(await response.json()).toMatchObject({ success: true, prixTotal: 50 });
    await expect(page.locator('#toast-container')).toContainText('Réservation confirmée');
    await page.screenshot({
        path: path.join(evidenceDirectory, 'parcours-reservation.png'),
        fullPage: true,
    });

    await expect(result).toContainText('2 places');
});

test('search endpoint meets the local response-time smoke threshold', async ({ request }) => {
    const url = `/trajet/recherche?villeDepart=Tunis&destination=Sfax&dateDepart=${fixtureDate}`;

    await (await request.get(url)).dispose();
    const samplesMs = [];
    for (let sample = 0; sample < 5; sample++) {
        const startedAt = performance.now();
        const response = await request.get(url);
        samplesMs.push(Number((performance.now() - startedAt).toFixed(2)));
        expect(response.status()).toBe(200);
        await response.dispose();
    }

    const sortedSamples = [...samplesMs].sort((a, b) => a - b);
    const medianMs = sortedSamples[Math.floor(sortedSamples.length / 2)];
    const maximumMs = Math.max(...samplesMs);
    const evidenceDirectory = path.resolve('preuves/semaine9');
    fs.mkdirSync(evidenceDirectory, { recursive: true });
    fs.writeFileSync(
        path.join(evidenceDirectory, 'performance.json'),
        JSON.stringify({
            scenario: 'Recherche de trajets Tunis-Sfax à date fixe',
            samplesMs,
            medianMs,
            maximumMs,
            maximumAllowedMs: 2000,
            result: maximumMs <= 2000 ? 'PASS' : 'FAIL',
        }, null, 2) + '\n'
    );

    expect(maximumMs).toBeLessThanOrEqual(2000);
});
