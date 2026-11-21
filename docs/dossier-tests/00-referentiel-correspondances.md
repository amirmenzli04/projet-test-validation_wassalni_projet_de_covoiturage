# 0. Référentiel officiel et correspondances

- **Version :** 1.0, 11 novembre 2026
- **Décision du responsable de campagne :** les identifiants des PDF des semaines 1 à 4 font foi.

## 0.1 Documents de référence

| Semaine | Livrable officiel | Identifiants définis |
|---|---|---|
| 1 | [`docs/semaine1-fiche-risques.pdf`](../semaine1-fiche-risques.pdf) | Risques **R1 à R21** (probabilité × impact) ; constats de code **K1 à K14** |
| 2 | [`docs/semaine2-plan-de-test.pdf`](../semaine2-plan-de-test.pdf) | Plan de test ; écarts **A-01 à A-13** |
| 3 | `docs/semaine3  – Exigences, critères d'acceptation et traçabilité.pdf` | Exigences **EX-01 à EX-35** ; critères **CA-01 à CA-22** |
| 4 | `docs/semaine4 – Conception des cas de test.pdf` (**version officielle**) | Cas **TC-xx** (28 détaillés) ; données U-*, V*, T-*, RES-* |

Les fichiers Markdown `01` à `06` de ce dossier constituent un **dossier de travail** antérieur. Ils emploient leurs propres identifiants (`R-01` à `R-09`, `EX-01` à `EX-14`, `CT-01` à `CT-31`). **Attention : `EX-xx` n'a pas la même signification dans les deux référentiels.** Dans les documents de campagne (07, 08, 09…), `EX-xx` désigne toujours l'exigence du PDF de la semaine 3.

Le PDF `docs/dossier-tests/Semaine-4-Conception-des-cas-de-test.pdf` (identifiants `CT`) est conservé comme **synthèse annexe** ; il ne remplace pas la version officielle.

## 0.2 Risques : Markdown → PDF de la semaine 1

| Markdown | Intitulé (Markdown) | Risques PDF |
|---|---|---|
| R-01 | Sur-réservation, stock incohérent | R1, R8 |
| R-02 | Accès à la ressource d'un autre utilisateur | R2, R3 |
| R-03 | Valeur de réservation invalide | R1, R7 |
| R-04 | Statut incohérent (trajet, réclamation) | R4, R17 |
| R-05 | Réclamation invalide ou mal rattachée | R13 |
| R-06 | Prix ou places perdus lors d'un échec | R7, R15 |
| R-07 | Recherche affichant des trajets passés ou complets | R17 |
| R-08 | Erreur d'interface dans les parcours critiques | — (couvert en semaine 9) |
| R-09 | Performance et accessibilité | R14 |

## 0.3 Exigences : Markdown → PDF de la semaine 3

| Markdown | Exigences PDF | Critères PDF |
|---|---|---|
| EX-01 (droits par rôle) | EX-31, EX-32 | CA-12, CA-13 |
| EX-02 (recherche) | EX-13, EX-14 | CA-04 |
| EX-03 (publication) | EX-07 à EX-10 | CA-02, CA-03, CA-21 |
| EX-04 (réservation valide) | EX-17, EX-18 | CA-07, CA-08 |
| EX-05 (bornes des places) | EX-16, EX-35 | CA-06 |
| EX-06 (unicité) | EX-16 | CA-09 |
| EX-07 (annulation par le propriétaire) | EX-19, EX-32 | CA-10, CA-13 |
| EX-08 (délai de 2 h) | — (ambiguïté) | CA-10 ne refuse que la réservation passée |
| EX-09 (création de réclamation) | EX-24 | CA-17 |
| EX-10 (propriété des réclamations) | EX-25, EX-32 | CA-13 |
| EX-11 (statuts de réclamation) | EX-25 (ambiguïté sur `EN_COURS`) | — |
| EX-12 (intégrité après échec) | EX-35 | CA-20 |
| EX-13 (route JSON) | EX-16 à EX-18, EX-33 | CA-06, CA-09, CA-14 |
| EX-14 (devise, fuseau horaire) | EX-09 (ambiguïté) | — |

## 0.4 Cas de test : CT (Markdown) → TC/CA (PDF) et test automatisé

