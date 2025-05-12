<?php

namespace App\Entity;

use App\Repository\PreferenceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PreferenceRepository::class)]
class Preference
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?bool $fumeur = null;

    #[ORM\Column]
    private ?bool $animaux = null;

    #[ORM\Column(nullable: true)]
    private ?array $autres = null;

    #[ORM\OneToOne(inversedBy: 'preference', targetEntity: Utilisateur::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'utilisateur_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?Utilisateur $utilisateur = null;

    #[ORM\OneToOne(mappedBy: 'preference', targetEntity: Covoiturage::class, cascade: ['persist', 'remove'])]
    private ?Covoiturage $covoiturage = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function isFumeur(): ?bool
    {
        return $this->fumeur;
    }

    public function setFumeur(bool $fumeur): static
    {
        $this->fumeur = $fumeur;

        return $this;
    }

    public function isAnimaux(): ?bool
    {
        return $this->animaux;
    }

    public function setAnimaux(bool $animaux): static
    {
        $this->animaux = $animaux;

        return $this;
    }

    public function getAutres(): ?array
    {
        return $this->autres;
    }

    public function setAutres(?array $autres): static
    {
        $this->autres = $autres;

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

public function getCovoiturage(): ?Covoiturage
    {
        return $this->covoiturage;
    }

    public function setCovoiturage(?Covoiturage $covoiturage): self
    {
        $this->covoiturage = $covoiturage;
        return $this;
    }

}
