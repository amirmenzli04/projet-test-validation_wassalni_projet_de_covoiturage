# Dossier de test — WASSALNI

- **Version :** 2.0 (finale) — 11 novembre 2026 — tag `v1.0.0`
- **Branche :** `test/campagne-finale` · **Révision testée :** `cc50095` (code applicatif identique à `e2fe62b`)
- **Statut :** campagne des semaines 1 à 10 terminée. **Décision : REFUS DE LIVRAISON (NO-GO)**, voir [14](14-semaine10-bilan-decision.md).
- **Rapport final illustré :** [Markdown](../rapport-final/rapport-final.md) · [PDF](../rapport-final/rapport-final.pdf)

## Livrables par semaine

| Semaine | Livrable officiel | Documents de campagne | Preuves |
|---|---|---|---|
| 1 | `docs/semaine1-fiche-risques.pdf` | [01 cadrage](01-cadrage-risques.md), [00 référentiel](00-referentiel-correspondances.md) | `preuves/semaine1/` |
| 2 | `docs/semaine2-plan-de-test.pdf` | [02 stratégie](02-strategie-calendrier.md), `docs/semaine2-environnement.md` | `preuves/semaine2/` |
| 3 | PDF semaine 3 (exigences, CA, traçabilité) | [09 matrice finale](09-matrice-tracabilite.md) ([PDF](Matrice-tracabilite-finale.pdf)) | `preuves/semaine3/` |
| 4 | PDF semaine 4 dans `docs/` (**officiel**) ; `Semaine-4-...pdf` ici = synthèse annexe | [04 catalogue](04-cas-de-test-donnees.md), [00 correspondance CT→TC](00-referentiel-correspondances.md) | `preuves/semaine7/p1-catalogue/` |
| 5 | [Semaine-5 PDF](Semaine-5-Transitions-droits-exploration.pdf) | [05 §5.9 résultats](05-etats-exploration-anomalies.md) | `preuves/semaine5/` |
| 6 | [Semaine-6 PDF](Semaine-6-Tests-unitaires-automatises.pdf) | [06 §6.7](06-automatisation-bilan.md) | `preuves/semaine6/` |
| 7 | — | [10 intégration](10-semaine7-integration.md) | `preuves/semaine7/` |
| 8 | — | [11 API](11-semaine8-api.md) | `preuves/semaine8/` |
| 9 | — | [12 interface et non fonctionnel](12-semaine9-interface-non-fonctionnel.md) | `preuves/semaine9/` |
| 10 | [Semaine-10 PDF](Semaine-10-Bilan-decision.pdf) | [14 bilan et décision](14-semaine10-bilan-decision.md), [07 anomalies](07-registre-anomalies.md), [13 recommandations](13-plan-corrections.md), [08 journal](08-journal-campagne.md) | `preuves/semaine10/` |

Les fichiers 01 à 06 sont conservés comme **dossier de travail** (historique des semaines 1 à 6) ; les identifiants officiels sont ceux des PDF (voir [00](00-referentiel-correspondances.md)).

## Reproduire toute la campagne

```powershell
composer install ; npm ci
# .env.test.local : WASSALNI_TEST_PASSWORD=<12 caractères min.> (jamais versionné) ; MariaDB avec la base wassalni_test migrée
powershell -NoProfile -ExecutionPolicy Bypass -File scripts/run-regression.ps1
```

## Historique (version 1.4 et antérieures)

### Contenu initial

1. [Cadrage, fiche projet et risques](01-cadrage-risques.md)
2. [Stratégie, calendrier et critères de campagne](02-strategie-calendrier.md)
3. [Exigences et matrice de traçabilité](03-exigences-tracabilite.md)
4. [Catalogue de cas de test et données](04-cas-de-test-donnees.md)
5. [Cycle de vie, exploration et anomalies à confirmer](05-etats-exploration-anomalies.md)
6. [Automatisation, rapports et décision de livraison](06-automatisation-bilan.md)
7. [Registre des anomalies](07-registre-anomalies.md)
8. [Journal de la campagne finale](08-journal-campagne.md)
9. Preuves : [index](../rapport-preuves/index-preuves.md) et [registre](../rapport-preuves/registre-preuves.md)

Une copie PDF complète de l'entité de réservation est disponible ici : [Reservation-complete.pdf](Reservation-complete.pdf).

### Livrables des semaines 4 et 5

- [Semaine 4 — Conception des cas de test (PDF)](Semaine-4-Conception-des-cas-de-test.pdf) — 31 cas, partitions d'équivalence, valeurs limites, données et table de décision.
- [Semaine 5 — Transitions, droits et tests exploratoires (PDF)](Semaine-5-Transitions-droits-exploration.pdf) — diagramme d'états, matrice des rôles, scénarios, chartes et observations préliminaires.

Les versions sources sont [le catalogue semaine 4](04-cas-de-test-donnees.md) et [le dossier semaine 5](05-etats-exploration-anomalies.md).

## Résumé exécutif (campagne du 11/11/2026, révision `cc50095`)

WASSALNI est une application Symfony 7.4 de covoiturage avec trois rôles (passager, conducteur, administrateur) et un traitement des réclamations. La campagne a couvert la connexion et les droits, la publication et la recherche de trajets, la réservation et l'annulation, les avis, les réclamations et l'administration. Le volet API porte sur les 4 routes JSON existantes (pas d'API REST générale).

| Niveau | Résultat |
|---|---|
| Régression PHPUnit (unitaire, intégration, HTTP/BDD) | 87 tests, 245 assertions, tous réussis |
| Anomalies ouvertes démontrées | 48 tests en échec attendu, 32 anomalies (8 S2, 16 S3, 8 S4) |
| Interface (Playwright) | 23 tests, 20 réussis (3 échecs = ANO-36, accessibilité) |
| API (Newman) | 19 requêtes, 32 / 35 assertions (3 échecs = ANO-22) |
| Performance (k6) | Seuils respectés, p95 217 ms |
| Couverture des lignes | 34,65 % |
| Exigences EX-01 à EX-35 | 9 réussies, 9 partielles, 14 échouées, 3 non exécutées |

**Décision : refus de livraison (NO-GO)**, car 8 anomalies majeures (S2) restent ouvertes. Justification et conditions d'un GO : [14](14-semaine10-bilan-decision.md). Critères et seuils de sortie : [plan de test, §2.7](02-strategie-calendrier.md).

## Utilisation

Exécuter les cas dans l'ordre des dépendances de la stratégie, consigner le résultat réel pour chaque identifiant (Réussi, Échoué, Bloqué ou Non exécuté), joindre une preuve anonymisée et ouvrir une anomalie pour tout écart. Ne pas remplacer les comptes ou données de test par des données personnelles réelles.
