# Registre des preuves — WASSALNI

- **Mise à jour :** 11 novembre 2026 (fin de P0)
- **Racine des preuves :** `preuves/semaineN/`

## Types de preuve

| Type | Définition |
|---|---|
| **Exécution** | Sortie brute ou rapport d'un outil (PHPUnit, Newman, Playwright) produit lors de l'exécution |
| **Résultat** | Capture ou relecture (HTTP, SQL) montrant l'état observé après l'action |
| **Illustrative** | Capture qui montre l'application sans prouver à elle seule un résultat de test |
| **Statique** | Constat de lecture de code ou de configuration, sans exécution |

## Statuts

- **Réussi :** le résultat observé est conforme à l'attendu.
- **Échoué :** le résultat observé n'est pas conforme ; une anomalie est liée.
- **Bloqué :** l'exécution est impossible (règle métier ou environnement manquant).
- **Non exécuté :** l'activité est prévue mais pas encore faite.

## Registre

| ID | Sem. | Scénario / cas | Description | Date | Environnement | Fichier | Type | Résultat | Statut |
|---|---|---|---|---|---|---|---|---|---|
| PREUVE-S01-001 | 1 | Exploration initiale | 12 captures d'essais manuels sur l'application en développement (tableaux de bord, formulaires, routes) | 04/11/2026 | WAMP, base de développement | `preuves/semaine1/essai*.png` | Illustrative | Parcours observés ; contenu à légender en P5 | Réussi (illustration) |
| PREUVE-S01-002 | 1 | OBS-C / TC-45 | `GET /get-last-user-email` sans session renvoie `{"email": ...}` | 04/11/2026 | WAMP, base de développement | `preuves/semaine1/essai10.png` | Résultat | Fuite d'e-mail visible ; à reproduire sur `wassalni_test` en P1 | À confirmer |
| PREUVE-S02-001 | 2 | Environnement | Version et nom de la base de test | 07/11/2026 | MariaDB 10.4.32 | `preuves/semaine2/serveur-test.txt` | Exécution | `wassalni_test`, 10.4.32-MariaDB | Réussi |
| PREUVE-S02-002 | 2 | Environnement | Moteur des 8 tables de test | 07/11/2026 | MariaDB 10.4.32 | `preuves/semaine2/moteurs-wassalni_test.txt` | Exécution | 8 tables InnoDB | Réussi |
| PREUVE-S02-003 | 2 | Suite initiale | PHPUnit, 2 exécutions | 07/11/2026 | PHP 8.4.27 / PHPUnit 12.5.23 | `preuves/semaine2/phpunit-run1.txt`, `phpunit-run2.txt` | Exécution | 8 tests, 15 assertions, OK | Réussi |
| PREUVE-S06-001 | 6 | Tests unitaires | TestDox de la suite de 27 tests | 11/11/2026 | PHP 8.4.27 / PHPUnit 12.5.23 | `preuves/semaine6/phpunit-testdox.txt` | Exécution | 27 tests, 74 assertions, OK | Réussi |
| PREUVE-S06-002 | 6 | Tests unitaires | Rapport JUnit XML | 11/11/2026 | idem | `preuves/semaine6/phpunit-junit.xml` | Exécution | 0 failure, 0 error | Réussi |
| PREUVE-S10-001 | 10 | ANO-02, 03, 04, 06 | Tests actuels exécutés sur le code de `b6dc42a` | 11/11/2026 | PHP 8.4.27, `wassalni_test` | `preuves/semaine10/p0-reference/01-tests-actuels-sur-HEAD.txt` | Exécution | 2 échecs, 2 erreurs, 2 dépréciations sur HEAD | Échoué (sur HEAD) → corrigé |
| PREUVE-S10-002 | 10 | ANO-05 | Validation du mapping Doctrine de `b6dc42a` | 11/11/2026 | idem | `.../02-mapping-doctrine-HEAD.txt` | Exécution | Mapping `Vehicule#trajets` invalide | Échoué (sur HEAD) → corrigé |
| PREUVE-S10-003 | 10 | ANO-01 / CT-12, CT-28 | API `new-ajax` avec `nbPlaces` 0 et −1 sur `b6dc42a` | 11/11/2026 | idem | `.../03-api-quantites-non-positives-HEAD.txt` | Résultat (HTTP et base) | HTTP 200, stock 4 → 5 | Échoué (sur HEAD) → corrigé |
| PREUVE-S10-004 | 10 | ANO-T02 | Sonde de l'EntityManager après redémarrage du kernel | 11/11/2026 | idem | `.../04-sonde-entitymanager-detache.txt` | Exécution | Entité détachée, `flush()` sans effet | Réussi (cause confirmée) |
| PREUVE-S10-005 | 10 | Version de référence | Suite complète sur `test/campagne-finale` | 11/11/2026 | idem | `.../05-suite-reference.txt`, `05-suite-reference-junit.xml` | Exécution | 34 tests, 105 assertions, 0 échec | Réussi |
| PREUVE-S10-006 | 10 | Qualité statique | `php -l`, conteneur, mapping, Twig, YAML | 11/11/2026 | idem | `.../06-controles-statiques.txt` | Exécution | Tous OK | Réussi |

## Preuves prévues (non exécutées à ce jour)

| Semaine | Preuves attendues | Phase |
|---|---|---|
| 5 | `S05_ST01` à `S05_ST10` (captures Playwright) et journaux EXPL-01 à 04 | P2 |
| 7 | JUnit et TestDox des tests d'intégration ; relectures SQL ; test de rollback | P1 |
| 8 | Rapport Newman (JSON et CLI) et capture `S08_TESTS_API.png` | P3 |
| 9 | Rapport Playwright, captures `S09_*`, rapport axe, mesures k6 | P2 / P4 |
| 10 | Régression finale, indicateurs, `S10_BILAN_FINAL.png` | P7 |
