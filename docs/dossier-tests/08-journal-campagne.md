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

- `docs/docs/semaine2-environnement.md` est un fichier **vide**, doublon de `docs/semaine2-environnement.md`. Il est laissé non versionné, en attente de votre accord pour le supprimer.
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
