# Preuves — Semaine 5 (transitions d'état, droits, exploration)

- **Date :** 11 novembre 2026, 21 h 57 à 22 h 05 (UTC+1). Les horodatages affichés par l'application sont en UTC (ANO-32).
- **Environnement :** Chrome local piloté par Playwright 1.59.1 ; serveur `php -S` avec `scripts/serveur-test-router.php`, qui force `APP_ENV=test` et donc la base `wassalni_test`. Un garde-fou (`tests/Playwright/global-setup.js`) interrompt l'exécution si la base servie n'est pas `wassalni_test`.
- **Comptes :** comptes fictifs `semaine7-9.*@example.test` (passager A, passager B, conducteur, administrateur), créés par `php bin/console app:tests:prepare-week7-9 --env=test`. Le mot de passe vient de `.env.test.local` et n'apparaît dans aucune capture.
- **Anonymisation :** les captures de la liste d'administration se limitent à la carte de la réclamation fictive, car la liste contient des réclamations préexistantes d'autres comptes.

## Fichiers

| Fichier | Type | Contenu |
|---|---|---|
| `S05_ST01_*.png` … `S05_ST10_4_*.png` | Résultat | Captures des scénarios ST-01 à ST-10 |
| `S05_verifications.json` | Résultat (base) | Relecture SQL de chaque scénario (statut, dates, stock) |
| `S05_playwright-execution.txt` / `S05_playwright-rapport.json` | Exécution | 10 scénarios : 10 attendus, 0 inattendu (ST-09 consigné « bloqué » dans le journal) |
| `S05_isolation-base.txt` | Exécution | Comptages de `wassalni_test` avant et après |
| `S05_transitions-phpunit.txt` / `S05_transitions-junit.xml` | Exécution | 6 tests de transitions (BrowserKit) : 5 réussis, 1 échec = ANO-33 |
| `S05_exploration-execution.txt` / `S05_exploration-journal.json` | Exécution et observations | Sessions exploratoires outillées EXPL-01 à 04 |
| `S05_EXPL0x_*.png` | Résultat | Captures des sondes exploratoires |
| `S05_fuseau-horaire.txt` | Exécution | Fuseau de PHP (UTC) comparé à l'heure locale (UTC+1) : ANO-32 |

## Reproduire

```powershell
npx playwright test tests/Playwright/semaine5-etats-droits.spec.js
npx playwright test tests/Playwright/semaine5-exploration.spec.js
vendor\bin\phpunit --testdox --exclude-group aucun tests\Functional\Catalogue\ReclamationTransitionsTest.php
```
