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

    #[ORM\Column]
    private ?int $placesReservees = null;

    #[ORM\Column(length: 50)]
    private ?string $statut = null;

    #[ORM\Column(nullable: true)]
    private ?float $montantPaye = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCovoiturage(): ?Covoiturage
    {
        return $this->covoiturage;
    }

    public function setCovoiturage(?Covoiturage $covoiturage): static
    {
        $this->covoiturage = $covoiturage;
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

    public function getPassenger(): ?Utilisateur
    {
        return $this->passenger;
    }

    public function setPassenger(?Utilisateur $passenger): static
    {
        $this->passenger = $passenger;
        return $this;
    }

}
