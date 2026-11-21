# Preuves — Semaine 1 (essais exploratoires initiaux)

- **Date des captures :** 4 novembre 2026, entre 22 h 30 et 22 h 45 (horloge visible sur les captures)
- **Environnement :** recette manuelle WAMP (MySQL 9.1.0, PHP 8.3.14), base de développement. **Ce n'est pas la base `wassalni_test`.**
- **Type de preuve :** captures manuelles de navigateur. Elles sont illustratives ou montrent un résultat observé. Ce sont des observations ponctuelles, non des tests automatisés rejouables.

## Originaux et copies anonymisées

Les fichiers `essai*.png` sont les **originaux, conservés sans modification** (empreintes SHA-256 vérifiées après l'anonymisation). Le dossier [`anonymise/`](anonymise/) contient des copies destinées au rapport, produites par `python scripts/anonymiser_captures_s1.py`. Les zones suivantes y sont floutées : onglets du navigateur, avatar du profil, barre des tâches, e-mail de la barre Symfony, noms d'utilisateurs de la base de développement, chemin local contenant le nom du compte Windows.

## Légendes

| Copie anonymisée | Original | Ce que montre la capture | Lien |
|---|---|---|---|
| `S01_E01_tableau_bord_conducteur.png` | essai1 | Tableau de bord conducteur : 12 trajets, 2 véhicules, 14 passagers, 0 trajet actif ; trajets du 04/11/2026 à 00:00 | Illustration ; R12 |
| `S01_E02_publication_arrivee_avant_depart.png` | essai2 | Alerte JavaScript « La date d'arrivée doit être égale ou après la date de départ » | CA-02 côté client (le serveur est vérifié par TC-13) |
| `S01_E03_recherche_trajets_passes_proposes.png` | essai3 | Recherche Sousse→Monastir à 22 h 34 le 04/11/2026 : trajets du 28/05/2026 et du 04/11/2026 à 00:00 proposés avec « Réserver » | A-03 → ANO-10 |
| `S01_E04_doublon_reservation_refuse_interface.png` | essai4 | Après une réservation (4 → 3 places), une seconde tentative est refusée : « Vous avez déjà réservé ce trajet » (voie JSON) | CA-09 ; la voie HTML n'est pas protégée (ANO-08) |
| `S01_E05_publication_date_passee_refusee.png` | essai5 | Alerte JavaScript : départ dans le passé et arrivée avant départ refusés | CA-02 côté client |
| `S01_E06_passager_sur_admin_403.png` | essai6 | `/admin/dashboard` : HTTP 403, « The user doesn't have ROLE_ADMIN » | CA-12 (confirmé par TC-41) |
| `S01_E08_mes_trajets_conducteur.png` | essai8 | Liste « Mes trajets » du conducteur | Illustration |
| `S01_E10_get_last_user_email_sans_connexion.png` | essai10 | `/get-last-user-email` renvoie `{"email": "client@test.com"}` | A-02 → ANO-22 (confirmé par TC-45) |
| `S01_E11_admin_liste_trajets.png` | essai11 | Administration des trajets : 35 validés, 0 en attente ; actions « Supprimer » par lien | A-01, A-06 → ANO-13, ANO-16 |
| `S01_E12_reclamation_autrui_403.png` | essai12 | `/reclamation/voir/6` : HTTP 403 « Vous ne pouvez pas voir cette réclamation » | CA-13 (confirmé par `ReclamationWorkflowTest`) |
| `S01_E13_connexion_identifiants_invalides.png` | essai13 | Connexion avec `test@tes.com` (adresse mal saisie) : « Invalid credentials. » | A-09 → ANO-30 |
| `S01_E13b_connexion_identifiants_invalides.png` | essai13b | Même message avec `test@test.com` | A-09 |

**Remarques**

- Les captures `essai7` et `essai9`, citées dans les PDF des semaines 2 et 3, ne sont pas présentes dans ce dossier.
- `essai13` ne montre pas le refus d'un compte bloqué : la mention correspondante du PDF de la semaine 3 n'est pas étayée par cette capture, et le test TC-04 démontre au contraire qu'un compte bloqué peut se connecter (ANO-21).
