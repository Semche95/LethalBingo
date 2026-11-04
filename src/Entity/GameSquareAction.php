<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * Dated trace of an action performed on a square during a game. Unlike the
 * marks, every occurrence is kept, so the course of the game can be replayed.
 */
#[ORM\Entity]
class GameSquareAction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private DateTimeImmutable $occurredAt;

    public function __construct(
        #[ORM\ManyToOne(inversedBy: 'squareActions')]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private Game $game,
        #[ORM\ManyToOne]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private BingoSquare $bingoSquare,
        #[ORM\Column(length: 20, enumType: SquareActionType::class)]
        private SquareActionType $type,
    ) {
        $this->occurredAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGame(): Game
    {
        return $this->game;
    }

    public function getBingoSquare(): BingoSquare
    {
        return $this->bingoSquare;
    }

    public function getType(): SquareActionType
    {
        return $this->type;
    }

    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function setOccurredAt(DateTimeImmutable $occurredAt): static
    {
        $this->occurredAt = $occurredAt;

        return $this;
    }
}
