# Preuves P0 — Stabilisation et version de référence

- **Date d'exécution :** 11 novembre 2026, entre 20 h 15 et 21 h 25 (heure locale, UTC+1)
- **Environnement :** Windows 11, PHP 8.4.27, Symfony 7.4.23, PHPUnit 12.5.23, MariaDB 10.4.32
- **Base utilisée :** `wassalni_test` uniquement (vérifiée par `SELECT DATABASE()` en `--env=test`)
- **Branche :** `test/campagne-finale`, créée depuis `main` (`b6dc42a`)

Ces fichiers sont des **sorties brutes** de commandes réellement exécutées. Seuls les chemins du répertoire temporaire local ont été remplacés par `<scratchpad>`.

## Pourquoi comparer avec HEAD ?

Au début de la campagne, la copie de travail contenait des modifications non commitées dans `src/`. Pour savoir si elles corrigeaient de vrais défauts, les tests actuels ont été exécutés **contre le code de `b6dc42a`**, dans un worktree Git temporaire créé hors du dépôt puis supprimé. Le chargement des classes depuis ce worktree est démontré par la première ligne de `01-tests-actuels-sur-HEAD.txt` (`Reservation chargee depuis: <scratchpad>\head-wt\src\...`).

## Fichiers

| Fichier | Contenu | Résultat |
|---|---|---|
| `head-sha.txt` | Révision testée pour la comparaison | `b6dc42a` |
| `01-tests-actuels-sur-HEAD.txt` | 19 tests d'entité et fonctionnels sur le code de HEAD | 2 échecs, 2 erreurs, 2 dépréciations → ANO-02, 03, 04, 06 |
| `02-mapping-doctrine-HEAD.txt` | `SchemaValidator::validateMapping()` sur HEAD | Mapping invalide `Vehicule#trajets` → ANO-05 |
| `03-api-quantites-non-positives-HEAD.txt` | `POST /reservation/new-ajax` avec `nbPlaces` = 0 et −1 sur HEAD | HTTP 200 ; −1 → `prixTotal` −25, stock 4 → 5 → ANO-01 |
| `04-sonde-entitymanager-detache.txt` | Sonde : état de l'EntityManager après deux requêtes BrowserKit | EM remplacé, entité détachée, `flush()` sans effet → ANO-T02 |
| `05-suite-reference.txt` / `05-suite-reference-junit.xml` | Suite complète après stabilisation | **34 tests, 105 assertions, 0 échec** |
| `06-controles-statiques.txt` | `php -l`, `lint:container`, `doctrine:schema:validate`, `lint:twig`, `lint:yaml` | Tous OK |
| `sondes/` | Code source des sondes temporaires (pour reproduire) | — |

## Reproduire

```powershell
# Suite de référence (sur la branche test/campagne-finale)
vendor\bin\phpunit --testdox --log-junit preuves\semaine10\p0-reference\05-suite-reference-junit.xml

# Comparaison avec HEAD d'origine : worktree temporaire hors du dépôt
git worktree add --detach $env:TEMP\wassalni-head b6dc42a
# copier .env, créer une jonction vers vendor\, copier tests\ et sondes\bootstrap-worktree-HEAD.php
# comme tests\bootstrap.php (il force l'autoload App\ vers le src\ du worktree), puis lancer phpunit.
# Supprimer d'abord la jonction (rmdir), ensuite : git worktree remove --force $env:TEMP\wassalni-head
```

## Isolation des données

- **Avant et après chaque exécution :** 0 compte `phpunit-*@example.test` résiduel et 26 réservations préexistantes inchangées.
- **Seul ajout volontaire et durable :** le trajet de fixture `Fixture automatisée W7-W9` (2099-12-01), avec ses deux comptes `semaine7-9.*@example.test` créés par `app:tests:prepare-week7-9`. La commande refuse toute base dont le nom ne se termine pas par `_test`.
