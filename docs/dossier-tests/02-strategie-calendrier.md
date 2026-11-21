# 2. Stratégie, plan de test et critères de campagne

## Contrôle du document

| Champ | Valeur |
|---|---|
| Document | Plan de test WASSALNI |
| Version | 2.0 |
| Date | 21 novembre 2026 |
| Statut | En vigueur pour la campagne de rejeu après corrections |
| Propriétaire | Responsable de campagne (étudiant) |
| Approbateur | Enseignant encadrant, à viser |
| Révisions | 1.0 (05/11) — première version ; 1.1 (05/11) — outils, dépôt Git, règles de campagne ; **2.0 (21/11)** — plan aligné sur les outils réellement utilisés, intégration continue, **seuils quantitatifs de sortie (§2.7)** formalisés après la campagne du 11/11 et applicables à toute campagne ultérieure |

> **Note de traçabilité.** La campagne du 11/11/2026 (révision `cc50095`) a été jugée avec les critères qualitatifs du §2.7.1, seuls définis à cette date. Les seuils chiffrés du §2.7.2 ont été ajoutés le 21/11/2026, **avant** la campagne de rejeu après corrections : c'est à elle qu'ils s'appliquent en premier.

## 2.0 Objectifs et périmètre

L'objectif est de fournir des preuves reproductibles sur les parcours à risque de WASSALNI : droits des utilisateurs, publication et recherche de trajets, réservation et restitution des places, traitement des réclamations, administration. La campagne vérifie les exigences EX-01 à EX-35 (PDF semaine 3) au travers des cas TC (PDF semaine 4) et des critères CA ; la [matrice de traçabilité](09-matrice-tracabilite.md) fait le lien exigence → risque → test → résultat → preuve.

Chaque exécution porte sur une révision identifiée par son SHA Git. Les règles métier ambiguës sont arbitrées par le responsable produit avant exécution, ou marquées **Bloqué**.

**API.** Jusqu'au 21/11/2026, l'application n'exposait pas d'API REST : le volet API portait sur les 4 routes JSON existantes, dont `POST /reservation/new-ajax`. Depuis le 21/11/2026, une API REST des réclamations (`/api/reclamations`) est testée en plus ([11 §11.5](11-semaine8-api.md)).

**Hors périmètre de la campagne du 11/11 :** assistant d'inscription et envoi d'e-mails (EX-01, EX-02, EX-30 — transport `null://null`), charge réaliste sur serveur de production, audit d'accessibilité manuel.

## 2.1 Niveaux, techniques et outils

| Niveau | Objet | Technique / outil | Emplacement | Résultat de référence (11/11, `cc50095`) |
|---|---|---|---|---|
| Unitaire | Règles des entités, validations, service de réservation | PHPUnit 12, Arrange – Act – Assert, valeurs limites, stub et mock | `tests/Entity`, `tests/Service`, `tests/Controller` (suite `unitaire`) | 33 tests, 52 assertions, OK |
| Intégration | Doctrine et MariaDB : CRUD, contraintes, transactions, cascades | PHPUnit + noyau Symfony, base `wassalni_test` | `tests/Integration` (suite `integration`) | 9 tests, 29 assertions, OK |
| Système (HTTP) | Parcours HTTP, droits par rôle, relecture en base | WebTestCase / BrowserKit, fixtures isolées | `tests/Functional` (suite `fonctionnel`) | 45 tests de régression, OK |
| Anomalies ouvertes | Démonstration rejouable de chaque défaut non corrigé | PHPUnit, groupe `anomalie-ouverte` (exclu de la régression) | répartis dans les suites | 48 tests en échec attendu |
| API | Routes JSON et API REST des réclamations : méthodes, statuts HTTP, corps JSON, en-têtes, authentification, droits, cas négatifs | Postman + Newman ; PHPUnit (API-01 à API-14) | `postman/`, `scripts/run-newman.js`, `tests/Functional/Api` | 19 requêtes, 32/35 assertions (11/11) ; 35 requêtes, 59/59 (21/11) |
| Interface | Parcours critique et scénarios d'états dans un navigateur | Playwright (Chrome) | `tests/Playwright/` | 23 tests, 20 réussis |
| Non fonctionnel | Performance, accessibilité, sécurité élémentaire, concurrence | k6, axe-core, contrôles d'en-têtes, `composer audit` | `tests/k6/`, `tests/Playwright/semaine9-*` | Voir [12](12-semaine9-interface-non-fonctionnel.md) |
| Acceptation | Scénarios métier par rôle | Scénarios ST-01 à ST-10, visa du responsable produit | [05](05-etats-exploration-anomalies.md) | 10 scénarios exécutés |
| Couverture | Lignes et méthodes de `src/` | PCOV + rapport PHPUnit (HTML, Clover) | `preuves/semaine6/campagne-finale/` | 34,65 % des lignes |

