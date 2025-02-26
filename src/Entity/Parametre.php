<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Parametre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "parametre_id", type: 'integer')] 
    private ?int $parametre_id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $propriete = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $valeur = null;

    #[ORM\ManyToOne(targetEntity: Configuration::class, inversedBy: 'parametres')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Configuration $configuration = null;

    public function getParametreId(): ?int
    {
        return $this->parametre_id;
    }

    public function getPropriete(): ?string
    {
        return $this->propriete;
    }

    public function setPropriete(string $propriete): self
    {
        $this->propriete = $propriete;
        return $this;
    }

    public function getValeur(): ?string
    {
        return $this->valeur;
    }

    public function setValeur(string $valeur): self
    {
        $this->valeur = $valeur;
        return $this;
    }

    public function getConfiguration(): ?Configuration
    {
        return $this->configuration;
    }

    public function setConfiguration(?Configuration $configuration): self
    {
        $this->configuration = $configuration;
        return $this;
    }
}
