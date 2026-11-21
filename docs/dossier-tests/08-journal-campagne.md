# 8. Journal de la campagne finale

Ce journal trace, phase par phase, les opérations réellement effectuées, les commandes exécutées et leurs résultats. Chaque résultat renvoie à une preuve conservée dans `preuves/`.

| Élément | Valeur |
|---|---|
| Branche | `test/campagne-finale` (créée depuis `main`, révision `b6dc42a`) |
| Environnement | Windows 11 Pro, PHP 8.4.27, Symfony 7.4.23, PHPUnit 12.5.23, Node 22.21.0, npm 11.12.1 |
| Base de test | `wassalni_test`, MariaDB 10.4.32 (suffixe `_test` imposé par `doctrine.yaml` en environnement `test`) |
| Base principale | `wassalni` : jamais utilisée par la campagne |
| Secret de test | `WASSALNI_TEST_PASSWORD`, défini uniquement dans `.env.test.local` (ignoré par Git) |

## P0 — Stabilisation du dépôt et version de référence (11/11/2026) — **Terminé**

### Constat de départ (audit)

- **Suite complète :** 34 tests, 98 assertions, **2 échecs**, tous deux dans `ReservationApiTest`, qui n'était pas versionné.
- **Périmètre historique** (`Controller`, `Entity`, `Service`, `AnonymousAccess`, `ReclamationWorkflow`) : **27 tests, 74 assertions, OK**, ce qui confirme le résultat annoncé au 10/11/2026.
- **Fichiers non versionnés :** seuls 3 fichiers de test sur 10 étaient suivis par Git, et 4 fichiers de `src/` étaient modifiés sans commit. Aucun résultat n'était donc reproductible à partir d'une révision Git.

### Opérations

1. **Analyse des modifications non commitées de `src/`.** Les tests actuels ont été exécutés contre le code de `b6dc42a`, dans un worktree temporaire hors du dépôt, supprimé ensuite. Résultat : les modifications corrigent **6 défauts réels**, ANO-01 à ANO-06 ([registre](07-registre-anomalies.md)). Le plus grave est ANO-01 : l'API acceptait `nbPlaces = -1` et augmentait le stock.
2. **Création de la branche** `test/campagne-finale` (`git switch -c`), sans perte de la copie de travail.
3. **Diagnostic des 2 tests en échec.** La cause a été confirmée par une sonde : il s'agit de défauts des tests (ANO-T01, ANO-T02), non de l'application. Correction côté test.
4. **Collection Postman** rendue valide (ANO-T04).
5. **Mot de passe fictif en clair** retiré de 3 fichiers et remplacé par `WASSALNI_TEST_PASSWORD`. Ces fichiers n'avaient jamais été commités, donc le mot de passe n'est pas dans l'historique.
6. **Défaut des fixtures** (arrivée = départ) détecté en exécutant la commande, puis corrigé (ANO-T03).
7. **`.gitignore`** complété (`node_modules`, rapports Playwright). **`.gitattributes`** ajouté : `Reservation-complete.pdf` était traité comme du texte et aurait été corrompu par `core.autocrlf` ; empreinte SHA-256 vérifiée identique.
8. **Commits** atomiques de la version de référence.

### Commandes principales

```powershell
git switch -c test/campagne-finale
vendor\bin\phpunit --testdox --log-junit preuves\semaine10\p0-reference\05-suite-reference-junit.xml
php bin/console app:tests:prepare-week7-9 --env=test       # refusé en --env=dev (garde _test)
php bin/console doctrine:schema:validate --env=test
php bin/console lint:container --env=test ; php bin/console lint:twig templates ; php bin/console lint:yaml config
```

### Résultats

| Contrôle | Résultat | Preuve |
|---|---|---|
| Suite PHPUnit complète | **34 tests, 105 assertions, 0 échec, 0 dépréciation** | `preuves/semaine10/p0-reference/05-*` |
| Tests actuels sur le code de `b6dc42a` | 19 tests : 2 échecs, 2 erreurs, 2 dépréciations | `01-tests-actuels-sur-HEAD.txt` |
| API sur `b6dc42a` avec `nbPlaces` = −1 | HTTP 200, stock 4 → 5 (défaut confirmé) | `03-api-quantites-non-positives-HEAD.txt` |
| Syntaxe PHP, conteneur, mapping, Twig, YAML | 48 fichiers PHP, 34 Twig et 24 YAML valides ; schéma synchronisé | `06-controles-statiques.txt` |
| Commande de données W7-W9 | Trajet 2099-12-01 10:00 → 12:00, 4 places ; refusée en `dev` | Sortie console ; requête SQL de relecture |
| Résidus de fixtures | 0 compte `phpunit-*` ; 26 réservations préexistantes inchangées | Requête SQL de contrôle |

