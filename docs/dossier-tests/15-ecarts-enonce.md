# 15. Écarts entre le référentiel de l'énoncé (RéclaGest) et WASSALNI

- **Version :** 1.1, 21 novembre 2026 — mise à jour après l'ajout de l'API REST des réclamations (révision `1ed214b`)
- **Révision analysée :** `cc50095` (code applicatif identique à `e2fe62b`)
- **Base :** énoncé du mini-projet, §1.1 (fonctionnalités à tester) et §1.2 (règles métier de référence)

## 15.1 Objet

L'énoncé autorise l'utilisation d'une application existante, à condition d'**adapter les règles à ses exigences réelles et de documenter les différences**. Ce document compare, point par point, le référentiel RéclaGest avec l'application testée, WASSALNI (covoiturage avec module de réclamations), et indique comment chaque écart a été traité dans la campagne.

Légende :

- **Couvert** : la fonctionnalité existe dans WASSALNI et elle est testée ;
- **Adapté** : une fonctionnalité équivalente existe, avec des règles différentes, testées selon les exigences réelles (EX-xx du PDF semaine 3) ;
- **Partiel** : seule une partie de la fonctionnalité existe ;
- **Absent** : la fonctionnalité n'existe pas ; aucun test n'est inventé, l'écart est une limite de la campagne.

## 15.2 Fonctionnalités à tester (énoncé §1.1)

| Module de l'énoncé | Attendu | Dans WASSALNI | Statut | Tests et références |
|---|---|---|---|---|
| Authentification | Connexion, déconnexion, identifiants invalides | `/connexion`, `/deconnexion`, formulaire Symfony ; contrôle du compte activé ou bloqué | **Couvert** | TC-03, TC-04, TC-51, `ConnexionAccesTest` ; EX-03, EX-04 ; ANO-20, ANO-21 |
| Gestion des utilisateurs | Création, consultation, contrôle des rôles | Inscription en 5 étapes ; liste admin ; activer, bloquer, supprimer ; rôles passager, conducteur, admin | **Partiel** | CA-12, TC-41, EXPL-02 (4 rôles × 7 URL), CA-15, CA-20 ; inscription non exécutée (EX-01, EX-02) |
| Réclamations | Création, modification, consultation, clôture | Création et consultation par l'auteur ; réponse de l'administrateur qui passe la réclamation à `TRAITEE` ; **aucune modification** par l'utilisateur, **aucune clôture distincte** | **Partiel** | ST-01 à ST-07, TC-36, `ReclamationWorkflowTest`, `ReclamationValidationTest` ; EX-24, EX-25 |
| Affectation | Attribution d'une réclamation à un agent | Pas de rôle « agent » ni d'affectation : tout administrateur traite toute réclamation | **Absent** | — |
| Suivi | Historique des changements et des statuts | Statut courant, dates de création et de traitement, réponse ; statut `EN_COURS` désormais atteignable par l'API ; réclamation traitée close (409). **Pas d'historique** des changements | **Partiel** | ST-06, ST-09, T-01 à T-06, API-11, API-12 ; ANO-33 |
| Recherche | Filtrage par statut, priorité et date | Recherche de **trajets** (ville, destination, date) ; **filtrage des réclamations par statut et par date** dans l'API (`?statut=`, `?dateMin=`, `?dateMax=`) ; pas de priorité | **Partiel** | TC-19, TC-20, CA-04, CA-05, API-03, API-04, API-05 ; ANO-18, ANO-19 |
| API REST | Échanges JSON, codes HTTP, gestion des erreurs | **API REST des réclamations** (`/api/reclamations` : GET, GET/{id}, POST, PATCH ; 200, 201 + `Location`, 400, 401, 403, 404, 405, 409, 415, 422), en plus des 4 routes JSON existantes | **Couvert** | API-01 à API-14 (PHPUnit), requêtes Newman 11 à 26, [11 §11.5](11-semaine8-api.md) |
| Persistance | Enregistrement, intégrité, cohérence | Doctrine ORM et MariaDB, 4 migrations, clés étrangères | **Couvert** | IT-01 à IT-07, 45 tests HTTP/BDD avec relecture en base ; EX-35 ; ANO-07, ANO-38 |

## 15.3 Règles métier de référence (énoncé §1.2)

