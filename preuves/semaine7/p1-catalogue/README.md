# Preuves P1 — Catalogue officiel exécuté en HTTP/BDD

- **Date :** 11 novembre 2026, 21 h 42 (UTC+1)
- **Révision :** `11fe404` sur la branche `test/campagne-finale`
- **Environnement :** PHP 8.4.27, Symfony 7.4.23, PHPUnit 12.5.23, MariaDB 10.4.32, base `wassalni_test`

## Objet

Les cas `TC-xx` du PDF de la semaine 4 (version officielle) et les critères `CA-xx` du PDF de la semaine 3 sont automatisés avec Symfony BrowserKit. BrowserKit envoie de vraies requêtes HTTP au noyau Symfony, sans navigateur ; l'état est ensuite relu en base par SQL. Le jeu de données du PDF (U-ADMIN, U-COND1/2, U-PASS1/2, U-PASS-NV, V1/V2, T-NOMINAL … T-PAST-RES, RES-F1/OTHER/PAST) est recréé à chaque test par `tests/Support/CatalogueFixture.php`, avec des dates relatives au jour J.

**Chaque test vérifie le résultat attendu par le critère d'acceptation.** Un échec signifie donc que l'application ne respecte pas l'exigence : c'est une anomalie confirmée, consignée dans le [registre](../../../docs/dossier-tests/07-registre-anomalies.md).

## Fichiers

| Fichier | Contenu | Résultat |
|---|---|---|
| `00-isolation-base.txt` | Comptages de `wassalni_test` avant et après les deux exécutions | Identiques (18 utilisateurs, 26 réservations, 36 trajets, 15 véhicules, 11 réclamations, 1 avis) ; 0 résidu |
| `01-regression-defaut.txt` / `01-regression-junit.xml` | `vendor/bin/phpunit --testdox` (groupe `anomalie-ouverte` exclu) | **62 tests, 189 assertions, OK** |
| `02-anomalies-ouvertes.txt` / `02-anomalies-junit.xml` | `vendor/bin/phpunit --testdox --group anomalie-ouverte` | **34 tests : 32 échecs, 2 dépréciations** → ANO-07 à ANO-27 |
| `03-erreurs-500-journal.txt` | Exceptions des erreurs HTTP 500, extraites de `var/log/test.log` | 4 causes distinctes (ANO-19, 23, 25) |

## Reproduire

```powershell
vendor\bin\phpunit --testdox                                # régression (doit être verte)
vendor\bin\phpunit --testdox --group anomalie-ouverte       # anomalies confirmées (doivent échouer tant qu'elles ne sont pas corrigées)
vendor\bin\phpunit --group ANO-07 --exclude-group aucun     # une anomalie précise (lève l'exclusion de la configuration)
```

## Limites

- BrowserKit n'exécute pas le JavaScript : les parcours dans un navigateur réel relèvent des semaines 5 et 9 (Playwright).
- Les connexions de TC-03, TC-04 et TC-51 passent par le vrai formulaire (jeton CSRF « authenticate » compris). Les autres tests utilisent `loginUser()`, qui ouvre une session sans passer par le formulaire.
- OBS-J (réservations concurrentes) demande un serveur démarré : elle est traitée en P4.
