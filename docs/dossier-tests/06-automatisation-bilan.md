# 6. Automatisation, rapports et décision de livraison

## 6.1 État de l'automatisation constaté au 10 novembre 2026

- Le dépôt déclare PHPUnit 12.5 et une suite qui découvre les tests sous `tests/`.
- `tests/Service/ReservationServiceTest.php` contient **7 tests** du service : création, décrément des places, insuffisance de places, réservation déjà existante, annulation, restitution et refus d'une annulation répétée.
- `tests/Entity/ReservationTest.php` contient **6 tests** de l'entité : annulation selon la date de départ, statut/date d'annulation et recalcul du prix.
- `tests/Controller/HealthControllerTest.php` contient **1 test** du contrôleur de santé.
- Arrange–Act–Assert, assertions, stub et mock sont déjà illustrés dans ce fichier.
- La commande `php bin/phpunit` a réussi avec PHP 8.4.27 et PHPUnit 12.5.23 : **27 tests, 74 assertions, aucun échec ni dépréciation**.
- La suite contient 7 tests de service, 6 tests de l'entité Reservation, 5 tests de validation des entités, 1 test de redirections anonymes sur trois routes, 7 tests fonctionnels du workflow de réclamation et 1 test du contrôleur de santé.
- `php -l` a réussi sur les **37 fichiers PHP de `src/`** et les **7 fichiers PHP de `tests/`**.
- Les **24 fichiers YAML**, les **34 templates Twig**, le conteneur Symfony et le manifeste Composer ont été validés. `doctrine:schema:validate --env=test` confirme que le mapping et la base sont synchronisés.
- Les sept tests fonctionnels couvrent la création/persistance d'une réclamation, les limites du formulaire, les droits de lecture, la liste filtrée et le traitement administratif. La base dédiée est accessible; les fixtures aléatoires sont nettoyées et le contrôle de résidu a retourné zéro. Aucun test d'exploration manuelle dans un navigateur réel, de performance ou d'accessibilité n'a été exécuté; aucun rapport de couverture n'a été produit.

### Procédure de relance

Depuis la racine du dépôt, avec les dépendances installées :

```powershell
php bin/phpunit
```

Pour vérifier la syntaxe de tous les fichiers PHP de l'application et des tests :

```powershell
Get-ChildItem src, tests -Recurse -File -Filter '*.php' | ForEach-Object { php -l $_.FullName }
```

Consigner la version PHP, le SHA Git et les sorties. Pour étendre la campagne d'intégration, conserver une base exclusivement réservée aux tests, appliquer les migrations et ajouter des tests ciblés. Ne jamais utiliser une URL de base de production.

### AAA attendu

1. **Arrange :** créer les entités et doubles nécessaires; fixer l'état initial et les nombres de places.
2. **Act :** appeler une seule action métier.
3. **Assert :** vérifier l'état, les effets de bord, les exceptions et, pour une intégration, la relecture persistée.

La suite unitaire actuelle couvre déjà plusieurs résultats positifs et négatifs du service de réservation. Les cas limites manquants doivent être ajoutés au code de test et exécutés avant de conclure à la couverture du besoin.

## 6.2 Rapport d'exécution

| Vérification | Résultat | Preuve / commentaire |
|---|---|---|
| Inspection de la suite PHPUnit | 27 tests : 7 service, 6 Reservation, 5 validations, 1 accès anonyme, 7 réclamations fonctionnels, 1 santé | Découverte dans `tests/` |
| `php bin/phpunit` | **Réussi** | PHP 8.4.27 / PHPUnit 12.5.23; 27 tests, 74 assertions, 0 échec, 0 dépréciation |
| Tests HTTP/DB du workflow de réclamation | **Réussi** | Création, limites, propriétaire, filtrage, traitement admin et refus de rôle; données relues en base |
| Nettoyage des fixtures | **Réussi** | `remaining_phpunit_fixtures = 0` après la suite; données antérieures préservées |
| Tests HTTP de contrôle d'accès anonyme | **Réussi partiellement** | Trois routes protégées redirigent vers `/connexion`; autres rôles et routes à couvrir |
| Syntaxe des fichiers PHP | **Réussi** | `php -l` sur 37 fichiers de `src/` et 7 fichiers de `tests/` |
| Configuration Symfony/Composer | **Réussi** | 24 YAML, 34 Twig, conteneur et `composer.json` valides |
| Mapping Doctrine | **Réussi** | `doctrine:schema:validate --env=test`; mapping valide et schéma synchronisé |
| Connexion à la base de test | **Réussie** | Base `wassalni_test` accessible via la configuration `--env=test` |
| Tests HTTP/API | **Partiel** | Flux de réclamation couvert par BrowserKit; API AJAX de réservation reste à tester |
| Test interface | Non exécuté | Navigateur/outillage non configuré dans cette campagne |
| Accessibilité/performance | Non exécutés | Seuils et environnement de mesure à convenir |
| Couverture de code | Non mesurée | Ne pas communiquer de pourcentage sans rapport généré |

