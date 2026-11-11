# 7. Registre des anomalies

- **Version :** 1.4, 11 novembre 2026 (semaines 1 à 9)
- **Branche de campagne :** `test/campagne-finale`
- **Environnement commun :** Windows 11, PHP 8.4.27, Symfony 7.4.23, PHPUnit 12.5.23, MariaDB 10.4.32, base `wassalni_test`

## 7.1 Règles du registre

- Une anomalie n'entre ici qu'**après reproduction par exécution**. Les constats de lecture de code restent des observations (section 7.4).
- **Sévérité :** S1 critique, S2 majeure, S3 moyenne, S4 mineure (définitions en [01 §1.4](01-cadrage-risques.md)). **Priorité :** P1 (corriger avant livraison) à P4 (opportuniste).
- **Statuts :** Ouverte → Confirmée → Corrigée → Vérifiée (régression réussie) ; ou Rejetée.
- **Préfixes :**
  - `ANO-xx` : défaut de l'application ;
  - `ANO-Txx` : défaut de l'outillage de test.

  Les identifiants `A-xx` du PDF de la semaine 3 désignent des écarts relevés pendant l'analyse ; la correspondance est donnée quand elle existe.

## 7.2 Anomalies de l'application

### ANO-01 — L'API de réservation accepte 0 ou un nombre négatif de places

| Champ | Valeur |
|---|---|
| Version détectée | `b6dc42a` (main) |
| Préconditions | Passager authentifié ; trajet `VALIDE`, 4 places, prix 25 |
| Étapes | `POST /reservation/new-ajax` avec `{"trajetId": <id>, "nbPlaces": -1}`, puis relecture du trajet en base. Variante : `nbPlaces: 0` |
| Résultat attendu | HTTP 400, `success: false`, aucune réservation, stock inchangé (EX-05, table de décision D5) |
| Résultat observé | HTTP 200, `"Réservation confirmée pour -1 place(s) !"`, `prixTotal: -25`, **stock 4 → 5**. Avec 0 : HTTP 200 et une réservation de 0 place créée |
| Preuve | [`preuves/semaine10/p0-reference/03-api-quantites-non-positives-HEAD.txt`](../../preuves/semaine10/p0-reference/03-api-quantites-non-positives-HEAD.txt) |
| Cause | `(int) ($data['nbPlaces'] ?? 1)`, sans contrôle de signe ; le stock est décrémenté d'une valeur négative |
| Sévérité / priorité | **S2 / P1** (stock et montant financier corrompus) |
| Correction | `e2fe62b` : `filter_var(..., FILTER_VALIDATE_INT, min_range 1)` sur `trajetId` et `nbPlaces`, et rejet d'un JSON invalide |
| Statut | **Vérifiée** : la régression passe sur `e2fe62b` |
| Régression | `ReservationApiTest::testApiRejectsMalformedJsonAndNonPositiveSeatCounts` |

Le même défaut est **présumé** sur la route HTML `POST /reservation/new/{id}`, qui n'a pas été corrigée. C'est l'observation OBS-A, à confirmer en P1.

### ANO-02 — `peutEtreAnnulee()` autorise l'annulation d'un trajet déjà parti

| Champ | Valeur |
|---|---|
| Version détectée | `b6dc42a` |
| Préconditions | Réservation `CONFIRMEE` sur un trajet dont le départ est passé |
| Étapes | Appeler `Reservation::peutEtreAnnulee()` |
| Résultat attendu | `false` |
| Résultat observé | `true` (`Failed asserting that true is false`) |
| Preuve | [`01-tests-actuels-sur-HEAD.txt`](../../preuves/semaine10/p0-reference/01-tests-actuels-sur-HEAD.txt) |
| Cause | `$interval->h >= 2 \|\| $interval->invert === 1` : `invert = 1` signifie justement « départ passé ». De plus, `h` ignore les jours |
| Sévérité / priorité | S3 / P2. La méthode ne pilote que l'affichage du bouton « Annuler » de `dashboard/passager.html.twig` ; le contrôleur d'annulation ne l'appelle pas (voir OBS-B) |
| Correction | `e2fe62b` : `dateDepart->getTimestamp() - time() >= 7200` |
| Statut | **Vérifiée** |
| Régression | `ReservationTest::peutEtreAnnulee_refuseUnDepartPasse` |

