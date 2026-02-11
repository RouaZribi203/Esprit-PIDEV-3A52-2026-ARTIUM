<?php

namespace App\Entity;

use App\Enum\GenreMusique;
use App\Repository\MusiqueRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MusiqueRepository::class)]
class Musique extends Oeuvre
{
    #[ORM\Column(enumType: GenreMusique::class)]
    #[Assert\NotBlank(message: 'Genre is required')]
    private ?GenreMusique $genre = null;

    #[ORM\Column(type: Types::BLOB)]
    private mixed $audio = null;

    /**
     * @var Collection<int, Playlist>
     */
    #[ORM\ManyToMany(targetEntity: Playlist::class, mappedBy: 'musique')]
    private Collection $playlists;

    public function __construct()
    {
        parent::__construct();
        $this->playlists = new ArrayCollection();
    }

    public function getGenre(): ?GenreMusique
    {
        return $this->genre;
    }

    public function setGenre(GenreMusique $genre): static
    {
        $this->genre = $genre;

        return $this;
    }

    public function getAudio(): mixed
    {
        return $this->audio;
    }

    public function setAudio(mixed $audio): static
    {
        $this->audio = $audio;

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
            $playlist->addMusique($this);
        }

        return $this;
    }

    public function removePlaylist(Playlist $playlist): static
    {
        if ($this->playlists->removeElement($playlist)) {
            $playlist->removeMusique($this);
        }

        return $this;
    }
}
