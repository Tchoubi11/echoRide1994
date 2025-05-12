<?php

namespace App\Entity;

use App\Repository\DriverPreferencesRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DriverPreferencesRepository::class)]
class DriverPreferences
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?bool $fumeur = null;

    #[ORM\Column]
    private ?bool $animaux = null;

    #[ORM\Column(type: Types::ARRAY, nullable: true)]
    private ?array $autres = null;

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
}