## 2.2 Environnements et données

| Paramètre | Poste local (campagne) | Intégration continue |
|---|---|---|
| Système | Windows 11 | Ubuntu (GitHub Actions) |
| PHP | 8.4.27 | 8.4 (PCOV) |
| Base | MariaDB 10.4.32, base `wassalni_test` | Service MariaDB 10.4 éphémère, base `wassalni_test` |
| Schéma | 4 migrations Doctrine | Mêmes migrations + `doctrine:schema:validate` |
| Outils navigateur et API | Chrome, Playwright, Newman, k6 | PHPUnit uniquement (régression + anomalies) |
| Secret de test | `WASSALNI_TEST_PASSWORD` dans `.env.test.local` (non versionné) | Variable d'environnement propre à la CI |

Règles :

- `APP_ENV=test` ; le suffixe `_test` est imposé par `doctrine.yaml`. Aucun scénario n'est exécuté sur la base `wassalni`. Chaque outil vérifie le nom de la base avant d'agir.
- Données fictives uniquement : passagers A et B, conducteur, administrateur, véhicule, trajets valide, à venir, passé et complet, réservations dans chaque statut.
- Les tests d'intégration et HTTP nettoient leurs données ; l'isolation est contrôlée (état identique avant et après).
- Les captures sont anonymisées ; aucun secret ni `.env.local` n'est versionné.

## 2.3 Rôles et responsabilités

| Rôle | Responsabilité |
|---|---|
| Testeur | Préparer les données, exécuter les cas, joindre les preuves, enregistrer les anomalies |
| Développeur | Corriger les défauts sur une branche `fix/*` et identifier la révision corrigée |
| Responsable produit / enseignant | Arbitrer les ambiguïtés, valider les critères d'acceptation, viser la décision |
| Responsable de campagne | Geler la révision testée, suivre les résultats, proposer la décision de sortie |

Dans ce projet individuel, l'étudiant tient les rôles de testeur, de développeur et de responsable de campagne ; la validation métier est consignée séparément (visa de l'enseignant).

## 2.4 Organisation du dépôt Git

```text
tests/
  Entity/ Service/ Controller/   # suite « unitaire »
  Integration/                   # suite « integration »
  Functional/                    # suite « fonctionnel » (Catalogue/ = cas TC officiels)
  Support/                       # fixtures et classes de base
  Playwright/                    # parcours navigateur, états, accessibilité
  k6/                            # test de charge
postman/                         # collection et environnement Newman
scripts/                         # run-regression.ps1, run-newman.js, run-k6.js
preuves/semaineN/                # sorties brutes, rapports JUnit, captures
docs/dossier-tests/              # dossier de test (ce document)
.github/workflows/ci.yml         # intégration continue
```

Flux Git :

1. `main` est la branche de référence ; le tag `baseline-v0` marque l'état initial et `v1.0.0` la campagne du 11/11.
2. La campagne est conduite sur `test/campagne-finale` ; les corrections sont faites sur des branches `fix/*`, puis fusionnées après rejeu.
3. Messages de commit explicites (`test(s7): …`, `fix(reservation): …`, `docs: …`).
4. Le SHA de la révision testée est consigné dans chaque rapport d'exécution ; un tag publié n'est jamais déplacé.
5. Chaque push sur `main`, `test/**` ou `fix/**` déclenche la CI (régression PHPUnit, rejeu des anomalies, couverture, rapports en artefacts).

## 2.5 Calendrier de 10 semaines

