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

## 11.5 API REST des réclamations (ajoutée le 21/11/2026)

L'écart E-08 ([15](15-ecarts-enonce.md)) a été traité en ajoutant une API REST au module de réclamations (`src/Controller/ApiReclamationController.php`).

| Requête | Rôle | Codes |
|---|---|---|
| `GET /api/reclamations?statut=&dateMin=&dateMax=` | Le passager voit les siennes, l'administrateur toutes | 200, 400 (filtre invalide), 401 |
| `GET /api/reclamations/{id}` | Auteur ou administrateur | 200, 401, 403, 404 |
| `POST /api/reclamations` | Tout utilisateur connecté | 201 + `Location`, 400, 401, 415, 422 |
| `PATCH /api/reclamations/{id}` | Administrateur | 200, 401, 403, 404, 409 (transition interdite ou réclamation close), 415, 422 |
| `DELETE`, `PUT` | — | 405 |

**Choix de conception.** Authentification par la session de l'application. Les requêtes qui modifient des données exigent `Content-Type: application/json` (sinon 415) : un formulaire d'un site tiers ne peut pas l'envoyer sans autorisation CORS, ce qui protège contre le CSRF. L'adresse e-mail de l'auteur n'est jamais renvoyée (EX-33). Les sujets autorisés sont partagés avec le formulaire HTML (`Reclamation::SUJETS`).

**Tests PHPUnit (`tests/Functional/Api/ReclamationApiRestTest.php`).**

| Id | Cas | Exigence |
|---|---|---|
| API-01 | Visiteur anonyme : 401 JSON sur les 4 opérations | EX-33 |
| API-02 | Liste limitée aux réclamations du passager ; aucune adresse e-mail | EX-32, EX-33 |
| API-03 | L'administrateur liste tout et filtre par statut | EX-25 |
| API-04 | Filtres invalides (statut inconnu, dates mal formées) : 400 | — |
| API-05 | Filtre par date : aujourd'hui inclus, demain exclu (valeurs limites) | — |
| API-06 | Détail : 200 (auteur), 403 (autre passager), 404 (inexistante) | EX-32 |
| API-07 | Création valide : 201, `Location`, `EN_ATTENTE`, relecture en base | EX-24 |
| API-08 | Message de 9 caractères refusé (422), de 10 accepté (201) | EX-24 |
| API-09 | Sujet hors liste, trajet inconnu (422) ; JSON malformé (400) ; formulaire au lieu de JSON (415) ; rien en base | EX-24 |
| API-10 | Un passager ne peut pas modifier le statut : 403 | EX-31 |
| API-11 | Cycle complet `EN_ATTENTE → EN_COURS → TRAITEE`, réponse et date enregistrées | EX-25 |
| API-12 | Retour en arrière et modification d'une réclamation close : 409, réponse inchangée | EX-25 |
| API-13 | Traitement sans réponse, statut inconnu (422) ; inexistante (404) | EX-25 |
| API-14 | `DELETE` et `PUT` : 405 | — |

**Efficacité des tests vérifiée par mutation.** Deux défauts ont été introduits volontairement dans l'API : suppression du contrôle de propriétaire, puis autorisation des transitions interdites. API-06 puis API-12 ont échoué ; le code d'origine les fait de nouveau réussir.

**Collection Newman : requêtes 11 à 26.** Parcours passager (création, erreurs 422 et 415, liste, modification refusée, `DELETE` refusé), déconnexion et accès anonyme (401), puis parcours administrateur (`EN_COURS`, refus sans réponse, `TRAITEE`, 409 sur la réclamation close, filtres). Un compte administrateur fictif (`adminEmail`) a été ajouté à l'environnement.

**Résultat du rejeu (révision `1ed214b`) :** 35 requêtes, **59 assertions, 0 échec** (`preuves/semaine10/apres-ameliorations/04-newman.txt`).

