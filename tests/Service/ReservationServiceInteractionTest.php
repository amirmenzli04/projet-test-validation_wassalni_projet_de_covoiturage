<?php

namespace App\Tests\Service;

use App\Entity\Trajet;
use App\Entity\User;
use App\Repository\ReservationRepository;
use App\Service\ReservationService;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

/**
 * Semaine 6 — Doublure de type MOCK (et non stub) : ici on vérifie une INTERACTION, à savoir que le
 * service interroge le dépôt avec le bon critère d'unicité (passager, trajet, CONFIRMEE), une seule fois,
 * et qu'il n'interroge pas le dépôt si le stock est insuffisant (court-circuit).
 */
final class ReservationServiceInteractionTest extends TestCase
{
    #[TestDox('UT-10 (CA-09) — reserver() recherche une réservation CONFIRMEE du même passager sur le même trajet')]
    public function testCritereDUniciteTransmisAuDepot(): void
    {
        // Arrange
        $passenger = new User();
        $trip = (new Trajet())->setPrix(10.0)->setPlacesDisponibles(3);
        $repository = $this->createMock(ReservationRepository::class);
        $repository->expects(self::once())
            ->method('findOneBy')
            ->with(['passager' => $passenger, 'trajet' => $trip, 'statut' => 'CONFIRMEE'])
            ->willReturn(null);

        // Act
        $reservation = (new ReservationService($repository))->reserver($trip, $passenger, 2);

        // Assert (les attentes du mock sont vérifiées automatiquement en fin de test)
        self::assertSame(20.0, $reservation->getPrixTotal());
        self::assertSame(1, $trip->getPlacesDisponibles());
    }

    #[TestDox('UT-11 (CA-06) — stock insuffisant : exception levée sans interroger le dépôt, stock intact')]
    public function testStockInsuffisantSansAppelAuDepot(): void
    {
        // Arrange
        $trip = (new Trajet())->setPrix(10.0)->setPlacesDisponibles(1);
        $repository = $this->createMock(ReservationRepository::class);
        $repository->expects(self::never())->method('findOneBy');

        // Act
        try {
            (new ReservationService($repository))->reserver($trip, new User(), 2);
            self::fail('Une DomainException était attendue');
        } catch (\DomainException $exception) {
            // Assert
            self::assertSame('Pas assez de places disponibles.', $exception->getMessage());
            self::assertSame(1, $trip->getPlacesDisponibles());
        }
    }
}
