# 5. Transitions d'état, droits et tests exploratoires

## 5.1 Objectif, périmètre et état d'exécution

Cette fiche prépare la semaine 5 à partir des entités, contrôleurs et règles de sécurité du dépôt. Elle décrit le comportement observé dans le code et les scénarios à exécuter. **Les parcours exploratoires ne sont pas déclarés exécutés** : ils nécessitent une application démarrée, des comptes fictifs et une base de test isolée.

Périmètre : cycle de vie des réclamations, lecture par propriétaire, accès administrateur, refus par rôle, et cohérence de la réservation lors des explorations croisées. Ne pas utiliser de données personnelles ni exécuter des scénarios mutatifs sur une base de production.

## 5.2 Diagramme d'états des réclamations

```text
                         réponse non vide, action admin
  [création]       +-----------------------------------------+
      |            |                                         v
      v            |                                  +-------------+
 +------------+    |                                  |   TRAITEE   |
 | EN_ATTENTE |----+                                  +-------------+
 +------------+                                       dateTraitement
      |
      | EN_COURS est déclaré dans le commentaire de l'entité,
      | mais aucune transition d'application vers cet état n'a été trouvée
      v
 +------------+  - - - réponse admin / transition à arbitrer - - -> TRAITEE
 |  EN_COURS  |
 +------------+

Transitions non spécifiées ou sans garde dans le modèle actuel :
  TRAITEE -> EN_ATTENTE ?     EN_ATTENTE -> statut inconnu ?
```

**Légende :** flèche pleine = transition observée dans le flux contrôleur; flèche pointillée = transition mentionnée comme possibilité, mais non implémentée/confirmée. Ce schéma ne transforme pas les ambiguïtés produit en règles approuvées.

### État observé dans le code

- La création d'une réclamation valide initialise explicitement `EN_ATTENTE`.
- Le traitement est protégé par `ROLE_ADMIN`; une réponse non vide est enregistrée, puis le contrôleur fixe `TRAITEE` et `dateTraitement`.
- `EN_COURS` apparaît dans le commentaire de l'entité, mais aucune action qui l'applique n'a été identifiée dans le parcours examiné.
- `Reclamation::setStatut()` accepte une chaîne sans liste autorisée ni contrôle de transition. L'entité ne définit donc pas à elle seule une machine à états.
- Le flux du contrôleur appelle deux fois la mise à jour de `dateTraitement` (via `setStatut('TRAITEE')`, puis `setDateTraitement`). Vérifier que la valeur persistée reste cohérente; ne pas en déduire une anomalie sans reproduction.

### Matrice de transitions à tester

| État initial | Acteur / action | État attendu selon le flux observé | Décision / résultat du test |
|---|---|---|---|
| Aucun | Utilisateur authentifié soumet un formulaire valide | `EN_ATTENTE`; date de création présente; propriétaire = utilisateur connecté | À exécuter (CT-18) |
| `EN_ATTENTE` | Administrateur soumet une réponse non vide | `TRAITEE`; réponse et date de traitement présentes | À exécuter (CT-22) |
| `EN_ATTENTE` | Administrateur soumet une réponse vide | Aucun traitement; flash d'erreur; état inchangé | À exécuter (CT-22, variante) |
| `EN_ATTENTE` | Utilisateur non administrateur appelle l'action admin | Refus d'accès; état inchangé | À exécuter (CT-02/03, EXPL-03) |
| `EN_ATTENTE` | Passage à `EN_COURS` | Aucune action web observée; règle cible à décider | Bloqué produit (CT-23) |
| `TRAITEE` | Deuxième soumission / modification | Garde de transition non visible; comportement cible à décider | À explorer; ne pas présumer un refus |
| Tout état | Statut inconnu transmis directement au setter | Pas de garde observée dans l'entité | Test unitaire de modèle à envisager après décision produit |

## 5.3 Matrice des rôles et propriétaires

