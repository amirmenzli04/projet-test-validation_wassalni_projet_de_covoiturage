<?php

namespace App\Service;

use App\Entity\Reservation;
use App\Entity\Trajet;
use App\Entity\User;
use App\Repository\ReservationRepository;

class ReservationService
{
    public function __construct(private ReservationRepository $reservationRepository)
    {
    }

    /**
     * Prépare une demande de réservation sans bloquer les places du trajet.
     * La sauvegarde en base reste à la charge de l'appelant (persist + flush).
     */
    public function reserver(Trajet $trajet, User $passager, int $nbPlaces): Reservation
    {
        if ($nbPlaces < 1) {
            throw new \DomainException('Le nombre de places doit être positif.');
        }

        if ($trajet->getPlacesDisponibles() < $nbPlaces) {
            throw new \DomainException('Pas assez de places disponibles.');
        }

        $reservationExistante = $this->reservationRepository->findActiveForPassengerAndTrajet($passager, $trajet);
        if ($reservationExistante !== null) {
            throw new \DomainException('Vous avez déjà réservé ce trajet.');
        }

        $reservation = new Reservation();
        $reservation->setPassager($passager);
        $reservation->setTrajet($trajet);
        $reservation->setNbPlaces($nbPlaces);
        $reservation->setDateReservation(new \DateTime());
        $reservation->setStatut('EN_ATTENTE');
        $reservation->setPrixTotal($trajet->getPrix() * $nbPlaces);

        return $reservation;
    }

    /**
     * Accepte une demande et décrémente les places du trajet.
     */
    public function accepter(Reservation $reservation, User $conducteur): void
    {
        $this->verifierProprietaire($reservation, $conducteur);

        if ($reservation->getStatut() !== 'EN_ATTENTE') {
            throw new \DomainException('Cette demande ne peut plus être acceptée.');
        }

        $trajet = $reservation->getTrajet();
        if ($trajet->getPlacesDisponibles() < $reservation->getNbPlaces()) {
            throw new \DomainException('Pas assez de places disponibles pour accepter cette demande.');
        }

        $trajet->setPlacesDisponibles($trajet->getPlacesDisponibles() - $reservation->getNbPlaces());
        $reservation->setStatut('CONFIRMEE');
    }

    /**
     * Refuse une demande sans modifier les places disponibles.
     */
    public function refuser(Reservation $reservation, User $conducteur): void
    {
        $this->verifierProprietaire($reservation, $conducteur);

        if ($reservation->getStatut() !== 'EN_ATTENTE') {
            throw new \DomainException('Seule une demande en attente peut être refusée.');
        }

        $reservation->setStatut('REFUSEE');
    }

    /**
     * Annule une réservation confirmée et rend les places au trajet.
     */
    public function annuler(Reservation $reservation, User $conducteur): void
    {
        $this->verifierProprietaire($reservation, $conducteur);

        if ($reservation->getStatut() !== 'CONFIRMEE') {
            throw new \DomainException('Seule une réservation confirmée peut être annulée.');
        }

        if (!$reservation->peutEtreAnnulee()) {
            throw new \DomainException('Cette réservation ne peut plus être annulée moins de deux heures avant le départ.');
        }

        $trajet = $reservation->getTrajet();
        $trajet->setPlacesDisponibles($trajet->getPlacesDisponibles() + $reservation->getNbPlaces());

        $reservation->setStatut('ANNULEE');
    }

    private function verifierProprietaire(Reservation $reservation, User $conducteur): void
    {
        $proprietaire = $reservation->getTrajet()->getConducteur();
        if ($proprietaire !== $conducteur
            && ($proprietaire?->getId() === null || $proprietaire->getId() !== $conducteur->getId())) {
            throw new \DomainException('Vous ne pouvez pas modifier les réservations de ce trajet.');
        }
    }
}