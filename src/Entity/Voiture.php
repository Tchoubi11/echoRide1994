<?php

namespace App\Entity;

use App\Repository\VoitureRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Entity\Covoiturage;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: VoitureRepository::class)]
class Voiture
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $modele = null;

    #[ORM\Column(length: 50)]
    private ?string $immatriculation = null;

    #[Assert\NotBlank(message: "L'énergie est obligatoire.")]
    #[ORM\Column(type: 'string')]
    private $energie;


    #[ORM\Column(type: 'integer')]
    private ?int $placesDisponibles = null;

    #[ORM\Column(length: 50)]
    private ?string $couleur = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $date_premiere_immatriculation = null;

    #[ORM\ManyToOne(targetEntity: Marque::class, inversedBy: 'voitures')]
    #[ORM\JoinColumn(name: 'marque_id', referencedColumnName: 'marque_id')]
    private ?Marque $marque = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'voitures')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $utilisateur = null;


    #[ORM\OneToMany(mappedBy: 'voiture', targetEntity: Covoiturage::class)]
    private Collection $covoiturages;

    public function __construct()
  {
    $this->covoiturages = new ArrayCollection();
  }

    // Getters and Setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getModele(): ?string
    {
        return $this->modele;
    }

    public function setModele(string $modele): static
    {
        $this->modele = $modele;
        return $this;
    }

    public function getImmatriculation(): ?string
    {
        return $this->immatriculation;
    }

    public function setImmatriculation(string $immatriculation): static
    {
        $this->immatriculation = $immatriculation;
        return $this;
    }

    public function getEnergie(): ?string
    {
        return $this->energie;
    }

    public function setEnergie(string $energie): static
    {
        $this->energie = $energie;
        return $this;
    }

    public function getCouleur(): ?string
    {
        return $this->couleur;
    }

    public function setCouleur(string $couleur): static
    {
        $this->couleur = $couleur;
        return $this;
    }

    public function getDatePremiereImmatriculation(): ?\DateTimeInterface
    {
        return $this->date_premiere_immatriculation;
    }

    public function setDatePremiereImmatriculation(\DateTimeInterface $date): static
    {
        $this->date_premiere_immatriculation = $date;
        return $this;
    }

    public function getMarque(): ?Marque
    {
        return $this->marque;
    }

    public function setMarque(?Marque $marque): static
    {
        $this->marque = $marque;
        return $this;
    }

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): static
    {
        $this->utilisateur = $utilisateur;
        return $this;
    }

    public function getPlacesDisponibles(): ?int
    {
        return $this->placesDisponibles;
    }

    public function setPlacesDisponibles(int $placesDisponibles): static
    {
        $this->placesDisponibles = $placesDisponibles;
        return $this;
    }
    
public function getCovoiturages(): Collection
{
    return $this->covoiturages;
}

public function addCovoiturage(Covoiturage $covoiturage): static
{
    if (!$this->covoiturages->contains($covoiturage)) {
        $this->covoiturages[] = $covoiturage;
        $covoiturage->setVoiture($this);
    }
    return $this;
}

public function removeCovoiturage(Covoiturage $covoiturage): static
{
    if ($this->covoiturages->removeElement($covoiturage)) {
        if ($covoiturage->getVoiture() === $this) {
            $covoiturage->setVoiture(null);
        }
    }
    return $this;
}
}
