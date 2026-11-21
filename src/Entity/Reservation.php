<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
class Reservation
{
    public const STATUT_CONFIRME = 'CONFIRMEE';
    public const STATUT_ANNULE = 'ANNULEE';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $dateReservation = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "Le nombre de places est obligatoire")]
    #[Assert\Positive(message: "Le nombre de places doit être positif")]
    #[Assert\LessThanOrEqual(propertyPath: "trajet.placesDisponibles", message: "Pas assez de places disponibles")]
    private ?int $nbPlaces = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(
        choices: [self::STATUT_CONFIRME, self::STATUT_ANNULE],
        message: 'Le statut de la réservation est invalide'
    )]
    private ?string $statut = self::STATUT_CONFIRME;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $prixTotal = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $dateAnnulation = null;

    #[ORM\ManyToOne(inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $passager = null;

    #[ORM\ManyToOne(inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Trajet $trajet = null;

    public function __construct()
    {
        $this->dateReservation = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateReservation(): ?\DateTimeInterface
    {
        return $this->dateReservation;
    }

    public function setDateReservation(\DateTimeInterface $dateReservation): static
    {
        $this->dateReservation = $dateReservation;
        return $this;
    }

    public function getNbPlaces(): ?int
    {
        return $this->nbPlaces;
    }

    public function setNbPlaces(int $nbPlaces): static
    {
        $this->nbPlaces = $nbPlaces;
        $this->updatePrixTotal();
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        $this->dateAnnulation = $statut === self::STATUT_ANNULE
            ? ($this->dateAnnulation ?? new \DateTime())
            : null;

        return $this;
    }

    public function getPrixTotal(): ?float
    {
        return $this->prixTotal;
    }

    public function setPrixTotal(?float $prixTotal): static
    {
        $this->prixTotal = $prixTotal;
        return $this;
    }

    public function getDateAnnulation(): ?\DateTimeInterface
    {
        return $this->dateAnnulation;
    }

    public function setDateAnnulation(?\DateTimeInterface $dateAnnulation): static
    {
        $this->dateAnnulation = $dateAnnulation;
        return $this;
    }

    public function getPassager(): ?User
    {
        return $this->passager;
    }

    public function setPassager(?User $passager): static
    {
        $this->passager = $passager;
        return $this;
    }

    public function getTrajet(): ?Trajet
    {
        return $this->trajet;
    }

    public function setTrajet(?Trajet $trajet): static
    {
        $this->trajet = $trajet;
        $this->updatePrixTotal();
        return $this;
    }

    private function updatePrixTotal(): void
    {
        $prixUnitaire = $this->trajet?->getPrix();

        if ($this->nbPlaces === null || $prixUnitaire === null) {
            $this->prixTotal = null;
            return;
        }

        $this->prixTotal = $prixUnitaire * $this->nbPlaces;
    }

    public function peutEtreAnnulee(): bool
    {
        if ($this->statut !== self::STATUT_CONFIRME || $this->trajet === null) {
            return false;
        }

        $dateDepart = $this->trajet->getDateDepart();
        if ($dateDepart === null) {
            return false;
        }

        return $dateDepart->getTimestamp() - time() >= 2 * 60 * 60;
    }
}