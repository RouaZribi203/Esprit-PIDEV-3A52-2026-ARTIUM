<?php

namespace App\Entity;

use App\Enum\CentreInteret;
use App\Enum\Role;
use App\Enum\Specialite;
use App\Enum\Statut;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity(
    fields: ['email'],
    message: 'Cet email est déjà utilisé par un autre utilisateur'
)]
class User implements PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(
        message: 'Le nom ne peut pas être vide'
    )]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Le nom doit contenir au moins {{ limit }} caractères',
        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères'
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-ZÀ-ÿ\s\'-]+$/u',
        message: 'Le nom ne doit contenir que des lettres, espaces, apostrophes et tirets'
    )]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(
        message: 'Le prénom ne peut pas être vide'
    )]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Le prénom doit contenir au moins {{ limit }} caractères',
        maxMessage: 'Le prénom ne peut pas dépasser {{ limit }} caractères'
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-ZÀ-ÿ\s\'-]+$/u',
        message: 'Le prénom ne doit contenir que des lettres, espaces, apostrophes et tirets'
    )]
    private ?string $prenom = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(
        message: 'La date de naissance ne peut pas être vide'
    )]
    #[Assert\LessThan(
        value: 'today',
        message: 'La date de naissance doit être dans le passé'
    )]
    #[Assert\GreaterThan(
        value: '-120 years',
        message: 'La date de naissance n\'est pas valide'
    )]
    #[Assert\Expression(
        expression: 'this.getAge() >= 13',
        message: 'Vous devez avoir au moins 13 ans pour vous inscrire'
    )]
    private ?\DateTime $date_naissance = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(
        message: 'L\'email ne peut pas être vide'
    )]
    #[Assert\Email(
        message: 'L\'adresse email "{{ value }}" n\'est pas valide'
    )]
    #[Assert\Length(
        max: 255,
        maxMessage: 'L\'email ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(
        message: 'Le mot de passe ne peut pas être vide'
    )]
    #[Assert\Length(
        min: 6,
        max: 255,
        minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères',
        maxMessage: 'Le mot de passe ne peut pas dépasser {{ limit }} caractères'
    )]
    #[Assert\Regex(
        pattern: '/^(?=.*[A-Za-z])(?=.*\d).+$/',
        message: 'Le mot de passe doit contenir au moins une lettre et un chiffre'
    )]
    private ?string $mdp = null;

    #[ORM\Column(enumType: Role::class)]
    #[Assert\NotNull(
        message: 'Le rôle ne peut pas être vide'
    )]
    private ?Role $role = null;

    #[ORM\Column(enumType: Statut::class)]
    #[Assert\NotNull(
        message: 'Le statut ne peut pas être vide'
    )]
    private ?Statut $statut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(
        message: 'La date d\'inscription ne peut pas être vide'
    )]
    #[Assert\LessThanOrEqual(
        value: 'today',
        message: 'La date d\'inscription ne peut pas être dans le futur'
    )]
    private ?\DateTime $date_inscription = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(
        message: 'Le numéro de téléphone ne peut pas être vide'
    )]
    #[Assert\Regex(
        pattern: '/^[2459]\d{7}$/',
        message: 'Le numéro de téléphone doit contenir 8 chiffres et commencer par 2, 4, 5 ou 9'
    )]
    private ?string $num_tel = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(
        message: 'La ville ne peut pas être vide'
    )]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'La ville doit contenir au moins {{ limit }} caractères',
        maxMessage: 'La ville ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $ville = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 2000,
        maxMessage: 'La biographie ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $biographie = null;

    #[ORM\Column(nullable: true, enumType: Specialite::class)]
    #[Assert\Expression(
        expression: 'this.getRole() == null or this.getRole().value != "ARTISTE" or this.getSpecialite() != null',
        message: 'La spécialité est obligatoire pour les artistes'
    )]
    private ?Specialite $specialite = null;

    #[ORM\Column(type: Types::SIMPLE_ARRAY, nullable: true, enumType: CentreInteret::class)]
    #[Assert\Expression(
        expression: 'this.getRole() == null or this.getRole().value != "AMATEUR" or this.getCentreInteret() != null',
        message: 'Au moins un centre d\'intérêt est obligatoire pour les amateurs'
    )]
    #[Assert\Count(
        max: 10,
        maxMessage: 'Vous ne pouvez pas sélectionner plus de {{ limit }} centres d\'intérêt'
    )]
    private ?array $centre_interet = null;

    /**
     * @var Collection<int, Collections>
     */
    #[ORM\OneToMany(targetEntity: Collections::class, mappedBy: 'artiste', orphanRemoval: true)]
    private Collection $collections;

    /**
     * @var Collection<int, Commentaire>
     */
    #[ORM\OneToMany(targetEntity: Commentaire::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $commentaires;

    /**
     * @var Collection<int, Playlist>
     */
    #[ORM\OneToMany(targetEntity: Playlist::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $playlists;

    /**
     * @var Collection<int, Oeuvre>
     */
    #[ORM\ManyToMany(targetEntity: Oeuvre::class, mappedBy: 'user_fav')]
    private Collection $fav_user;

    /**
     * @var Collection<int, Reclamation>
     */
    #[ORM\OneToMany(targetEntity: Reclamation::class, mappedBy: 'user')]
    private Collection $reclamations;

    /**
     * @var Collection<int, Reponse>
     */
    #[ORM\OneToMany(targetEntity: Reponse::class, mappedBy: 'user_admin')]
    private Collection $reponses;

    /**
     * @var Collection<int, Evenement>
     */
    #[ORM\OneToMany(targetEntity: Evenement::class, mappedBy: 'artiste')]
    private Collection $evenements;

    /**
     * @var Collection<int, Ticket>
     */
    #[ORM\OneToMany(targetEntity: Ticket::class, mappedBy: 'user')]
    private Collection $tickets;

    /**
     * @var Collection<int, LocationLivre>
     */
    #[ORM\OneToMany(targetEntity: LocationLivre::class, mappedBy: 'user')]
    private Collection $locationLivres;

    /**
     * @var Collection<int, Like>
     */
    #[ORM\OneToMany(targetEntity: Like::class, mappedBy: 'user')]
    private Collection $likes;

    public function __construct()
    {
        $this->collections = new ArrayCollection();
        $this->commentaires = new ArrayCollection();
        $this->playlists = new ArrayCollection();
        $this->fav_user = new ArrayCollection();
        $this->reclamations = new ArrayCollection();
        $this->reponses = new ArrayCollection();
        $this->evenements = new ArrayCollection();
        $this->tickets = new ArrayCollection();
        $this->locationLivres = new ArrayCollection();
        $this->likes = new ArrayCollection();
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

    public function getDateNaissance(): ?\DateTime
    {
        return $this->date_naissance;
    }

    public function setDateNaissance(\DateTime $date_naissance): static
    {
        $this->date_naissance = $date_naissance;

        return $this;
    }

    /**
     * Calcule l'âge de l'utilisateur
     */
    public function getAge(): ?int
    {
        if ($this->date_naissance === null) {
            return null;
        }

        $now = new \DateTime();
        $interval = $this->date_naissance->diff($now);
        
        return $interval->y;
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

    public function getMdp(): ?string
    {
        return $this->mdp;
    }

    public function setMdp(string $mdp): static
    {
        $this->mdp = $mdp;

        return $this;
    }

    public function getRole(): ?Role
    {
        return $this->role;
    }

    public function setRole(Role $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getStatut(): ?Statut
    {
        return $this->statut;
    }

    public function setStatut(Statut $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getDateInscription(): ?\DateTime
    {
        return $this->date_inscription;
    }

    public function setDateInscription(\DateTime $date_inscription): static
    {
        $this->date_inscription = $date_inscription;

        return $this;
    }

    public function getNumTel(): ?string
    {
        return $this->num_tel;
    }

    public function setNumTel(string $num_tel): static
    {
        $this->num_tel = $num_tel;

        return $this;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(string $ville): static
    {
        $this->ville = $ville;

        return $this;
    }

    public function getBiographie(): ?string
    {
        return $this->biographie;
    }

    public function setBiographie(?string $biographie): static
    {
        $this->biographie = $biographie;

        return $this;
    }

    public function getSpecialite(): ?Specialite
    {
        return $this->specialite;
    }

    public function setSpecialite(?Specialite $specialite): static
    {
        $this->specialite = $specialite;

        return $this;
    }

    /**
     * @return CentreInteret[]|null
     */
    public function getCentreInteret(): ?array
    {
        return $this->centre_interet;
    }

    public function setCentreInteret(?array $centre_interet): static
    {
        $this->centre_interet = $centre_interet;

        return $this;
    }

    /**
     * @return Collection<int, Collections>
     */
    public function getCollections(): Collection
    {
        return $this->collections;
    }

    public function addCollection(Collections $collection): static
    {
        if (!$this->collections->contains($collection)) {
            $this->collections->add($collection);
            $collection->setArtiste($this);
        }

        return $this;
    }

    public function removeCollection(Collections $collection): static
    {
        if ($this->collections->removeElement($collection)) {
            if ($collection->getArtiste() === $this) {
                $collection->setArtiste(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Commentaire>
     */
    public function getCommentaires(): Collection
    {
        return $this->commentaires;
    }

    public function addCommentaire(Commentaire $commentaire): static
    {
        if (!$this->commentaires->contains($commentaire)) {
            $this->commentaires->add($commentaire);
            $commentaire->setUser($this);
        }

        return $this;
    }

    public function removeCommentaire(Commentaire $commentaire): static
    {
        if ($this->commentaires->removeElement($commentaire)) {
            if ($commentaire->getUser() === $this) {
                $commentaire->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Playlist>
     */
    public function getPlaylists(): Collection
    {
        return $this->playlists;
    }

    public function addPlaylist(Playlist $playlist): static
    {
        if (!$this->playlists->contains($playlist)) {
            $this->playlists->add($playlist);
            $playlist->setUser($this);
        }

        return $this;
    }

    public function removePlaylist(Playlist $playlist): static
    {
        if ($this->playlists->removeElement($playlist)) {
            if ($playlist->getUser() === $this) {
                $playlist->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Oeuvre>
     */
    public function getFavUser(): Collection
    {
        return $this->fav_user;
    }

    public function addFavUser(Oeuvre $favUser): static
    {
        if (!$this->fav_user->contains($favUser)) {
            $this->fav_user->add($favUser);
            $favUser->addUserFav($this);
        }

        return $this;
    }

    public function removeFavUser(Oeuvre $favUser): static
    {
        if ($this->fav_user->removeElement($favUser)) {
            $favUser->removeUserFav($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Reclamation>
     */
    public function getReclamations(): Collection
    {
        return $this->reclamations;
    }

    public function addReclamation(Reclamation $reclamation): static
    {
        if (!$this->reclamations->contains($reclamation)) {
            $this->reclamations->add($reclamation);
            $reclamation->setUser($this);
        }

        return $this;
    }

    public function removeReclamation(Reclamation $reclamation): static
    {
        if ($this->reclamations->removeElement($reclamation)) {
            if ($reclamation->getUser() === $this) {
                $reclamation->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Reponse>
     */
    public function getReponses(): Collection
    {
        return $this->reponses;
    }

    public function addReponse(Reponse $reponse): static
    {
        if (!$this->reponses->contains($reponse)) {
            $this->reponses->add($reponse);
            $reponse->setUserAdmin($this);
        }

        return $this;
    }

    public function removeReponse(Reponse $reponse): static
    {
        if ($this->reponses->removeElement($reponse)) {
            if ($reponse->getUserAdmin() === $this) {
                $reponse->setUserAdmin(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Evenement>
     */
    public function getEvenements(): Collection
    {
        return $this->evenements;
    }

    public function addEvenement(Evenement $evenement): static
    {
        if (!$this->evenements->contains($evenement)) {
            $this->evenements->add($evenement);
            $evenement->setArtiste($this);
        }

        return $this;
    }

    public function removeEvenement(Evenement $evenement): static
    {
        if ($this->evenements->removeElement($evenement)) {
            if ($evenement->getArtiste() === $this) {
                $evenement->setArtiste(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Ticket>
     */
    public function getTickets(): Collection
    {
        return $this->tickets;
    }

    public function addTicket(Ticket $ticket): static
    {
        if (!$this->tickets->contains($ticket)) {
            $this->tickets->add($ticket);
            $ticket->setUser($this);
        }

        return $this;
    }

    public function removeTicket(Ticket $ticket): static
    {
        if ($this->tickets->removeElement($ticket)) {
            if ($ticket->getUser() === $this) {
                $ticket->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, LocationLivre>
     */
    public function getLocationLivres(): Collection
    {
        return $this->locationLivres;
    }

    public function addLocationLivre(LocationLivre $locationLivre): static
    {
        if (!$this->locationLivres->contains($locationLivre)) {
            $this->locationLivres->add($locationLivre);
            $locationLivre->setUser($this);
        }

        return $this;
    }

    public function removeLocationLivre(LocationLivre $locationLivre): static
    {
        if ($this->locationLivres->removeElement($locationLivre)) {
            if ($locationLivre->getUser() === $this) {
                $locationLivre->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Like>
     */
    public function getLikes(): Collection
    {
        return $this->likes;
    }

    public function addLike(Like $like): static
    {
        if (!$this->likes->contains($like)) {
            $this->likes->add($like);
            $like->setUser($this);
        }

        return $this;
    }

    public function removeLike(Like $like): static
    {
        if ($this->likes->removeElement($like)) {
            if ($like->getUser() === $this) {
                $like->setUser(null);
            }
        }

        return $this;
    }

    public function getPassword(): string
    {
        return $this->mdp ?? '';
    }
}