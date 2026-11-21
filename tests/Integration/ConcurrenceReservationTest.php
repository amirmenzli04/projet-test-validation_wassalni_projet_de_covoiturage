<?php

namespace App\Tests\Integration;

use App\Entity\Reservation;
use App\Entity\Trajet;
use App\Entity\User;
use App\Service\StockPlaces;
use App\Tests\Support\ReservationTestFixture;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Semaine 9 / OBS-J — Concurrence sur le stock de places (risque R1 : surréservation).
 *
 * Le serveur de test (php -S sous Windows) traite une requête à la fois : la concurrence HTTP réelle
 * n'y est pas reproductible. On rejoue donc l'algorithme de ReservationController::newAjax
 * (lire le stock → vérifier → créer la réservation → décrémenter → flush) dans DEUX connexions
 * distinctes à wassalni_test, avec un entrelacement déterministe : A lit, B lit, A écrit, B écrit.
 * C'est exactement ce qui se produit lorsque deux requêtes PHP-FPM simultanées passent le contrôle
 * avant que l'une ou l'autre ne valide sa transaction.
 *
 * Avant correction (révision cc50095), l'étape d'écriture faisait « stock − n » en PHP : 2 réservations
 * pour 1 place (ANO-38). Depuis la correction, l'écriture passe par StockPlaces::prendre(), le même code
 * que le contrôleur : un UPDATE conditionnel atomique. Le test rejoue donc l'algorithme CORRIGÉ.
 */
final class ConcurrenceReservationTest extends KernelTestCase
{
    use ReservationTestFixture;

    private ?EntityManagerInterface $second = null;

    protected function setUp(): void
    {
        self::bootKernel();
        self::assertStringEndsWith('_test', (string) $this->entityManager()->getConnection()->getDatabase());
    }

    protected function tearDown(): void
    {
        $this->second?->getConnection()->close();
        static::getContainer()->get('doctrine')->resetManager();
        $this->cleanReservationFixtures();
        parent::tearDown();
    }

    /** Étape « lecture et contrôle » du contrôleur : renvoie le trajet si le stock suffit. */
    private function readAndCheck(EntityManagerInterface $em, int $tripId, int $seats): ?Trajet
    {
        $trip = $em->find(Trajet::class, $tripId);

        return $trip->getPlacesDisponibles() >= $seats ? $trip : null;
    }

    /** Étape « écriture » du contrôleur corrigé : décrément atomique puis enregistrement, dans une transaction. */
    private function write(EntityManagerInterface $em, Trajet $trip, int $passengerId, int $seats): bool
    {
        return $em->wrapInTransaction(function () use ($em, $trip, $passengerId, $seats): bool {
            if (!(new StockPlaces())->prendre($em->getConnection(), $trip->getId(), $seats)) {
                return false;
            }
            $em->persist((new Reservation())->setPassager($em->find(User::class, $passengerId))->setTrajet($trip)->setNbPlaces($seats));
            $em->flush();

            return true;
        });
    }

    #[Group('ANO-38')]
    #[TestDox('IT-07 (R1, EX-35, CA-06) — deux réservations entrelacées sur la dernière place : une seule doit être confirmée')]
    public function testDerniereplaceEntrelacee(): void
    {
        // Arrange : trajet à 1 place, deux passagers distincts, deux connexions indépendantes
        $fixture = $this->createReservationFixture();
        $other = $this->createUser('ROLE_PASSAGER', bin2hex(random_bytes(6)) . '-passenger2');
        $first = $this->entityManager();
        $first->persist($other);
        $first->flush();
        $tripId = $fixture['trip']->getId();
        $first->getConnection()->executeStatement('UPDATE trajet SET places_disponibles = 1 WHERE id = ?', [$tripId]);
        $first->clear();

        $connection = DriverManager::getConnection($first->getConnection()->getParams(), $first->getConfiguration());
        $this->second = new EntityManager($connection, $first->getConfiguration(), $first->getEventManager());

        // Act : A lit, B lit, A écrit, B écrit
        $tripSeenByA = $this->readAndCheck($first, $tripId, 1);
        $tripSeenByB = $this->readAndCheck($this->second, $tripId, 1);
        $writeA = $tripSeenByA ? $this->write($first, $tripSeenByA, $fixture['passenger']->getId(), 1) : false;
        $writeB = $tripSeenByB ? $this->write($this->second, $tripSeenByB, $other->getId(), 1) : false;

        // Assert : au plus une réservation confirmée pour une place
        $sql = $first->getConnection();
        $state = [
            'controle_A_passe' => $tripSeenByA !== null,
            'controle_B_passe' => $tripSeenByB !== null,
            'ecriture_A' => $writeA,
            'ecriture_B' => $writeB,
            'reservations_confirmees' => (int) $sql->fetchOne("SELECT COUNT(*) FROM reservation WHERE trajet_id = ? AND statut = 'CONFIRMEE'", [$tripId]),
            'places_restantes' => (int) $sql->fetchOne('SELECT places_disponibles FROM trajet WHERE id = ?', [$tripId]),
        ];
        // Les deux contrôles passent (c'est le scénario dangereux) ; seule la première écriture doit réussir.
        self::assertSame(
            ['controle_A_passe' => true, 'controle_B_passe' => true, 'ecriture_A' => true, 'ecriture_B' => false,
             'reservations_confirmees' => 1, 'places_restantes' => 0],
            $state,
            'Surréservation : ' . json_encode($state)
        );
    }
}
