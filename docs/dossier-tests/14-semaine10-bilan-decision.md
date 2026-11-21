# 14. Semaine 10 — Campagne finale, bilan et décision de livraison

> **Mise à jour du 21/11/2026.** Après la décision NO-GO du 11/11 (§14.1 à §14.6, conservés tels quels), les 8 anomalies S2 ont été corrigées et la campagne complète a été rejouée. **La décision en vigueur est au §14.8 : validation conditionnelle, confirmée après l'accessibilité et l'API REST.**

- **Date :** 11 novembre 2026
- **Révision testée :** `cc50095` (branche `test/campagne-finale`). Le code applicatif est identique à `e2fe62b`.
- **Environnement :** Windows 11, PHP 8.4.27, Symfony 7.4.23, MariaDB 10.4.32 (`wassalni_test`), Chrome local, Playwright 1.59.1, Newman 6.2.3, k6 2.3.0, PCOV 1.0.12
- **Commande de régression :** `powershell -NoProfile -ExecutionPolicy Bypass -File scripts/run-regression.ps1` (preuves : `preuves/semaine10/campagne-finale/`)

## 14.1 Résultats de la campagne finale (rejeu complet, même révision)

| Niveau | Outil | Exécutés | Réussis | Échoués | Commentaire |
|---|---|---|---|---|---|
| Unitaire, intégration, HTTP/BDD (régression) | PHPUnit | 87 tests, 245 assertions | 87 | 0 | `01-phpunit-regression.txt` |
| Tests d'anomalies ouvertes | PHPUnit, groupe `anomalie-ouverte` | 48 tests | 0 | 41 échecs, 5 erreurs, 2 dépréciations | Échecs **attendus** : ils prouvent les 32 anomalies non corrigées (`02-phpunit-anomalies.txt`) |
| Interface (navigateur) | Playwright + axe | 23 tests | 20 | 3 | Les 3 échecs sont les violations d'accessibilité critiques (ANO-36). D'où le code de sortie 1 (`03-playwright.txt`) |
| API JSON | Newman | 19 requêtes, 35 assertions | 32 assertions | 3 assertions | Les 3 échecs relèvent d'ANO-22 (`04-newman.txt`) |
| Performance | k6 | 5 VU, 30 s | Seuils respectés | 0 % d'échec | p95 217,0 ms ; recherche p95 204,4 ms (`05-k6.txt`) |

