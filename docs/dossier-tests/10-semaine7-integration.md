# 10. Semaine 7 — Tests d'intégration et base de données

- **Date d'exécution :** 11 novembre 2026
- **Base :** `wassalni_test` exclusivement (MariaDB 10.4.32, InnoDB, 11 clés étrangères en `RESTRICT`)
- **Preuves :** `preuves/semaine7/campagne-finale/` et `preuves/semaine7/p1-catalogue/`

## 10.1 Objectifs et niveaux

| Niveau | Outil | Ce qui est vérifié |
|---|---|---|
| Intégration ORM / SGBD | `KernelTestCase` + Doctrine + MariaDB | Création, lecture, modification, suppression ; contraintes d'intégrité ; transactions et rollback ; relations ; propagation des suppressions |
| Intégration HTTP / BDD | `WebTestCase` (BrowserKit) + relecture SQL | Réservation, annulation, rôles, propriétaires, réclamations : la requête HTTP, puis l'état réellement écrit en base |

## 10.2 Tests d'intégration ORM / SGBD (`tests/Integration`)

**9 tests, 29 assertions, 0 échec** (`S07_integration.txt`).

| ID | Test | Résultat attendu et vérifié | Statut |
|---|---|---|---|
| — | `ReservationPersistenceTest::…CreatedReadUpdatedAndDeleted` | CRUD complet ; prix 2 × 25 = 50 relu ; date d'annulation renseignée au passage à `ANNULEE` | Réussi |
| — | `…RejectsReservationWithUnknownPassenger` | La clé étrangère refuse un passager inexistant ; 0 ligne écrite | Réussi |
| — | `…RejectsDuplicateUserEmail` | La contrainte unique refuse un e-mail en double | Réussi |
| IT-01 | Rollback explicite | La réservation est visible dans la transaction, puis absente après `rollback()` ; stock revenu à 4 | Réussi |
| IT-02 | Échec au milieu d'un `flush` | Une réservation valide, un décrément de stock et un utilisateur en double dans le même `flush` : **rien n'est écrit**, stock intact (atomicité du `flush` Doctrine) | Réussi |
| IT-03 | `wrapInTransaction` et exception métier | Une exception levée après le `flush` (paiement refusé simulé) annule toutes les écritures | Réussi |
| IT-04 | Relations relues depuis la base | Trajet ↔ Réservation, Véhicule → Trajet (mapping corrigé par ANO-05), User → Réservation ; prix total persistant | Réussi |
| IT-05 | Suppression d'un trajet | Les réservations sont supprimées en cascade ; 0 orphelin | Réussi |
| IT-06 | Trajet visé par une réclamation | La base refuse la suppression (`RESTRICT`) ; trajet et réclamation intacts | Réussi (intégrité préservée) |

**Observation de conception.** `Reservation::setTrajet()` ne met pas à jour la collection `Trajet::$reservations`. Dans un même `EntityManager`, la cascade de suppression ne voit donc pas une réservation ajoutée juste avant. En HTTP, chaque requête relit les données et le problème n'apparaît pas : la suppression d'un trajet avec réservations est propre (CA-20). C'est un risque de maintenance, pas une anomalie observable.

## 10.3 Tests d'intégration HTTP / BDD (`tests/Functional`)

**45 tests, 164 assertions, 0 échec** pour la régression (`S07_http-bdd.txt`). Le groupe `anomalie-ouverte` compte **41 tests : 39 échecs et 2 dépréciations** (`S07_http-bdd-anomalies.txt`). Le détail par cas figure dans le [registre](07-registre-anomalies.md) et dans les preuves P1.

| Domaine | Réussis (exemples) | Anomalies confirmées |
|---|---|---|
| Réservation | TC-22 (4 places, 88 DT, stock 0), TC-23, TC-25, TC-28 bis et TC-50 bis (voie JSON) | ANO-07 (−1 place, stock 4 → 5), 08, 10, 11, 12 |
| Annulation | TC-29, CA-10 (double annulation), CA-13 (tiers → 403) | ANO-09 (trajet passé), ANO-12 (GET sans CSRF) |
| Rôles et propriétaires | TC-41, CA-12, CA-13 (véhicule), ST-04 et ST-08 | ANO-20, 21, 22 |
| Administration | CA-20 (trajet avec réservations) | ANO-13, 23, 24 |
| Réclamations | Workflow (7 tests), TC-36, transitions T-01 à T-06 | ANO-31, 33 |

## 10.4 Isolation des données

- Toutes les données de test sont fictives : `phpunit-*@example.test`, `cat-*@example.test`, `semaine7-9.*@example.test`.
- Les nettoyages ne suppriment que les lignes rattachées à ces comptes (par identifiant ou par conducteur fixture).
- **Vérification** (`S07_isolation-base.txt`) : données préexistantes avant et après identiques (16 utilisateurs, 26 réservations, 11 réclamations, 35 trajets), **0 compte de test résiduel**.
- La base principale `wassalni` n'a jamais été utilisée. Un garde-fou existe à chaque niveau :
  - les tests vérifient le suffixe `_test` ;
  - la commande de données refuse toute autre base ;
  - le serveur de test force `APP_ENV=test`.

## 10.5 Limites

- Les tests de concurrence réelle (deux transactions simultanées sur le même stock, OBS-J) sont traités en semaine 9 avec k6, sur un serveur démarré.
- Les transactions sont démontrées au niveau ORM. Le code applicatif n'en ouvre aucune explicitement : les contrôleurs reposent sur l'atomicité d'un `flush` unique, ce qui suffit tant que chaque action ne fait qu'un `flush`.

## 10.6 Reproduire

```powershell
vendor\bin\phpunit --testsuite integration --testdox
vendor\bin\phpunit --testsuite fonctionnel --testdox
vendor\bin\phpunit --testsuite fonctionnel --group anomalie-ouverte --testdox
```
