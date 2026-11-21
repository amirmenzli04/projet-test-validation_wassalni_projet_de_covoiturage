# Preuves d'exécution — Semaine 6

## Campagne

- Date : 11 novembre 2026
- Commande : `php bin/phpunit --testdox --colors=never --log-junit preuves\semaine6\phpunit-junit.xml`
- Environnement : PHP 8.4.27, PHPUnit 12.5.23
- Résultat : 27 tests réussis, 74 assertions, 0 échec, 0 erreur

## Fichiers de preuve

- `phpunit-testdox.txt` : date, révision, environnement, noms TestDox et résultat console.
- `phpunit-junit.xml` : résultat structuré PHPUnit, avec chaque classe et méthode de test.

Le rapport PDF [Semaine 6](../../docs/dossier-tests/Semaine-6-Tests-unitaires-automatises.pdf) présente les 18 tests unitaires par identifiant, leur fichier source, le comportement vérifié et le résultat. Les 9 autres tests de la campagne complète sont fonctionnels ou contrôleur.

## Vérification d'un cas

Rechercher l'identifiant de méthode PHPUnit dans `phpunit-junit.xml` sous un élément `testcase` (attributs `class` et `name`). Le test doit être présent sans élément enfant `failure` ni `error`. Le fichier TestDox fournit en parallèle le libellé lisible et le résultat réussi (`✔`).
