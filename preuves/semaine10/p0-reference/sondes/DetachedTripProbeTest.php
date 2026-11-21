<?php

namespace App\Tests\Functional;

use App\Entity\Trajet;
use App\Tests\Support\ReservationTestFixture;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/** Sonde temporaire : l'entite fixture est-elle encore geree par l'EntityManager apres une requete du client ? */
class DetachedTripProbeTest extends WebTestCase
{
    use ReservationTestFixture;

    protected function tearDown(): void
    {
        $this->cleanReservationFixtures();
        parent::tearDown();
    }

    public function testProbe(): void
    {
        $client = static::createClient(['debug' => false]);
        $fixture = $this->createReservationFixture();
        $client->loginUser($fixture['passenger']);
        $em = $this->entityManager();
        $before = $em->contains($fixture['trip']);
        $client->request('POST', '/reservation/new-ajax', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['trajetId' => 2147483000]));
        $client->request('POST', '/reservation/new-ajax', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['trajetId' => 2147483000]));
        $emAfter = $this->entityManager();
        fwrite(STDERR, sprintf("managed_avant=%s meme_EM=%s managed_apres=%s\n",
            var_export($before, true), var_export($em === $emAfter, true), var_export($emAfter->contains($fixture['trip']), true)));
        $fixture['trip']->setPlacesDisponibles(0);
        $emAfter->flush();
        $emAfter->clear();
        $db = $emAfter->getRepository(Trajet::class)->find($fixture['trip']->getId())->getPlacesDisponibles();
        fwrite(STDERR, "places_en_base_apres_flush=$db (attendu si persiste: 0)\n");
        self::assertTrue(true);
    }
}