| Fonction | Anonyme | Utilisateur authentifié / propriétaire | Autre utilisateur | Conducteur | Administrateur |
|---|---|---|---|---|---|
| Créer une réclamation (`/reclamation/nouvelle`) | Refus par `ROLE_USER` | Autorisé; réclamation rattachée à la session | Chaque compte crée la sienne | Autorisé si `ROLE_USER` (rôle hiérarchique à confirmer) | Autorisé si `ROLE_USER` |
| Liste « mes réclamations » | Refus par `ROLE_USER` | Liste filtrée sur l'utilisateur courant | Ne doit pas voir la liste d'autrui | Même règle utilisateur | La route de liste reste filtrée sur l'utilisateur courant |
| Détail `/reclamation/voir/{id}` | Refus par `ROLE_USER` | Autorisé pour le propriétaire | Refus explicite si non propriétaire | Autorisé seulement si propriétaire | Autorisé par exception dans le contrôleur |
| Liste/traitement admin | Refus | Refus sans `ROLE_ADMIN` | Refus sans `ROLE_ADMIN` | Refus sans `ROLE_ADMIN` | Autorisé; réponse non vide traite la réclamation |
| Réserver / annuler | Refus par `ROLE_PASSAGER` | Passager autorisé pour ses propres actions | Le propriétaire doit être contrôlé à l'annulation | Refus sauf si aussi `ROLE_PASSAGER` | À tester selon la composition réelle de ses rôles |
| Gérer un véhicule | Refus par `ROLE_CONDUCTEUR` | Propriétaire du véhicule autorisé | Un autre propriétaire doit être refusé pour modifier/supprimer | `ROLE_CONDUCTEUR` requis | À tester selon les rôles effectifs |

Les refus attendus ci-dessus sont des critères de test à confirmer avec une session HTTP et la configuration réelle des rôles. `ROLE_USER` est un attribut de contrôleur; vérifier les rôles hérités/attribués aux comptes de fixture.

## 5.4 Scénarios de test associés

| ID | Préconditions | Action | Résultat et preuve attendus |
|---|---|---|---|
| ST-01 | Utilisateur A connecté; formulaire valide | Créer une réclamation | Une seule ligne; `EN_ATTENTE`; propriétaire A; date de création; affichée dans la liste A |
| ST-02 | Message vide puis 9 caractères | Soumettre chaque cas | Formulaire invalide; aucun enregistrement |
| ST-03 | Message de 10 caractères | Soumettre | Accepté si le sujet est une valeur de la liste |
| ST-04 | Réclamation A existante; utilisateur B connecté | Ouvrir le détail par URL directe | Refus d'accès; aucune donnée de A dans la réponse |
| ST-05 | Réclamation A existante; administrateur connecté | Ouvrir son détail puis la liste admin | Consultation permise |
| ST-06 | Réclamation en attente; administrateur connecté | Répondre avec texte non vide | Réponse persistée; état `TRAITEE`; date de traitement non nulle |
| ST-07 | Réclamation en attente; administrateur connecté | Soumettre une réponse vide | Flash d'erreur; statut et date non modifiés |
| ST-08 | Réclamation en attente; compte sans rôle admin | Appeler directement la route de traitement | Accès refusé; aucune mutation |
| ST-09 | Décision produit sur `EN_COURS` non fournie | Chercher/essayer le passage à `EN_COURS` | Documenter l'absence de transition; cas bloqué, pas de résultat inventé |
| ST-10 | Deux sessions et stock isolé | Réserver dans la première, rafraîchir la seconde; annuler puis répéter | Stock, statut, montant et propriété cohérents; aucun double remboursement de places |

## 5.5 Tests exploratoires — chartes et journal

Chaque session dure environ 30 minutes. Consigner build/SHA, environnement, rôle, données fictives, heure, actions, résultat, preuve anonymisée et anomalie. Un test non exécuté reste **Non exécuté**; une règle produit inconnue reste **Bloqué**.

### Charte EXPL-01 — cycle de réclamation

- **Mission :** parcourir dépôt, suivi et traitement de la réclamation.
- **Compte/données :** utilisateur A, utilisateur B, administrateur; formulaire avec sujet de liste et message de 10 caractères.
- **Variations :** message vide/9/10 caractères; champ trajet optionnel; réponse admin vide/non vide; rafraîchissement et retour navigateur.
- **Observer :** doublons, statut, dates, propriétaire, flashs, persistance après rechargement.
- **Arrêt :** fuite d'une réclamation d'autrui ou mutation inattendue; préserver la preuve et l'état.

