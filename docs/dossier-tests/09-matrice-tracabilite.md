# 9. Matrice de traçabilité finale — Exigence → Risque → Cas de test → Résultat → Preuve

- **Version :** 2.1, 21 novembre 2026 — tests de l'API REST ajoutés aux exigences concernées, accessibilité corrigée (révision `1ed214b`)
- **Version 2.0 :** 21 novembre 2026 — colonne « Résultat 21/11 » ajoutée après les corrections S2 (révision `72cff37`)
- **Version précédente :** 1.0, 11 novembre 2026 (campagne finale, révision `cc50095`)
- **Référentiel :** exigences EX et critères CA (PDF semaine 3), risques R (PDF semaine 1), cas TC (PDF semaine 4). Les tests automatisés sont nommés par leur identifiant TC, CA, ST, UT ou IT.
- **Statuts :** **Réussi** (tous les cas conformes), **Partiel** (une partie des cas conformes, avec anomalie sur le reste), **Échoué** (exigence non satisfaite), **Non exécuté**.
- **Preuves :**
  - P1 = `preuves/semaine7/p1-catalogue/` ;
  - S5 = `preuves/semaine5/` ;
  - S6 = `preuves/semaine6/campagne-finale/` ;
  - S7 = `preuves/semaine7/campagne-finale/` ;
  - S8 = `preuves/semaine8/` ;
  - S9 = `preuves/semaine9/` ;
  - S10 = `preuves/semaine10/campagne-finale/` (rejeu final du 11/11) ;
  - S10b = `preuves/semaine10/apres-corrections/` (rejeu du 21/11 après corrections S2) ;
  - S10c = `preuves/semaine10/apres-ameliorations/` (rejeu du 21/11 après accessibilité et API REST).

