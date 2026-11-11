<?php

namespace App\Tests\Functional;

use App\Entity\Reservation;
use App\Entity\Trajet;
use App\Tests\Support\ReservationTestFixture;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ReservationApiTest extends WebTestCase
{
    use ReservationTestFixture;

    private $client;
    private array $fixture = [];

    protected function setUp(): void
    {
        $this->client = static::createClient(['debug' => false]);
        $this->fixture = $this->createReservationFixture();
    }

    protected function tearDown(): void
    {
        $this->cleanReservationFixtures();
        parent::tearDown();
    }

    public function testAnonymousUserCannotCreateReservation(): void
    {
        $this->client->request('POST', '/reservation/new-ajax', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['trajetId' => $this->fixture['trip']->getId(), 'nbPlaces' => 1]));

        self::assertResponseRedirects('/connexion');
    }

    public function testPassengerCreatesReservationAndPersistsAvailability(): void
    {
        $this->client->loginUser($this->fixture['passenger']);
        $this->client->request('POST', '/reservation/new-ajax', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ], json_encode(['trajetId' => $this->fixture['trip']->getId(), 'nbPlaces' => 2]));

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/json');
        $response = json_decode($this->client->getResponse()->getContent(), true);
        self::assertTrue($response['success']);
        // JSON ne distingue pas 50 et 50.0 : JsonResponse encode le float 50.0 en "50", relu comme int.
        // On vérifie donc la valeur numérique et non le type PHP (cause confirmée le 11/11/2026).
        self::assertIsNumeric($response['prixTotal']);
        self::assertEqualsWithDelta(50.0, $response['prixTotal'], 0.001);

        $entityManager = $this->entityManager();
        $entityManager->clear();
        $savedTrip = $entityManager->getRepository(Trajet::class)->find($this->fixture['trip']->getId());
        self::assertNotNull($savedTrip);
        self::assertSame(2, $savedTrip->getPlacesDisponibles());
        self::assertSame(1, $entityManager->getRepository(Reservation::class)->count([
            'passager' => $this->fixture['passenger']->getId(),
            'trajet' => $savedTrip->getId(),
        ]));
    }

    public function testApiRejectsMalformedJsonAndNonPositiveSeatCounts(): void
    {
        $this->client->loginUser($this->fixture['passenger']);

        foreach (['{invalid', json_encode(['trajetId' => $this->fixture['trip']->getId(), 'nbPlaces' => 0]), json_encode(['trajetId' => $this->fixture['trip']->getId(), 'nbPlaces' => -1])] as $payload) {
            $this->client->request('POST', '/reservation/new-ajax', [], [], [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            ], $payload);

            self::assertResponseStatusCodeSame(400);
            self::assertFalse(json_decode($this->client->getResponse()->getContent(), true)['success']);
        }
    }

    public function testApiRejectsUnknownUnavailableAndDuplicateReservations(): void
    {
        $this->client->loginUser($this->fixture['passenger']);

        $this->requestReservation(['trajetId' => 2147483000, 'nbPlaces' => 1]);
        self::assertResponseStatusCodeSame(404);

        $this->setAvailableSeats(0);
        $this->requestReservation(['trajetId' => $this->fixture['trip']->getId(), 'nbPlaces' => 1]);
        self::assertResponseStatusCodeSame(400);
        $this->setAvailableSeats(4);

        $this->requestReservation(['trajetId' => $this->fixture['trip']->getId(), 'nbPlaces' => 1]);
        self::assertResponseIsSuccessful();
        $this->requestReservation(['trajetId' => $this->fixture['trip']->getId(), 'nbPlaces' => 1]);
        self::assertResponseStatusCodeSame(400);
    }

    /**
     * Le client BrowserKit redémarre le kernel avant chaque requête (sauf la première) :
     * l'EntityManager change et l'entité fixture n'est plus gérée, un flush() ne la persiste pas.
     * On recharge donc le trajet depuis l'EntityManager courant avant de le modifier.
     */
    private function setAvailableSeats(int $seats): void
    {
        $entityManager = $this->entityManager();
        $trip = $entityManager->getRepository(Trajet::class)->find($this->fixture['trip']->getId());
        self::assertNotNull($trip);
        $trip->setPlacesDisponibles($seats);
        $entityManager->flush();
    }

    private function requestReservation(array $payload): void
    {
        $this->client->request('POST', '/reservation/new-ajax', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ], json_encode($payload));
    }
}
