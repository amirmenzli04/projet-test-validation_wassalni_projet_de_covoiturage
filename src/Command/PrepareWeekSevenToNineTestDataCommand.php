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
    description: 'Prépare des données fictives reproductibles (semaines 5 à 9) dans une base *_test uniquement'
)]
class PrepareWeekSevenToNineTestDataCommand extends Command
{
    private const PASSENGER_EMAIL = 'semaine7-9.passager@example.test';
    private const DRIVER_EMAIL = 'semaine7-9.conducteur@example.test';
    /** Comptes fictifs : e-mail => [prénom, rôle, téléphone]. Le nom commun « Tests W7-9 » protège les comptes préexistants. */
    private const ACCOUNTS = [
        self::PASSENGER_EMAIL => ['Passager W7-9', 'ROLE_PASSAGER', '90000001'],
        self::DRIVER_EMAIL => ['Conducteur W7-9', 'ROLE_CONDUCTEUR', '90000002'],
        'semaine7-9.passager2@example.test' => ['Passager B W7-9', 'ROLE_PASSAGER', '90000003'],
        'semaine7-9.admin@example.test' => ['Admin W7-9', 'ROLE_ADMIN', '90000004'],
    ];
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
        $users = [];
        foreach (self::ACCOUNTS as $email => [$name, $role, $phone]) {
            $users[$email] = $this->getOrCreateUser($userRepository->findOneBy(['email' => $email]), $email, $name, $role, $phone, $password);
        }
        $passenger = $users[self::PASSENGER_EMAIL];
        $driver = $users[self::DRIVER_EMAIL];
        $this->entityManager->flush();

        // Rejouabilité : on efface uniquement les réclamations et avis de ces comptes fictifs.
        $ids = implode(',', array_map(fn (User $u) => (int) $u->getId(), $users));
        $connection->executeStatement("DELETE FROM reclamation WHERE user_id IN ($ids)");
        $connection->executeStatement("DELETE FROM avis WHERE passager_id IN ($ids)");

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
            'accounts' => array_keys(self::ACCOUNTS),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        return Command::SUCCESS;
    }

    private function getOrCreateUser(?User $user, string $email, string $name, string $role, string $phone, string $password): User
    {
        if ($user === null) {
            $user = (new User())
                ->setEmail($email)
                ->setNom('Tests W7-9')
                ->setPrenom($name)
                ->setTelephone($phone)
                ->setRoles([$role]);
            $this->entityManager->persist($user);
        } elseif ($user->getNom() !== 'Tests W7-9' || $user->getPrenom() !== $name) {
            throw new \LogicException(sprintf('Refus de modifier le compte préexistant %s.', $user->getEmail()));
        }

        $user
            ->setRoles([$role])
            ->setStatut('ACTIF')
            ->setIsVerified(true)
            ->setPassword($this->passwordHasher->hashPassword($user, $password));

        return $user;
    }
}
