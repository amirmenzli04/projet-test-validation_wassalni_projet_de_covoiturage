<?php

namespace App\Tests\Support;

use App\Entity\Reservation;
use App\Entity\Trajet;
use App\Entity\User;
use App\Entity\Vehicule;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

trait ReservationTestFixture
{
    private array $fixtureEmails = [];

    /**
     * @return array{driver: User, passenger: User, vehicle: Vehicule, trip: Trajet}
     */
    private function createReservationFixture(): array
    {
        $suffix = bin2hex(random_bytes(6));
        $driver = $this->createUser('ROLE_CONDUCTEUR', $suffix . '-driver');
        $passenger = $this->createUser('ROLE_PASSAGER', $suffix . '-passenger');

        $vehicle = (new Vehicule())
            ->setMarque('Toyota')
            ->setModele('Corolla')
            ->setImmatriculation(strtoupper(substr($suffix, 0, 8)))
            ->setNbPlaces(4)
            ->setProprietaire($driver);

        $departure = new \DateTime('+3 days');
        $trip = (new Trajet())
            ->setVilleDepart('Tunis')
            ->setDestination('Sfax')
            ->setDateDepart($departure)
            ->setDateArrivee((clone $departure)->modify('+3 hours'))
            ->setPrix(25.0)
            ->setPlacesDisponibles(4)
            ->setStatut('VALIDE')
            ->setDescription('Fixture PHPUnit ' . $suffix)
            ->setConducteur($driver)
            ->setVehicule($vehicle);

        $entityManager = $this->entityManager();
        $entityManager->persist($driver);
        $entityManager->persist($passenger);
        $entityManager->persist($vehicle);
        $entityManager->persist($trip);
        $entityManager->flush();

        return [
            'driver' => $driver,
            'passenger' => $passenger,
            'vehicle' => $vehicle,
            'trip' => $trip,
        ];
    }

    private function cleanReservationFixtures(): void
    {
        if (static::$kernel === null || $this->fixtureEmails === []) {
            return;
        }

        $entityManager = $this->entityManager();
        foreach ($this->fixtureEmails as $email) {
            $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($user === null) {
                continue;
            }

            foreach ($entityManager->getRepository(Reservation::class)->findBy(['passager' => $user]) as $reservation) {
                $entityManager->remove($reservation);
            }
            foreach ($entityManager->getRepository(Trajet::class)->findBy(['conducteur' => $user]) as $trip) {
                $entityManager->remove($trip);
            }
            foreach ($entityManager->getRepository(Vehicule::class)->findBy(['proprietaire' => $user]) as $vehicle) {
                $entityManager->remove($vehicle);
            }

            $entityManager->remove($user);
        }

        $entityManager->flush();
        $entityManager->clear();
    }

    private function createUser(string $role, string $suffix): User
    {
        $email = 'phpunit-' . $suffix . '@example.test';
        $this->fixtureEmails[] = $email;

        return (new User())
            ->setEmail($email)
            ->setNom('Testeur')
            ->setPrenom('Automatique')
            ->setTelephone('10000000')
            ->setPassword('test-password')
            ->setRoles([$role])
            ->setIsVerified(true);
    }

    private function entityManager(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }
}
