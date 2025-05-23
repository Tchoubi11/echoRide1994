<?php

namespace App\Entity;

use App\Repository\UtilisateurRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

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

    #[ORM\Column(length: 100)]
    private ?string $adresse = null;

    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $date_naissance = null;


    #[ORM\Column(type: "float", nullable: true)]
    private ?float $rating = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $pseudo = null;

    #[ORM\Column(length: 20)]
    private ?string $type_utilisateur;

    #[ORM\Column(type: 'boolean')]
    private bool $isSuspended = false;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\OneToOne(mappedBy: 'utilisateur', targetEntity: Preference::class, cascade: ['persist', 'remove'])]
    private ?Preference $preference = null;

    #[ORM\OneToOne(targetEntity: Image::class, cascade: ["persist", "remove"])]
    #[ORM\JoinColumn(nullable: true)]
    private ?Image $photo = null;

    #[ORM\OneToMany(mappedBy: "driver", targetEntity: Covoiturage::class)]
    private Collection $covoiturages;

    #[ORM\OneToMany(mappedBy: "passenger", targetEntity: Reservation::class, cascade: ["remove"])]
    private Collection $reservations;

    #[ORM\OneToMany(mappedBy: "utilisateur", targetEntity: Voiture::class)]
    private Collection $voitures;

    private ?string $plainPassword = null;

    public function __construct()
    {
        $this->covoiturages = new ArrayCollection();
        $this->reservations = new ArrayCollection();
        $this->voitures = new ArrayCollection();

        if (!$this->photo) {
            $defaultImage = new Image();
            $defaultImage->setImagePath('uploads/images/67c88b34e4a07.jpg');
            $this->photo = $defaultImage;
        }
    }

    public function getId(): ?int { return $this->id; }

    public function getNom(): ?string { return $this->nom; }

    public function setNom(string $nom): static { $this->nom = $nom; return $this; }

    public function getPrenom(): ?string { return $this->prenom; }

    public function setPrenom(string $prenom): static { $this->prenom = $prenom; return $this; }

    public function getEmail(): ?string { return $this->email; }

    public function setEmail(string $email): static { $this->email = $email; return $this; }

    public function getPassword(): ?string { return $this->password; }

    public function setPassword(string $password): static { $this->password = $password; return $this; }

    public function getPlainPassword(): ?string { return $this->plainPassword; }

    public function setPlainPassword(?string $plainPassword): static { $this->plainPassword = $plainPassword; return $this; }

    public function getTelephone(): ?string { return $this->telephone; }

    public function setTelephone(string $telephone): static { $this->telephone = $telephone; return $this; }

    public function getAdresse(): ?string { return $this->adresse; }

    public function setAdresse(string $adresse): static { $this->adresse = $adresse; return $this; }

    public function getDateNaissance(): ?\DateTimeInterface
{
    return $this->date_naissance;
}

public function setDateNaissance(?\DateTimeInterface $date_naissance): static
{
    $this->date_naissance = $date_naissance;
    return $this;
}

    public function getPseudo(): ?string { return $this->pseudo; }

    public function setPseudo(string $pseudo): static { $this->pseudo = $pseudo; return $this; }

    public function getRating(): ?float { return $this->rating; }

    public function setRating(?float $rating): static { $this->rating = $rating; return $this; }

    public function getTypeUtilisateur(): ?string { return $this->type_utilisateur; }

    public function setTypeUtilisateur(string $type_utilisateur): static { $this->type_utilisateur = $type_utilisateur; return $this; }

    public function getPhoto(): ?Image { return $this->photo; }

    public function setPhoto(?Image $photo): static { $this->photo = $photo; return $this; }

    public function getPreference(): ?Preference { return $this->preference; }

    public function setPreference(?Preference $preference): static
    {
        if ($preference && $preference->getUtilisateur() !== $this) {
            $preference->setUtilisateur($this);
        }
        $this->preference = $preference;
        return $this;
    }

    public function getCovoiturages(): Collection { return $this->covoiturages; }

    public function getReservations(): Collection { return $this->reservations; }

    public function addReservation(Reservation $reservation): static
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations[] = $reservation;
            $reservation->setPassenger($this);
        }
        return $this;
    }

    public function removeReservation(Reservation $reservation): static
    {
        if ($this->reservations->removeElement($reservation) && $reservation->getPassenger() === $this) {
            $reservation->setPassenger(null);
        }
        return $this;
    }

    public function getVoitures(): Collection { return $this->voitures; }

    public function addVoiture(Voiture $voiture): static
    {
        if (!$this->voitures->contains($voiture)) {
            $this->voitures[] = $voiture;
            $voiture->setUtilisateur($this);
        }
        return $this;
    }

    public function removeVoiture(Voiture $voiture): static
    {
        if ($this->voitures->removeElement($voiture) && $voiture->getUtilisateur() === $this) {
            $voiture->setUtilisateur(null);
        }
        return $this;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        if (!in_array('ROLE_USER', $roles)) {
            $roles[] = 'ROLE_USER';
        }
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getUserIdentifier(): string { return $this->email; }

    public function eraseCredentials(): void {}

    public function isSuspended(): bool { return $this->isSuspended; }

    public function setIsSuspended(bool $isSuspended): static
    {
        $this->isSuspended = $isSuspended;
        return $this;
    }
}
