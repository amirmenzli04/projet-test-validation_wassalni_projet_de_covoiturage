# Index des preuves — WASSALNI (version finale)

Point d'entrée pour vérifier le travail. Le [registre des preuves](registre-preuves.md) détaille chaque identifiant. Toutes les preuves sont des **sorties réelles** d'exécution ou des **captures réelles** de l'application (comptes fictifs uniquement). Les captures `S06_*`, `S07_*`, `S08_*` et `S10_*` sont le rendu des fichiers de sortie archivés : leur fichier source est indiqué en en-tête.

| Semaine | Dossier | Contenu | État |
|---|---|---|---|
| 1 | [`preuves/semaine1/`](../../preuves/semaine1/) | 12 captures d'essais manuels (originaux) et [copies anonymisées](../../preuves/semaine1/anonymise/) légendées | Complet |
| 2 | [`preuves/semaine2/`](../../preuves/semaine2/) | Environnement de test, premières exécutions PHPUnit | Complet |
| 3 | [`preuves/semaine3/`](../../preuves/semaine3/) | Liste des routes (`debug:router`) : périmètre API, ANO-29 | Complet |
| 4 | — | Cas TC exécutés : voir semaine 7 (P1) | Complet |
| 5 | [`preuves/semaine5/`](../../preuves/semaine5/README.md) | ST-01 à ST-10 (captures et relectures SQL), transitions, exploration, fuseau horaire | Complet |
| 6 | [`preuves/semaine6/`](../../preuves/semaine6/) et [`campagne-finale/`](../../preuves/semaine6/campagne-finale/README.md) | TestDox, JUnit, couverture (texte, clover, HTML), captures | Complet |
| 7 | [`p1-catalogue/`](../../preuves/semaine7/p1-catalogue/README.md), [`campagne-finale/`](../../preuves/semaine7/campagne-finale/) | Catalogue TC en HTTP/BDD, intégration, isolation, journal des erreurs 500 | Complet |
| 8 | [`preuves/semaine8/`](../../preuves/semaine8/) | Newman (console, JSON nettoyé, JUnit), capture | Complet |
| 9 | [`preuves/semaine9/`](../../preuves/semaine9/README.md) | Parcours critique, axe, sécurité, k6, concurrence, audits | Complet |
| 10 | [`p0-reference/`](../../preuves/semaine10/p0-reference/README.md), [`regression/`](../../preuves/semaine10/regression/), [`campagne-finale/`](../../preuves/semaine10/campagne-finale/) | Version de référence, référence « avant corrections », campagne finale complète | Complet |

## Vérifier une preuve

1. Repérer l'identifiant `PREUVE-Sxx-nnn` dans le registre.
2. Ouvrir le fichier indiqué et comparer avec le résultat consigné.
3. Rejouer : `powershell -NoProfile -ExecutionPolicy Bypass -File scripts/run-regression.ps1`.