**Comparaison avec la référence « avant corrections »** (`preuves/semaine10/regression/`) : le résultat est identique (48 tests d'anomalies dans le même état, régression à 87 tests OK). Aucune correction n'ayant été appliquée, la régression confirme la **stabilité** des résultats, et non une amélioration.

## 14.2 Bilan par cas et par exigence

| Indicateur | Valeur |
|---|---|
| Cas TC détaillés dans le PDF officiel de la semaine 4 | 28 |
| Cas TC exécutés (automatisés) | **28 / 28**, plus les variantes « bis » (voie JSON) et 21 critères CA testés directement |
| Scénarios ST (semaine 5) | 10 exécutés : 9 réussis, ST-09 conforme à la règle validée le 11/11 |
| Sessions exploratoires | EXPL-01 à 04 conduites avec outil (pas de session manuelle humaine) |
| Exigences EX-01 à EX-35 | **9 réussies, 9 partielles, 14 échouées, 3 non exécutées** ([matrice](09-matrice-tracabilite.md)) |
| Exigences critiques sans test | Aucune ; les 3 non exécutées (EX-01, 02, 30 : inscription et e-mails) sont des risques résiduels |

## 14.3 Anomalies

| Sévérité | Ouvertes | Exemples |
|---|---|---|
| S1 critique | 0 | — |
| **S2 majeure** | **8** | Quantité négative acceptée par la voie HTML (stock 4 → 5, ANO-07) ; actions sans CSRF (ANO-12, 13) ; comptes non vérifiés ou bloqués connectés (ANO-20, 21) ; fuite d'e-mail (ANO-22) ; ajout de véhicule en erreur 500 (ANO-35) ; surréservation concurrente (ANO-38) |
| S3 moyenne | 16 | Trajets passés réservables, doublons, modération contournée, erreurs 500, messages non affichés, accessibilité… |
| S4 mineure | 8 | Statistiques codées en dur, message en anglais, fuseau, contraste… |
| **Total** | **32** | Registre complet : [07](07-registre-anomalies.md). Recommandations de correction : [13](13-plan-corrections.md) |

Six anomalies (ANO-01 à 06) avaient été corrigées par des modifications antérieures à la campagne, puis vérifiées. Cinq défauts de l'outillage de test (ANO-T01 à T05) ont été corrigés.

## 14.4 Couverture

- **Couverture de code** (PCOV, régression) : **34,65 % des lignes**, 49,56 % des méthodes. La suite unitaire seule couvre 7,00 % des lignes ([analyse en semaine 6](06-automatisation-bilan.md)).
- **Couverture des exigences** : 32 exigences sur 35 exécutées.
- **Couverture des risques** : les 6 risques de niveau 9 et 6 du PDF de la semaine 1 (R1, R4, R5, R6, R12, R15, R17, R20, R21) sont tous testés. Chacun a révélé au moins une anomalie, sauf R15, dont l'intégrité de la base a été confirmée.

## 14.5 Risques résiduels

1. **Sécurité (R20, R21, R5)** : CSRF absent, fuite d'e-mail, contrôle de l'état du compte absent.
2. **Intégrité commerciale (R1)** : stock manipulable par la voie HTML (quantité négative) et surréservation en cas de concurrence.
3. **Parcours conducteur bloqué** : impossible d'ajouter un véhicule (ANO-35).
4. **Non testé** : inscription en plusieurs étapes et envoi d'e-mails (EX-01, 02, 30) ; performance avec un volume réaliste et un serveur de production ; audit d'accessibilité manuel.
5. **Écarts avec le référentiel de l'énoncé** : affectation à un agent, historique des statuts, priorité et API REST générale n'existent pas dans WASSALNI ; ils n'ont donc pas été testés (liste E-01 à E-08 dans [15](15-ecarts-enonce.md)).

## 14.6 Critères de sortie (plan de test, §2.7.1) et décision

| Critère de sortie | Atteint ? |
|---|---|
| Cas P1 exécutés et réussis | **Non** : plusieurs cas P1 échouent (TC-24, TC-04, TC-45, CA-15…) |
| Aucun défaut S1/S2 ouvert | **Non** : 8 anomalies S2 ouvertes |
| Pas d'incohérence de stock ni de prix | **Non** : ANO-07, ANO-38 |
| Unitaires, intégration, API et interface exécutés sur la même révision | **Oui** (`cc50095`) |
| Aucune exigence critique orpheline | **Oui** |
| Régression réussie après la dernière correction | Sans objet : aucune correction applicative pendant la campagne |

### Décision : **REFUS DE LIVRAISON (NO-GO)**

**Justification.** La campagne est complète et reproductible. Mais 8 anomalies majeures (S2) restent ouvertes et touchent la sécurité (CSRF, comptes, données personnelles), l'intégrité des réservations (stock et prix manipulables, surréservation) et un parcours métier bloquant (ajout de véhicule). Ces défauts sont démontrés par des tests rejouables. Les critères de sortie ne sont pas atteints. Une validation conditionnelle n'est pas défendable, car les défauts S2 concernent des données réelles d'utilisateurs et un risque financier.

**Conditions pour un GO :**

1. Corriger les 8 anomalies S2, puis les S3 bloquantes pour l'usage (ANO-10, 16, 25, 34). Le [plan de correction 13](13-plan-corrections.md) propose une correction par anomalie.
2. Retirer les tests corrigés du groupe `anomalie-ouverte` et rejouer `scripts/run-regression.ps1` sur la nouvelle révision.
3. Tester l'inscription et les e-mails avec un transport de mail de test (Mailpit).
4. Obtenir le visa du responsable produit ou de l'enseignant.

| Décisionnaire | Décision | Révision | Date |
|---|---|---|---|
| Responsable de campagne | NO-GO (refus de livraison) | `cc50095` | 11/11/2026 |
| Responsable produit / enseignant | À viser | — | — |

## 14.7 Rejeu après corrections et nouvelle décision (21/11/2026)

- **Révision testée :** `72cff37` (branche `fix/anomalies-s2`, issue de `test/campagne-finale`)
- **Environnement :** identique au §14.1 ; intégration continue GitHub Actions verte sur la même branche
- **Commande :** `scripts/run-regression.ps1 -Sortie preuves/semaine10/apres-corrections` (preuves : `preuves/semaine10/apres-corrections/`)
- **Corrections :** détail par anomalie dans le [registre, §7.7](07-registre-anomalies.md)

### 14.7.1 Résultats comparés

| Niveau | 11/11 (`cc50095`) | **21/11 (`72cff37`)** |
|---|---|---|
| Régression PHPUnit | 87 tests, 245 assertions, OK | **110 tests, 291 assertions, OK** |
| Tests d'anomalies ouvertes | 48 | **27** (25 échecs, 2 dépréciations) |
| Playwright | 20 / 23 | 20 / 23 (3 échecs = ANO-36, accessibilité) |
| Newman | 32 / 35 assertions | **35 / 35** |
| k6 (5 VU, 30 s) | p95 217,0 ms, seuils respectés | p95 392,8 ms, seuils respectés |
| Couverture des lignes (PCOV) | 34,65 % | **41,33 %** (méthodes : 57,02 %) |
| Anomalies ouvertes | 32 (8 S2, 16 S3, 8 S4) | **24 (0 S2, 16 S3, 8 S4)**, aucune P1 |
| Exigences EX-01 à EX-35 | 9 R, 9 P, 14 E, 3 NE | **15 R, 10 P, 7 E, 3 NE** |

La hausse du p95 de k6 (217 → 393 ms) reste très en dessous du seuil de 1 500 ms. Elle n'est pas interprétée comme une régression : les mesures sont faites sur un serveur de développement mono-processus, dont les temps varient d'une exécution à l'autre ; le décrément atomique ajoute une requête SQL par réservation, mais le scénario k6 ne réserve pas.

### 14.7.2 Critères et seuils de sortie (plan de test v2.0, §2.7)

| Critère | Seuil | Résultat | Atteint ? |
|---|---|---|---|
| Régression PHPUnit | 100 %, 0 dépréciation | 110 / 110 | Oui |
| Couverture globale | ≥ 30 % | 41,33 % | Oui |
| Classes critiques | ≥ 80 % chacune | `ReservationController` 87,8 %, `TrajetController` 85,7 %, `ReclamationController` 100 %, `Reservation` 94,9 %, `Reclamation` 93,5 %, `ReservationService` 100 % | Oui |
| Exigences exécutées | ≥ 90 % | 32 / 35 (91 %) | Oui |
| Défauts S1 et S2 ouverts | 0 | 0 | Oui |
| Newman | 100 % | 35 / 35 | Oui |
| Parcours critique Playwright | Réussi | Réussi | Oui |
| Performance k6 | < 1 % d'échecs, p95 < 1 500 ms, contrôles > 99 % | 0 %, 392,8 ms, 100 % | Oui |
| Accessibilité | 0 violation critique sur le parcours critique | Violations `select-name` et `label` sur 3 pages (ANO-36) | **Non** |
| Cas P1 exécutés et réussis | Tous | Les cas P1 en échec le 11/11 (TC-03, TC-04, TC-24, TC-45, CA-15) réussissent ; aucune anomalie ouverte n'est P1 | Oui |
| Stock et prix cohérents après réservation et annulation | Aucune incohérence | Stock cohérent (TC-22 à TC-29, IT-07) ; voir réserve sur ANO-15 ci-dessous | Oui, avec réserve |
| Régression réussie après la dernière correction | — | Rejeu complet sur `72cff37` | Oui |

### 14.7.3 Décision : **VALIDATION CONDITIONNELLE**

**Justification.** Selon la règle du plan (§2.7.3), la validation simple n'est pas possible : le seuil d'accessibilité n'est pas atteint (ANO-36) et 24 anomalies S3/S4 restent ouvertes. Le refus de livraison n'est plus justifié : aucun défaut S1 ou S2 n'est ouvert, la régression est entièrement réussie sur la révision candidate, les seuils de couverture, d'API et de performance sont atteints, et les risques majeurs du 11/11 (sécurité des comptes, CSRF, fuite de données, intégrité du stock, parcours conducteur bloqué) sont corrigés et couverts par des tests de régression.

**Réserve sur ANO-15** (prix « abc » enregistré à 0 DT). Elle touche le prix d'un trajet à sa **publication**, et non la cohérence stock/prix d'une réservation. Elle est classée S3 / P2 et figure dans les conditions ci-dessous.

**Conditions de livraison :**

1. **Avant la mise en production :** corriger les anomalies S3 de priorité P2 qui touchent l'intégrité métier : ANO-10 et ANO-11 (réservation de trajets passés ou non validés), ANO-15 (prix non numérique), ANO-16 (trajet visible avant modération), ANO-08 (doublon par la voie HTML), ANO-09 (annulation d'un trajet passé) et ANO-17 (véhicule d'un autre conducteur).
2. **Avant la mise en production :** supprimer les erreurs 500 connues : ANO-23, ANO-25 et ANO-34.
3. **Dans le mois suivant :** corriger l'accessibilité (ANO-36), puis les anomalies S3/S4 restantes selon leur priorité.
4. **Pour chaque correction :** sortir le test correspondant du groupe `anomalie-ouverte` et rejouer `scripts/run-regression.ps1` ; la CI doit rester verte.
5. **Risques résiduels acceptés :** inscription et e-mails non testés (EX-01, EX-02, EX-30) ; performance mesurée sur un serveur de développement ; écarts avec le référentiel de l'énoncé ([15](15-ecarts-enonce.md)).

| Décisionnaire | Décision | Révision | Date |
|---|---|---|---|
| Responsable de campagne | NO-GO (refus de livraison) | `cc50095` | 11/11/2026 |
| Responsable de campagne | **Validation conditionnelle** | `72cff37` | 21/11/2026 |
| Responsable produit / enseignant | À viser | — | — |

## 14.8 Accessibilité, API REST et confirmation de la décision (21/11/2026)

- **Révision testée :** `1ed214b` (branche `amelioration/a11y-api`)
- **Commande :** `scripts/run-regression.ps1 -Sortie preuves/semaine10/apres-ameliorations`
- **Changements :** ANO-36 corrigée ; API REST des réclamations ([11 §11.5](11-semaine8-api.md)) ; ANO-39 découverte et corrigée ([07 §7.8](07-registre-anomalies.md))

| Niveau | 21/11, après corrections S2 (`72cff37`) | **21/11, après améliorations (`1ed214b`)** |
|---|---|---|
| Régression PHPUnit | 110 tests, 291 assertions | **131 tests, 365 assertions, OK** |
| Tests d'anomalies ouvertes | 27 | 27 |
| Playwright | 20 / 23 | **23 / 23** |
| Newman | 19 requêtes, 35 / 35 | **35 requêtes, 59 / 59** |
| k6 | p95 392,8 ms | p95 250,8 ms ; seuils respectés |
| Couverture des lignes | 41,33 % | **44,69 %** (`ApiReclamationController` : 98,1 %) |
| Anomalies ouvertes | 24 (0 S2, 16 S3, 8 S4) | **23 (0 S2, 15 S3, 8 S4)** |
| Exigences | 15 R, 10 P, 7 E, 3 NE | 15 R, 10 P, 7 E, 3 NE |

**Seuils du plan (§2.7.2) : tous atteints**, y compris l'accessibilité (0 violation critique sur les 4 pages auditées).

### Décision : **VALIDATION CONDITIONNELLE (confirmée)**

Tous les seuils quantitatifs sont atteints. La validation simple n'est pourtant pas retenue :

- au sens de l'énoncé, une validation suppose qu'**aucun défaut bloquant ne soit connu** et que les exigences critiques soient satisfaites ; or **7 exigences restent non satisfaites** (EX-05, 09, 10, 11, 12, 26, 29), dont les règles de publication des trajets (EX-09 à EX-12) ;
- 15 anomalies S3 restent ouvertes, dont 7 qui touchent l'intégrité métier (réservation de trajets passés ou non validés, prix non numérique, modération contournée) ;
- la réserve sur ANO-15 (prix « abc » enregistré à 0 DT) est maintenue.

Ces défauts sont connus, documentés, rejoués par des tests et ne compromettent ni la sécurité ni la cohérence du stock : ils relèvent de la validation conditionnelle.

**Conditions de livraison (mises à jour) :**

1. **Avant la mise en production :** corriger ANO-08, 09, 10, 11, 15, 16 et 17 (intégrité métier) et les erreurs 500 connues (ANO-23, 25, 34) ; corriger ANO-25 fera passer l'inscription par la validation de `User`, désormais sans erreur (ANO-39).
2. **Dans le mois suivant :** corriger les autres anomalies S3/S4, dont le contraste (ANO-37).
3. **Pour chaque correction :** sortir le test du groupe `anomalie-ouverte`, rejouer `scripts/run-regression.ps1`, garder la CI verte.
4. **Risques résiduels acceptés :** inscription et e-mails non testés (EX-01, EX-02, EX-30) ; performance mesurée sur un serveur de développement ; écarts restants avec l'énoncé (E-01 à E-06, [15](15-ecarts-enonce.md)).

| Décisionnaire | Décision | Révision | Date |
|---|---|---|---|
| Responsable de campagne | NO-GO (refus de livraison) | `cc50095` | 11/11/2026 |
| Responsable de campagne | Validation conditionnelle | `72cff37` | 21/11/2026 |
| Responsable de campagne | **Validation conditionnelle (confirmée)** | `1ed214b` | 21/11/2026 |
| Responsable produit / enseignant | À viser | — | — |

