# Preuves — Semaine 6, campagne finale (tests unitaires et couverture)

- **Date :** 11 novembre 2026, vers 22 h 10 (UTC+1)
- **Outils :** PHP 8.4.27, PHPUnit 12.5.23, **PCOV 1.0.12** (DLL officielle `php_pcov-1.0.12-8.4-nts-vs17-x64`, déposée dans `tools/pcov/` et chargée par `-d extension=…`, sans modifier `php.ini`)
- **Prédécesseur :** les preuves du 11/11 matin (27 tests, 74 assertions) restent dans `preuves/semaine6/` ; elles ne sont pas modifiées.

## Fichiers

| Fichier | Contenu | Résultat |
|---|---|---|
| `S06_unitaire-couverture.txt` / `S06_unitaire-junit.xml` / `S06_couverture-unitaire-clover.xml` | Suite `unitaire` seule (TestDox et couverture) | **33 tests, 52 assertions, OK** ; lignes 7,00 % (106/1515), méthodes 16,67 % |
| `S06_regression-couverture.txt` / `S06_couverture-complete-clover.xml` | Régression complète (unitaire, intégration, fonctionnel) | **81 tests, 225 assertions, OK** ; lignes 34,65 % (525/1515), méthodes 49,56 %, classes 18,92 % |
| `couverture-html/index.html` | Rapport HTML de couverture navigable (régression complète) | — |

Le groupe `anomalie-ouverte` est exclu de ces mesures. Les tests qui démontrent des anomalies (dont UT-04, UT-05 et UT-06) sont rejoués à part : `vendor\bin\phpunit --group anomalie-ouverte`.

## Reproduire

```powershell
$pcov = "-d extension=$PWD\tools\pcov\php_pcov.dll -d pcov.enabled=1 -d pcov.directory=src"
php $pcov.Split(' ') vendor\bin\phpunit --testsuite unitaire --coverage-text
php $pcov.Split(' ') vendor\bin\phpunit --coverage-html preuves\semaine6\campagne-finale\couverture-html
```
