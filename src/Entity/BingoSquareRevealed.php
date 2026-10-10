<?php

namespace App\Entity;

use App\Repository\BingoSquareRevealedRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BingoSquareRevealedRepository::class)]
#[ORM\UniqueConstraint(fields: ['game', 'bingoSquare'])]
#[ORM\AssociationOverrides([
    new ORM\AssociationOverride(name: 'game', inversedBy: 'bingoSquareRevealed'),
    new ORM\AssociationOverride(name: 'bingoSquare', inversedBy: 'bingoSquareRevealed'),
])]
class BingoSquareRevealed extends GameSquareMark
{
    #[ORM\Column]
    private DateTimeImmutable $revealedAt;

    public function __construct()
    {
        $this->revealedAt = new DateTimeImmutable();
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
