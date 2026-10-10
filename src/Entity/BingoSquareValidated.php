<?php

namespace App\Entity;

use App\Repository\BingoSquareValidatedRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BingoSquareValidatedRepository::class)]
#[ORM\UniqueConstraint(fields: ['game', 'bingoSquare'])]
#[ORM\AssociationOverrides([
    new ORM\AssociationOverride(name: 'game', inversedBy: 'bingoSquareValidated'),
    new ORM\AssociationOverride(name: 'bingoSquare', inversedBy: 'bingoSquareValidated'),
])]
class BingoSquareValidated extends GameSquareMark
{
    #[ORM\Column]
    private DateTimeImmutable $validatedAt;

    public function __construct()
    {
        $this->validatedAt = new DateTimeImmutable();
    }

    public function getValidatedAt(): DateTimeImmutable
    {
        return $this->validatedAt;
    }

    public function setValidatedAt(DateTimeImmutable $validatedAt): static
    {
        $this->validatedAt = $validatedAt;

        return $this;
    }
}
