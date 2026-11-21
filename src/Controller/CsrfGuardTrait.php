<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;

/**
 * ANO-12, ANO-13 (EX-34, CA-15) : toute action qui modifie des données
 * doit arriver en POST et porter un jeton CSRF valide, propre à l'action et à l'objet visé.
 * Sinon : HTTP 403 et aucune modification.
 */
trait CsrfGuardTrait
{
    private function denyUnlessValidCsrf(Request $request, string $tokenId): void
    {
        if (!$request->isMethod('POST')
            || !$this->isCsrfTokenValid($tokenId, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Requête refusée : jeton CSRF absent ou invalide.');
        }
    }
}
