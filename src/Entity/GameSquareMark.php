<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A square marked in a game (revealed, validated...): one mark per square and game.
 */
#[ORM\MappedSuperclass]
abstract class GameSquareMark
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // The inverse sides on Game and BingoSquare are set by each subclass through AssociationOverrides.
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Game $game;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private BingoSquare $bingoSquare;

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
}
