<?php

namespace App\Tests\Support;

use App\Entity\Reservation;
use App\Entity\Trajet;
use App\Entity\User;
use App\Entity\Vehicule;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Jeu de données du catalogue officiel (PDF semaine 4, section 3), dates relatives au jour J.
 *
 * Isolation : chaque exécution crée des comptes `cat-<suffixe>-*@example.test` dans wassalni_test
 * puis supprime exclusivement ce qu'elle a créé (et ce que l'application a créé pour ces comptes).
 * Les données préexistantes de la base de test ne sont jamais modifiées.
 *
 * Les entités sont détachées dès que le client redémarre le kernel (ANO-T02) : on manipule
 * donc des identifiants et on relit l'état avec {@see reload()}.
 */
trait CatalogueFixture
{
    /** @var list<int> */
    protected array $catalogueUserIds = [];
    /** @var array<string, int> alias => id */
    protected array $ids = [];
    /** @var array<string, string> alias => email */
    protected array $emails = [];
    protected string $plainPassword = '';
    /** @var array{0: string, 1: string}|null [mot de passe, empreinte] partagés par le processus (hachage coûteux) */
    private static ?array $passwordCache = null;

    protected function createCatalogue(): void
    {
        $this->assertTestDatabase();
        $suffix = bin2hex(random_bytes(4));
        if (self::$passwordCache === null) {
            $plain = 'Cat-' . bin2hex(random_bytes(8));
            self::$passwordCache = [$plain, static::getContainer()->get(UserPasswordHasherInterface::class)->hashPassword(new User(), $plain)];
        }
        [$this->plainPassword, $hash] = self::$passwordCache;
        $em = $this->em();

        $users = [
            'U-ADMIN' => ['ROLE_ADMIN', true],
            'U-COND1' => ['ROLE_CONDUCTEUR', true],
            'U-COND2' => ['ROLE_CONDUCTEUR', true],
            'U-PASS1' => ['ROLE_PASSAGER', true],
            'U-PASS2' => ['ROLE_PASSAGER', true],
            'U-PASS-NV' => ['ROLE_PASSAGER', false],
        ];
        $entities = [];
        foreach ($users as $alias => [$role, $verified]) {
            $email = sprintf('cat-%s-%s@example.test', $suffix, strtolower(str_replace('U-', '', $alias)));
            $user = (new User())
                ->setEmail($email)
                ->setNom('Catalogue')
                ->setPrenom($alias)
                ->setTelephone('20000000')
                ->setRoles([$role])
                ->setIsVerified($verified);
            $user->setPassword($hash);
            $em->persist($user);
            $entities[$alias] = $user;
            $this->emails[$alias] = $email;
        }

        $v1 = (new Vehicule())->setMarque('Peugeot')->setModele('308')->setImmatriculation('CAT' . strtoupper(substr($suffix, 0, 5)) . 'A')
            ->setNbPlaces(5)->setProprietaire($entities['U-COND1']);
        $v2 = (new Vehicule())->setMarque('Kia')->setModele('Rio')->setImmatriculation('CAT' . strtoupper(substr($suffix, 0, 5)) . 'B')
            ->setNbPlaces(4)->setProprietaire($entities['U-COND1']);
        $em->persist($v1);
        $em->persist($v2);
        $entities['V1'] = $v1;
        $entities['V2'] = $v2;

        // alias => [départ, destination, J, heure départ, heure arrivée, prix, places, statut]
        $trips = [
            'T-NOMINAL' => ['Tunis', 'Sousse', 7, '08:00', '10:00', 15.0, 2, 'VALIDE'],
            'T-P4' => ['Tunis', 'Sfax', 8, '07:00', '10:00', 22.0, 4, 'VALIDE'],
            'T-FUT-EARLY' => ['Tunis', 'Sfax', 2, '09:00', '12:00', 18.0, 3, 'VALIDE'],
            'T-P1' => ['Tunis', 'Nabeul', 9, '10:00', '11:00', 10.0, 1, 'VALIDE'],
            'T-FULL' => ['Tunis', 'Bizerte', 9, '15:00', '16:00', 12.0, 0, 'VALIDE'],
            'T-PEND' => ['Sousse', 'Monastir', 10, '09:00', '11:00', 10.0, 3, 'EN_ATTENTE'],
            'T-PAST' => ['Tunis', 'Sfax', -10, '07:00', '10:00', 22.0, 3, 'VALIDE'],
            'T-PAST-RES' => ['Sousse', 'Tunis', -5, '08:00', '10:00', 15.0, 2, 'VALIDE'],
        ];
        foreach ($trips as $alias => [$from, $to, $day, $start, $end, $price, $seats, $status]) {
            $trip = (new Trajet())
                ->setVilleDepart($from)
                ->setDestination($to)
                ->setDateDepart(new \DateTime(sprintf('%+d days %s', $day, $start)))
                ->setDateArrivee(new \DateTime(sprintf('%+d days %s', $day, $end)))
                ->setPrix($price)
                ->setPlacesDisponibles($seats)
                ->setStatut($status)
                ->setDescription('Catalogue ' . $suffix . ' ' . $alias)
                ->setConducteur($entities['U-COND1'])
                ->setVehicule($v1);
            $em->persist($trip);
            $entities[$alias] = $trip;
        }

        // T-NOMINAL : 4 places à l'origine, 2 occupées par RES-F1 et RES-OTHER (2 restantes).
        foreach (['RES-F1' => ['U-PASS1', 'T-NOMINAL'], 'RES-OTHER' => ['U-PASS2', 'T-NOMINAL'], 'RES-PAST' => ['U-PASS1', 'T-PAST-RES']] as $alias => [$passenger, $trip]) {
            $reservation = (new Reservation())
                ->setPassager($entities[$passenger])
                ->setTrajet($entities[$trip])
                ->setNbPlaces(1)
                ->setStatut(Reservation::STATUT_CONFIRME);
            $em->persist($reservation);
            $entities[$alias] = $reservation;
        }

        $em->flush();
        foreach ($entities as $alias => $entity) {
            $this->ids[$alias] = $entity->getId();
            if ($entity instanceof User) {
                $this->catalogueUserIds[] = $entity->getId();
            }
        }
        $em->clear();
    }

