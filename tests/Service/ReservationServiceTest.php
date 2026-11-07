<?php

namespace App\Tests\Service;

use App\Entity\Reservation;
use App\Entity\Trajet;
use App\Entity\User;
use App\Repository\ReservationRepository;
use App\Service\ReservationService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

class ReservationServiceTest extends TestCase
{
    private Trajet $trajet;
    private User $passager;
    private User $conducteur;

    protected function setUp(): void
    {
        $this->trajet = new Trajet();
        $this->trajet->setPrix(10.0);
        $this->trajet->setPlacesDisponibles(3);
        $this->conducteur = new User();
        $this->trajet->setConducteur($this->conducteur);
        $this->passager = new User();
    }

    private function creerServiceAvecStub(?Reservation $reservationExistante = null): ReservationService
    {
        $repository = $this->createStub(ReservationRepository::class);
        $repository->method('findActiveForPassengerAndTrajet')->willReturn($reservationExistante);

        return new ReservationService($repository);
    }

    private function creerReservationConfirmee(int $nbPlaces): Reservation
    {
        $reservation = new Reservation();
        $reservation->setPassager($this->passager);
        $reservation->setTrajet($this->trajet);
        $reservation->setNbPlaces($nbPlaces);
        $reservation->setStatut('CONFIRMEE');

        return $reservation;
    }

    #[Test]
    public function reserver_placesSuffisantes_creeDemandeEnAttenteSansDiminuerLesPlaces(): void
    {
        $reservation = $this->creerServiceAvecStub()->reserver($this->trajet, $this->passager, 2);

        $this->assertSame('EN_ATTENTE', $reservation->getStatut());
        $this->assertSame(3, $this->trajet->getPlacesDisponibles());
        $this->assertSame(20.0, $reservation->getPrixTotal());
    }

    #[Test]
    public function reserver_pasAssezDePlaces_leveException(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Pas assez de places disponibles.');

        $this->creerServiceAvecStub()->reserver($this->trajet, $this->passager, 5);
    }

    #[Test]
    public function reserver_demandeEnAttenteExistante_leveException(): void
    {
        $reservationExistante = new Reservation();
        $repository = $this->createStub(ReservationRepository::class);
        $repository->method('findActiveForPassengerAndTrajet')->willReturn($reservationExistante);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Vous avez déjà réservé ce trajet.');

        (new ReservationService($repository))->reserver($this->trajet, $this->passager, 1);
    }

    #[Test]
    public function reserver_reservationConfirmeeExistante_leveException(): void
    {
        $reservationExistante = new Reservation();
        $reservationExistante->setStatut('CONFIRMEE');
        $repository = $this->createStub(ReservationRepository::class);
        $repository->method('findActiveForPassengerAndTrajet')->willReturn($reservationExistante);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Vous avez déjà réservé ce trajet.');

        (new ReservationService($repository))->reserver($this->trajet, $this->passager, 1);
    }

    #[Test]
    public function demandeEnAttente_nbPlacesNeDependPasDesPlacesDisponiblesAuNiveauValidation(): void
    {
        $reservation = new Reservation();
        $reservation->setTrajet($this->trajet);
        $reservation->setNbPlaces(4);
        $this->trajet->setPlacesDisponibles(3);

        $violations = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
            ->validateProperty($reservation, 'nbPlaces');

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function accepter_demandeEnAttenteDuConducteur_diminueLesPlaces(): void
    {
        $reservation = $this->creerServiceAvecStub()->reserver($this->trajet, $this->passager, 2);

        $this->creerServiceAvecStub()->accepter($reservation, $this->conducteur);

        $this->assertSame('CONFIRMEE', $reservation->getStatut());
        $this->assertSame(1, $this->trajet->getPlacesDisponibles());
    }

    #[Test]
    public function accepter_pasAssezDePlaces_neModifiePasLaDemande(): void
    {
        $reservation = $this->creerServiceAvecStub()->reserver($this->trajet, $this->passager, 2);
        $this->trajet->setPlacesDisponibles(1);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Pas assez de places disponibles pour accepter cette demande.');
        $this->creerServiceAvecStub()->accepter($reservation, $this->conducteur);
    }

    #[Test]
    public function accepter_deuxiemeConfirmation_leveExceptionEtNeRetirePasDePlaces(): void
    {
        $reservation = $this->creerServiceAvecStub()->reserver($this->trajet, $this->passager, 2);
        $service = $this->creerServiceAvecStub();
        $service->accepter($reservation, $this->conducteur);

        $this->expectException(\DomainException::class);
        $service->accepter($reservation, $this->conducteur);
    }

    #[Test]
    public function refuser_demandeEnAttente_neDiminuePasLesPlaces(): void
    {
        $reservation = $this->creerServiceAvecStub()->reserver($this->trajet, $this->passager, 2);

        $this->creerServiceAvecStub()->refuser($reservation, $this->conducteur);

        $this->assertSame('REFUSEE', $reservation->getStatut());
        $this->assertSame(3, $this->trajet->getPlacesDisponibles());
    }

    #[Test]
    public function modifierReservation_unAutreConducteur_leveException(): void
    {
        $reservation = $this->creerServiceAvecStub()->reserver($this->trajet, $this->passager, 1);

        $this->expectException(\DomainException::class);
        $this->creerServiceAvecStub()->accepter($reservation, new User());
    }

    #[Test]
    public function annuler_reservationConfirmeeDuConducteur_rendLesPlacesAuTrajet(): void
    {
        $this->trajet->setDateDepart((new \DateTime())->modify('+1 day'));
        $reservation = $this->creerReservationConfirmee(2);
        $this->trajet->setPlacesDisponibles(1);

        $this->creerServiceAvecStub()->annuler($reservation, $this->conducteur);

        $this->assertSame('ANNULEE', $reservation->getStatut());
        $this->assertSame(3, $this->trajet->getPlacesDisponibles());
    }

    #[Test]
    public function annuler_reservationDejaAnnulee_leveException(): void
    {
        $this->trajet->setDateDepart((new \DateTime())->modify('+1 day'));
        $reservation = $this->creerReservationConfirmee(2);
        $reservation->setStatut('ANNULEE');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Seule une réservation confirmée peut être annulée.');
        $this->creerServiceAvecStub()->annuler($reservation, $this->conducteur);
    }

    #[Test]
    public function annuler_unPassagerNePeutPasAnnulerLaReservation(): void
    {
        $this->trajet->setDateDepart((new \DateTime())->modify('+1 day'));
        $reservation = $this->creerReservationConfirmee(1);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Vous ne pouvez pas modifier les réservations de ce trajet.');
        $this->creerServiceAvecStub()->annuler($reservation, $this->passager);
    }
}