| Exigence | Risque | Critère | Cas et tests exécutés | Résultat 11/11 | Résultat 21/11 | Anomalies | Preuve |
|---|---|---|---|---|---|---|---|
| EX-01 Inscription en 5 étapes | R11 | CA-19 | — (assistant d'inscription non automatisé) | **Non exécuté** | Inchangé | — | — |
| EX-02 E-mail de confirmation valable 24 h | R5, R11 | CA-19 | — (transport de mail `null://null` en test) | **Non exécuté** | Inchangé | — | — |
| EX-03 Seuls les comptes activés se connectent | R5 | CA-11 | TC-03, TC-51 | **Échoué** (TC-51 réussi) | **Réussi** (ANO-20 corrigée) | ANO-20 | P1, S10, S10b |
| EX-04 Un compte bloqué ne se connecte pas | R5 | CA-11 | TC-04 | **Échoué** | **Réussi** (ANO-21 corrigée) | ANO-21 | P1, S10, S10b |
| EX-05 Modification du profil | — | CA-22 | Liste des routes | **Échoué** (fonction absente) | Inchangé | ANO-29 | `preuves/semaine3/routes-application.txt` |
| EX-06 Gestion des véhicules | R19 | CA-21 | CA-21 ajout, CA-13 véhicule d'autrui, UT-05, UT-06 | **Échoué** (CA-13 réussi) | **Réussi** (ANO-35 corrigée) | ANO-35 | P1, S6, S10, S10b |
| EX-07 Véhicule requis pour publier | — | CA-21 | CA-21 conducteur sans véhicule | **Réussi** | Inchangé | — | P1 |
| EX-08 Publication d'un trajet | R6 | CA-02 | TC-09 | **Partiel** (heures non saisissables, A-04) | Inchangé | ANO-14 | P1 |
| EX-09 Départ futur, arrivée après départ | R6 | CA-02 | TC-10, TC-11, TC-12, TC-13, UT-01, UT-03, UT-04 | **Échoué** (TC-10, 13, UT-01, 03 réussis) | Inchangé | ANO-14 | P1, S6 |
| EX-10 Prix strictement positif | R7 | CA-03 | TC-14 (5 valeurs), UT-02 | **Échoué** (0, −1, 0,01, 15 conformes) | Inchangé | ANO-15 | P1, S6 |
| EX-11 Trajet créé « en attente » | R4 | CA-01 | TC-15 | **Échoué** | Inchangé | ANO-16 | P1 |
| EX-12 Visible après validation seulement | R4 | CA-01 | TC-16, TC-50, TC-50 bis | **Échoué** (TC-50 bis réussi) | Inchangé | ANO-11, ANO-16 | P1 |
| EX-13 Filtres ville, date, prix | R18 | CA-04 | TC-19, TC-20 a/b, CA-04 date, recherche date invalide | **Partiel** | Inchangé | ANO-18, ANO-19 | P1 |
| EX-14 Seulement trajets validés avec places | R17 | CA-04 | TC-18, TC-19, TC-49, TC-49 bis | **Partiel** (TC-19 réussi) | Inchangé | ANO-10 | P1 |
| EX-15 Tri chronologique | — | CA-05 | CA-05 | **Réussi** | Inchangé | — | P1 |
| EX-16 Réserver 1 à n places | R1, R16 | CA-06, CA-09 | TC-22, TC-23, TC-24, TC-28, TC-28 bis, Newman 05–09, `ReservationApiTest` | **Échoué** (voie JSON conforme) | **Partiel** (ANO-07 corrigée ; ANO-08, 11 ouvertes) | ANO-07, 08, 11 | P1, S8, S10b |
| EX-17 Prix total = places × prix | R7 | CA-07 | TC-22 (88 DT), TC-25, UT-10 | **Réussi** | Inchangé | — | P1, S6 |
| EX-18 Confirmation immédiate et décrément | R1 | CA-08 | TC-22, IT-04, CT-29 (Playwright), Newman 05b | **Réussi** | Inchangé | — | P1, S7, S8, S9 |
| EX-19 Annulation des réservations futures, places restituées | R8 | CA-10 | TC-29, TC-30, CA-10 double annulation, ST-10 | **Partiel** | Inchangé | ANO-09 | P1, S5 |
| EX-20 Consulter ses réservations | — | — | CT-29 étape 5, ST-10 | **Réussi** | Inchangé | — | S5, S9 |
| EX-21 Avis 1 à 5 après le trajet | R9 | CA-18 | TC-33, UT-07, CA-18 avant la fin du trajet | **Partiel** (bornes conformes) | Inchangé | ANO-26, ANO-27 | P1, S6 |
| EX-22 Un seul avis par trajet | R9 | CA-18 | CA-18 second avis, CA-18 sans réservation | **Réussi** (dépréciation ANO-27) | Inchangé | ANO-27 | P1 |
| EX-23 Note moyenne et avis du conducteur | — | — | UT-09, `/avis/conducteur/{id}` | **Partiel** (calcul conforme, page en erreur 500) | Inchangé | ANO-25 | S6, P1 |
| EX-24 Dépôt de réclamation (message ≥ 10) | R13 | CA-17 | TC-36, ST-01 à ST-03, `ReclamationWorkflowTest`, API-07, API-08, API-09 | **Réussi** | Inchangé | — | P1, S5, S10c |
| EX-25 Réponse admin et suivi du statut | — | — | ST-05, ST-06, ST-07, ST-09, T-01 à T-06, API-03, API-11 à API-13 | **Réussi** pour la règle validée (traitement direct) ; retour utilisateur absent | Inchangé | ANO-31, ANO-33 | S5, S10c |
| EX-26 Tableau de bord admin | R12 | CA-16 | CA-16 compteur réclamations | **Échoué** | Inchangé | ANO-24 | P1 |
| EX-27 Gérer les utilisateurs | R20 | CA-15, CA-20 | TC-04 (blocage), CA-15 blocage par GET, CA-20 suppression | **Partiel** (fonction présente, non protégée) | Inchangé | ANO-13, ANO-23 | P1 |
| EX-28 Valider ou supprimer les trajets | R20, R15 | CA-15, CA-20 | TC-38, CA-15 suppression, CA-20, IT-05, IT-06 | **Partiel** | Inchangé | ANO-13, ANO-23 | P1, S7 |
| EX-29 Statistiques exactes des tableaux de bord | R12 | CA-16 | `/passager/dashboard`, A-05, T-05 | **Échoué** | Inchangé | ANO-28, ANO-33, ANO-34 | P1, S5 |
| EX-30 E-mails envoyés | R11 | CA-19 | — (`null://null`) | **Non exécuté** | Inchangé | — | — |
| EX-31 Accès limité aux pages de son rôle | R2 | CA-12 | TC-41, CA-12, EXPL-02 (matrice 4 rôles × 7 URL), ST-08, API-10 | **Réussi** | Inchangé | — | P1, S5, S10c |
| EX-32 Pas d'accès aux ressources d'autrui | R3 | CA-13 | CA-13 (réservation, véhicule), ST-04, `ReclamationWorkflowTest`, publication avec le véhicule d'autrui, API-02, API-06 | **Partiel** | Inchangé | ANO-17 | P1, S5, S10c |
| EX-33 Aucune donnée personnelle sans connexion | R21 | CA-14 | TC-45, CA-14, Newman 02b et 02c, API-01, API-02 | **Échoué** | **Réussi** (ANO-22 corrigée) | ANO-22 | P1, S8, S10b, S10c |
| EX-34 Modifications par POST avec jeton CSRF | R20 | CA-15 | CA-15 (réservation, annulation, admin), TC-38 | **Échoué** | **Réussi** (ANO-12, 13 corrigées) | ANO-12, ANO-13 | P1, S10b |
| EX-35 Cohérence des données | R1, R15 | CA-06, CA-20 | TC-24, IT-01 à IT-07, CA-20 | **Échoué** (transactions et intégrité de la base conformes) | **Réussi** (ANO-07, 38 corrigées) | ANO-07, ANO-38 | P1, S7, S9, S10b |

## Synthèse

| Statut | 11/11 (`cc50095`) | **21/11 (`72cff37`)** | Exigences au 21/11 |
|---|---|---|---|
| Réussi | 9 | **15** | EX-03, 04, 06, 07, 15, 17, 18, 20, 22, 24, 25, 31, 33, 34, 35 |
| Partiel | 9 | **10** | EX-08, 13, 14, 16, 19, 21, 23, 27, 28, 32 |
| Échoué | 14 | **7** | EX-05, 09, 10, 11, 12, 26, 29 |
| Non exécuté | 3 | 3 | EX-01, 02, 30 (inscription et e-mails) |

Total : **35 exigences**. Les 35 exigences sont rattachées à au moins un critère ou un test. Les 3 exigences non exécutées concernent l'inscription et l'envoi d'e-mails, qui demandent un transport de mail réel (risque R11, résiduel).

## Exigences non fonctionnelles (semaine 9)

| Domaine | Test | Résultat | Preuve |
|---|---|---|---|
| Performance (R14) | k6, 5 VU, 30 s | Réussi : 0 % d'échec, p95 217,0 ms (11/11) ; p95 392,8 ms, seuils respectés (21/11) | S10, S10b |
| Accessibilité | axe-core sur 4 pages, navigation clavier | 11/11 : échoué, 3 pages avec violations critiques (ANO-36), contraste (ANO-37) ; clavier conforme. **21/11 : réussi**, 0 violation critique sur les 4 pages (ANO-36 corrigée) ; contraste insuffisant sur la page de connexion (ANO-37, S4, gravité « serious ») | S9, S10, S10c |
| Sécurité élémentaire | En-têtes, cookie, page d'erreur, audits de dépendances | Partiel : cookie et page d'erreur conformes, `composer audit` propre ; en-têtes absents (REC-01) | S9 |
| Concurrence (R1) | IT-07, entrelacement de deux transactions | 11/11 : échoué, surréservation (ANO-38). **21/11 : réussi**, une seule réservation, 0 place restante | S9, S10b |

## API REST des réclamations (21/11/2026)

| Test | Exigence | Résultat | Preuve |
|---|---|---|---|
| API-01 Anonyme : 401 | EX-33 | Réussi | S10c |
| API-02 Liste limitée au propriétaire, sans e-mail | EX-32, EX-33 | Réussi | S10c |
| API-03 Liste et filtre par statut (administrateur) | EX-25 | Réussi | S10c |
| API-04, API-05 Filtres invalides (400) ; filtre par date | Écart E-07 ([15](15-ecarts-enonce.md)) | Réussi | S10c |
| API-06 Détail : 200, 403, 404 | EX-32 | Réussi | S10c |
| API-07 à API-09 Création : 201, valeurs limites, 400, 415, 422 | EX-24 | Réussi | S10c |
| API-10 Modification refusée à un passager | EX-31 | Réussi | S10c |
| API-11 à API-13 Transitions autorisées et interdites | EX-25 | Réussi | S10c |
| API-14 Méthodes non prévues : 405 | Écart E-08 | Réussi | S10c |
| Newman 11 à 26 | EX-24, EX-25, EX-31, EX-32, EX-33 | Réussi (59/59 assertions sur toute la collection) | S10c |

Les statuts des exigences de la synthèse ne changent pas : EX-24, EX-25, EX-31 et EX-33 étaient déjà réussies ; EX-32 reste partielle à cause d'ANO-17 (véhicule d'un autre conducteur).

