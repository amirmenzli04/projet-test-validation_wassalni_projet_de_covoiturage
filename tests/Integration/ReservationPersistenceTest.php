<?php

namespace App\Tests\Integration;

use App\Entity\Reservation;
use App\Tests\Support\ReservationTestFixture;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ReservationPersistenceTest extends KernelTestCase
{
    use ReservationTestFixture;

    protected function setUp(): void
    {
        self::bootKernel();
    }

    protected function tearDown(): void
    {
        $this->cleanReservationFixtures();
        parent::tearDown();
    }

    public function testReservationCanBeCreatedReadUpdatedAndDeleted(): void
    {
        $fixture = $this->createReservationFixture();
        $entityManager = $this->entityManager();
        $reservation = (new Reservation())
            ->setPassager($fixture['passenger'])
            ->setTrajet($fixture['trip'])
            ->setNbPlaces(2)
            ->setStatut(Reservation::STATUT_CONFIRME);

        $entityManager->persist($reservation);
        $entityManager->flush();
        $reservationId = $reservation->getId();

        $entityManager->clear();
        $savedReservation = $entityManager->getRepository(Reservation::class)->find($reservationId);
        self::assertNotNull($savedReservation);
        self::assertSame(2, $savedReservation->getNbPlaces());
        self::assertSame(50.0, $savedReservation->getPrixTotal());

        $savedReservation->setStatut(Reservation::STATUT_ANNULE);
        $entityManager->flush();
        $entityManager->clear();

        $updatedReservation = $entityManager->getRepository(Reservation::class)->find($reservationId);
        self::assertNotNull($updatedReservation);
        self::assertSame(Reservation::STATUT_ANNULE, $updatedReservation->getStatut());
        self::assertNotNull($updatedReservation->getDateAnnulation());

        $entityManager->remove($updatedReservation);
        $entityManager->flush();
        self::assertNull($entityManager->getRepository(Reservation::class)->find($reservationId));
    }

    public function testDatabaseRejectsReservationWithUnknownPassenger(): void
    {
        $fixture = $this->createReservationFixture();
        $connection = $this->entityManager()->getConnection();
        $unknownPassengerId = (int) $connection->fetchOne('SELECT COALESCE(MAX(id), 0) + 1000000 FROM `user`');

        try {
            $connection->insert('reservation', [
                'date_reservation' => (new \DateTime())->format('Y-m-d H:i:s'),
                'nb_places' => 1,
                'statut' => Reservation::STATUT_CONFIRME,
                'passager_id' => $unknownPassengerId,
                'trajet_id' => $fixture['trip']->getId(),
                'prix_total' => 25.0,
            ]);
            self::fail('La contrainte de clé étrangère aurait dû rejeter le passager inexistant.');
        } catch (ForeignKeyConstraintViolationException) {
            self::assertSame(
                0,
                (int) $connection->fetchOne(
                    'SELECT COUNT(*) FROM reservation WHERE passager_id = ?',
                    [$unknownPassengerId]
                )
            );
        }
    }

    public function testDatabaseRejectsDuplicateUserEmail(): void
    {
        $fixture = $this->createReservationFixture();
        $connection = $this->entityManager()->getConnection();
        $passengerId = $fixture['passenger']->getId();

        try {
            $connection->executeStatement(
                'INSERT INTO `user` (email, nom, prenom, telephone, roles, password, statut, date_inscription, is_verified, verification_token, verification_token_expires_at)
                 SELECT email, nom, prenom, telephone, roles, password, statut, date_inscription, is_verified, verification_token, verification_token_expires_at
                 FROM `user` WHERE id = ?',
                [$passengerId]
            );
            self::fail('La contrainte unique aurait dû rejeter une adresse email déjà utilisée.');
        } catch (UniqueConstraintViolationException) {
            self::assertSame(
                1,
                (int) $connection->fetchOne('SELECT COUNT(*) FROM `user` WHERE id = ?', [$passengerId])
            );
        }
    }
}