### Commits de la version de référence

| Commit | Contenu |
|---|---|
| `38e1a14` | `.gitignore` (outillage Node) et documentation de la variable de secret |
| `e2fe62b` | Corrections métier ANO-01 à ANO-06 (modifications préexistantes, vérifiées) |
| `b8497aa` | Tests d'entité, fonctionnels et d'intégration ; corrections ANO-T01 à T03 ; commande de données |
| `a7cd086` | Collection Postman (ANO-T04) et parcours Playwright |
| `f0e496a` | Livrables des semaines 4 à 6 tels que rédigés, sans modification |
| `400a15f` | `.gitattributes` (PDF et images binaires) |

### Limites et points en attente

- `docs/docs/semaine2-environnement.md` était un fichier **vide**, doublon de `docs/semaine2-environnement.md`. Il a été supprimé le 21/11/2026.
- `Reservation-complete.pdf` est une impression du code source, non une preuve d'exécution. Il est conservé mais ne sera pas cité comme preuve.
- **Deux référentiels d'identifiants coexistent** : la lignée PDF des semaines 1 à 4 (`EX`, `CA`, `TC`, `A`) et la lignée Markdown du dossier (`R`, `EX`, `CT`). Les numéros `EX-xx` ont des **significations différentes** d'une lignée à l'autre. Une harmonisation est prévue en P5, après votre décision.

## P1 — Confirmation des observations et exécution du catalogue officiel (11/11/2026) — **Terminé**

### Décisions appliquées

Le référentiel adopté est celui des PDF : exigences `EX-01` à `EX-35` et critères `CA-01` à `CA-22` (semaine 3), cas `TC-xx` (semaine 4, version officielle de `docs/`), écarts `A-xx`. Le catalogue Markdown `CT-xx` sera relié à ce référentiel par une table de correspondance (P5).

### Opérations

1. **Jeu de données officiel** (`tests/Support/CatalogueFixture.php`), conforme à la section 3 du PDF de la semaine 4 : 6 comptes, 2 véhicules, 8 trajets, 3 réservations, dates relatives au jour J. Le nettoyage ne supprime que les lignes rattachées aux comptes `cat-*@example.test` créés par le test.
2. **Quatre classes de tests HTTP/BDD**, une par groupe A à E du PDF, nommées avec leur identifiant TC/CA (`#[TestDox]`).
3. **Trois défauts de mes propres tests** détectés puis corrigés avant de produire les preuves :
   - propriétés `private` du trait invisibles depuis les sous-classes, d'où des URL sans identifiant et un 404 ; certains tests auraient pu réussir à tort ;
   - méthode `status()` réservée par PHPUnit ;
   - sujet de réclamation absent de la liste des choix dans TC-36.
4. **Classement des tests révélant une anomalie** dans les groupes `anomalie-ouverte` et `ANO-xx`, exclus de la régression par défaut.
5. **`phpunit.dist.xml`** découpé en trois suites : `unitaire`, `integration`, `fonctionnel`.

### Résultats

| Mesure | Valeur |
|---|---|
| Régression par défaut | **62 tests, 189 assertions, OK** |
| Groupe `anomalie-ouverte` | **34 tests : 32 échecs, 2 dépréciations** |
| Anomalies confirmées | 21 (ANO-07 à ANO-27), dont **6 de sévérité S2** (ANO-07, 12, 13, 20, 21, 22) |
| Observations OBS-A à OBS-I | Toutes confirmées ; OBS-J reportée en P4 |
| Isolation de la base | Comptages identiques avant et après ; 0 résidu |

Les preuves sont dans `preuves/semaine7/p1-catalogue/` (voir son README) ; le détail des anomalies est dans [07 §7.5](07-registre-anomalies.md).

### Points en attente

Aucune correction du code métier n'a été faite. Les corrections proposées vous seront présentées anomalie par anomalie avant P6.


