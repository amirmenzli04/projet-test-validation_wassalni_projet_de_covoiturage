<?php

namespace App\Tests\Entity;

use App\Entity\Reclamation;
use App\Entity\Reservation;
use App\Entity\Trajet;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ReclamationValidationTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    #[Test]
    public function messageDeNeufCaracteresEstRefuse(): void
    {
        $reclamation = (new Reclamation())
            ->setSujet('Autre')
            ->setMessage('123456789');

        self::assertSame(
            1,
            $this->validator->validate($reclamation)->count()
        );
    }

    #[Test]
    public function messageDeDixCaracteresEstAccepte(): void
    {
        $reclamation = (new Reclamation())
            ->setSujet('Autre')
            ->setMessage('1234567890');

        self::assertCount(0, $this->validator->validate($reclamation));
    }

    #[Test]
    public function sujetDeQuatreCaracteresEstRefuseEtCinqCaracteresEstAccepte(): void
    {
        $tropCourt = (new Reclamation())
            ->setSujet('Autr')
            ->setMessage('Message valide');
        $valide = (new Reclamation())
            ->setSujet('Autre')
            ->setMessage('Message valide');

        self::assertSame(1, $this->validator->validate($tropCourt)->count());
        self::assertCount(0, $this->validator->validate($valide));
    }

    #[Test]
    public function reservationRefuseUneQuantiteNulleEtAccepteLaQuantiteDisponible(): void
    {
        $trajet = (new Trajet())
            ->setPrix(10.0)
            ->setPlacesDisponibles(3);
        $quantiteNulle = (new Reservation())
            ->setTrajet($trajet)
            ->setNbPlaces(0);
        $quantiteDisponible = (new Reservation())
            ->setTrajet($trajet)
            ->setNbPlaces(3);

        self::assertGreaterThan(
            0,
            $this->validator->validate($quantiteNulle)->count()
        );
        self::assertCount(0, $this->validator->validate($quantiteDisponible));
    }

    #[Test]
    public function reservationRefuseUneQuantiteSuperieureAuStock(): void
    {
        $trajet = (new Trajet())
            ->setPrix(10.0)
            ->setPlacesDisponibles(3);
        $reservation = (new Reservation())
            ->setTrajet($trajet)
            ->setNbPlaces(4);

        self::assertGreaterThan(
            0,
            $this->validator->validate($reservation)->count()
        );
    }
}
