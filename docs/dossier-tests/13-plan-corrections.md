# 13. Recommandations de correction (non appliquées)

- **Date :** 11 novembre 2026
- **Statut :** recommandations. **Décision du responsable (11/11/2026) : le code de l'application n'est pas modifié pendant la campagne ; seuls les livrables de test sont produits.** Aucune de ces corrections n'est appliquée. Toutes les anomalies restent ouvertes et sont rejouables par `vendor/bin/phpunit --group anomalie-ouverte`.
- **Décisions produit enregistrées :** fuseau horaire attendu Africa/Tunis (ANO-32 reste ouverte) ; traitement direct `EN_ATTENTE → TRAITEE` accepté (OBS-05-01 et ST-09 clos comme conformes à la règle validée).
- **Principe :** une correction par anomalie, la plus petite possible, sans changer de règle métier non documentée. Après chaque lot : régression complète (PHPUnit, Playwright, Newman) ; les tests `ANO-xx` concernés quittent le groupe `anomalie-ouverte` et doivent passer.

## Lot A — Sévérité S2 (sécurité, intégrité des données, parcours bloquant)

| Anomalie | Problème | Correction proposée | Fichiers | Risque |
|---|---|---|---|---|
| ANO-07, 08, 11 | La réservation HTML accepte quantité ≤ 0 ou non entière, doublon, trajet non validé | Appliquer à `POST /reservation/new/{id}` **les mêmes contrôles** que la voie JSON, déjà testés (entier ≥ 1, statut `VALIDE`, unicité), regroupés dans une méthode privée commune | `ReservationController.php` | Faible : la voie JSON sert de référence |
| ANO-10 | Trajets passés proposés et réservables | Recherche : ajouter `t.dateDepart > :maintenant` ; réservation (deux voies) : refuser un départ passé | `TrajetController.php`, `ReservationController.php` | Faible |
| ANO-12 | Réserver ou annuler sans jeton CSRF ; annulation par GET | Annulation limitée à POST avec `isCsrfTokenValid()` ; jeton ajouté aux formulaires (`trajet/show`, `reservation/index`, `dashboard/passager`) ; liens « Annuler » remplacés par de petits formulaires POST | Contrôleur et 3 templates | Moyen : modifie l'interface d'annulation (testée par Playwright ST-10) |
| ANO-13 | Actions d'administration par GET ou sans jeton | Routes admin mutantes limitées à POST et jeton CSRF ; boutons des listes transformés en formulaires | `AdminController.php` et 3 templates admin | Moyen (interface d'administration) |
| ANO-20, 21 | Compte non vérifié ou bloqué connecté | Ajouter un `UserChecker` (`checkPreAuth`) qui refuse `isVerified = false` et `statut = BLOQUE` avec un message clair, déclaré dans `security.yaml` | `src/Security/UserChecker.php` (nouveau), `security.yaml` | Faible |
| ANO-22 | E-mail du dernier inscrit divulgué sans connexion | Ne lire **que** l'e-mail mémorisé en session lors de l'inscription ; supprimer le repli « dernier utilisateur de la base » ; renvoyer 404 sinon | `SecurityController.php` | Faible (vérifier la page d'attente de vérification) |
| ANO-35 | Formulaire de véhicule en erreur 500 | `#[Assert\Range(min: 1, max: 9, notInRangeMessage: …)]` | `Vehicule.php` (1 ligne) | Très faible |
| ANO-38 | Surréservation en cas d'accès simultanés | Verrou pessimiste dans une transaction (`find(..., LockMode::PESSIMISTIC_WRITE)`) sur le trajet lors de la réservation (deux voies) | `ReservationController.php` | Faible (InnoDB) ; test IT-07 à adapter au verrou |

## Lot B — Sévérité S3 (règles métier et robustesse)

| Anomalie | Correction proposée | Fichiers |
|---|---|---|
| ANO-09 | Refuser l'annulation si le départ est passé (CA-10) | `ReservationController.php` |
| ANO-14, 15 | Valider l'entité `Trajet` avec le validateur Symfony avant `persist` ; contrainte de départ `GreaterThan('now')` au lieu de `'today'` ; prix converti seulement s'il est numérique | `TrajetController.php`, `Trajet.php` |
| ANO-16 | Statut initial `EN_ATTENTE` (le message affiché l'annonce déjà ; l'administrateur valide via ANO-13) | `TrajetController.php` |
| ANO-17 | Refuser un véhicule dont le conducteur n'est pas propriétaire | `TrajetController.php` |
| ANO-19 | Date de recherche invalide : filtre ignoré avec un message, sans exception | `TrajetController.php` |
| ANO-23 | Suppressions administratives : intercepter l'erreur de clé étrangère et afficher un refus explicite | `AdminController.php` |
| ANO-25 | Ajouter `use App\Entity\User;` (`AvisController`) et `use App\Form\RegistrationType;` (`SecurityController`) | 2 contrôleurs |
| ANO-26 | Avis autorisé seulement si la date d'arrivée du trajet est passée | `AvisController.php` |
| ANO-31 | Afficher les messages flash dans les templates qui reçoivent des redirections (réservations, réclamations, administration, recherche) | Templates |
| ANO-34 | Calculer et transmettre `stats` au tableau de bord passager | `DashboardController.php` |
| ANO-36 | Associer un libellé (`<label for>` ou `aria-label`) aux listes et au champ date | 3 templates |

## Lot C — Sévérité S4 et évolutions (à arbitrer)

| Anomalie | Nature | Proposition |
|---|---|---|
| ANO-24, 28, 33 | Statistiques fausses ou codées en dur | Calcul à partir des données (correction simple) |
| ANO-27 | Dépréciations `AvisType` | Arguments nommés (même correction qu'ANO-06) |
| ANO-30 | Message de connexion en anglais | Traduction française du message de sécurité |
| ANO-37 | Contraste | Ajuster deux couleurs CSS |
| ANO-32 | Fuseau UTC | **Décision produit requise** (Africa/Tunis ?) puis `date.timezone` / `date_default_timezone_set` |
| ANO-18 | Filtre par prix absent | **Évolution fonctionnelle**, hors correction de défaut |
| ANO-29 | Pas de modification du profil | **Évolution fonctionnelle** (nouvelle page), hors correction de défaut |

## Ambiguïtés produit

- Statut `EN_COURS` des réclamations : **tranché** le 11/11/2026, le traitement direct est accepté.
- Délai d'annulation de 2 h (règle présente dans le code, absente des exigences officielles).
- Retraitement d'une réclamation déjà `TRAITEE` (T-03).
