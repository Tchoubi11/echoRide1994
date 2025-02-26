<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
class Configuration
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_configuration', type: 'integer')] 
    private ?int $id_configuration = null;


    #[ORM\OneToMany(mappedBy: 'configuration', targetEntity: Parametre::class, cascade: ['persist', 'remove'])]
    private Collection $parametres;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'configurations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $utilisateur = null;

    public function __construct()
    {
        $this->parametres = new ArrayCollection();
    }

    public function getIdConfiguration(): ?int
    {
        return $this->id_configuration;
    }

    public function getParametres(): Collection
    {
        return $this->parametres;
    }

    public function addParametre(Parametre $parametre): self
    {
        if (!$this->parametres->contains($parametre)) {
            $this->parametres[] = $parametre;
            $parametre->setConfiguration($this);
        }

        return $this;
    }

    public function removeParametre(Parametre $parametre): self
    {
        if ($this->parametres->removeElement($parametre)) {
            if ($parametre->getConfiguration() === $this) {
                $parametre->setConfiguration(null);
            }
        }

        return $this;
    }

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): self
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }
}
