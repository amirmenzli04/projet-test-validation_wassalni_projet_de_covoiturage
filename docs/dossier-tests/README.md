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

Les PDF incluent les résultats actualisés de la suite. Les versions sources sont [le catalogue semaine 4](04-cas-de-test-donnees.md) et [le dossier semaine 5](05-etats-exploration-anomalies.md). Les scénarios exploratoires navigateur, les mesures non fonctionnelles et les ambiguïtés produit restent à traiter.

## Résumé exécutif

WASSALNI permet à des passagers et conducteurs de gérer des trajets et réservations, et aux utilisateurs de soumettre des réclamations. Le code examiné contient aussi des rôles administrateur, conducteur et passager, ainsi qu'un traitement administratif des réclamations.

Le dossier couvre les parcours d'inscription/connexion, publication et recherche de trajet, réservation/annulation, réclamation et administration. Une route JSON de réservation existe (`POST /reservation/new-ajax`); aucun contrat d'API REST général n'a été identifié dans le périmètre examiné. Le volet API est donc limité à cette route tant qu'une API REST distincte n'est pas confirmée.

La suite PHPUnit complète a été exécutée avec PHP 8.4.27 et PHPUnit 12.5.23 : **27 tests et 74 assertions réussis, sans échec ni dépréciation**. Elle inclut sept tests HTTP/DB du flux de réclamation avec utilisateurs de test persistés. Le nettoyage a été vérifié : aucune fixture `phpunit-*@example.test` ne reste dans la base dédiée. Les tests exploratoires dans un vrai navigateur, l'accessibilité et la performance restent à exécuter; la couverture de code n'a pas été mesurée.

**Décision à ce stade : livraison non validée (NO-GO probatoire).** La suite automatisée et une partie des parcours d'intégration passent, mais la campagne d'acceptation, d'exploration et non fonctionnelle reste incomplète. Cette décision porte sur les preuves disponibles et ne signifie pas que l'application est défaillante. Le détail figure dans [le bilan](06-automatisation-bilan.md).

## Utilisation

Exécuter les cas dans l'ordre des dépendances de la stratégie, consigner le résultat réel pour chaque identifiant (Réussi, Échoué, Bloqué ou Non exécuté), joindre une preuve anonymisée et ouvrir une anomalie pour tout écart. Ne pas remplacer les comptes ou données de test par des données personnelles réelles.
