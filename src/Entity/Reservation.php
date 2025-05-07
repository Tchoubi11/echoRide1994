<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $passenger = null;

    #[ORM\ManyToOne(targetEntity: Covoiturage::class, inversedBy: "reservations")]
    #[ORM\JoinColumn(nullable: false)]
    private ?Covoiturage $covoiturage = null;

    #[ORM\OneToOne(mappedBy: 'reservation', targetEntity: Avis::class, cascade: ['persist', 'remove'])]
    private ?Avis $avis = null;

    #[ORM\Column(type: 'boolean')]
    private bool $aParticipe = false;

    #[ORM\Column(type: 'boolean')]
    private bool $aConfirmeParticipation = false;

    #[ORM\Column]
    private ?int $placesReservees = null;

    #[ORM\Column(length: 50)]
    private ?string $statut = null;

    #[ORM\Column(nullable: true)]
    private ?float $montantPaye = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $isValidatedByPassenger = null;

    #[ORM\Column(type: 'boolean')]
   private bool $problemeSignale = false;

   #[ORM\Column(type: 'text', nullable: true)]
   private ?string $detailsProbleme = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPassenger(): ?Utilisateur
    {
        return $this->passenger;
    }

    public function setPassenger(?Utilisateur $passenger): static
    {
        $this->passenger = $passenger;
        return $this;
    }

    public function getCovoiturage(): ?Covoiturage
    {
        return $this->covoiturage;
    }

    public function setCovoiturage(?Covoiturage $covoiturage): static
    {
        $this->covoiturage = $covoiturage;
        return $this;
    }

    public function getPlacesReservees(): ?int
    {
        return $this->placesReservees;
    }

    public function setPlacesReservees(int $placesReservees): static
    {
        $this->placesReservees = $placesReservees;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getMontantPaye(): ?float
    {
        return $this->montantPaye;
    }

    public function setMontantPaye(?float $montantPaye): static
    {
        $this->montantPaye = $montantPaye;
        return $this;
    }

    public function getIsValidatedByPassenger(): ?bool
    {
        return $this->isValidatedByPassenger;
    }

    public function setIsValidatedByPassenger(?bool $value): static
    {
        $this->isValidatedByPassenger = $value;
        return $this;
    }

    public function isProblemeSignale(): bool
{
    return $this->problemeSignale;
}

public function setProblemeSignale(bool $problemeSignale): static
{
    $this->problemeSignale = $problemeSignale;
    return $this;
}

public function getDetailsProbleme(): ?string
{
    return $this->detailsProbleme;
}

public function setDetailsProbleme(?string $details): static
{
    $this->detailsProbleme = $details;
    return $this;
}

public function getAvis(): ?Avis
{
    return $this->avis;
}

public function setAvis(?Avis $avis): static
{
    $this->avis = $avis;

    if ($avis && $avis->getReservation() !== $this) {
        $avis->setReservation($this);
    }

    return $this;
}
public function getAParticipe(): bool
{
    return $this->aParticipe;
}

public function setAParticipe(bool $aParticipe): self
{
    $this->aParticipe = $aParticipe;
    return $this;
}

public function isAConfirmeParticipation(): bool
{
    return $this->aConfirmeParticipation;
}

public function setAConfirmeParticipation(bool $aConfirmeParticipation): self
{
    $this->aConfirmeParticipation = $aConfirmeParticipation;
    return $this;
}

}
