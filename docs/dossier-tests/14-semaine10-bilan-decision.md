# 14. Semaine 10 — Campagne finale, bilan et décision de livraison

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

## 14.6 Critères de sortie (plan de test, §2.6) et décision

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
