<?php

namespace App\Tests\Functional;

use App\Entity\Trajet;
use App\Tests\Support\ReservationTestFixture;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/** Sonde temporaire (hors depot) : comportement de HEAD b6dc42a pour nbPlaces = -1 sur la route AJAX. */
class HeadNegativeSeatsProbeTest extends WebTestCase
{
    use ReservationTestFixture;

    protected function tearDown(): void
    {
        $this->cleanReservationFixtures();
        parent::tearDown();
    }

    public function testNegativeSeatsOnHead(): void
    {
        $client = static::createClient(['debug' => false]);
        $fixture = $this->createReservationFixture();
        $client->loginUser($fixture['passenger']);
        $client->request('POST', '/reservation/new-ajax', [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['trajetId' => $fixture['trip']->getId(), 'nbPlaces' => -1]));

        $em = $this->entityManager();
        $em->clear();
        $places = $em->getRepository(Trajet::class)->find($fixture['trip']->getId())->getPlacesDisponibles();
        fwrite(STDERR, sprintf("HTTP=%d body=%s places_avant=4 places_apres=%d\n",
            $client->getResponse()->getStatusCode(), $client->getResponse()->getContent(), $places));
        self::assertTrue(true);
    }
}
