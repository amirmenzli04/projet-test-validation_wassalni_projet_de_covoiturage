# Dossier de test — WASSALNI

- **Version :** 1.3 (résultats unitaires et intégration HTTP/DB)
- **Date du dossier :** 10 novembre 2026
- **Périmètre :** application web de covoiturage Symfony
- **Statut :** suite PHPUnit et tests d'intégration HTTP/DB exécutés; exploration navigateur et contrôles non fonctionnels à compléter.

## Contenu

1. [Cadrage, fiche projet et risques](01-cadrage-risques.md)
2. [Stratégie, calendrier et critères de campagne](02-strategie-calendrier.md)
3. [Exigences et matrice de traçabilité](03-exigences-tracabilite.md)
4. [Catalogue de cas de test et données](04-cas-de-test-donnees.md)
5. [Cycle de vie, exploration et anomalies à confirmer](05-etats-exploration-anomalies.md)
6. [Automatisation, rapports et décision de livraison](06-automatisation-bilan.md)

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
