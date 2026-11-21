<?php

namespace App\Tests\Entity;

use App\Entity\Reservation;
use App\Entity\Trajet;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ReservationTest extends TestCase
{
    #[Test]
    public function peutEtreAnnulee_refuseUnDepartPasse(): void
    {
        $trajet = (new Trajet())->setDateDepart(new \DateTime('-1 hour'));
        $reservation = (new Reservation())->setTrajet($trajet);

        self::assertFalse($reservation->peutEtreAnnulee());
    }

    #[Test]
    public function peutEtreAnnulee_refuseUnDepartDansMoinsDeDeuxHeures(): void
    {
        $trajet = (new Trajet())->setDateDepart(new \DateTime('+1 hour'));
        $reservation = (new Reservation())->setTrajet($trajet);

        self::assertFalse($reservation->peutEtreAnnulee());
    }

    #[Test]
    public function peutEtreAnnulee_autoriseUnDepartDansPlusDeDeuxHeures(): void
    {
        $trajet = (new Trajet())->setDateDepart(new \DateTime('+3 hours'));
        $reservation = (new Reservation())->setTrajet($trajet);

        self::assertTrue($reservation->peutEtreAnnulee());
    }

    #[Test]
    public function peutEtreAnnulee_refuseUnTrajetAbsent(): void
    {
        self::assertFalse((new Reservation())->peutEtreAnnulee());
    }

    #[Test]
    public function setStatut_enregistreEtEffaceLaDateAnnulationSelonLeStatut(): void
    {
        $reservation = new Reservation();

        $reservation->setStatut(Reservation::STATUT_ANNULE);
        self::assertNotNull($reservation->getDateAnnulation());

        $reservation->setStatut(Reservation::STATUT_CONFIRME);
        self::assertNull($reservation->getDateAnnulation());
    }

    #[Test]
    public function lePrixTotalEstRecalculeQuandLeTrajetEstRetire(): void
    {
        $trajet = (new Trajet())->setPrix(10.0);
        $reservation = (new Reservation())
            ->setTrajet($trajet)
            ->setNbPlaces(2);

        self::assertSame(20.0, $reservation->getPrixTotal());

        $reservation->setTrajet(null);
        self::assertNull($reservation->getPrixTotal());
    }
}