## Semaines 1 à 10 — Clôture de la campagne (11/11/2026)

| Phase | Réalisé | Document |
|---|---|---|
| Semaines 1 à 4 | Référentiel PDF adopté et correspondances ; captures S1 anonymisées ; A-05, A-09, A-11 confirmés | 00, 07 |
| Semaine 5 | ST-01 à ST-10 dans Chrome, transitions, explorations outillées ; ANO-31 à 34 | 05 §5.9 |
| Semaine 6 | UT-01 à UT-11, couverture PCOV (7,00 % / 34,65 %) ; ANO-35 | 06 §6.7 |
| Semaine 7 | IT-01 à IT-06 (rollback, atomicité, cascade) ; isolation vérifiée | 10 |
| Semaine 8 | Newman : 19 requêtes, 32/35 assertions (ANO-22) | 11 |
| Semaine 9 | Parcours critique, axe, k6, sécurité, concurrence (ANO-36 à 38) | 12 |
| Semaine 10 | Campagne finale `scripts/run-regression.ps1` ; matrice ; décision NO-GO | 09, 14, rapport final |

**Décision du responsable (11/11/2026) :** aucune modification du code de l'application. Des corrections avaient été commencées après une première autorisation ; elles ont été **annulées avant tout commit** (`git checkout`), et le code est resté identique à la révision testée. Les 32 anomalies restent ouvertes et documentées.

**Incidents d'outillage traités :**

- **ANO-T05 :** dépendance d'ordre entre fichiers Playwright.
- **Encodage de la synthèse PowerShell :** script réenregistré en UTF-8 avec BOM ; l'en-tête de `00-synthese.txt` a été redécodé de cp1252 vers UTF-8, sans changement des valeurs.

## Corrections S2 et rejeu (21/11/2026)

| Étape | Réalisé | Révision |
|---|---|---|
| Intégration continue | Workflow réparé : `.env` absent du dépôt (cause des 12 échecs), base MariaDB éphémère, rapports en artefacts | `d5f4c7c` |
| Plan de test v2.0 | Seuils quantitatifs de sortie (§2.7.2) et règle de décision (§2.7.3) | `e166adf` |
| Écarts avec l'énoncé | Document [15](15-ecarts-enonce.md) | `7038214` |
| Corrections 1 | ANO-07, 20, 21, 22, 35 ; 14 tests passés en régression | `f7a3aca` |
| Corrections 2 | ANO-12, 13 (CSRF) ; 6 tests passés en régression ; ST-10 stabilisé (ANO-T07) | `adc66be` |
| Corrections 3 | ANO-38 (décrément atomique) ; garde-fou d'architecture après un commit incomplet | `cb09a55`, `72cff37` |
| Rejeu complet | 110 tests OK ; 27 tests d'anomalies ; Newman 35/35 ; Playwright 20/23 ; k6 respecté | `72cff37` |
| Décision | **Validation conditionnelle** ([14 §14.7](14-semaine10-bilan-decision.md)) | `72cff37` |

**Incidents d'outillage :** ANO-T06 (cache de test non reconstruit en `debug => false`) et ANO-T07 (ST-10 instable), voir [07 §7.3](07-registre-anomalies.md).

## Accessibilité et API REST (21/11/2026, suite)

| Étape | Réalisé | Révision |
|---|---|---|
| Accessibilité | ANO-36 corrigée (9 étiquettes reliées) ; Playwright 23/23 | `eb863be` |
| API REST | `/api/reclamations` (GET, POST, PATCH) ; API-01 à API-14 ; Newman 11 à 26 ; efficacité des tests vérifiée par mutation | `1ed214b` |
| Défaut découvert | ANO-39 (contrainte `Length` mal paramétrée sur `User`), corrigée ; garde-fou UT-12, UT-13 | `1ed214b` |
| Rejeu complet | 131 tests OK ; Playwright 23/23 ; Newman 59/59 ; k6 respecté | `1ed214b` |
| Décision | Validation conditionnelle confirmée ([14 §14.8](14-semaine10-bilan-decision.md)) | `1ed214b` |

**Incident d'outillage :** ANO-T08 (délai de 30 s dépassé au premier démarrage du serveur après un vidage du cache), voir [07 §7.3](07-registre-anomalies.md).

