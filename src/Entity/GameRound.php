<?php

namespace App\Entity;

use App\Repository\GameRoundRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Stringable;

#[ORM\Entity(repositoryClass: GameRoundRepository::class)]
#[ORM\UniqueConstraint(fields: ['number', 'season'])]
class GameRound implements Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $number = null;

    /**
     * @var Collection<int, BingoSquare>
     */
    #[ORM\OneToMany(targetEntity: BingoSquare::class, mappedBy: 'round', cascade: ['remove'])]
    private Collection $bingoSquares;

    /**
     * @var Collection<int, Game>
     */
    #[ORM\OneToMany(targetEntity: Game::class, mappedBy: 'round')]
    private Collection $games;

    #[ORM\Column]
    private ?int $season = null;

    #[ORM\Column]
    private ?DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
        $this->season = 1;
        $this->bingoSquares = new ArrayCollection();
        $this->games = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getNumber(): ?int
    {
        return $this->number;
    }

    public function setNumber(int $number): static
    {
        $this->number = $number;

        return $this;
    }

    /**
     * @return Collection<int, BingoSquare>
     */
    public function getBingoSquares(): Collection
    {
        return $this->bingoSquares;
    }

    public function addBingoSquare(BingoSquare $bingoSquare): static
    {
        if (!$this->bingoSquares->contains($bingoSquare)) {
            $this->bingoSquares->add($bingoSquare);
            $bingoSquare->setRound($this);
        }

        return $this;
    }

    public function removeBingoSquare(BingoSquare $bingoSquare): static
    {
        if ($this->bingoSquares->removeElement($bingoSquare)) {
            // set the owning side to null (unless already changed)
            if ($bingoSquare->getRound() === $this) {
                $bingoSquare->setRound(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Game>
     */
    public function getGames(): Collection
    {
        return $this->games;
    }

    public function addGame(Game $game): static
    {
        if (!$this->games->contains($game)) {
            $this->games->add($game);
            $game->setRound($this);
        }

        return $this;
    }

    public function removeGame(Game $game): static
    {
        if ($this->games->removeElement($game)) {
            // set the owning side to null (unless already changed)
            if ($game->getRound() === $this) {
                $game->setRound(null);
            }
        }

        return $this;
    }

    public function getSeason(): ?int
    {
        return $this->season;
    }

    public function setSeason(int $season): static
    {
        $this->season = $season;

        return $this;
    }

    public function __toString(): string
    {
        if ($this->number !== null && $this->season !== null) {
            return 'Round ' . $this->number . ' Saison ' . $this->season;
        }
        return '';
    }
}
