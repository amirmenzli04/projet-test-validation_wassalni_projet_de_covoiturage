# 7. Registre des anomalies

- **Version :** 1.0, 11 novembre 2026 (phase P0)
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

## 7.4 Observations statiques à confirmer (phases P1 à P3)

Ces points proviennent de la lecture du code et **ne sont pas des anomalies confirmées**.

| ID | Constat | Lien avec la semaine 3 (PDF) | Phase |
|---|---|---|---|
| OBS-A | `POST /reservation/new/{id}` : ni `nbPlaces > 0`, ni statut `VALIDE`, ni doublon, ni CSRF | EX-16, CA-06/09 | P1 |
| OBS-B | `/reservation/annuler/{id}` en GET sans CSRF ; règle des 2 h non appliquée | CA-10, CA-13 | P1 |
| OBS-C | `/get-last-user-email` et `/check-verification-status` publics : fuite de l'e-mail du dernier inscrit | CA-14, TC-45 | P1 |
| OBS-D | Actions d'administration par GET sans CSRF ; traitement de réclamation sans CSRF | CA-15, TC-38 | P1 |
| OBS-E | Compte `BLOQUE` toujours autorisé à se connecter | CA-11, TC-04 | P1 |
| OBS-F | Publication avec le véhicule d'un autre conducteur ; statut `VALIDE` alors que le message dit « en attente » | CA-01, TC-50 | P1 |
| OBS-G | Date invalide dans la recherche → exception probable (500) | CA-04 | P1 |
| OBS-H | `use` manquants : `/avis/conducteur/{id}` et `/inscription` → erreur 500 probable | — | P1 |
| OBS-I | Route `admin_dashboard` déclarée deux fois ; compteur de réclamations codé en dur à 0 | CA-16 | P1 |
| OBS-J | Aucun verrou sur le stock lors de réservations concurrentes | R-01 | P1 / P4 |
| OBS-05-01 à 04 | Cycle de vie des réclamations (voir [05 §5.6](05-etats-exploration-anomalies.md)) | EX-11 | P2 |