    /** Supprime uniquement les lignes rattachées aux comptes du catalogue (DBAL, ordre des clés étrangères). */
    protected function cleanCatalogue(): void
    {
        if ($this->catalogueUserIds === [] || static::$kernel === null) {
            return;
        }
        $connection = $this->em()->getConnection();
        $users = implode(',', array_map('intval', $this->catalogueUserIds));
        $trips = "SELECT id FROM trajet WHERE conducteur_id IN ($users)";
        $tripIds = implode(',', array_map('intval', $connection->fetchFirstColumn($trips))) ?: '0';

        $connection->executeStatement("DELETE FROM avis WHERE passager_id IN ($users) OR conducteur_id IN ($users) OR trajet_id IN ($tripIds)");
        $connection->executeStatement("DELETE FROM reclamation WHERE user_id IN ($users) OR utilisateur_concerne_id IN ($users) OR trajet_concerne_id IN ($tripIds)");
        $connection->executeStatement("DELETE FROM reservation WHERE passager_id IN ($users) OR trajet_id IN ($tripIds)");
        $connection->executeStatement("DELETE FROM trajet WHERE id IN ($tripIds)");
        $connection->executeStatement("DELETE FROM vehicule WHERE proprietaire_id IN ($users)");
        $connection->executeStatement("DELETE FROM `user` WHERE id IN ($users)");
        $this->catalogueUserIds = [];
    }

    protected function assertTestDatabase(): void
    {
        $database = (string) $this->em()->getConnection()->getDatabase();
        if (!str_ends_with($database, '_test')) {
            throw new \LogicException("Base refusée pour les tests : $database");
        }
    }

    protected function em(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    /** @template T of object @param class-string<T> $class @return T|null */
    protected function reload(string $class, string $alias): ?object
    {
        $em = $this->em();
        $em->clear();

        return $em->getRepository($class)->find($this->ids[$alias]);
    }

    protected function user(string $alias): User
    {
        $user = $this->em()->getRepository(User::class)->find($this->ids[$alias]);
        self::assertNotNull($user);

        return $user;
    }

    protected function seats(string $tripAlias): int
    {
        return (int) $this->em()->getConnection()->fetchOne('SELECT places_disponibles FROM trajet WHERE id = ?', [$this->ids[$tripAlias]]);
    }

    protected function countReservations(string $passengerAlias, string $tripAlias, ?string $status = null): int
    {
        $sql = 'SELECT COUNT(*) FROM reservation WHERE passager_id = ? AND trajet_id = ?';
        $params = [$this->ids[$passengerAlias], $this->ids[$tripAlias]];
        if ($status !== null) {
            $sql .= ' AND statut = ?';
            $params[] = $status;
        }

        return (int) $this->em()->getConnection()->fetchOne($sql, $params);
    }

    /** Connexion par le vrai formulaire (CSRF inclus), pour tester l'authentificateur. */
    protected function loginByForm(KernelBrowser $client, string $email, string $password): void
    {
        $crawler = $client->request('GET', '/connexion');
        $form = $crawler->filter('form')->form(['_username' => $email, '_password' => $password]);
        $client->submit($form);
    }

    /** Vrai si la session courante donne accès à une page réservée aux passagers. */
    protected function hasPassengerSession(KernelBrowser $client): bool
    {
        $client->request('GET', '/reservation/mes-reservations');

        return $client->getResponse()->getStatusCode() === 200;
    }
}