## 6.3 Fiche de résultat à copier par cas

```text
Identifiant :
Version / SHA :
Environnement :
Préconditions / données anonymisées :
Résultat attendu :
Résultat observé :
Statut : Réussi | Échoué | Bloqué | Non exécuté
Preuve :
Anomalie liée :
Exécutant / date :
```

## 6.4 Régression minimale

Après chaque correction concernant réservation, réexécuter CT-10 à CT-17 et CT-24 à CT-28 selon la surface touchée. Après correction des rôles/propriétaires, réexécuter CT-01 à CT-04, CT-16 et CT-21. Après correction des réclamations, réexécuter CT-18 à CT-23. Toute régression doit cibler la même révision candidate de livraison.

## 6.5 Bilan de campagne et décision

### Synthèse au 10 novembre 2026

| Mesure | État |
|---|---|
| Cas spécifiés | 31 dans le catalogue |
| Tests automatisés présents | 27 (7 service, 6 Reservation, 5 validations, 1 contrôle anonyme, 7 réclamation, 1 santé) |
| Tests exécutés avec succès | 27 tests, 74 assertions |
| Syntaxe PHP vérifiée | 37 fichiers de `src/`, 7 fichiers de `tests/` |
| Configuration vérifiée | YAML, Twig, conteneur Symfony, Composer et mapping Doctrine valides |
| Cas échoués confirmés | Aucun dans la suite PHPUnit exécutée |
| Anomalies confirmées | Aucune par ces tests; les autres parcours restent à vérifier |
| Observations à reproduire | OBS-01 à OBS-05 |
| Exigences ambiguës bloquantes | Délai d'annulation, statuts de réclamation, contrat API, bornes de données |

### Décision argumentée

**NO-GO probatoire / décision de livraison différée.** Les 27 tests automatisés passent et le workflow de réclamation est partiellement vérifié en HTTP/DB, mais l'ensemble des 31 cas n'est pas couvert : les parcours complets de réservation/publication, l'API AJAX, le navigateur réel et les tests non fonctionnels restent à exécuter; des exigences demeurent à arbitrer et des observations statiques restent à reproduire.

Cette décision n'affirme pas que les tests échoueraient. Elle signifie que les critères de sortie ne sont pas démontrés. Un **GO** pourra être proposé après exécution sur une révision identifiée, réussite des cas prioritaires, résolution/acceptation formelle des anomalies majeures, clôture des ambiguïtés et visa de recette.

### Visa de livraison

| Décisionnaire | Décision | Révision (SHA/tag) | Date | Réserves acceptées / signature |
|---|---|---|---|---|
| Responsable produit / enseignant | En attente | À renseigner | À renseigner | À renseigner |
| Responsable campagne | NO-GO probatoire | À renseigner | 10 novembre 2026 | Suite automatisée et workflow réclamation réussis; recette navigateur/non fonctionnelle à compléter |

## 6.7 Semaine 6 — campagne finale du 11/11/2026 (tests unitaires et couverture)

### Inventaire de la suite `unitaire`

La suite `unitaire` compte **33 tests exécutés, 52 assertions, OK**. Les 6 tests du groupe `anomalie-ouverte` sont rejoués à part. Aucun de ces tests ne sollicite HTTP ni la base, sauf le test de santé.

| Fichier | Tests | Règles vérifiées | Doublure |
|---|---|---|---|
| `tests/Service/ReservationServiceTest.php` (existant) | 7 | Création, décrément, stock insuffisant, doublon, annulation, restitution, double annulation | **Stub** du dépôt : on fixe sa réponse, sans vérifier l'appel |
| `tests/Service/ReservationServiceInteractionTest.php` (UT-10, UT-11) | 2 | Critère d'unicité transmis au dépôt (CA-09) ; court-circuit si stock insuffisant (CA-06) | **Mock** : `expects(once())->with(...)` puis `expects(never())`. Le mock est justifié ici parce que le résultat attendu est une interaction |
| `tests/Entity/ReservationTest.php` (existant) | 6 | `peutEtreAnnulee()`, date d'annulation, recalcul du prix | Aucune |
| `tests/Entity/ReclamationValidationTest.php` (existant) | 5 | Bornes du message (9/10) et du sujet (4/5), bornes des places | Aucune (validateur réel) |
| `tests/Entity/RegleMetierEntitesTest.php` (UT-01 à UT-09) | 14 exécutés + 6 en anomalie | Arrivée strictement après le départ (VL), prix > 0 (VL : 0 / 0,01), départ futur, bornes de la note 1–5 (VL), rôles, note moyenne arrondie | Aucune (validateur réel) |
| `tests/Controller/HealthControllerTest.php` (existant) | 1 | `/health` répond `ok` | — |