### Charte EXPL-02 — rôles et accès directs

- **Mission :** essayer les liens puis les URL directes avec session anonyme, passager A/B, conducteur et admin.
- **Cibles :** créer/lister/voir une réclamation; ouvrir les pages admin; annuler une réservation d'autrui.
- **Observer :** statut HTTP/redirection, contenu de la page, mutation éventuelle, différence navigation vs accès direct.
- **Résultat attendu :** refus sans révéler de données privées; seul le propriétaire ou l'admin autorisé voit le détail.

### Charte EXPL-03 — cohérence réservation/annulation

- **Mission :** suivre le stock avant/après une réservation, une annulation et une répétition.
- **Variations :** 1, nombre disponible, 0, négatif, supérieur au stock; départ à plus/moins de deux heures.
- **Observer :** UI et JSON si les deux chemins sont disponibles; prix, statut, date, stock après relecture.
- **Précaution :** données isolées; aucune concurrence destructive sur un environnement partagé.

### Charte EXPL-04 — navigation et robustesse

- **Mission :** session expirée, retour navigateur, double clic, requête répétée et formulaire partiellement rempli.
- **Observer :** doublons, erreurs non maîtrisées, messages, fuite d'informations et état final.
- **Clôture :** noter les limites de l'exploration; ne pas conclure à l'accessibilité ou à la performance sans campagne dédiée.

### Fiche de journal à remplir

| Champ | Valeur |
|---|---|
| Session / date / exécutant | À renseigner |
| Version / SHA / environnement | À renseigner |
| Charte / mission | EXPL-01 à EXPL-04 |
| Rôle et jeu de données fictif | À renseigner |
| Actions exactes et résultat attendu | À renseigner |
| Résultat observé / Réussi-Échoué-Bloqué-Non exécuté | À renseigner |
| Preuve anonymisée / anomalie liée | À renseigner |

## 5.6 Premières fiches d'observation/anomalie

Les observations ci-dessous sont issues d'une lecture statique. **Aucune n'est confirmée en exécution**. L'impact et la sévérité restent à évaluer avec une règle métier approuvée.

### OBS-05-01 — Transition directe vers `TRAITEE`

- **Préconditions :** réclamation `EN_ATTENTE`; session administrateur; base de test.
- **Étapes :** ouvrir la liste admin; saisir une réponse non vide; soumettre; relire l'état et la date.
- **Attendu :** dépend de la décision produit : soit traitement direct autorisé, soit passage intermédiaire `EN_COURS` requis.
- **Observation statique :** le contrôleur admin passe directement à `TRAITEE`; aucun flux `EN_COURS` n'a été trouvé dans le parcours examiné.
- **État :** ambiguïté à arbitrer / reproduction à exécuter; pas un défaut confirmé.
- **Priorité / sévérité :** non attribuées.

### OBS-05-02 — Transition de statut non gardée dans l'entité

- **Préconditions :** instance de réclamation en mémoire.
- **Étapes :** appeler `setStatut()` avec `EN_COURS`, `TRAITEE`, puis une chaîne non prévue; vérifier statut/date.
- **Attendu :** à définir si l'entité doit n'accepter que les transitions autorisées.
- **Observation statique :** le setter accepte une chaîne sans liste de choix ni validation de transition; `dateTraitement` est renseignée pour `TRAITEE`.
- **État :** constat de code, effet métier et reproductibilité à tester; pas un défaut confirmé.
- **Priorité / sévérité :** non attribuées.

### OBS-05-03 — Réponse et date de traitement modifiées par deux appels

