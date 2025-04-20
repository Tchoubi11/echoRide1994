<?php

namespace App\Entity;

use App\Repository\CovoiturageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Entity\Voiture;

#[ORM\Entity(repositoryClass: CovoiturageRepository::class)]
class Covoiturage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $heure_depart = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $heure_arrivee = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isCancelled = false;

    #[ORM\Column(type: 'boolean')]
   private bool $isStarted = false;

  #[ORM\Column(type: 'datetime_immutable', nullable: true)]
   private ?\DateTimeImmutable $startAt = null;

   #[ORM\Column(type: 'boolean')]
   private bool $isCompleted = false;
   
   #[ORM\Column(type: 'datetime_immutable', nullable: true)]
   private ?\DateTimeImmutable $endAt = null;
   

    #[ORM\Column(length: 50)]
    private ?string $lieu_depart = null;

    #[ORM\Column(length: 50)]
    private ?string $lieu_arrivee = null;

    #[ORM\Column(length: 50)]
    private ?string $statut = null;

    #[ORM\Column(name: "nb_place")]
    private ?int $nbPlace = null;

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

    #[ORM\ManyToMany(targetEntity: Utilisateur::class)]
    #[ORM\JoinTable(name: "covoiturage_passagers")]
    private Collection $passengers;

    #[ORM\ManyToOne(targetEntity: Voiture::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Voiture $voiture = null;

    public function __construct()
    {
        $this->reservations = new ArrayCollection();
        $this->passengers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    
    public function getDateDepart(): ?\DateTimeInterface
    {
        return $this->heure_depart;
    }

    public function setDateDepart(?\DateTimeInterface $date): self
    {
        $this->heure_depart = $date;
        return $this;
    }

    public function getDateArrivee(): ?\DateTimeInterface
    {
        return $this->heure_arrivee;
    }

    public function setDateArrivee(?\DateTimeInterface $date): self
    {
        $this->heure_arrivee = $date;
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

    public function getHeureArrivee(): ?\DateTimeInterface
    {
        return $this->heure_arrivee;
    }

    public function setHeureArrivee(\DateTimeInterface $heure_arrivee): static
    {
        $this->heure_arrivee = $heure_arrivee;
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
        return $this->nbPlace;
    }

    public function setNbPlace(int $nbPlace): static
    {
        $this->nbPlace = $nbPlace;
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

        return $this->nbPlace - $placesReservees;
    }

    public function getPassengers(): Collection
    {
        return $this->passengers;
    }

    public function addPassenger(Utilisateur $user): self
    {
        if (!$this->passengers->contains($user)) {
            $this->passengers[] = $user;
        }
        return $this;
    }

    public function removePassenger(Utilisateur $user): self
    {
        if ($this->passengers->contains($user)) {
            $this->passengers->removeElement($user);
        }
        return $this;
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

    public function getVoiture(): ?Voiture
    {
        return $this->voiture;
    }

    public function setVoiture(?Voiture $voiture): self
    {
        $this->voiture = $voiture;
        return $this;
    }

    public function isCancelled(): bool
    {
        return $this->isCancelled;
    }

    public function setIsCancelled(bool $isCancelled): self
    {
        $this->isCancelled = $isCancelled;
        return $this;
    }

    public function isStarted(): bool
{
    return $this->isStarted;
}

public function setIsStarted(bool $isStarted): self
{
    $this->isStarted = $isStarted;
    return $this;
}

public function getStartAt(): ?\DateTimeImmutable
{
    return $this->startAt;
}

public function setStartAt(?\DateTimeImmutable $startAt): self
{
    $this->startAt = $startAt;
    return $this;
}

public function isCompleted(): bool
{
    return $this->isCompleted;
}

public function setIsCompleted(bool $isCompleted): self
{
    $this->isCompleted = $isCompleted;
    return $this;
}

public function getEndAt(): ?\DateTimeImmutable
{
    return $this->endAt;
}

public function setEndAt(?\DateTimeImmutable $endAt): self
{
    $this->endAt = $endAt;
    return $this;
}

}
