<?php

namespace App\Tests\Entity;

use App\Entity\Avis;
use App\Entity\Trajet;
use App\Entity\User;
use App\Entity\Vehicule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Semaine 6 — Tests unitaires des règles métier portées par les entités (sans base, sans HTTP).
 * Structure Arrange – Act – Assert ; valeurs limites (VL) et partitions d'équivalence (EP).
 *
 * Point d'analyse : les contraintes de Trajet sont correctes au niveau de l'entité, mais
 * TrajetController ne les invoque pas (ANO-14, ANO-15). Ces tests montrent donc que la règle
 * existe dans le modèle ; les tests système montrent qu'elle n'est pas appliquée.
 */
final class RegleMetierEntitesTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
    }

    private function violations(object $object, string $property): int
    {
        return $this->validator->validateProperty($object, $property)->count();
    }

    private function trip(string $departure, string $arrival, float $price = 15.0): Trajet
    {
        return (new Trajet())
            ->setVilleDepart('Tunis')
            ->setDestination('Sousse')
            ->setDateDepart(new \DateTime($departure))
            ->setDateArrivee(new \DateTime($arrival))
            ->setPrix($price);
    }

    #[TestDox('UT-01 (EX-09, VL) — arrivée égale au départ refusée ; arrivée une minute après acceptée')]
    public function testArriveeStrictementApresLeDepart(): void
    {
        // Arrange
        $equal = $this->trip('+1 day 08:00', '+1 day 08:00');
        $oneMinuteLater = $this->trip('+1 day 08:00', '+1 day 08:01');

        // Act
        $equalViolations = $this->violations($equal, 'dateArrivee');
        $laterViolations = $this->violations($oneMinuteLater, 'dateArrivee');

        // Assert
        self::assertSame([1, 0], [$equalViolations, $laterViolations]);
    }

    public static function prices(): iterable
    {
        yield 'prix -1 (EP négatif)' => [-1.0, 1];
        yield 'prix 0 (VL)' => [0.0, 1];
        yield 'prix 0,01 (VL)' => [0.01, 0];
        yield 'prix 15 (EP nominal)' => [15.0, 0];
    }

    #[DataProvider('prices')]
    #[TestDox('UT-02 (EX-10, CA-03) — $_dataName : $expected violation(s)')]
    public function testPrixStrictementPositif(float $price, int $expected): void
    {
        // Arrange
        $trip = $this->trip('+1 day 08:00', '+1 day 10:00', $price);

        // Act
        $count = $this->violations($trip, 'prix');

        // Assert
        self::assertSame($expected, $count);
    }

    #[TestDox('UT-03 (EX-09, VL) — départ hier refusé, départ demain accepté')]
    public function testDepartDansLeFutur(): void
    {
        // Arrange
        $yesterday = $this->trip('-1 day 08:00', '-1 day 10:00');
        $tomorrow = $this->trip('+1 day 08:00', '+1 day 10:00');

        // Act / Assert
        self::assertSame([1, 0], [$this->violations($yesterday, 'dateDepart'), $this->violations($tomorrow, 'dateDepart')]);
    }

    #[Group('anomalie-ouverte')]
    #[Group('ANO-14')]
    #[TestDox('UT-04 (CA-02, VL) — départ aujourd\'hui à 00:00:01 (déjà passé) : refus attendu')]
    public function testDepartPasseDuJourMeme(): void
    {
        // Arrange : instant passé de la journée courante (toujours passé, sauf à minuit exactement)
        $earlierToday = $this->trip('today 00:00:01', 'today 23:59:00');

        // Act
        $count = $this->violations($earlierToday, 'dateDepart');

        // Assert : la contrainte GreaterThan("today") compare à minuit, pas à l'instant présent
        self::assertSame(1, $count, 'Un départ déjà passé de la journée est accepté par la contrainte de l\'entité');
    }

    public static function seats(): iterable
    {
        yield '0 place (VL)' => [0, 1];
        yield '1 place (VL)' => [1, 0];
        yield '9 places (VL)' => [9, 0];
        yield '10 places (VL)' => [10, 1];
    }

    #[Group('ANO-35')]
    #[DataProvider('seats')]
    #[TestDox('UT-05 (EX-06) — véhicule : $_dataName → $expected violation(s)')]
    public function testNombreDePlacesDuVehicule(int $seats, int $expected): void
    {
        $vehicle = (new Vehicule())->setNbPlaces($seats);
        self::assertSame($expected, $this->violations($vehicle, 'nbPlaces'));
    }

    #[Group('ANO-35')]
    #[TestDox('UT-06 (EX-06) — immatriculation : « TUN1234 » acceptée, « 123 TUN 4567 » (format avec espaces) refusée')]
    public function testFormatImmatriculation(): void
    {
        $compact = (new Vehicule())->setImmatriculation('TUN1234');
        $spaced = (new Vehicule())->setImmatriculation('123 TUN 4567');
        // Constat : le format tunisien usuel avec espaces est refusé par la règle /^[A-Z0-9]{1,10}$/.
        self::assertSame([0, 1], [$this->violations($compact, 'immatriculation'), $this->violations($spaced, 'immatriculation')]);
    }

    public static function notes(): iterable
    {
        yield 'note 0 (VL)' => [0, 1];
        yield 'note 1 (VL)' => [1, 0];
        yield 'note 5 (VL)' => [5, 0];
        yield 'note 6 (VL)' => [6, 1];
    }

    #[DataProvider('notes')]
    #[TestDox('UT-07 (EX-21, CA-18) — avis : $_dataName → $expected violation(s)')]
    public function testBornesDeLaNote(int $note, int $expected): void
    {
        $review = (new Avis())->setNote($note);
        self::assertSame($expected, $this->violations($review, 'note'));
    }

    #[TestDox('UT-08 (EX-31) — getRoles() ajoute toujours ROLE_USER, sans doublon ; un nouvel utilisateur est passager')]
    public function testRolesUtilisateur(): void
    {
        // Arrange
        $newUser = new User();
        $driver = (new User())->setRoles(['ROLE_CONDUCTEUR', 'ROLE_USER']);

        // Act
        $newUserRoles = $newUser->getRoles();
        $driverRoles = array_values($driver->getRoles());

        // Assert
        self::assertSame(['ROLE_PASSAGER', 'ROLE_USER'], $newUserRoles);
        self::assertSame(['ROLE_CONDUCTEUR', 'ROLE_USER'], $driverRoles);
    }

    #[TestDox('UT-09 (EX-23) — note moyenne du conducteur : (4 + 5 + 5) / 3 arrondie à 4,7 ; aucun avis → 0')]
    public function testNoteMoyenneDuConducteur(): void
    {
        // Arrange
        $driver = new User();
        foreach ([4, 5, 5] as $note) {
            $driver->getAvisRecus()->add((new Avis())->setNote($note));
        }

        // Act / Assert
        self::assertSame(4.7, $driver->getNoteMoyenne());
        self::assertSame(3, $driver->getNombreAvis());
        self::assertEquals(0, (new User())->getNoteMoyenne());
    }
}