### ANO-03 — `peutEtreAnnulee()` provoque une erreur fatale sans trajet

| Champ | Valeur |
|---|---|
| Version détectée | `b6dc42a` |
| Étapes | `(new Reservation())->peutEtreAnnulee()` |
| Résultat attendu | `false` |
| Résultat observé | `Error: Call to a member function getDateDepart() on null` |
| Preuve | `01-tests-actuels-sur-HEAD.txt` |
| Sévérité / priorité | S4 / P3. La colonne `trajet_id` est non nulle en base, donc le cas ne se produit qu'en mémoire |
| Correction / statut | `e2fe62b` / **Vérifiée** |
| Régression | `ReservationTest::peutEtreAnnulee_refuseUnTrajetAbsent` |

### ANO-04 — Le prix total n'est pas remis à zéro quand le trajet est retiré

| Champ | Valeur |
|---|---|
| Version détectée | `b6dc42a` |
| Étapes | Réservation de 2 places sur un trajet à 10, puis `setTrajet(null)` |
| Résultat attendu | `prixTotal` à `null` |
| Résultat observé | `20.0` conservé (`Failed asserting that 20.0 is null`) |
| Preuve | `01-tests-actuels-sur-HEAD.txt` |
| Sévérité / priorité | S4 / P4 |
| Correction / statut | `e2fe62b` / **Vérifiée** |
| Régression | `ReservationTest::lePrixTotalEstRecalculeQuandLeTrajetEstRetire` |

### ANO-05 — Mapping Doctrine invalide entre Trajet et Vehicule

| Champ | Valeur |
|---|---|
| Version détectée | `b6dc42a` |
| Étapes | `SchemaValidator::validateMapping()` (équivalent de `doctrine:schema:validate`) |
| Résultat attendu | Aucune erreur |
| Résultat observé | `Vehicule#trajets is on the inverse side ... Trajet#vehicule does not contain the required 'inversedBy: "trajets"'` |
| Preuve | [`02-mapping-doctrine-HEAD.txt`](../../preuves/semaine10/p0-reference/02-mapping-doctrine-HEAD.txt) |
| Sévérité / priorité | S3 / P3 (relation bidirectionnelle incohérente) |
| Correction / statut | `e2fe62b` (`#[ORM\ManyToOne(inversedBy: 'trajets')]`) / **Vérifiée** : `doctrine:schema:validate` OK ([06-controles-statiques.txt](../../preuves/semaine10/p0-reference/06-controles-statiques.txt)) |

### ANO-06 — Contraintes de formulaire dépréciées dans `ReclamationType`

| Champ | Valeur |
|---|---|
| Version détectée | `b6dc42a` |
| Étapes | Soumettre le formulaire de réclamation (`ReclamationWorkflowTest`) |
| Résultat attendu | Aucune dépréciation (`failOnDeprecation="true"`) |
| Résultat observé | `Since symfony/validator 7.3: Passing an array of options ... NotBlank / Length ... is deprecated` (2 tests en échec) |
| Preuve | `01-tests-actuels-sur-HEAD.txt` |
| Sévérité / priorité | S4 / P3. Maintenabilité : l'écriture sera supprimée en Symfony 8 |
| Correction / statut | `e2fe62b` (arguments nommés) / **Vérifiée** |

## 7.3 Anomalies de l'outillage de test

