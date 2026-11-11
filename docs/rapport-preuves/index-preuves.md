# Index des preuves — WASSALNI

Point d'entrée pour vérifier le travail. Le détail de chaque preuve (date, environnement, résultat, statut) figure dans le [registre des preuves](registre-preuves.md).

| Semaine | Dossier | Contenu actuel | État |
|---|---|---|---|
| 1 | [`preuves/semaine1/`](../../preuves/semaine1/) | 12 captures d'essais manuels (04/11/2026) | Présent ; légendes et anonymisation des onglets à faire (P5) |
| 2 | [`preuves/semaine2/`](../../preuves/semaine2/) | Environnement de test et premières exécutions PHPUnit | Présent |
| 3 | — | Traçabilité dans les documents (aucune preuve d'exécution attendue) | Matrice complète à produire (P5) |
| 4 | — | Cas de test dans les documents | Résultats à reporter par cas (P5) |
| 5 | `preuves/semaine5/` | — | À produire (P2) |
| 6 | [`preuves/semaine6/`](../../preuves/semaine6/) | TestDox et JUnit, 27 tests | Présent |
| 7 | `preuves/semaine7/` | — | À produire (P1) |
| 8 | `preuves/semaine8/` | — | À produire (P3) |
| 9 | `preuves/semaine9/` | — | À produire (P2 / P4) |
| 10 | [`preuves/semaine10/p0-reference/`](../../preuves/semaine10/p0-reference/) | Version de référence et comparaison avec `b6dc42a` | Présent ([README](../../preuves/semaine10/p0-reference/README.md)) |

## Vérifier une preuve

1. Repérer l'identifiant `PREUVE-Sxx-nnn` dans le registre.
2. Ouvrir le fichier indiqué et comparer avec le résultat consigné.
3. Pour les sorties PHPUnit, relancer la commande du README du dossier concerné sur la révision indiquée.
