<?php

namespace App\Tests\Integration;

use App\Entity\Reclamation;
use App\Entity\Reservation;
use App\Entity\Trajet;
use App\Entity\User;
use App\Entity\Vehicule;
use App\Tests\Support\ReservationTestFixture;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Semaine 7 — Intégration Doctrine / MariaDB (wassalni_test, InnoDB) : transactions, rollback,
 * atomicité d'un flush en échec, relations bidirectionnelles et propagation des suppressions.
 * Données fictives phpunit-*@example.test, supprimées en fin de test (vérifié par comptage).
 */
final class TransactionIntegriteTest extends KernelTestCase
{
    use ReservationTestFixture;

    protected function setUp(): void
    {
        self::bootKernel();
        self::assertStringEndsWith('_test', (string) $this->entityManager()->getConnection()->getDatabase());
    }

    protected function tearDown(): void
    {
        // Après une exception de flush, l'EntityManager est fermé : on le réinitialise avant le nettoyage.
        static::getContainer()->get('doctrine')->resetManager();
        $connection = $this->entityManager()->getConnection();
        $users = $this->fixtureEmails === [] ? [] : $connection->fetchFirstColumn(
            'SELECT id FROM `user` WHERE email IN (?)', [$this->fixtureEmails], [\Doctrine\DBAL\ArrayParameterType::STRING]);
        if ($users !== []) {
            $ids = implode(',', array_map('intval', $users));
            $connection->executeStatement("DELETE FROM reclamation WHERE user_id IN ($ids) OR trajet_concerne_id IN (SELECT id FROM trajet WHERE conducteur_id IN ($ids))");
        }
        $this->cleanReservationFixtures();
        parent::tearDown();
    }

    private function em(): EntityManagerInterface
    {
        return $this->entityManager();
    }

    private function seats(int $tripId): int
    {
        return (int) $this->em()->getConnection()->fetchOne('SELECT places_disponibles FROM trajet WHERE id = ?', [$tripId]);
    }

    private function reservationsOn(int $tripId): int
    {
        return (int) $this->em()->getConnection()->fetchOne('SELECT COUNT(*) FROM reservation WHERE trajet_id = ?', [$tripId]);
    }

    #[TestDox('IT-01 (CT-25, EX-35) — rollback explicite : réservation et décrément de stock annulés ensemble')]
    public function testRollbackExplicite(): void
    {
        // Arrange
        $fixture = $this->createReservationFixture();
        $tripId = $fixture['trip']->getId();
        $em = $this->em();
        $trip = $em->find(Trajet::class, $tripId);
        $passenger = $em->find(User::class, $fixture['passenger']->getId());

        // Act
        $em->beginTransaction();
        $em->persist((new Reservation())->setPassager($passenger)->setTrajet($trip)->setNbPlaces(2));
        $trip->setPlacesDisponibles($trip->getPlacesDisponibles() - 2);
        $em->flush();
        $visibleInsideTransaction = $this->reservationsOn($tripId);
        $em->rollback();
        $em->clear();

        // Assert
        self::assertSame(1, $visibleInsideTransaction, 'La réservation doit être visible dans la transaction');
        self::assertSame(['reservations' => 0, 'places' => 4], ['reservations' => $this->reservationsOn($tripId), 'places' => $this->seats($tripId)]);
    }

    #[TestDox('IT-02 (CT-25) — échec au milieu d\'un flush (e-mail en double) : rien n\'est persisté, stock intact')]
    public function testFlushAtomiqueEnCasDEchec(): void
    {
        // Arrange
        $fixture = $this->createReservationFixture();
        $tripId = $fixture['trip']->getId();
        $em = $this->em();
        $trip = $em->find(Trajet::class, $tripId);
        $passenger = $em->find(User::class, $fixture['passenger']->getId());
        $duplicate = (new User())->setEmail($passenger->getEmail())->setNom('Doublon')->setPrenom('Test')
            ->setTelephone('10000000')->setPassword('x')->setRoles(['ROLE_PASSAGER']);

        // Act : une réservation valide, un décrément de stock et un utilisateur en double dans le même flush
        $em->persist((new Reservation())->setPassager($passenger)->setTrajet($trip)->setNbPlaces(1));
        $trip->setPlacesDisponibles(3);
        $em->persist($duplicate);
        $failed = false;
        try {
            $em->flush();
        } catch (UniqueConstraintViolationException) {
            $failed = true;
        }
        static::getContainer()->get('doctrine')->resetManager();

        // Assert
        self::assertTrue($failed, 'Le flush aurait dû échouer sur la contrainte d\'unicité');
        self::assertSame(['reservations' => 0, 'places' => 4], ['reservations' => $this->reservationsOn($tripId), 'places' => $this->seats($tripId)]);
    }

