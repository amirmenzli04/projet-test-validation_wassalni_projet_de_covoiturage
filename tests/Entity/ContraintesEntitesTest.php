<?php

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * ANO-35 et ANO-39 : une contrainte de validation mal paramétrée (paramètre inexistant, ex. « message » sur
 * Range ou Length) ne se révèle qu'à l'exécution, par une erreur fatale. Ce test valide chaque entité :
 * la validation doit renvoyer des violations, jamais lever d'erreur.
 */
final class ContraintesEntitesTest extends KernelTestCase
{
    private function validator(): ValidatorInterface
    {
        self::bootKernel();

        return static::getContainer()->get('validator');
    }

    public static function entites(): iterable
    {
        foreach (['User', 'Trajet', 'Vehicule', 'Reservation', 'Reclamation', 'Avis'] as $entite) {
            yield $entite => ["App\\Entity\\$entite"];
        }
    }

    #[DataProvider('entites')]
    #[Group('ANO-39')]
    #[TestDox('UT-12 (ANO-35, ANO-39) — $classe : les contraintes de validation se chargent sans erreur')]
    public function testLesContraintesSeChargent(string $classe): void
    {
        $violations = $this->validator()->validate(new $classe());

        self::assertGreaterThanOrEqual(0, count($violations));
    }

    #[Group('ANO-39')]
    #[TestDox('UT-13 (ANO-39, valeurs limites) — mot de passe de 5 caractères refusé, de 6 caractères accepté')]
    public function testLongueurMinimaleDuMotDePasse(): void
    {
        $validator = $this->validator();

        $cinq = count($validator->validateProperty((new User())->setPassword('12345'), 'password'));
        $six = count($validator->validateProperty((new User())->setPassword('123456'), 'password'));

        self::assertSame(['5 caractères' => 1, '6 caractères' => 0], ['5 caractères' => $cinq, '6 caractères' => $six]);
    }
}
