<?php

namespace App\Entity;

use App\Repository\BingoSquareCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Stringable;

#[ORM\Entity(repositoryClass: BingoSquareCategoryRepository::class)]
#[ORM\UniqueConstraint(fields: ['label'])]
class BingoSquareCategory implements Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $label = null;

    #[ORM\Column(options: ['default' => 0])]
    private ?int $position = null;

    /**
     * @var Collection<int, BingoSquare>
     */
    #[ORM\OneToMany(targetEntity: BingoSquare::class, mappedBy: 'category')]
    private Collection $bingoSquares;

    public function __construct()
    {
        $this->bingoSquares = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

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
            $bingoSquare->setCategory($this);
        }

        return $this;
    }

    public function removeBingoSquare(BingoSquare $bingoSquare): static
    {
        $this->bingoSquares->removeElement($bingoSquare);

        return $this;
    }

    public function getChildSquares(GameRound $gameRound): Collection
    {
        $criteria = Criteria::create()
            ->andWhere(Criteria::expr()->eq('round', $gameRound))
            ->orderBy(['row' => 'ASC', 'col' => 'ASC']);

        return $this->bingoSquares->matching($criteria);
    }

    public function __toString(): string
    {
        return $this->label ?? '';
    }
}
