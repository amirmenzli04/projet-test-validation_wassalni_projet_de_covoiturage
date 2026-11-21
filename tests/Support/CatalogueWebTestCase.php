<?php

namespace App\Tests\Support;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Base des tests HTTP/BDD du catalogue officiel (TC-xx du PDF semaine 4).
 * Chaque test assertionne le résultat ATTENDU par le critère d'acceptation (CA-xx) :
 * un échec signifie que l'application ne respecte pas l'exigence (anomalie à consigner).
 */
abstract class CatalogueWebTestCase extends WebTestCase
{
    use CatalogueFixture;
    use CsrfTokenTrait;

    protected KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient(['debug' => false]);
        $this->client->catchExceptions(true);
        $this->createCatalogue();
    }

    protected function tearDown(): void
    {
        $this->cleanCatalogue();
        parent::tearDown();
    }

    protected function httpStatus(): int
    {
        return $this->client->getResponse()->getStatusCode();
    }

    protected function csrfToken(string $tokenId): string
    {
        return $this->csrfTokenFor($this->client, $tokenId);
    }

    protected function body(): string
    {
        return (string) $this->client->getResponse()->getContent();
    }

    /** Une action refusée renvoie 4xx, ou redirige sans avoir muté les données (contrôlé par l'appelant). */
    protected function assertNotServerError(string $context): void
    {
        self::assertLessThan(500, $this->httpStatus(), "$context : erreur serveur HTTP {$this->httpStatus()}");
    }
}