| CT | Objet | Cas officiel | Test automatisé |
|---|---|---|---|
| CT-01 | Accès anonyme | CA-12 | `AnonymousAccessTest`, `ConnexionAccesTest::testCA12RolesEtAnonyme` |
| CT-02 | Passager sur l'administration | TC-41 | `ConnexionAccesTest::testTC41PassagerSurPagesAdmin` |
| CT-03 | Conducteur hors de son rôle | CA-12 | `ReservationAnnulationTest::testCA12ConducteurNePeutPasReserver` |
| CT-04 | Administrateur sur un formulaire passager | — | Non détaillé dans le PDF (hors minimum) |
| CT-05 | Recherche : statut et places | TC-19 | `PublicationRechercheTest::testTC19FiltresStatutEtPlaces` |
| CT-06 | Filtres de villes | TC-19, TC-20 b | `testTC19…`, `testTC20VillesEnMinuscules` |
| CT-07 | Filtre de date | CA-04 | `testCA04FiltreDate`, `testRechercheDateInvalide` |
| CT-08 | Publication nominale | TC-09 | `testTC09PublicationNominale` |
| CT-09 | Publication invalide | TC-10 à TC-14, CA-21 | `testTC10…` à `testTC14…`, `testCA21…` |
| CT-10 | Réserver 2 places | TC-25 | `ReservationAnnulationTest::testTC25CalculDuTotal` |
| CT-11 | Réserver toutes les places | TC-22 | `testTC22ReserverToutesLesPlaces` |
| CT-12 | 0 place, valeur négative | TC-24 | `testTC24NombreDePlacesInvalideVoieHtml`, `ReservationApiTest` |
| CT-13 | Plus que le stock | TC-23 | `testTC23DemandeSuperieureAuStock` |
| CT-14 | Doublon | TC-28 | `testTC28DoublonVoieHtml`, `testTC28DoublonVoieJson` |
| CT-15 | Annulation par le propriétaire | TC-29 | `testTC29AnnulerPuisReserverDeNouveau`, `testCA10DoubleAnnulation` |
| CT-16 | Annulation par un autre passager | CA-13 | `testCA13AnnulationParUnAutrePassager` |
| CT-17 | Délai d'annulation | TC-30 | `testTC30AnnulationReservationPassee` (la règle des 2 h reste une ambiguïté) |
| CT-18 | Créer une réclamation | EX-24 | `ReclamationWorkflowTest::testAuthenticatedUserCanCreateAComplaint` |
| CT-19 | Message vide | TC-36 | `AvisReclamationTest::testTC36BornesDeLaReclamation` |
| CT-20 | Message de 9 ou 10 caractères | TC-36 | `testTC36…`, `ReclamationValidationTest` |
| CT-21 | Réclamation d'un autre utilisateur | CA-13 | `ReclamationWorkflowTest::testOnlyTheComplaintOwnerCanReadItsDetails` |
| CT-22 | Réponse de l'administrateur | EX-25 | `ReclamationWorkflowTest::testAdministratorCanProcessAndPersistAComplaint` |
| CT-23 | Statut `EN_COURS` | — (ambiguïté) | Bloqué (règle produit absente) |
| CT-24 | Persistance de la réservation | EX-18, EX-35 | `ReservationPersistenceTest` (semaine 7) |
| CT-25 | Échec de persistance, rollback | EX-35, CA-20 | Semaine 7 |
| CT-26 à CT-28 | Route JSON | CA-06, CA-09, CA-14 | `ReservationApiTest`, `testTC28DoublonVoieJson`, `testTC49TrajetPasseVoieJson`, `testTC50TrajetNonValideVoieJson`, collection Postman (semaine 8) |
| CT-29 | Parcours dans un navigateur | — | Playwright (semaine 9) |
| CT-30 | Accessibilité | R14 (partiel) | axe-core (semaine 9) |
| CT-31 | Performance | R14 | k6 (semaine 9) |

## 0.5 Cas TC listés mais non détaillés dans le PDF de la semaine 4

Le PDF officiel indique que les cas TC-01, 02, 05, 06, 07, 08, 21, 26, 27, 31, 32, 34, 35, 37, 39, 40, 42, 43, 44, 46, 47 et 48 « restent à détailler » et ne sont pas nécessaires au minimum de 15 cas. Ils ne sont pas inventés ici. Les exigences correspondantes sont tout de même couvertes par des tests de critère (CA-xx) lorsque c'était possible ; la [matrice de traçabilité](09-matrice-tracabilite.md) donne le détail.
