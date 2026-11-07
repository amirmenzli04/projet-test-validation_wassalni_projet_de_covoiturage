# Fiche d'environnement - Semaine 2

Date : 7 novembre 2026
Depot : projet-test-validation_wassalni_projet_de_covoiturage (commits 4f5ced9 et fcbb7e4)

- PHP 8.4.27 (CLI), Symfony 7.4.23, PHPUnit 12.5.23
- Base de test : wassalni_test sur MariaDB 10.4.32 (verifiee par SELECT DATABASE() en environnement test)
- Moteur des tables de test : InnoDB sur les 8 tables (preuves/semaine2/moteurs-wassalni_test.txt)
- La base reelle wassalni n'existe pas sur ce serveur : les tests ne peuvent pas la modifier.
- Deux environnements : recette manuelle (WAMP, MySQL 9.1.0, PHP 8.3.14, tables MyISAM sans cle etrangere)
  et tests automatises (MariaDB 10.4.32, PHP 8.4.27, tables InnoDB avec cles etrangeres).
- Mailer : null://null ; Messenger : in-memory
- Commande : vendor\bin\phpunit --testdox
- Resultat : OK (8 tests, 15 assertions) - preuves/semaine2/phpunit-run1.txt

Remarques
- Les 8 tests existants viennent du projet importe : HealthController et ReservationService.
- ReservationService n'est appele nulle part dans src, config ni templates (anomalie A-13) :
  ces tests ne couvrent pas la logique reelle de ReservationController.
- Les migrations ont ete corrigees dans le commit fcbb7e4 (anomalie A-12).