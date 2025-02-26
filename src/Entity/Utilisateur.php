<?php

namespace App\Entity;

use App\Repository\UtilisateurRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
class Utilisateur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "utilisateur_id", type: "integer")]
    private ?int $utilisateur_id = null;

    #[ORM\Column(length: 50)]
    private ?string $nom = null;

    #[ORM\Column(length: 50)]
    private ?string $prenom = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(length: 50)]
    private ?string $telephone = null;

    #[ORM\Column(length: 255)]
    private ?string $adresse = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date_naissance = null;

    #[ORM\Column(type: Types::BLOB, nullable: true)]
    private $photo;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $pseudo = null;

    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: Configuration::class)]
    private Collection $configurations;

    #[ORM\ManyToMany(targetEntity: Role::class, inversedBy: "utilisateurs")]
    #[ORM\JoinTable(name: "utilisateur_role")]
    private Collection $roles;

    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: Avis::class)]
    private Collection $avis;

    private Collection $covoiturages;

public function __construct()
{
    $this->configurations = new ArrayCollection();
    $this->roles = new ArrayCollection();
    $this->avis = new ArrayCollection();
    $this->covoiturages = new ArrayCollection();
}


    public function getUtilisateurId(): ?int
    {
        return $this->utilisateur_id;
    }

    #[ORM\OneToOne(mappedBy: "utilisateur", cascade: ["persist", "remove"])]
private ?Voiture $voiture = null;

public function getVoiture(): ?Voiture
{
    return $this->voiture;
}

public function setVoiture(?Voiture $voiture): static
{
    // Ici je vérifie si une autre voiture était déjà liée et la dissocie
    if ($voiture === null && $this->voiture !== null) {
        $this->voiture->setUtilisateur(null);
    }

    // Ici j'associe la nouvelle voiture
    if ($voiture !== null && $voiture->getUtilisateur() !== $this) {
        $voiture->setUtilisateur($this);
    }

    $this->voiture = $voiture;
    return $this;
}


    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): static
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(string $adresse): static
    {
        $this->adresse = $adresse;
        return $this;
    }

    public function getDateNaissance(): ?\DateTimeInterface
    {
        return $this->date_naissance;
    }

    public function setDateNaissance(\DateTimeInterface $date_naissance): static
    {
        $this->date_naissance = $date_naissance;
        return $this;
    }

    public function getPhoto()
    {
        return $this->photo;
    }

    public function setPhoto($photo): static
    {
        $this->photo = $photo;
        return $this;
    }

    public function getPseudo(): ?string
    {
        return $this->pseudo;
    }

    public function setPseudo(string $pseudo): static
    {
        $this->pseudo = $pseudo;
        return $this;
    }

    public function getConfigurations(): Collection
    {
        return $this->configurations;
    }

    public function addConfiguration(Configuration $configuration): self
    {
        if (!$this->configurations->contains($configuration)) {
            $this->configurations[] = $configuration;
            $configuration->setUtilisateur($this);
        }

        return $this;
    }

    public function removeConfiguration(Configuration $configuration): self
    {
        if ($this->configurations->removeElement($configuration)) {
            if ($configuration->getUtilisateur() === $this) {
                $configuration->setUtilisateur(null);
            }
        }

        return $this;
    }

    public function getRoles(): Collection
    {
        return $this->roles;
    }

    public function addRole(Role $role): self
    {
        if (!$this->roles->contains($role)) {
            $this->roles->add($role);
        }

        return $this;
    }

    public function removeRole(Role $role): self
    {
        $this->roles->removeElement($role);
        return $this;
    }

    public function getAvis(): Collection
    {
        return $this->avis;
    }

    public function addAvis(Avis $avis): self
    {
        if (!$this->avis->contains($avis)) {
            $this->avis[] = $avis;
            $avis->setUtilisateur($this); // Définit l'utilisateur de l'avis
        }

        return $this;
    }

    public function removeAvis(Avis $avis): self
    {
        if ($this->avis->removeElement($avis)) {
            if ($avis->getUtilisateur() === $this) {
                $avis->setUtilisateur(null); // Dissocie l'avis de l'utilisateur
            }
        }

        return $this;
    }

    public function getCovoiturages(): Collection
{
    return $this->covoiturages;
}

// Ajout d'un covoiturage
public function addCovoiturage(Covoiturage $covoiturage): self
{
    if (!$this->covoiturages->contains($covoiturage)) {
        $this->covoiturages[] = $covoiturage;
    }

    return $this;
}

// Retrait d'un covoiturage
public function removeCovoiturage(Covoiturage $covoiturage): self
{
    $this->covoiturages->removeElement($covoiturage);

    return $this;
}
}