| Semaine | Thème | Livrable | État |
|---|---|---|---|
| 1 | Compréhension, risques, périmètre | `docs/semaine1-fiche-risques.pdf`, [01](01-cadrage-risques.md) | Réalisé |
| 2 | Stratégie et plan de test | Ce document, `docs/semaine2-plan-de-test.pdf` | Réalisé (v2.0 le 21/11) |
| 3 | Exigences, critères d'acceptation, traçabilité | PDF semaine 3, [09](09-matrice-tracabilite.md) | Réalisé |
| 4 | Conception des cas de test | PDF semaine 4 (28 cas TC), [04](04-cas-de-test-donnees.md) | Réalisé |
| 5 | Transitions d'état, droits, exploration | [05](05-etats-exploration-anomalies.md) | Réalisé |
| 6 | Tests unitaires et couverture | [06](06-automatisation-bilan.md) | Réalisé |
| 7 | Intégration et base de données | [10](10-semaine7-integration.md) | Réalisé |
| 8 | API | [11](11-semaine8-api.md) | Réalisé |
| 9 | Interface et non fonctionnel | [12](12-semaine9-interface-non-fonctionnel.md) | Réalisé |
| 10 | Campagne finale et décision | [14](14-semaine10-bilan-decision.md), [07](07-registre-anomalies.md) | Réalisé (NO-GO) ; rejeu après corrections à venir |

## 2.6 Critères d'entrée

1. Révision Git (SHA ou tag) et configuration de test consignées.
2. Règles ambiguës arbitrées ou marquées bloquantes.
3. Base `wassalni_test` disponible, migrée, schéma valide.
4. Comptes fictifs disponibles pour chaque rôle.
5. CI verte sur la révision candidate (régression PHPUnit sans échec).

## 2.7 Critères de sortie

### 2.7.1 Critères qualitatifs (en vigueur depuis la version 1.1)

- tous les cas de priorité P1 exécutés et réussis ;
- aucun défaut S1 ou S2 ouvert, sauf acceptation documentée par le responsable habilité ;
- aucune incohérence de stock ou de prix après réservation, annulation et relecture en base ;
- tests unitaires, d'intégration, API et d'interface exécutés sur la même révision ;
- aucune exigence critique sans test ;
- régression réussie après la dernière correction ;
- limites, anomalies acceptées et risques résiduels documentés.

### 2.7.2 Seuils quantitatifs (ajoutés en version 2.0)

| Indicateur | Seuil | Mesure |
|---|---|---|
| Régression PHPUnit | 100 % de réussite, 0 dépréciation | `vendor/bin/phpunit` (CI et local) |
| Couverture globale des lignes de `src/` | **≥ 30 %** | PCOV, régression complète |
| Couverture des classes critiques (`ReservationController`, `TrajetController`, `ReclamationController`, `Reservation`, `Reclamation`, `ReservationService`) | **≥ 80 %** chacune | Rapport Clover |
| Exigences exécutées | ≥ 90 % (et 100 % des exigences liées aux risques de niveau ≥ 6) | [Matrice 09](09-matrice-tracabilite.md) |
| Défauts ouverts | 0 S1, 0 S2 | [Registre 07](07-registre-anomalies.md) |
| API (Newman) | 100 % des assertions réussies | `scripts/run-newman.js` |
| Parcours critique Playwright | Réussi | `reservation-flow.spec.js` |
| Performance (k6, 5 VU, 30 s) | Échecs HTTP < 1 % ; p95 < 1 500 ms ; contrôles > 99 % | `tests/k6/recherche-charge.js` |
| Accessibilité (axe-core) | 0 violation critique sur les pages du parcours critique | `semaine9-accessibilite-securite.spec.js` |

La couverture est un indicateur, pas une preuve d'absence de défaut : une classe très couverte peut contenir des anomalies (cas de `ReservationController`, 84,5 % et 5 anomalies).

### 2.7.3 Règle de décision

| Décision | Condition |
|---|---|
| **Validation** | Tous les critères du §2.7.1 et tous les seuils du §2.7.2 atteints |
| **Validation conditionnelle** | 0 S1 et 0 S2 ; seuils de régression et de couverture atteints ; écarts restants (S3/S4, accessibilité, performance) acceptés, datés et assortis de conditions de livraison |
| **Refus de livraison** | Au moins un défaut S1 ou S2 ouvert, une incohérence de stock ou de prix, ou une régression en échec |

## 2.8 Arrêt de la campagne

La campagne est arrêtée si elle cible la mauvaise base, si des fixtures touchent des données réelles ou si une dépendance métier empêche d'établir le résultat attendu. Le résultat est alors **Bloqué**, jamais « réussi » par défaut.
