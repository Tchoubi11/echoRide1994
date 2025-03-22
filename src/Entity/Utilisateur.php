<?php

namespace App\Entity;

use App\Repository\UtilisateurRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
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

    #[ORM\Column(length: 50)]
    private ?string $email = null;

    #[ORM\Column(length: 50)]
    private ?string $password = null;

    #[ORM\Column(length: 50)]
    private ?string $telephone = null;

    #[ORM\Column(length: 50)]
    private ?string $adresse = null;

    #[ORM\Column(length: 50)]
    private ?string $date_naissance = null;
 
    #[ORM\Column(type: "float", nullable: true)]
    private ?float $rating = null; 

    #[ORM\Column(length: 50)]
    private ?string $pseudo = null;

    #[ORM\Column(type: "float", nullable: true)]
    private ?float $credits = null;


    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $preferences = null;

    #[ORM\OneToOne(targetEntity: Image::class, cascade: ["persist", "remove"])]
    #[ORM\JoinColumn(nullable: true)]
    private ?Image $photo = null;
    
    #[ORM\OneToMany(mappedBy: "driver", targetEntity: Covoiturage::class)]
    private Collection $covoiturages;

    #[ORM\OneToMany(mappedBy: "passenger", targetEntity: Reservation::class, cascade: ["remove"])]
    private Collection $reservations;

    #[ORM\OneToMany(mappedBy: "user", targetEntity: Avis::class)]
    private Collection $reviews; 

    #[ORM\ManyToOne(targetEntity: Voiture::class, inversedBy: "users")]
    #[ORM\JoinColumn(nullable: false)]
    private ?Voiture $vehicle = null;

    #[ORM\OneToMany(mappedBy: "utilisateur", targetEntity: Role::class)]
    private Collection $roles;

    private ?string $plainPassword = null;

    public function __construct()
    {
        $this->covoiturages = new ArrayCollection();
        $this->reservations = new ArrayCollection();  
        $this->reviews = new ArrayCollection(); 
        $this->roles = new ArrayCollection();
        $this->credits = 20;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDateNaissance(): ?string
    {
        return $this->date_naissance;
    }

    public function setDateNaissance(string $date_naissance): static
    {
        $this->date_naissance = $date_naissance;
        return $this;
    }

    public function getPhoto(): ?Image
    {
        return $this->photo;
    }

    public function setPhoto(?Image $photo): static
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

    public function getCovoiturages(): Collection
    {
        return $this->covoiturages;
    }

    public function getRating(): ?float
    {
        return $this->rating;
    }

    public function setRating(?float $rating): static
    {
        $this->rating = $rating;
        return $this;
    }

    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): self
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations->add($reservation);
            $reservation->setPassenger($this);
        }

        return $this;
    }

    public function removeReservation(Reservation $reservation): self
    {
        if ($this->reservations->removeElement($reservation)) {
            if ($reservation->getPassenger() === $this) {
                $reservation->setPassenger(null);
            }
        }

        return $this;
    }
    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    public function addReview(Avis $review): self
{
    if (!$this->reviews->contains($review)) {
        $this->reviews[] = $review;
        $review->setUser($this);  
    }

    return $this;
}


    public function getVehicle(): ?Voiture
    {
        return $this->vehicle;
    }

    public function setVehicle(?Voiture $vehicle): static
    {
        $this->vehicle = $vehicle;
        return $this;
    }


    public function getPreferences(): ?string
    {
        return $this->preferences;
    }

    public function setPreferences(?string $preferences): static
    {
        $this->preferences = $preferences;

        return $this;
    }

    public function getCredits(): ?float
   {
        return $this->credits;
   }

   public function setCredits(?float $credits): static
  {
    $this->credits = $credits;
    return $this;
  }

  public function getUserIdentifier(): string
{
    return $this->email; 
}
public function getRoles(): array
{
    $roles = $this->roles->map(fn($role) => $role->getNom())->toArray();
    $roles[] = 'ROLE_USER';  
    return array_unique($roles);
}


public function eraseCredentials(): void
{
    
}
  public function addRole(Role $role): static
  {
      if (!$this->roles->contains($role)) {
          $this->roles->add($role);
          $role->setUtilisateur($this);
      }

      return $this;
  }

  public function removeRole(Role $role): static
  {
      if ($this->roles->removeElement($role)) {
          
          if ($role->getUtilisateur() === $this) {
              $role->setUtilisateur(null);
          }
      }


      return $this;
  }

  public function getPlainPassword(): ?string
  {
      return $this->plainPassword;
  }

  public function setPlainPassword(?string $plainPassword): static
  {
      $this->plainPassword = $plainPassword;
      return $this;
  }
}
