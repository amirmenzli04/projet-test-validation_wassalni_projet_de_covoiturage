# Registre des preuves — WASSALNI

- **Mise à jour :** 11 novembre 2026 (version finale, semaines 1 à 10)
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
| PREUVE-S03-001 | 3 | EX-05, périmètre API | Liste des routes de l'application | 11/11/2026 | Symfony 7.4.23 | `preuves/semaine3/routes-application.txt` | Exécution | Aucune route de profil ; 4 routes JSON | Réussi (constat) |
| PREUVE-S05-001 | 5 | ST-01 à ST-10 | Scénarios dans Chrome, captures et relectures SQL | 11/11/2026 | Chrome, Playwright 1.59.1, `wassalni_test` | `preuves/semaine5/S05_ST*.png`, `S05_verifications.json`, `S05_playwright-*.{txt,json}` | Résultat | 10 scénarios conformes (ST-09 conforme à la règle validée) | Réussi |
| PREUVE-S05-002 | 5 | Transitions T-01 à T-06 | Tests BrowserKit des transitions de réclamation | 11/11/2026 | PHPUnit 12.5.23 | `preuves/semaine5/S05_transitions-*` | Exécution | 5 réussis ; T-05 en échec = ANO-33 | Échoué (ANO-33) |
| PREUVE-S05-003 | 5 | EXPL-01 à 04 | Sessions exploratoires outillées | 11/11/2026 | Chrome, Playwright | `preuves/semaine5/S05_exploration-*`, `S05_EXPL*.png` | Exécution et observations | 5 sondes ; découverte d'ANO-34 | Réussi (sondes) |
| PREUVE-S05-004 | 5 | ANO-32 | Fuseau horaire de PHP comparé à l'heure locale | 11/11/2026 | PHP 8.4.27 | `preuves/semaine5/S05_fuseau-horaire.txt` | Exécution | UTC contre UTC+1 | Échoué (ANO-32) |
| PREUVE-S06-003 | 6 | UT-01 à UT-11 | Suite unitaire et couverture PCOV | 11/11/2026 | PHPUnit, PCOV 1.0.12 | `preuves/semaine6/campagne-finale/*`, `S06_TESTS_UNITAIRES.png`, `S06_COUVERTURE.png` | Exécution | 33 tests OK ; 7,00 % (unitaire), 34,65 % (régression) | Réussi |
| PREUVE-S07-001 | 7 | TC-xx, CA-xx | Catalogue officiel en HTTP/BDD (P1) | 11/11/2026 | BrowserKit, `wassalni_test` | `preuves/semaine7/p1-catalogue/*` | Exécution | 62 tests OK ; 34 tests d'anomalies en échec | Échoué (anomalies) |
| PREUVE-S07-002 | 7 | IT-01 à IT-06 | Intégration Doctrine, transactions, isolation | 11/11/2026 | MariaDB 10.4.32 | `preuves/semaine7/campagne-finale/*`, `S07_TESTS_INTEGRATION.png` | Exécution | 9 intégration OK ; 45 HTTP/BDD OK ; isolation vérifiée | Réussi |
| PREUVE-S08-001 | 8 | API JSON | Collection Newman (19 requêtes) | 11/11/2026 | Newman 6.2.3 | `preuves/semaine8/*`, `S08_TESTS_API.png` | Exécution | 32/35 assertions ; 3 échecs = ANO-22 | Échoué (ANO-22) |
| PREUVE-S09-001 | 9 | CT-29 | Parcours critique dans Chrome | 11/11/2026 | Chrome, Playwright | `preuves/semaine9/S09_PARCOURS_*`, `S09_parcours-verifications.json` | Résultat | Réservation de 2 places, persistance vérifiée | Réussi |
| PREUVE-S09-002 | 9 | Accessibilité | axe-core sur 4 pages et sonde clavier | 11/11/2026 | @axe-core/playwright 4.14.0 | `preuves/semaine9/S09_A11Y_*`, `S09_accessibilite-axe.json` | Exécution | 3 pages avec violations critiques | Échoué (ANO-36, 37) |
| PREUVE-S09-003 | 9 | Performance | k6, 5 VU pendant 30 s | 11/11/2026 | k6 2.3.0 | `preuves/semaine9/S09_k6-*` | Exécution | 0 % d'échec, p95 208,7 ms | Réussi |
| PREUVE-S09-004 | 9 | OBS-J | Concurrence, deux transactions entrelacées | 11/11/2026 | MariaDB 10.4.32 | `preuves/semaine9/S09_concurrence-OBS-J.txt` | Exécution | 2 réservations pour 1 place | Échoué (ANO-38) |
| PREUVE-S09-005 | 9 | Sécurité | En-têtes, cookie, page d'erreur, audits de dépendances | 11/11/2026 | Chrome, composer, npm | `preuves/semaine9/S09_securite-elementaire.json`, `S09_audit-dependances.txt` | Exécution | Cookie et page d'erreur conformes ; en-têtes absents | Réussi partiel (REC-01) |
| PREUVE-S10-007 | 10 | Régression | Référence « avant corrections » | 11/11/2026 | PHPUnit | `preuves/semaine10/regression/*` | Exécution | 87 OK ; 48 tests d'anomalies | Référence |
| PREUVE-S10-008 | 10 | Campagne finale | Rejeu complet sur `cc50095` | 11/11/2026 | Tous les outils | `preuves/semaine10/campagne-finale/*`, `S10_BILAN_FINAL.png`, `S10_PLAYWRIGHT_FINAL.png` | Exécution | PHPUnit 87 OK ; Playwright 20/23 ; Newman 32/35 ; k6 OK | Réussi (régression) ; échecs attendus = anomalies |

## Preuves non produites

| Domaine | Raison |
|---|---|
| Inscription en plusieurs étapes, e-mails (EX-01, 02, 30) | Non exécutés : le transport de mail est `null://null` dans l'environnement de test |
| Exploration manuelle humaine | Sessions conduites avec outil uniquement ; fiche de journal prête (doc 05 §5.5) |
