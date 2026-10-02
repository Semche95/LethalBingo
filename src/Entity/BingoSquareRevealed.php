<?php

namespace App\Entity;

use App\Repository\BingoSquareRevealedRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BingoSquareRevealedRepository::class)]
#[ORM\UniqueConstraint(fields: ['game', 'bingoSquare'])]
class BingoSquareRevealed
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'bingoSquareRevealed')]
    #[ORM\JoinColumn(nullable: false)]
    private Game $game;

    #[ORM\ManyToOne(inversedBy: 'bingoSquareRevealed')]
    #[ORM\JoinColumn(nullable: false)]
    private BingoSquare $bingoSquare;

    #[ORM\Column]
    private DateTimeImmutable $revealedAt;

    public function __construct()
    {
        $this->revealedAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGame(): Game
    {
        return $this->game;
    }

    public function setGame(Game $game): static
    {
        $this->game = $game;

        return $this;
    }

    public function getBingoSquare(): BingoSquare
    {
        return $this->bingoSquare;
    }

    public function setBingoSquare(BingoSquare $bingoSquare): static
    {
        $this->bingoSquare = $bingoSquare;

        return $this;
    }

    public function getRevealedAt(): DateTimeImmutable
    {
        return $this->revealedAt;
    }

    public function setRevealedAt(DateTimeImmutable $revealedAt): static
    {
        $this->revealedAt = $revealedAt;

        return $this;
    }
}
