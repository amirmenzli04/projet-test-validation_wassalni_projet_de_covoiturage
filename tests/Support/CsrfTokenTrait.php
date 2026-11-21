<?php

namespace App\Tests\Support;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;

/**
 * Obtient un jeton CSRF valide pour la session du client de test, comme le ferait
 * le formulaire affiché à l'utilisateur (ANO-12, ANO-13).
 * Les tests d'actions LÉGITIMES envoient ce jeton ; les tests qui prouvent la protection
 * envoient une requête sans jeton (ou en GET) et vérifient le refus.
 */
trait CsrfTokenTrait
{
    protected function csrfTokenFor(KernelBrowser $client, string $tokenId): string
    {
        $session = $client->getSession();
        $request = new Request();
        $request->setSession($session);

        $container = static::getContainer();
        $stack = $container->get('request_stack');
        $stack->push($request);
        try {
            $value = $container->get('security.csrf.token_manager')->getToken($tokenId)->getValue();
        } finally {
            $stack->pop();
        }
        $session->save();

        return $value;
    }
}
