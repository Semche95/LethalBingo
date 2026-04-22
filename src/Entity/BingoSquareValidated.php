<?php

namespace App\Entity;

use App\Repository\BingoSquareValidatedRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BingoSquareValidatedRepository::class)]
#[ORM\UniqueConstraint(fields: ['game', 'bingoSquare'])]
class BingoSquareValidated
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'bingoSquareValidated')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Game $game = null;

    #[ORM\ManyToOne(inversedBy: 'bingoSquareValidated')]
    #[ORM\JoinColumn(nullable: false)]
    private ?BingoSquare $bingoSquare = null;

    #[ORM\Column]
    private ?DateTimeImmutable $validatedAt;

    public function __construct()
    {
        $this->validatedAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGame(): ?Game
    {
        return $this->game;
    }

    public function setGame(?Game $game): static
    {
        $this->game = $game;

        return $this;
    }

    public function getBingoSquare(): ?BingoSquare
    {
        return $this->bingoSquare;
    }

    public function setBingoSquare(?BingoSquare $bingoSquare): static
    {
        $this->bingoSquare = $bingoSquare;

        return $this;
    }

    public function getValidatedAt(): ?DateTimeImmutable
    {
        return $this->validatedAt;
    }

    public function setValidatedAt(DateTimeImmutable $validatedAt): static
    {
        $this->validatedAt = $validatedAt;

        return $this;
    }
}