| ID | Titre | Preuve de la cause | Correction | Statut |
|---|---|---|---|---|
| ANO-T01 | `assertSame(50.0, prixTotal)` échoue : `JsonResponse` encode `50.0` en `50`, relu comme entier | Message `Failed asserting that 50 is identical to 50.0` (audit du 11/11) | `b8497aa` : `assertEqualsWithDelta` | Vérifiée |
| ANO-T02 | Le stock remis à 4 n'est pas persisté : après la 2ᵉ requête, BrowserKit redémarre le kernel, l'EntityManager change et l'entité fixture est détachée | [`04-sonde-entitymanager-detache.txt`](../../preuves/semaine10/p0-reference/04-sonde-entitymanager-detache.txt) : `meme_EM=false`, `places_en_base=4` | `b8497aa` : rechargement du trajet avant modification | Vérifiée |
| ANO-T03 | Les fixtures ont une date d'arrivée égale à la date de départ : `$departure->modify()` modifie l'objet partagé | Sortie de la commande : `dateDepart 12:00` au lieu de `10:00` | `b8497aa` : `(clone $departure)->modify(...)`. Relecture SQL : 10:00 → 12:00 | Vérifiée |
| ANO-T04 | Collection Postman illisible : tableau `event` fermé par `}` (ligne 37) | `json.decoder.JSONDecodeError: line 38 column 7` | `a7cd086` | Vérifiée (JSON valide, 10 requêtes) |

## 7.4 Devenir des observations statiques (P1, 11/11/2026)

Chaque observation a été soumise à un test HTTP/BDD (preuve : `preuves/semaine7/p1-catalogue/`).

| ID | Résultat de l'exécution | Anomalie |
|---|---|---|
| OBS-A | **Confirmée** : quantités 0, −1, 3,5 et « abc » acceptées ; doublon accepté ; trajet `EN_ATTENTE` réservable ; aucun CSRF. Une quantité supérieure au stock est bien refusée (TC-23 réussi). | ANO-07, 08, 11, 12 |
| OBS-B | **Confirmée** pour l'annulation par GET sans CSRF et pour l'annulation d'une réservation passée. La règle « 2 h avant le départ » n'est pas une exigence officielle (CA-10 ne refuse que le passé) : elle n'est pas retenue comme anomalie et reste une ambiguïté. | ANO-09, 12 |
| OBS-C | **Confirmée** : l'e-mail est renvoyé sans connexion (HTTP 200). | ANO-22 |
| OBS-D | **Confirmée** : validation, blocage et suppression par GET ; traitement d'une réclamation sans jeton. | ANO-13 |
| OBS-E | **Confirmée** : un compte `BLOQUE` obtient une session. Le PDF de la semaine 3 notait « compte bloqué refusé (essai 13) » sur l'environnement de recette WAMP ; le test automatisé sur `wassalni_test` le contredit. | ANO-21 |
| OBS-F | **Confirmée** : le véhicule d'un autre conducteur est accepté ; le trajet est `VALIDE` dès sa création et visible avant validation. | ANO-16, 17 |
| OBS-G | **Confirmée** : `DateMalformedStringException`, HTTP 500. | ANO-19 |
| OBS-H | **Confirmée** : HTTP 500 sur les deux routes. | ANO-25 |
| OBS-I | **Confirmée** : le compteur affiche 0 pour 6 réclamations en attente. | ANO-24 |
| OBS-J | **Confirmée en semaine 9** par simulation déterministe de deux transactions entrelacées : surréservation. | ANO-38 |
| OBS-05-01 à 04 | Cycle de vie des réclamations | Traité en P2 |

## 7.5 Anomalies confirmées en P1 et en semaines 5 à 9

Champs communs à toutes les fiches de cette section :

