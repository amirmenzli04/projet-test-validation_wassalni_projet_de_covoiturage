/**
 * Produit des captures PNG des SORTIES D'EXÉCUTION RÉELLES archivées dans preuves/ (rendu dans Chrome).
 * Chaque image affiche en en-tête le fichier source et sa date : ce ne sont ni des maquettes ni du code.
 * Usage : node scripts/captures-rapports.js
 */
const fs = require('node:fs');
const path = require('node:path');
const { chromium } = require('@playwright/test');

const root = path.resolve(__dirname, '..');
const chrome = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const escape = s => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

const captures = [
    { source: 'preuves/semaine6/campagne-finale/S06_unitaire-couverture.txt', cible: 'preuves/semaine6/campagne-finale/S06_TESTS_UNITAIRES.png', titre: 'Semaine 6 — suite unitaire PHPUnit (TestDox + couverture texte)' },
    { source: 'preuves/semaine7/campagne-finale/S07_integration.txt', cible: 'preuves/semaine7/campagne-finale/S07_TESTS_INTEGRATION.png', titre: 'Semaine 7 — tests d\'intégration Doctrine/MariaDB' },
    { source: 'preuves/semaine8/S08_newman-execution.txt', cible: 'preuves/semaine8/S08_TESTS_API.png', titre: 'Semaine 8 — exécution Newman (synthèse)', dernieresLignes: 45 },
    { source: 'preuves/semaine10/campagne-finale/00-synthese.txt', cible: 'preuves/semaine10/S10_BILAN_FINAL.png', titre: 'Semaine 10 — synthèse de la campagne de régression finale' },
    { source: 'preuves/semaine10/campagne-finale/03-playwright.txt', cible: 'preuves/semaine10/S10_PLAYWRIGHT_FINAL.png', titre: 'Semaine 10 — Playwright, campagne finale (fin de sortie)', dernieresLignes: 40 },
];

(async () => {
    const browser = await chromium.launch({ executablePath: fs.existsSync(chrome) ? chrome : undefined });
    const page = await browser.newPage({ viewport: { width: 1280, height: 720 } });
    for (const c of captures) {
        const file = path.join(root, c.source);
        let lines = fs.readFileSync(file, 'utf8').replace(/^\uFEFF/, '').split(/\r?\n/);
        if (c.dernieresLignes) {
            lines = lines.slice(-c.dernieresLignes);
        }
        const date = fs.statSync(file).mtime.toISOString().replace('T', ' ').slice(0, 19);
        await page.setContent(`<html><body style="margin:0;font-family:Segoe UI,Arial;background:#0f172a;color:#e2e8f0">
<div style="padding:12px 18px;background:#1e293b;border-bottom:2px solid #6366f1"><b>${escape(c.titre)}</b><br>
<span style="font-size:13px;color:#94a3b8">Capture de la sortie réelle : ${escape(c.source)} (fichier du ${date} UTC)</span></div>
<pre style="margin:0;padding:14px 18px;font:13px/1.45 Consolas,monospace;white-space:pre-wrap">${escape(lines.join('\n'))}</pre></body></html>`);
        await page.screenshot({ path: path.join(root, c.cible), fullPage: true });
        console.log('capture', c.cible);
    }
    await page.goto('file:///' + path.join(root, 'preuves/semaine6/campagne-finale/couverture-html/index.html').replace(/\\/g, '/'));
    await page.screenshot({ path: path.join(root, 'preuves/semaine6/campagne-finale/S06_COUVERTURE.png'), fullPage: true });
    console.log('capture preuves/semaine6/campagne-finale/S06_COUVERTURE.png');
    await browser.close();
})();
