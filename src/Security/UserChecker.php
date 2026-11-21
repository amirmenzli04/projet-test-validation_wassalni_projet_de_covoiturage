<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Contrôle l'état du compte avant toute authentification (ANO-20, ANO-21).
 * Un compte non vérifié ou bloqué n'obtient jamais de session (EX-03, EX-04, CA-11).
 */
class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if (!$user->isVerified()) {
            throw new CustomUserMessageAccountStatusException('Veuillez confirmer votre adresse e-mail avant de vous connecter.');
        }

        if ($user->getStatut() === 'BLOQUE') {
            throw new CustomUserMessageAccountStatusException('Votre compte est bloqué. Contactez l\'administrateur.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
    }
}
