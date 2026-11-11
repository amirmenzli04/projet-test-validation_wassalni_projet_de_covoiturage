<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class AnonymousAccessTest extends WebTestCase
{
    public function testAnonymousVisitorsAreRedirectedFromProtectedRoutes(): void
    {
        $client = static::createClient();

        foreach ([
            '/reservation/mes-reservations',
            '/reclamation/nouvelle',
            '/admin/dashboard',
        ] as $path) {
            $client->request('GET', $path);

            self::assertSame(
                Response::HTTP_FOUND,
                $client->getResponse()->getStatusCode(),
                sprintf('Expected an anonymous visitor to be redirected from %s.', $path)
            );
            self::assertStringContainsString(
                '/connexion',
                $client->getResponse()->headers->get('Location', ''),
                sprintf('Expected a login redirect from %s.', $path)
            );

            $client->restart();
        }
    }

}