- **Environnement :** Windows 11, PHP 8.4.27, Symfony 7.4.23, PHPUnit 12.5.23, MariaDB 10.4.32, base `wassalni_test`.
- **Version Git :** `11fe404` (code applicatif identique à `e2fe62b`).
- **Données :** jeu de données officiel du PDF de la semaine 4, recréé par `tests/Support/CatalogueFixture.php`.
- **Statut :** **Confirmée, ouverte**. Toute correction attend votre accord.
- **Reproduction :** `vendor/bin/phpunit --group ANO-xx --exclude-group aucun`. L'option `--exclude-group aucun` remplace l'exclusion définie dans `phpunit.dist.xml`.
- **Preuves :**
  - `preuves/semaine7/p1-catalogue/02-anomalies-ouvertes.txt` (TestDox) ;
  - `02-anomalies-junit.xml` (JUnit) ;
  - `03-erreurs-500-journal.txt` (exceptions des erreurs 500).

| ID | Titre | Préconditions et étapes | Attendu (critère) | Observé | Sév. / Prio. | Écart PDF | Régression (test) |
|---|---|---|---|---|---|---|---|
| ANO-07 | La réservation HTML accepte une quantité nulle, négative, décimale ou textuelle | U-PASS1 connecté ; T-P4, 4 places, 22 DT ; `POST /reservation/new/{T-P4}` avec `nbPlaces` = 0, −1, 3.5, abc | Refus ; aucune réservation ; 4 places (CA-06, EX-35) | 0 → réservation de 0 place ; **−1 → réservation de −1 place, prix −22 DT, stock 4 → 5** ; 3.5 → 3 places ; abc → 0 place | **S2 / P1** | A-10 | `ReservationAnnulationTest::testTC24NombreDePlacesInvalideVoieHtml` |
| ANO-08 | La voie HTML ne contrôle pas le doublon de réservation | RES-F1 existe ; U-PASS1 réserve T-NOMINAL par `POST /reservation/new/{id}` | Refus ; une seule réservation `CONFIRMEE` (CA-09) | 2 réservations `CONFIRMEE` (la voie JSON refuse : TC-28 bis réussi) | S3 / P2 | A-07 | `testTC28DoublonVoieHtml` |
| ANO-09 | Une réservation dont le trajet est passé peut être annulée | RES-PAST (départ J−5) ; `GET /reservation/annuler/{RES-PAST}` | Refus ; statut `CONFIRMEE` (CA-10) | Statut `ANNULEE` | S3 / P2 | A-08 | `testTC30AnnulationReservationPassee` |
| ANO-10 | Les trajets passés sont proposés et réservables | T-PAST (J−10, `VALIDE`) ; recherche Tunis→Sfax ; réservation par les voies HTML et JSON | Absent de la recherche ; réservation refusée (CA-04) | Affiché dans la recherche ; 1 réservation créée par chaque voie | S3 / P2 | A-03 | `testTC18TrajetPasseAbsent`, `testTC49TrajetPasseVoieHtml`, `testTC49TrajetPasseVoieJson` |
| ANO-11 | La voie HTML permet de réserver un trajet non validé | T-PEND `EN_ATTENTE` ; `POST /reservation/new/{T-PEND}` | Refus (CA-01) | 1 réservation créée (la voie JSON refuse : TC-50 bis réussi) | S3 / P2 | — | `testTC50TrajetNonValideVoieHtml` |
| ANO-12 | Réserver et annuler ne sont pas protégés contre le CSRF ; l'annulation se fait par GET | U-PASS1 connecté ; `GET /reservation/annuler/{RES-F1}` ; `POST /reservation/new/{T-P4}` sans jeton | Requête GET ou sans jeton refusée (CA-15, EX-34) | Annulation exécutée (HTTP 302) ; réservation créée sans jeton | **S2 / P1** | A-06 | `testCA15AnnulationParGetSansJeton`, `testCA15ReservationHtmlSansJeton` |
| ANO-13 | Les actions d'administration modifient les données par GET ou sans jeton CSRF | U-ADMIN connecté ; GET `/admin/trajet/valider/{T-PEND}`, `/admin/utilisateur/bloquer/{U-PASS2}`, `/admin/trajet/supprimer/{T-P1}` ; POST `/admin/reclamation/traiter/{id}` sans jeton | Refus ; données inchangées (TC-38, CA-15) | T-PEND → `VALIDE` (HTTP 302) ; U-PASS2 → `BLOQUE` ; T-P1 supprimé ; réclamation → `TRAITEE` | **S2 / P1** | A-06 | `testTC38ValidationParGet`, `testCA15BlocageParGet`, `testCA15SuppressionTrajetParGet`, `testCA15TraitementReclamationSansJeton` |
| ANO-14 | Les règles de date de publication sont incomplètes | U-COND1 publie : (a) départ le jour même ; (b) arrivée = départ (J+1) | Refus (TC-11, TC-12, CA-02) | Les deux trajets sont créés. Au niveau de l'entité, `GreaterThan("today")` compare à minuit : un départ déjà passé de la journée est accepté (UT-04). Le contrôleur n'appelle de toute façon pas le validateur | S3 / P3 | A-04 | `testTC11DepartLeJourMeme`, `testTC12ArriveeEgaleAuDepart`, `RegleMetierEntitesTest::testDepartPasseDuJourMeme` |
| ANO-15 | Un prix non numérique est accepté et enregistré à 0 | U-COND1 publie avec `prix=abc` | Refus (TC-14, CA-03) | Trajet créé, **prix enregistré 0** | S3 / P2 | — | `testTC14PrixNonNumerique` |
| ANO-16 | Un trajet est validé dès sa publication et visible avant modération | U-COND1 publie un trajet valide ; U-PASS1 recherche Tunis→Sousse | Statut `EN_ATTENTE` ; absent de la recherche (TC-15, TC-16, CA-01) | Statut `VALIDE` ; trajet visible. Le message affiché annonce pourtant « En attente de validation » | S3 / P2 | A-01 | `testTC15StatutInitialEnAttente`, `testTC16TrajetNonValideInvisible` |
| ANO-17 | Un conducteur peut publier avec le véhicule d'un autre conducteur | U-COND2 possède un véhicule ; publie avec `vehicule` = V1 (de U-COND1) | Refus (EX-32, CA-13) | Trajet créé avec V1 | S3 / P2 | — | `testCA13VehiculeDUnAutreConducteur` |
| ANO-18 | La recherche n'a pas de filtre par prix | Recherche Tunis→Sfax avec `prixMax=20` | T-FUT-EARLY (18 DT) seul (TC-20, EX-13) | T-P4 (22 DT) affiché : paramètre ignoré, fonction absente | S4 / P3 | — | `testTC20FiltrePrix` |
| ANO-19 | Une date de recherche invalide provoque une erreur 500 | `GET /trajet/recherche?dateDepart=abc` | Pas d'erreur serveur | HTTP 500, `DateMalformedStringException` (`TrajetController`, ligne 43) | S3 / P3 | — | `testRechercheDateInvalide` |
| ANO-20 | Un compte non vérifié obtient une session authentifiée | U-PASS-NV (`isVerified=false`) se connecte par le formulaire puis ouvre `/reservation/mes-reservations` | Aucune session (TC-03, CA-11, EX-03) | HTTP 200 : page protégée accessible (la connexion redirige seulement vers la page d'attente) | **S2 / P1** | — | `testTC03CompteNonVerifie` |
| ANO-21 | Un compte bloqué peut se connecter | U-ADMIN bloque U-PASS2 (statut `BLOQUE` vérifié en base) ; U-PASS2 se connecte | Connexion refusée à l'étape 3 (TC-04, CA-11, EX-04) | Session obtenue ; étapes 1, 2 et 5 conformes | **S2 / P1** | — | `testTC04BlocagePuisReactivation` |
| ANO-22 | Une route publique divulgue l'e-mail du dernier inscrit | Visiteur anonyme ; `GET /get-last-user-email` et `/check-verification-status` | Accès refusé ; aucune donnée personnelle (TC-45, CA-14, EX-33) | HTTP 200 `{"email":"cat-…-pass-nv@example.test"}` (compte fictif du test) ; HTTP 200 `{"verified":false}` | **S2 / P1** | A-02 | `testTC45FuiteEmailDernierInscrit`, `testCA14StatutVerificationAnonyme` |
| ANO-23 | Les suppressions administratives bloquées par une clé étrangère provoquent une erreur 500 | U-ADMIN : (a) `GET /admin/utilisateur/supprimer/{U-PASS1}` (U-PASS1 a des réservations) ; (b) `GET /admin/trajet/supprimer/{T-P4}` (trajet visé par une réclamation) | Refus explicite ou propagation propre, sans erreur serveur (CA-20) | HTTP 500 dans les deux cas : `ForeignKeyConstraintViolationException` (`reservation.passager_id`, `reclamation.trajet_concerne_id`, toutes les clés sont en `RESTRICT`). L'intégrité de la base est préservée (IT-06). La suppression d'un trajet avec réservations seulement est propre (cascade ORM, IT-05) | S3 / P2 | — | `testCA20SuppressionUtilisateurAvecReservations`, `testCA20SuppressionTrajetViseParUneReclamation` ; `TransactionIntegriteTest::testSuppressionBloqueeParUneReclamation` |
| ANO-24 | Le compteur « Réclamations » du tableau de bord administrateur est faux | 6 réclamations `EN_ATTENTE` en base ; U-ADMIN ouvre `/admin/dashboard` | 6 (CA-16, EX-26) | 0 : la route `admin_dashboard` est déclarée deux fois et la version retenue code ce compteur en dur | S4 / P3 | — (R12) | `testCA16CompteurReclamationsAdmin` |
| ANO-25 | Deux pages renvoient une erreur 500 | `GET /avis/conducteur/{U-COND1}` ; `GET /inscription` | Page affichée | HTTP 500 : argument `$conducteur` non résolu (`use App\Entity\User` manquant) ; `Could not load type "App\Controller\RegistrationType"` | S3 / P2 | — | `testPagesSansErreurServeur` |
| ANO-26 | Un avis peut être déposé avant la fin du trajet | U-PASS1 a RES-F1 sur T-NOMINAL (départ J+7) ; ouvre `/avis/nouveau/{T-NOMINAL}` | Refus (CA-18, EX-21) | Formulaire proposé ; avis enregistré | S3 / P3 | — | `testCA18AvisAvantLaFinDuTrajet` |
| ANO-27 | `AvisType` utilise des options de contraintes dépréciées | Soumettre le formulaire d'avis | Aucune dépréciation | `Since symfony/validator 7.3: Passing an array of options ... NotBlank / Length` ; le contrôle des bornes de la note est conforme (TC-33) | S4 / P3 | — | `testTC33BornesDeLaNote`, `testCA18SecondAvis` |
| ANO-28 | La page de recherche affiche des statistiques codées en dur | U-PASS1 ouvre `/trajet/recherche` | Valeurs calculées à partir des données (EX-29) | « 50k+ » affiché alors que la base compte 24 utilisateurs ; « 10k+ » et « 4.9 » sont aussi écrits en dur dans `search.html.twig` (lignes 755 à 765) | S4 / P4 | A-05 | `testA05StatistiquesDeLaRecherche` |
| ANO-29 | Aucune fonction de modification du profil | Liste des routes (`debug:router`) | Une route permet de modifier ses informations personnelles (EX-05, CA-22) | Aucune route de profil ; seule `vehicule_edit` modifie une ressource | S3 / P3 | A-11 | Preuve statique exécutée : `preuves/semaine3/routes-application.txt` (pas de test automatisable sans la fonction) |
| ANO-30 | Le message d'échec de connexion est en anglais | U-PASS1 se connecte avec un mauvais mot de passe | Message en français, comme le reste de l'interface | « Invalid credentials. » | S4 / P4 | A-09 | `testA09MessageDeConnexionEnFrancais` |
| ANO-31 | Les messages de retour (flash) ne sont pas affichés sur la plupart des pages | U-ADMIN envoie une réponse vide à une réclamation (même constat pour réservation, annulation, actions d'administration) | Le message « Veuillez saisir une réponse » est affiché | Redirection vers la liste sans aucun message : seuls `reclamation/new` et `trajet/new` affichent les messages flash (recherche dans `templates/`) | S3 / P3 | — | `ConnexionAccesTest::testMessageFlashAffiche` ; ST-07 (`message_flash_affiche: false`) |
| ANO-32 | Les horodatages sont enregistrés et affichés en UTC | Créer puis traiter une réclamation à 21 h 58 (heure locale, UTC+1) | Heure locale de l'utilisateur (Tunisie, UTC+1), ou règle de fuseau documentée | « Date traitement 11/11/2026 20:58 » ; `php.ini` `date.timezone = UTC` ; aucun réglage dans l'application. Le contrôle « départ dans le futur » est décalé d'une heure | S4 / P3 | — | Preuve `preuves/semaine5/S05_fuseau-horaire.txt` et capture `S05_ST06_traitement_admin.png` ; règle de fuseau à confirmer par le produit |
| ANO-33 | Les statistiques de « Mes réclamations » ignorent le statut EN_COURS | U-PASS1 possède une réclamation `EN_COURS` ; ouvre `/reclamation/mes-reclamations` | Total = en attente + traitées (+ en cours) (EX-29) | Total 1, en attente 0, traitées 0 | S4 / P4 | OBS-05-04 | `ReclamationTransitionsTest::testStatistiquesAvecEnCours` |
| ANO-34 | Le tableau de bord passager renvoie une erreur 500 | U-PASS1 connecté ; `GET /passager/dashboard` | Page affichée (EX-29) | HTTP 500 : `Variable "stats" does not exist in "dashboard/passager.html.twig" at line 14` (le contrôleur ne transmet que `reservations`) | S3 / P2 | — | `ConnexionAccesTest::testTableauDeBordPassager` ; découverte en exploration EXPL-02 |
| ANO-35 | Le formulaire de véhicule plante : contrainte `Range` mal paramétrée | U-COND2 (sans véhicule) ouvre `/vehicule/nouveau` et soumet les 5 champs valides | Véhicule enregistré (EX-06, CA-21) ; ensuite, publication possible (EX-07) | **HTTP 500** : `Error: Unknown named parameter $message` (`Vehicule.php` ligne 34 : `#[Assert\Range(..., message: ...)]`, paramètre inexistant pour `Range`). Toute validation d'un véhicule échoue : ajout et modification impossibles | **S2 / P1** | — | `PublicationRechercheTest::testCA21AjoutVehicule` ; découverte par les tests unitaires UT-05 et UT-06 |
| ANO-36 | Des champs de formulaire n'ont pas de nom accessible (axe : gravité critique) | Audit axe-core (WCAG 2.0/2.1 A et AA) de `/trajet/recherche`, `/reclamation/nouvelle`, `/reservation/mes-reservations` | Chaque contrôle a un libellé associé (WCAG 1.3.1, 4.1.2) | `select-name` : 3 listes sur la recherche (`#villeDepart`…), 3 sur la réclamation (`#reclamation_sujet`…), 1 sur « Mes réservations » (`#statusFilter`) ; `label` : `#dateDepart` sans libellé associé | S3 / P3 | — | `semaine9-accessibilite-securite.spec.js` (pages recherche, nouvelle-reclamation, mes-reservations) ; `preuves/semaine9/S09_accessibilite-axe.json` |
| ANO-37 | Contraste de couleurs insuffisant | Audit axe-core de `/connexion` et `/trajet/recherche` | Contraste ≥ 4,5:1 (WCAG 1.4.3, niveau AA) | `color-contrast` (gravité *serious*) : 2 éléments sur la connexion, 15 sur la recherche (libellés du formulaire) | S4 / P4 | — | Même spécification (constat, la règle n'est pas bloquante) |
| ANO-38 | Surréservation possible en cas de réservations simultanées (OBS-J) | Trajet à 1 place ; deux passagers ; l'algorithme de `newAjax` est rejoué dans deux connexions MariaDB distinctes, entrelacées (A lit, B lit, A écrit, B écrit) | Une seule réservation confirmée ; stock jamais incohérent (R1, EX-35, CA-06) | Les deux contrôles passent ; **2 réservations confirmées pour 1 place** ; stock final 0 (mise à jour perdue). Aucun verrou (`PESSIMISTIC_WRITE`, version optimiste ou décrément conditionnel en SQL) | S2 / P2 | R1 | `ConcurrenceReservationTest::testDerniereplaceEntrelacee` (simulation déterministe ; la concurrence HTTP réelle est impossible avec `php -S` sous Windows) |

### Cas conformes vérifiés en P1

Ces cas ont été exécutés avec succès et font partie de la régression par défaut :

- **Groupe C (réservation et annulation) :** TC-22, TC-23, TC-25, TC-28 bis (JSON), TC-29, TC-50 bis (JSON), CA-10 (double annulation), CA-12 (conducteur), CA-13 (annulation par un tiers).
- **Groupes A et B (publication et recherche) :** TC-09, TC-10, TC-13, TC-14 (0, −1, 0,01 et 15), TC-19, TC-20 b (minuscules), CA-04 (filtre de date), CA-05 (tri), CA-21.
- **Groupe D (accès) :** TC-41, TC-51, CA-12 (rôles et anonyme), CA-13 (véhicule), CA-20 (suppression d'un trajet avec réservations).
- **Groupe E (avis et réclamations) :** TC-36, CA-18 (avis sans réservation).


## 7.6 Correspondance avec les écarts A-xx des PDF des semaines 2 et 3

| Écart PDF | Intitulé (PDF) | État au 11/11/2026 | Anomalie |
|---|---|---|---|
| A-01 | Trajets validés dès leur création | Confirmé par test | ANO-16 |
| A-02 | `/get-last-user-email` sans connexion | Confirmé par test | ANO-22 |
| A-03 | Trajets passés proposés et réservables | Confirmé par test | ANO-10 |
| A-04 | Pas de champ d'heure ; arrivée le même jour | Confirmé par test | ANO-14 |
| A-05 | Statistiques fixes sur la page de recherche | Confirmé par test | ANO-28 |
| A-06 | Actions de modification par GET, sans CSRF | Confirmé par test | ANO-12, ANO-13 |
| A-07 | Doublon par `/reservation/new/{id}` | Confirmé par test | ANO-08 |
| A-08 | Annulation d'une réservation passée | Confirmé par test | ANO-09 |
| A-09 | Message « Invalid credentials » en anglais | Confirmé par test | ANO-30 |
| A-10 | Tables MyISAM sans clé étrangère (recette WAMP) | Hors base de test : `wassalni_test` est en InnoDB avec clés étrangères (`preuves/semaine2/moteurs-wassalni_test.txt`) ; la valeur −1 observée en recette est reproduite par ANO-07 | ANO-07 (conséquence) |
| A-11 | Aucune route de modification du profil | Confirmé (liste des routes) | ANO-29 |
| A-12 | Migrations en échec sur une base vide | Corrigé dans `fcbb7e4` (semaine 2) ; 4 migrations sur 4 appliquées | — |
| A-13 | `ReservationService` n'est appelé nulle part | Toujours vrai : les 7 tests de service ne couvrent pas le chemin réel ; la couverture réelle est assurée par les tests HTTP | Observation de conception |

Les captures `essai7` et `essai9` citées dans les PDF ne figurent pas dans `preuves/semaine1/`. La capture `essai13` montre un e-mail mal saisi (`test@tes.com`) refusé avec « Invalid credentials », et non un compte bloqué : la mention « compte bloqué refusé (essai 13) » du PDF de la semaine 3 n'est pas étayée par cette capture, et le test TC-04 démontre l'inverse (ANO-21).
