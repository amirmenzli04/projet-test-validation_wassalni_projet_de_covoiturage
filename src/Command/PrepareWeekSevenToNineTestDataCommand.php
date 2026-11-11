<?php

namespace App\Command;

use App\Entity\Reservation;
use App\Entity\Trajet;
use App\Entity\User;
use App\Entity\Vehicule;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(
    name: 'app:tests:prepare-week7-9',
    description: 'Prepare isolated, repeatable fixtures for weeks 7–9 acceptance tests'
)]
class PrepareWeekSevenToNineTestDataCommand extends Command
{
    private const PASSENGER_EMAIL = 'semaine7-9.passager@example.test';
    private const DRIVER_EMAIL = 'semaine7-9.conducteur@example.test';
    /** Le mot de passe des comptes fictifs est lu dans .env.test.local (non versionné). */
    private const PASSWORD_ENV = 'WASSALNI_TEST_PASSWORD';
    private const TRIP_MARKER = 'Fixture automatisée W7-W9';
    private const TRIP_DATE = '2099-12-01 10:00:00';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly KernelInterface $kernel
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $connection = $this->entityManager->getConnection();
        if ($this->kernel->getEnvironment() !== 'test' || !str_ends_with((string) $connection->getDatabase(), '_test')) {
            throw new \LogicException('Ces données ne peuvent être préparées que dans une base dont le nom se termine par _test.');
        }

        $password = (string) ($_SERVER[self::PASSWORD_ENV] ?? $_ENV[self::PASSWORD_ENV] ?? '');
        if (strlen($password) < 12) {
            throw new \LogicException(sprintf('Définir %s (12 caractères minimum) dans .env.test.local avant de préparer les données.', self::PASSWORD_ENV));
        }

        $userRepository = $this->entityManager->getRepository(User::class);
        $passenger = $this->getOrCreateUser($userRepository->findOneBy(['email' => self::PASSENGER_EMAIL]), 'Passager W7-9', 'ROLE_PASSAGER', $password);
        $driver = $this->getOrCreateUser($userRepository->findOneBy(['email' => self::DRIVER_EMAIL]), 'Conducteur W7-9', 'ROLE_CONDUCTEUR', $password);

        $vehicle = $this->entityManager->getRepository(Vehicule::class)->findOneBy([
            'proprietaire' => $driver,
            'immatriculation' => 'WASSALNI79',
        ]);
        if ($vehicle === null) {
            $vehicle = (new Vehicule())
                ->setMarque('Toyota')
                ->setModele('Corolla')
                ->setImmatriculation('WASSALNI79')
                ->setNbPlaces(4)
                ->setProprietaire($driver);
            $this->entityManager->persist($vehicle);
        }

        $tripRepository = $this->entityManager->getRepository(Trajet::class);
        $trip = $tripRepository->findOneBy(['description' => self::TRIP_MARKER]);
        if ($trip === null) {
            $trip = new Trajet();
            $this->entityManager->persist($trip);
        } else {
            foreach ($this->entityManager->getRepository(Reservation::class)->findBy(['trajet' => $trip]) as $reservation) {
                $this->entityManager->remove($reservation);
            }
        }

        $departure = new \DateTime(self::TRIP_DATE);
        $trip
            ->setVilleDepart('Tunis')
            ->setDestination('Sfax')
            ->setDateDepart($departure)
            ->setDateArrivee((clone $departure)->modify('+2 hours'))
            ->setPrix(25.0)
            ->setPlacesDisponibles(4)
            ->setStatut('VALIDE')
            ->setDescription(self::TRIP_MARKER)
            ->setConducteur($driver)
            ->setVehicule($vehicle);

        $this->entityManager->flush();

        $output->writeln(json_encode([
            'environment' => $this->kernel->getEnvironment(),
            'database' => $connection->getDatabase(),
            'tripId' => $trip->getId(),
            'dateDepart' => $departure->format('Y-m-d H:i:s'),
            'passengerEmail' => $passenger->getEmail(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        return Command::SUCCESS;
    }

    private function getOrCreateUser(?User $user, string $name, string $role, string $password): User
    {
        if ($user === null) {
            $user = (new User())
                ->setEmail($role === 'ROLE_PASSAGER' ? self::PASSENGER_EMAIL : self::DRIVER_EMAIL)
                ->setNom('Tests W7-9')
                ->setPrenom($name)
                ->setTelephone($role === 'ROLE_PASSAGER' ? '90000001' : '90000002')
                ->setRoles([$role]);
            $this->entityManager->persist($user);
        } elseif ($user->getNom() !== 'Tests W7-9' || $user->getPrenom() !== $name) {
            throw new \LogicException(sprintf('Refus de modifier le compte préexistant %s.', $user->getEmail()));
        }

        $user
            ->setRoles([$role])
            ->setIsVerified(true)
            ->setPassword($this->passwordHasher->hashPassword($user, $password));

        return $user;
    }
}
