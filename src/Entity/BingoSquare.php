<?php

namespace App\Entity;

use App\Repository\BingoSquareRepository;
use App\Service\Utils;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Stringable;

#[ORM\Entity(repositoryClass: BingoSquareRepository::class)]
#[ORM\UniqueConstraint(fields: ['round', 'row', 'col'])]
class BingoSquare implements Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private string $label = '';

    #[ORM\Column]
    private int $row;

    #[ORM\Column]
    private int $col;

    #[ORM\Column]
    private bool $visible = false;

    #[ORM\ManyToOne(inversedBy: 'bingoSquares')]
    #[ORM\JoinColumn(nullable: false)]
    private GameRound $round;

    #[ORM\ManyToOne(inversedBy: 'bingoSquares')]
    private ?BingoSquareCategory $category = null;

    /**
     * @var Collection<int, BingoSquareValidated>
     */
    #[ORM\OneToMany(targetEntity: BingoSquareValidated::class, mappedBy: 'bingoSquare', cascade: ['remove'])]
    private Collection $bingoSquareValidated;

    /**
     * @var Collection<int, BingoSquareRevealed>
     */
    #[ORM\OneToMany(targetEntity: BingoSquareRevealed::class, mappedBy: 'bingoSquare', cascade: ['remove'])]
    private Collection $bingoSquareRevealed;

    public function __construct()
    {
        $this->bingoSquareValidated = new ArrayCollection();
        $this->bingoSquareRevealed = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getRow(): int
    {
        return $this->row;
    }

    public function setRow(int $row): static
    {
        $this->row = $row;

        return $this;
    }

    public function getCol(): int
    {
        return $this->col;
    }

    public function setCol(int $col): static
    {
        $this->col = $col;

        return $this;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function setVisible(bool $visible): static
    {
        $this->visible = $visible;

        return $this;
    }

    public function getRound(): GameRound
    {
        return $this->round;
    }

    public function setRound(GameRound $round): static
    {
        $this->round = $round;

        return $this;
    }

    public function getCategory(): ?BingoSquareCategory
    {
        return $this->category;
    }

    public function setCategory(?BingoSquareCategory $category): static
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return Collection<int, BingoSquareValidated>
     */
    public function getBingoSquareValidated(): Collection
    {
        return $this->bingoSquareValidated;
    }

    /**
     * @return Collection<int, BingoSquareRevealed>
     */
    public function getBingoSquareRevealed(): Collection
    {
        return $this->bingoSquareRevealed;
    }

    public static function getRowLetter(int $row): string
    {
        return Utils::num2alpha($row - 1);
    }

    public function getCoords(): string
    {
        if (!isset($this->row, $this->col)) {
            return '';
        }

        return self::getRowLetter($this->row) . $this->col;
    }

    public function __toString(): string
    {
        return $this->label;
    }
}