Tous les nouveaux tests suivent la structure **Arrange – Act – Assert**, signalée par des commentaires. Les valeurs limites sont exercées de part et d'autre de chaque borne : 0 / 0,01 pour le prix, 0 / 1 / 9 / 10 pour les places, 0 / 1 / 5 / 6 pour la note, égalité puis +1 minute pour les dates.

### Ce que vérifient les assertions

- **`assertSame`** (identité de valeur et de type) est utilisé pour les nombres de violations, les statuts et les montants. Une égalité lâche masquerait par exemple `"0"` ≠ `0`.
- **Comparaison de tableaux** (`assertSame([1, 0], …)`) : les deux côtés d'une limite sont vérifiés dans une seule assertion, ce qui rend le message d'échec lisible.
- **Attentes des mocks** : `once()`, `never()` et `with()` sont des assertions d'interaction, vérifiées automatiquement en fin de test.
- **Exceptions** : dans UT-11, le message de la `DomainException` **et** l'absence d'effet de bord (stock inchangé) sont vérifiés.

### Défauts révélés par les tests unitaires

| Test | Résultat | Anomalie |
|---|---|---|
| UT-04 — départ aujourd'hui à 00:00:01 (déjà passé) | **Échec** : accepté, car `GreaterThan("today")` compare à minuit | ANO-14 (complément) |
| UT-05 / UT-06 — validation d'un véhicule | **Erreur** `Unknown named parameter $message` (`Vehicule.php:34`) | **ANO-35** : confirmée ensuite au niveau système (HTTP 500 à l'ajout d'un véhicule) |

Cet exemple illustre bien l'intérêt du test unitaire : un défaut de configuration de contrainte, invisible tant que le formulaire de véhicule n'est pas soumis, est détecté en isolation puis confirmé de bout en bout.

### Couverture de code (PCOV 1.0.12, chargé localement)

| Périmètre | Tests | Lignes | Méthodes | Classes |
|---|---|---|---|---|
| Suite `unitaire` seule | 33 | **7,00 %** (106/1515) | 16,67 % | 2,70 % |
| Régression complète (unitaire + intégration + fonctionnel) | 81 | **34,65 %** (525/1515) | 49,56 % | 18,92 % |

Détail par classe (régression complète) : `ReservationService` 100 %, `Reservation` 94,9 %, `Reclamation` 87,1 %, `ReclamationController` 100 %, `ReservationController` 84,5 %, `TrajetController` 85,7 %, `LoginFormAuthenticator` 53,6 %, `AdminController` 15,1 %, `VehiculeController` 5,3 %. Plusieurs classes sont à 0 % : `RegistrationWizardController`, `ConducteurController`, `DashboardController`, `HomeController`, `MailerService`, les formulaires d'inscription et la commande de données de test. Rapport navigable : `preuves/semaine6/campagne-finale/couverture-html/index.html`.

### Analyse et limites

1. **La couverture unitaire est faible (7 %), et c'est attendu.** La logique de WASSALNI est presque entièrement écrite dans les contrôleurs, que seuls les tests HTTP exécutent. Le seul service métier, `ReservationService`, est couvert à 100 %, mais il **n'est appelé par aucun contrôleur** (écart A-13). Sa couverture ne garantit donc rien sur le comportement réel. C'est une limite de conception, pas un manque de tests.
2. **Une ligne couverte n'est pas une ligne vérifiée.** `ReservationController` est couvert à 84,5 % alors que la voie HTML contient 5 anomalies (ANO-07, 08, 10, 11, 12). La couverture mesure l'exécution, pas la pertinence des assertions.
3. Les tests du groupe `anomalie-ouverte` sont exclus de la mesure. Les inclure augmenterait la couverture sans améliorer la confiance.
4. **Zones non couvertes, à risque :** l'inscription en plusieurs étapes et l'e-mail de confirmation (EX-01, EX-02, EX-30 ; R11) ne sont testés à aucun niveau. C'est un risque résiduel à signaler.
