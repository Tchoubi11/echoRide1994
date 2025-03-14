<?php

namespace App\Entity;

use App\Repository\CovoiturageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: CovoiturageRepository::class)]
class Covoiturage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date_depart = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)] 
    private ?\DateTimeInterface $heure_depart = null;

    #[ORM\Column(length: 50)]
    private ?string $lieu_depart = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date_arrivee = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)] 
    private ?\DateTimeInterface $heure_arrivee = null;

    #[ORM\Column(length: 50)]
    private ?string $lieu_arrivee = null;

    #[ORM\Column(length: 50)]
    private ?string $statut = null;

    #[ORM\Column]
    private ?int $nb_place = null;

    #[ORM\Column(type: "float", nullable: true)]
    private ?float $max_price = null;

    #[ORM\Column(type: "float", nullable: true)]
   private ?float $max_duration = null;

   #[ORM\Column(type: "float", nullable: true)]
    private ?float $min_rating = null;


    #[ORM\Column]
    private ?float $prix_personne = null;

    #[ORM\Column(type: "boolean")]
    private bool $isEco = false;  

    #[ORM\OneToMany(mappedBy: "covoiturage", targetEntity: Reservation::class, cascade: ["remove"])]
    private Collection $reservations;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: "driver_id", referencedColumnName: "id", nullable: false)]
    private ?Utilisateur $driver = null; 

    public function __construct()
    {
        $this->reservations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateDepart(): ?\DateTimeInterface
    {
        return $this->date_depart;
    }

    public function setDateDepart(\DateTimeInterface $date_depart): static
    {
        $this->date_depart = $date_depart;
        return $this;
    }

    public function getHeureDepart(): ?\DateTimeInterface
    {
        return $this->heure_depart;
    }

    public function setHeureDepart(\DateTimeInterface $heure_depart): static
    {
        $this->heure_depart = $heure_depart;
        return $this;
    }

    public function getLieuDepart(): ?string
    {
        return $this->lieu_depart;
    }

    public function setLieuDepart(string $lieu_depart): static
    {
        $this->lieu_depart = $lieu_depart;
        return $this;
    }

    public function getDateArrivee(): ?\DateTimeInterface
    {
        return $this->date_arrivee;
    }

    public function setDateArrivee(\DateTimeInterface $date_arrivee): static
    {
        $this->date_arrivee = $date_arrivee;
        return $this;
    }

    public function getHeureArrivee(): ?\DateTimeInterface
    {
        return $this->heure_arrivee;
    }

    public function setHeureArrivee(\DateTimeInterface $heure_arrivee): static
    {
        $this->heure_arrivee = $heure_arrivee;
        return $this;
    }

    public function getLieuArrivee(): ?string
    {
        return $this->lieu_arrivee;
    }

    public function setLieuArrivee(string $lieu_arrivee): static
    {
        $this->lieu_arrivee = $lieu_arrivee;
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

    public function getNbPlace(): ?int
    {
        return $this->nb_place;
    }

    public function setNbPlace(int $nb_place): static
    {
        $this->nb_place = $nb_place;
        return $this;
    }

    public function getPrixPersonne(): ?float
    {
        return $this->prix_personne;
    }

    public function setPrixPersonne(float $prix_personne): static
    {
        $this->prix_personne = $prix_personne;
        return $this;
    }

    public function getIsEco(): bool
    {
        return $this->isEco;
    }

    public function setIsEco(bool $isEco): self
    {
        $this->isEco = $isEco;
        return $this;
    }

    public function getDriver(): ?Utilisateur
    {
        return $this->driver;
    }

    public function setDriver(?Utilisateur $driver): static
    {
        $this->driver = $driver;
        return $this;
    }

    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function getPlacesRestantes(): int
    {
        $placesReservees = array_reduce($this->reservations->toArray(), function ($total, $reservation) {
            return $total + $reservation->getPlacesReservees();
        }, 0);

        return $this->nb_place - $placesReservees;
    }

    public function getPassengers(): Collection
{
    return new ArrayCollection(
        $this->reservations->map(fn ($reservation) => $reservation->getPassenger())->toArray()
    );
}

public function getMaxPrice(): ?float
{
    return $this->max_price;
}

public function setMaxPrice(?float $max_price): self
{
    $this->max_price = $max_price;
    return $this;
}

public function getMaxDuration(): ?float
{
    return $this->max_duration;
}

public function setMaxDuration(?float $max_duration): self
{
    $this->max_duration = $max_duration;
    return $this;
}

public function getMinRating(): ?float
{
    return $this->min_rating;
}

public function setMinRating(?float $min_rating): self
{
    $this->min_rating = $min_rating;
    return $this;
}

}