- **Préconditions :** réclamation en attente; administrateur; horloge de test documentée.
- **Étapes :** traiter avec réponse non vide; relire la date enregistrée et le statut.
- **Attendu :** date de traitement renseignée une seule fois et cohérente avec l'événement.
- **Observation statique :** le contrôleur appelle `setStatut('TRAITEE')`, qui renseigne déjà la date, puis appelle `setDateTraitement()` immédiatement après.
- **État :** à reproduire; deux valeurs très rapprochées ne prouvent pas à elles seules une défaillance.
- **Priorité / sévérité :** non attribuées.

### OBS-05-04 — Comptage des réclamations en cours non explicite

- **Préconditions :** existence éventuelle d'une réclamation en `EN_COURS`.
- **Étapes :** comparer le statut stocké aux statistiques de la liste utilisateur et au tableau admin.
- **Attendu :** à définir si `EN_COURS` devient un état utilisé.
- **Observation statique :** les statistiques de la liste comptent `EN_ATTENTE` et `TRAITEE`, sans compteur explicite `EN_COURS`.
- **État :** dépend de la décision sur ce statut; pas un défaut confirmé.
- **Priorité / sévérité :** non attribuées.

### Fiche générique à ouvrir après reproduction

```text
ID / titre :
Version (SHA), environnement, date :
Rôle et données anonymisées :
Préconditions :
Étapes exactes :
Résultat attendu (règle approuvée) :
Résultat observé :
Preuve / fréquence :
Impact, sévérité, priorité :
État : À reproduire | Confirmé | Rejeté | Bloqué :
Régression et responsable :
```

## 5.7 Critères de clôture de la semaine 5

- Le responsable métier a tranché si `EN_COURS` est un état exigé et quelles transitions sont légales.
- ST-01 à ST-10 exécutés ou marqués explicitement bloqués/non exécutés avec motif.
- Les contrôles propriétaire/administrateur sont vérifiés par accès direct et par interface.
- Chaque observation est confirmée, rejetée ou conservée comme ambiguïté avec preuve anonymisée.
- Les risques de fuite d'une réclamation et de modification par rôle non autorisé sont réévalués avant la campagne de livraison.

## 5.8 Résultats réellement exécutés le 10 novembre 2026

La suite complète `php bin/phpunit` a réussi : **27 tests, 74 assertions, 0 échec et aucune dépréciation** (PHP 8.4.27, PHPUnit 12.5.23). Sept tests utilisent Symfony WebTestCase et la base de test dédiée; leurs comptes et réclamations de fixture ont été supprimés. Le contrôle SQL a trouvé zéro compte de test résiduel.

| Scénario / contrôle | Résultat observé | Statut |
|---|---|---|
| Création authentifiée d'une réclamation | Formulaire accepté; ligne persistée avec `EN_ATTENTE`, date de création et propriétaire correct | Réussi (HTTP/DB) |
| Message de 9 caractères / message de 10 caractères | 9 rejetés par le formulaire; 10 acceptés dans le scénario de création | Réussi (HTTP/DB) |
| Visiteur anonyme sur réservation, nouvelle réclamation et admin | Les trois routes redirigent vers `/connexion` (HTTP 302) | Réussi pour l'accès anonyme seulement |
| Propriétaire / utilisateur tiers sur le détail | Propriétaire autorisé; autre compte reçoit HTTP 403 sans le message testé dans la réponse | Réussi (HTTP) |
| Liste « mes réclamations » | Seule la réclamation du compte connecté est affichée | Réussi (HTTP) |
| Réponse admin non vide / vide | Texte non vide persiste avec `TRAITEE` et date; réponse vide ne modifie pas l'état | Réussi (HTTP/DB) |
| Non-admin sur administration | Un passager connecté reçoit HTTP 403; traitement réservé au rôle admin | Réussi |
| Transition vers `EN_COURS` | Aucune action observée; statut à arbitrer par le métier | Bloqué produit |
| Tests exploratoires dans un navigateur réel, accessibilité et performance | Parcours manuel et mesures non effectués | Non exécutés |

Les contrôles HTTP ont été exécutés par BrowserKit avec le kernel Symfony; ils ne constituent pas une exploration manuelle dans un navigateur. Les chartes EXPL-01 à EXPL-04 restent à effectuer; aucune des observations OBS-05-01 à OBS-05-04 n'est déclarée confirmée par exécution.
