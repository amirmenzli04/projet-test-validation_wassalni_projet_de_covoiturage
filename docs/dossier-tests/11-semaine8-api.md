# 11. Semaine 8 — Tests de l'API

- **Date d'exécution :** 11 novembre 2026
- **Outils :** Postman (format de collection v2.1), exécutée par **Newman 6.2.3** ; tests PHPUnit/BrowserKit complémentaires
- **Preuves :** `preuves/semaine8/` (sortie console, rapport JSON nettoyé, rapport JUnit)

## 11.1 Périmètre réel : pas d'API REST

L'inventaire des routes (`php bin/console debug:router`, preuve `preuves/semaine3/routes-application.txt`) montre que WASSALNI est une application **Twig rendue côté serveur**. **Aucune API REST** n'existe : ni ressources versionnées, ni contrat OpenAPI, ni authentification par jeton. La surface JSON réellement exposée se limite à quatre routes :

| Route | Méthode | Authentification | Rôle |
|---|---|---|---|
| `/reservation/new-ajax` | POST | Session, `ROLE_PASSAGER` | Réservation depuis la fenêtre de la page de recherche |
| `/health` | GET | Aucune | État de l'application et de la base |
| `/get-last-user-email` | ANY | **Aucune** | Utilisée par l'attente de vérification d'e-mail |
| `/check-verification-status` | ANY | **Aucune** | Idem |

**Limite assumée.** Conformément à la consigne, aucune route n'a été inventée et aucune API n'a été développée. Si une API REST est exigée, il faut d'abord définir son contrat (ressources, versions, codes d'erreur, authentification par jeton) : cela relève d'une décision produit. La collection teste donc la surface JSON existante. L'authentification passe par le vrai formulaire de connexion, puis par le cookie de session, comme dans le navigateur.

## 11.2 Collection et environnement

- **Collection :** `postman/Wassalni-Semaine-8-API.postman_collection.json` (19 requêtes, 35 assertions). Elle a été rendue valide en P0 (ANO-T04), puis complétée.
- **Environnement :** `postman/Wassalni-Test.postman_environment.json`. Variables : `baseUrl`, `passengerEmail`, `fromCity`, `toCity`, `fixtureDate`, `tripId` (calculé), `csrfToken` (calculé). **`passengerPassword` est laissé vide** dans le fichier : il est injecté à l'exécution depuis `.env.test.local`.
- **Lanceur :** `node scripts/run-newman.js`. Il démarre le serveur de test (`APP_ENV=test` forcé), recrée les données fictives, vérifie que la base servie est `wassalni_test`, exécute Newman, puis retire des rapports le mot de passe, les cookies de session et les corps de réponse.

## 11.3 Résultats (exécution du 11/11/2026)

**19 requêtes exécutées, 0 erreur de requête ; 35 assertions, 32 réussies, 3 échouées.**

| Requête | Cas | Assertions vérifiées | Résultat |
|---|---|---|---|
| 01 | Recherche du trajet de fixture | 200 ; un seul trajet trouvé, son identifiant extrait | Réussi |
| 02 | Réservation anonyme | 302 vers `/connexion` | Réussi |
| 02b | `/get-last-user-email` sans connexion (CA-14) | Accès refusé ; aucune adresse dans la réponse | **Échoué (2/2)** : HTTP 200, e-mail d'un compte fictif renvoyé (ANO-22) |
| 02c | `/check-verification-status` sans connexion (CA-14) | Accès refusé | **Échoué** : HTTP 200 (ANO-22) |
| 02d | `/health` | 200 ; `Content-Type` JSON ; `{"status":"ok"}` ; pas de cache public ; < 1000 ms | Réussi (5/5) |
| 02e | `/health` en POST | 405 | Réussi |
| 03 | Formulaire de connexion | 200 ; jeton CSRF présent | Réussi |
| 04 | Connexion du passager (en-tête `Origin`) | Session ouverte, page de recherche affichée | Réussi |
| 05 | Réservation d'une place | 200 JSON ; `success`, `prixTotal` 25, `reservationId` numérique | Réussi |
| 05b | Relecture de l'état | Stock 4 → 3 (CA-08) | Réussi |
| 05c | JSON malformé | 400 ; `success:false` ; « Corps JSON invalide » | Réussi |
| 05d | `nbPlaces = -1` (régression ANO-01) | 400 ; message « entier positif » | Réussi |
| 05e | `trajetId = "abc"` | 400 ; « Identifiant de trajet invalide » | Réussi |
| 05f | `trajetId` absent | 400 ; « Trajet non spécifié » | Réussi |
| 06 | `nbPlaces = 0` | 400 | Réussi |
| 07 | Trajet inexistant | 404 | Réussi |
| 08 | 99 places (> stock) | 400 | Réussi |
| 09 | Doublon (CA-09) | 400 ; « déjà réservé » | Réussi |
| 10 | GET sur `new-ajax` | 405 | Réussi |

Temps de réponse moyen : 182 ms (minimum 67, maximum 1455 ms, dû au premier appel qui chauffe le cache). Il s'agit d'une indication, pas d'une mesure de performance (voir la semaine 9).

## 11.4 Couverture complémentaire par PHPUnit

La même route est testée au niveau HTTP avec relecture en base dans `ReservationApiTest` (4 tests) et dans le catalogue : TC-28 bis (doublon JSON), TC-49 bis (trajet passé : **réservation acceptée**, ANO-10) et TC-50 bis (trajet non validé : refusé). La voie JSON est nettement mieux protégée que la voie HTML (comparer ANO-07, 08 et 11 avec les cas JSON réussis). La seule lacune JSON restante est l'absence de contrôle de la date de départ (ANO-10).

## 11.5 Reproduire

```powershell
node scripts/run-newman.js                  # écrit dans preuves/semaine8
npx newman run postman/Wassalni-Semaine-8-API.postman_collection.json -e postman/Wassalni-Test.postman_environment.json --env-var passengerPassword=<secret>   # serveur de test déjà démarré
```