    #[TestDox('IT-03 (CT-25) — wrapInTransaction : une exception métier après le flush annule toutes les écritures')]
    public function testWrapInTransactionAnnuleSurException(): void
    {
        $fixture = $this->createReservationFixture();
        $tripId = $fixture['trip']->getId();
        $passengerId = $fixture['passenger']->getId();

        try {
            $this->em()->wrapInTransaction(function (EntityManagerInterface $em) use ($tripId, $passengerId): void {
                $trip = $em->find(Trajet::class, $tripId);
                $em->persist((new Reservation())->setPassager($em->find(User::class, $passengerId))->setTrajet($trip)->setNbPlaces(4));
                $trip->setPlacesDisponibles(0);
                $em->flush();
                throw new \DomainException('Paiement refusé (échec simulé)');
            });
            self::fail('Exception attendue');
        } catch (\DomainException) {
        }
        static::getContainer()->get('doctrine')->resetManager();

        self::assertSame(['reservations' => 0, 'places' => 4], ['reservations' => $this->reservationsOn($tripId), 'places' => $this->seats($tripId)]);
    }

    #[TestDox('IT-04 (EX-18) — relations relues depuis la base : Trajet↔Reservation, Vehicule→Trajet (ANO-05), User→Reservation')]
    public function testRelationsBidirectionnelles(): void
    {
        $fixture = $this->createReservationFixture();
        $em = $this->em();
        $em->persist((new Reservation())->setPassager($em->find(User::class, $fixture['passenger']->getId()))
            ->setTrajet($em->find(Trajet::class, $fixture['trip']->getId()))->setNbPlaces(1));
        $em->flush();
        $em->clear();

        $trip = $em->find(Trajet::class, $fixture['trip']->getId());
        $vehicle = $em->find(Vehicule::class, $fixture['vehicle']->getId());
        $passenger = $em->find(User::class, $fixture['passenger']->getId());

        self::assertCount(1, $trip->getReservations());
        self::assertSame($passenger->getId(), $trip->getReservations()->first()->getPassager()->getId());
        self::assertTrue($vehicle->getTrajets()->contains($trip));
        self::assertCount(1, $passenger->getReservations());
        self::assertSame(25.0, $trip->getReservations()->first()->getPrixTotal());
    }

    #[TestDox('IT-05 (CA-20) — suppression d\'un trajet : ses réservations sont supprimées en cascade, aucun orphelin')]
    public function testCascadeDesReservations(): void
    {
        $fixture = $this->createReservationFixture();
        $em = $this->em();
        $em->persist((new Reservation())->setPassager($em->find(User::class, $fixture['passenger']->getId()))
            ->setTrajet($em->find(Trajet::class, $fixture['trip']->getId()))->setNbPlaces(1));
        $em->flush();
        $tripId = $fixture['trip']->getId();
        // Reservation::setTrajet() ne synchronise pas Trajet::$reservations (côté inverse) : sans clear(),
        // la collection en mémoire reste vide et la cascade ne voit rien. On simule une nouvelle requête.
        $em->clear();

        $em->remove($em->find(Trajet::class, $tripId));
        $em->flush();

        self::assertSame(0, $this->reservationsOn($tripId));
        self::assertSame(0, (int) $em->getConnection()->fetchOne('SELECT COUNT(*) FROM reservation r LEFT JOIN trajet t ON t.id = r.trajet_id WHERE t.id IS NULL'));
    }

    #[TestDox('IT-06 (CA-20, EX-35) — trajet visé par une réclamation : la base refuse la suppression (RESTRICT), trajet et réclamation intacts')]
    public function testSuppressionBloqueeParUneReclamation(): void
    {
        $fixture = $this->createReservationFixture();
        $em = $this->em();
        $tripId = $fixture['trip']->getId();
        $em->persist((new Reclamation())->setSujet('Retard important')->setMessage('Réclamation liée au trajet')
            ->setUser($em->find(User::class, $fixture['passenger']->getId()))->setTrajetConcerne($em->find(Trajet::class, $tripId)));
        $em->flush();

        $refused = false;
        try {
            $em->remove($em->find(Trajet::class, $tripId));
            $em->flush();
        } catch (ForeignKeyConstraintViolationException) {
            $refused = true;
        }
        static::getContainer()->get('doctrine')->resetManager();

        self::assertTrue($refused);
        self::assertSame(1, (int) $this->em()->getConnection()->fetchOne('SELECT COUNT(*) FROM trajet WHERE id = ?', [$tripId]));
    }
}