| # | Règle de l'énoncé | Règle réelle dans WASSALNI | Statut | Traitement dans la campagne |
|---|---|---|---|---|
| R-E1 | Une réclamation a un identifiant, un titre, une description, une catégorie, une priorité, un statut et une date de création | Identifiant, **sujet** (liste fermée de 8 valeurs, qui joue le rôle de titre et de catégorie), **message** (description), statut, date de création ; en plus : date de traitement, réponse, trajet et utilisateur concernés. **Pas de priorité** | **Adapté** | Champs vérifiés par relecture SQL (ST-01) ; priorité hors périmètre |
| R-E2 | Titre obligatoire, 5 à 100 caractères | `sujet` : `NotBlank` et `Length(5, 100)` au niveau de l'entité ; dans le formulaire, choix dans une liste fermée | **Adapté** | Valeurs limites 4 et 5 caractères testées au niveau unitaire (`ReclamationValidationTest`) ; dans l'interface, une valeur hors liste est refusée |
| R-E3 | Description obligatoire, 10 à 1 000 caractères | `message` : `NotBlank` et minimum de 10 caractères ; **aucun maximum** (colonne `TEXT`) | **Partiel** | Bornes 9 et 10 testées (TC-36, ST-02, ST-03, unitaire) ; la borne haute de l'énoncé n'existe pas (écart E-03) |
| R-E4 | Création au statut `NOUVELLE` | Création au statut `EN_ATTENTE` | **Adapté** | ST-01 (relecture SQL) |
| R-E5 | Transitions `NOUVELLE → EN_COURS → RESOLUE → CLOTUREE` | Interface HTML : `EN_ATTENTE → TRAITEE` (traitement direct, validé le 11/11/2026). API : `EN_ATTENTE → EN_COURS → TRAITEE` ou `EN_ATTENTE → TRAITEE` ; tout retour en arrière est refusé (409) | **Adapté** | T-01 à T-06, ST-07 à ST-09 ([05](05-etats-exploration-anomalies.md)) ; API-11 (cycle complet), API-12 (transitions interdites), API-13 |
| R-E6 | Une réclamation clôturée n'est plus modifiable par un utilisateur ordinaire | L'utilisateur ne peut jamais modifier sa réclamation ; par l'API, une réclamation `TRAITEE` n'est plus modifiable, même par l'administrateur (409). Par l'interface HTML, l'administrateur peut encore écraser sa réponse (T-03) | **Adapté** | API-10, API-12 ; T-03 |
| R-E7 | Le client ne voit que ses réclamations ; l'agent traite celles qui lui sont accessibles ; l'administrateur gère utilisateurs et affectations | Le passager ne voit que les siennes (403 sinon) ; pas d'agent ; l'administrateur traite toutes les réclamations et gère les utilisateurs | **Partiel** | ST-04, CA-13, ST-08 (traitement par un non-administrateur : 403), CA-12 |

## 15.4 Compensation : ce qui a été testé à la place

Le cycle de vie des réclamations de WASSALNI est plus court que celui de l'énoncé (2 états contre 4). Pour exercer réellement la technique des **transitions d'état** (semaine 5), la campagne a aussi modélisé et testé deux autres cycles de vie plus riches :

| Objet | États | Tests |
|---|---|---|
| Trajet | `EN_ATTENTE → VALIDE`, suppression | TC-15, TC-16, TC-38, TC-50 ; ANO-11, ANO-16 |
| Réservation | `CONFIRMEE → ANNULEE`, annulation répétée, trajet passé | TC-29, TC-30, CA-10, ST-10 ; ANO-09 |

Avant le 21/11/2026, l'absence d'API REST était compensée par un test exhaustif des routes JSON existantes : méthodes autorisées et refusées (405), JSON invalide, authentification, codes 400, 403 et 404, cohérence en base.

## 15.5 Liste des écarts

| Id | Écart | Conséquence pour la campagne |
|---|---|---|
| E-01 | Pas de champ priorité | Recherche par priorité non testable |
| E-02 | Titre remplacé par une liste fermée de sujets | Bornes 5 à 100 vérifiées seulement au niveau de l'entité |
| E-03 | Pas de longueur maximale pour la description | Borne de 1 000 caractères non applicable |
| E-04 | Cycle de vie à 3 états (dont `EN_COURS`, atteignable par l'API) au lieu de 4 | Transitions testées sur le cycle réel (HTML et API), et sur les trajets et réservations |
| E-05 | Pas de rôle agent ni d'affectation | Module « Affectation » hors périmètre |
| E-06 | Pas d'historique des statuts | Seuls le statut courant et les dates sont vérifiés |
| E-07 | Filtre des réclamations par statut et date uniquement par l'API, sans priorité | Filtres testés (API-03 à API-05) ; priorité non testable (E-01) |
| E-08 | ~~Pas d'API REST générale~~ — **résolu le 21/11/2026** : API REST des réclamations | Volet API étendu : 35 requêtes Newman, 14 tests PHPUnit |

Ces écarts sont des **différences de référentiel**, et non des anomalies : les anomalies ne sont ouvertes que par rapport aux exigences réelles de WASSALNI (EX-01 à EX-35). Ils constituent toutefois des **limites de la campagne**, reprises dans le bilan ([14](14-semaine10-bilan-decision.md)).
