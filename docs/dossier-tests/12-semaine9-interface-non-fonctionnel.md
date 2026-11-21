# 12. Semaine 9 — Interface et tests non fonctionnels

- **Date d'exécution :** 11 novembre 2026
- **Environnement :**
  - Chrome local piloté par Playwright 1.59.1 ; @axe-core/playwright 4.14.0 ; k6 2.3.0 (binaire local) ;
  - serveur `php -S` avec le routeur qui force `APP_ENV=test`, donc la base `wassalni_test` ;
  - un garde-fou vérifie la base servie avant chaque campagne.
- **Preuves :** `preuves/semaine9/` ([README](../../preuves/semaine9/README.md))

## 12.1 Parcours critique automatisé (CT-29)

`tests/Playwright/reservation-flow.spec.js` : **réussi**.

| Étape | Action réelle dans l'interface | Vérification | Figure |
|---|---|---|---|
| 1 | Connexion par le formulaire | Redirection vers la recherche | — |
| 2 | Saisie de la date, puis choix Tunis → Sfax (soumission automatique) | Carte du trajet : villes, « 4 places » | `S09_PARCOURS_1_recherche.png` |
| 3 | « Réserver », puis « + » | 2 places, total 50 DT affiché | `S09_PARCOURS_2_fenetre_reservation.png` |
| 4 | « Confirmer » | HTTP 200 `{success: true, prixTotal: 50}` ; message « Réservation confirmée » | `S09_PARCOURS_3_confirmation.png` |
| 5 | « Mes réservations » | « 2 place(s) réservée(s) », 50 DT | `S09_PARCOURS_4_mes_reservations.png` |
| 6 | Relecture SQL | `CONFIRMEE`, 2 places, 50 ; stock 4 → 2 | `S09_parcours-verifications.json` |

Les parcours de la semaine 5 (ST-01 à ST-10, 10 scénarios) complètent cette couverture de l'interface : réclamations, droits, deux sessions simultanées.

## 12.2 Accessibilité (axe-core, règles WCAG 2.0/2.1 A et AA)

| Page | Règles conformes | Critiques | Graves (*serious*) | Règles en défaut |
|---|---|---|---|---|
| Connexion | 13 | 0 | 1 | `color-contrast` (2 éléments) |
| Recherche (avec résultats) | 12 | **2** | 1 | `select-name` (3), `label` (`#dateDepart`), `color-contrast` (15) |
| Nouvelle réclamation | 13 | **1** | 0 | `select-name` (3) |
| Mes réservations | 12 | **1** | 0 | `select-name` (`#statusFilter`) |

- **Navigation au clavier (connexion) : réussie.** Le champ e-mail reçoit le focus automatiquement, Tab mène au mot de passe, et Entrée connecte l'utilisateur (`/trajet/recherche`).
- **Anomalies :** ANO-36 (contrôles sans nom accessible, gravité critique selon axe) et ANO-37 (contraste insuffisant).
- **Limite :** un audit automatique ne vérifie qu'une partie des critères WCAG (environ un tiers). Ni lecteur d'écran ni audit manuel n'ont été réalisés. **Aucune conformité WCAG n'est revendiquée.**

## 12.3 Performance (k6)

Scénario en lecture seule : 5 utilisateurs virtuels pendant 30 s. Chaque itération comprend la recherche filtrée, le détail du trajet, la connexion et `/health`, puis 1 s de pause. Les seuils sont des **hypothèses de travail**, faute de seuil fixé par le produit : taux d'échec < 1 %, p95 < 1500 ms.

| Mesure | Résultat |
|---|---|
| Requêtes / itérations | 452 / 113 (14,63 req/s) |
| Échecs HTTP / vérifications | **0,00 %** / **100 %** réussies |
| Durée (toutes pages) | moyenne 86,0 ms ; médiane 72,8 ms ; **p95 208,7 ms** ; maximum 299,3 ms |
| Durée de la recherche | p95 196,7 ms |
| Seuils | **Tous respectés** |

**Interprétation et limites.** Le serveur intégré `php -S` traite une requête à la fois : les utilisateurs virtuels sont mis en file d'attente et la mesure caractérise cet environnement local, pas une production PHP-FPM. Le volume de données de test est faible (36 trajets). Le risque R14, lenteur avec un grand volume, n'est donc **pas** démontré écarté.

## 12.4 Concurrence (OBS-J, risque R1)

La concurrence HTTP réelle n'est pas reproductible avec `php -S` sous Windows (traitement séquentiel). Le test `ConcurrenceReservationTest` rejoue l'algorithme du contrôleur dans **deux connexions MariaDB distinctes**, entrelacées de façon déterministe.

**Résultat : 2 réservations confirmées pour 1 place, stock final 0 (mise à jour perdue) → ANO-38 (S2).**

## 12.5 Sécurité élémentaire

| Contrôle | Observation | Appréciation |
|---|---|---|
| Dépendances PHP (`composer audit`) | Aucune vulnérabilité connue | Conforme |
| Dépendances Node (`npm audit`) | 19 vulnérabilités (1 critique, 12 graves) dans **l'outillage de test** (dépendances transitives de Newman), non livré avec l'application | Hors produit ; à surveiller |
| Cookie de session | `HttpOnly`, `SameSite=Lax` ; `Secure` absent (HTTP local) | Conforme pour l'environnement de test |
| Page d'erreur 500 (`APP_DEBUG=0`) | Message générique, sans trace ni chemin | Conforme |
| En-têtes de protection | `X-Frame-Options`, `Content-Security-Policy`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy` **absents** ; `X-Powered-By: PHP/8.4.27` exposé | **Recommandation REC-01** (durcissement ; aucune exigence EX ne l'impose) |
| CSRF, accès sans connexion, contrôle des rôles | Voir ANO-12, 13, 20, 21, 22 (semaines 5, 7 et 8) | Anomalies S2 |

## 12.6 Robustesse de l'interface

Voir EXPL-01 et EXPL-04 (semaine 5). Les cas suivants sont conformes : double clic, envoi après expiration de session, rafraîchissement et retour arrière. Les erreurs 500 ANO-19, 25, 34 et 35 relèvent aussi de la robustesse.

## 12.7 Reproduire

```powershell
npx playwright test tests/Playwright/reservation-flow.spec.js tests/Playwright/semaine9-accessibilite-securite.spec.js
node scripts/run-k6.js
vendor\bin\phpunit --exclude-group aucun tests\Integration\ConcurrenceReservationTest.php
composer audit ; npm audit
```
