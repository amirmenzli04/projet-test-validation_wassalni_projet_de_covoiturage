# 9. Matrice de traçabilité finale — Exigence → Risque → Cas de test → Résultat → Preuve

- **Version :** 1.0, 11 novembre 2026 (campagne finale, révision `cc50095`)
- **Référentiel :** exigences EX et critères CA (PDF semaine 3), risques R (PDF semaine 1), cas TC (PDF semaine 4). Les tests automatisés sont nommés par leur identifiant TC, CA, ST, UT ou IT.
- **Statuts :** **Réussi** (tous les cas conformes), **Partiel** (une partie des cas conformes, avec anomalie sur le reste), **Échoué** (exigence non satisfaite), **Non exécuté**.
- **Preuves :**
  - P1 = `preuves/semaine7/p1-catalogue/` ;
  - S5 = `preuves/semaine5/` ;
  - S6 = `preuves/semaine6/campagne-finale/` ;
  - S7 = `preuves/semaine7/campagne-finale/` ;
  - S8 = `preuves/semaine8/` ;
  - S9 = `preuves/semaine9/` ;
  - S10 = `preuves/semaine10/campagne-finale/` (rejeu final de tous les tests).

| Exigence | Risque | Critère | Cas et tests exécutés | Résultat | Anomalies | Preuve |
|---|---|---|---|---|---|---|
| EX-01 Inscription en 5 étapes | R11 | CA-19 | — (assistant d'inscription non automatisé) | **Non exécuté** | — | — |
| EX-02 E-mail de confirmation valable 24 h | R5, R11 | CA-19 | — (transport de mail `null://null` en test) | **Non exécuté** | — | — |
| EX-03 Seuls les comptes activés se connectent | R5 | CA-11 | TC-03, TC-51 | **Échoué** (TC-51 réussi) | ANO-20 | P1, S10 |
| EX-04 Un compte bloqué ne se connecte pas | R5 | CA-11 | TC-04 | **Échoué** | ANO-21 | P1, S10 |
| EX-05 Modification du profil | — | CA-22 | Liste des routes | **Échoué** (fonction absente) | ANO-29 | `preuves/semaine3/routes-application.txt` |
| EX-06 Gestion des véhicules | R19 | CA-21 | CA-21 ajout, CA-13 véhicule d'autrui, UT-05, UT-06 | **Échoué** (CA-13 réussi) | ANO-35 | P1, S6, S10 |
| EX-07 Véhicule requis pour publier | — | CA-21 | CA-21 conducteur sans véhicule | **Réussi** | — | P1 |
| EX-08 Publication d'un trajet | R6 | CA-02 | TC-09 | **Partiel** (heures non saisissables, A-04) | ANO-14 | P1 |
| EX-09 Départ futur, arrivée après départ | R6 | CA-02 | TC-10, TC-11, TC-12, TC-13, UT-01, UT-03, UT-04 | **Échoué** (TC-10, 13, UT-01, 03 réussis) | ANO-14 | P1, S6 |
| EX-10 Prix strictement positif | R7 | CA-03 | TC-14 (5 valeurs), UT-02 | **Échoué** (0, −1, 0,01, 15 conformes) | ANO-15 | P1, S6 |
| EX-11 Trajet créé « en attente » | R4 | CA-01 | TC-15 | **Échoué** | ANO-16 | P1 |
| EX-12 Visible après validation seulement | R4 | CA-01 | TC-16, TC-50, TC-50 bis | **Échoué** (TC-50 bis réussi) | ANO-11, ANO-16 | P1 |
| EX-13 Filtres ville, date, prix | R18 | CA-04 | TC-19, TC-20 a/b, CA-04 date, recherche date invalide | **Partiel** | ANO-18, ANO-19 | P1 |
| EX-14 Seulement trajets validés avec places | R17 | CA-04 | TC-18, TC-19, TC-49, TC-49 bis | **Partiel** (TC-19 réussi) | ANO-10 | P1 |
| EX-15 Tri chronologique | — | CA-05 | CA-05 | **Réussi** | — | P1 |
| EX-16 Réserver 1 à n places | R1, R16 | CA-06, CA-09 | TC-22, TC-23, TC-24, TC-28, TC-28 bis, Newman 05–09, `ReservationApiTest` | **Échoué** (voie JSON conforme) | ANO-07, 08, 11 | P1, S8 |
| EX-17 Prix total = places × prix | R7 | CA-07 | TC-22 (88 DT), TC-25, UT-10 | **Réussi** | — | P1, S6 |
| EX-18 Confirmation immédiate et décrément | R1 | CA-08 | TC-22, IT-04, CT-29 (Playwright), Newman 05b | **Réussi** | — | P1, S7, S8, S9 |
| EX-19 Annulation des réservations futures, places restituées | R8 | CA-10 | TC-29, TC-30, CA-10 double annulation, ST-10 | **Partiel** | ANO-09 | P1, S5 |
| EX-20 Consulter ses réservations | — | — | CT-29 étape 5, ST-10 | **Réussi** | — | S5, S9 |
| EX-21 Avis 1 à 5 après le trajet | R9 | CA-18 | TC-33, UT-07, CA-18 avant la fin du trajet | **Partiel** (bornes conformes) | ANO-26, ANO-27 | P1, S6 |
| EX-22 Un seul avis par trajet | R9 | CA-18 | CA-18 second avis, CA-18 sans réservation | **Réussi** (dépréciation ANO-27) | ANO-27 | P1 |
| EX-23 Note moyenne et avis du conducteur | — | — | UT-09, `/avis/conducteur/{id}` | **Partiel** (calcul conforme, page en erreur 500) | ANO-25 | S6, P1 |
| EX-24 Dépôt de réclamation (message ≥ 10) | R13 | CA-17 | TC-36, ST-01 à ST-03, `ReclamationWorkflowTest` | **Réussi** | — | P1, S5 |
| EX-25 Réponse admin et suivi du statut | — | — | ST-05, ST-06, ST-07, ST-09, T-01 à T-06 | **Réussi** pour la règle validée (traitement direct) ; retour utilisateur absent | ANO-31, ANO-33 | S5 |
| EX-26 Tableau de bord admin | R12 | CA-16 | CA-16 compteur réclamations | **Échoué** | ANO-24 | P1 |
| EX-27 Gérer les utilisateurs | R20 | CA-15, CA-20 | TC-04 (blocage), CA-15 blocage par GET, CA-20 suppression | **Partiel** (fonction présente, non protégée) | ANO-13, ANO-23 | P1 |
| EX-28 Valider ou supprimer les trajets | R20, R15 | CA-15, CA-20 | TC-38, CA-15 suppression, CA-20, IT-05, IT-06 | **Partiel** | ANO-13, ANO-23 | P1, S7 |
| EX-29 Statistiques exactes des tableaux de bord | R12 | CA-16 | `/passager/dashboard`, A-05, T-05 | **Échoué** | ANO-28, ANO-33, ANO-34 | P1, S5 |
| EX-30 E-mails envoyés | R11 | CA-19 | — (`null://null`) | **Non exécuté** | — | — |
| EX-31 Accès limité aux pages de son rôle | R2 | CA-12 | TC-41, CA-12, EXPL-02 (matrice 4 rôles × 7 URL), ST-08 | **Réussi** | — | P1, S5 |
| EX-32 Pas d'accès aux ressources d'autrui | R3 | CA-13 | CA-13 (réservation, véhicule), ST-04, `ReclamationWorkflowTest`, publication avec le véhicule d'autrui | **Partiel** | ANO-17 | P1, S5 |
| EX-33 Aucune donnée personnelle sans connexion | R21 | CA-14 | TC-45, CA-14, Newman 02b et 02c | **Échoué** | ANO-22 | P1, S8 |
| EX-34 Modifications par POST avec jeton CSRF | R20 | CA-15 | CA-15 (réservation, annulation, admin), TC-38 | **Échoué** | ANO-12, ANO-13 | P1 |
| EX-35 Cohérence des données | R1, R15 | CA-06, CA-20 | TC-24, IT-01 à IT-07, CA-20 | **Échoué** (transactions et intégrité de la base conformes) | ANO-07, ANO-38 | P1, S7, S9 |

## Synthèse

| Statut | Nombre d'exigences | Exigences |
|---|---|---|
| Réussi | 9 | EX-07, 15, 17, 18, 20, 22, 24, 25, 31 |
| Partiel | 9 | EX-08, 13, 14, 19, 21, 23, 27, 28, 32 |
| Échoué | 14 | EX-03, 04, 05, 06, 09, 10, 11, 12, 16, 26, 29, 33, 34, 35 |
| Non exécuté | 3 | EX-01, 02, 30 (inscription et e-mails) |

Total : **35 exigences**. Les 35 exigences sont rattachées à au moins un critère ou un test. Les 3 exigences non exécutées concernent l'inscription et l'envoi d'e-mails, qui demandent un transport de mail réel (risque R11, résiduel).

## Exigences non fonctionnelles (semaine 9)

| Domaine | Test | Résultat | Preuve |
|---|---|---|---|
| Performance (R14) | k6, 5 VU, 30 s | Réussi : 0 % d'échec, p95 217,0 ms (campagne finale) | S10 |
| Accessibilité | axe-core sur 4 pages, navigation clavier | Échoué : 3 pages avec violations critiques (ANO-36), contraste (ANO-37) ; clavier conforme | S9, S10 |
| Sécurité élémentaire | En-têtes, cookie, page d'erreur, audits de dépendances | Partiel : cookie et page d'erreur conformes, `composer audit` propre ; en-têtes absents (REC-01) | S9 |
| Concurrence (R1) | IT-07, entrelacement de deux transactions | Échoué : surréservation (ANO-38) | S9 |
