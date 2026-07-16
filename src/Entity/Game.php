<?php

namespace App\Entity;

use App\Repository\GameRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints as DoctrineAssert;
use Symfony\Component\String\ByteString;

#[ORM\Entity(repositoryClass: GameRepository::class)]
#[ORM\UniqueConstraint(fields: ['slug'])]
#[ORM\UniqueConstraint(fields: ['team', 'round'])]
#[DoctrineAssert\UniqueEntity(fields: ['team', 'round'])]
class Game
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $slug;

    #[ORM\ManyToOne(inversedBy: 'games')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Team $team = null;

    #[ORM\ManyToOne(inversedBy: 'games')]
    #[ORM\JoinColumn(nullable: false)]
    private ?GameRound $round = null;

    /**
     * @var Collection<int, BingoSquareValidated>
     */
    #[ORM\OneToMany(targetEntity: BingoSquareValidated::class, mappedBy: 'game')]
    private Collection $bingoSquareValidated;

    /**
     * @var Collection<int, BingoSquareRevealed>
     */
    #[ORM\OneToMany(targetEntity: BingoSquareRevealed::class, mappedBy: 'game')]
    private Collection $bingoSquareRevealed;

    #[ORM\Column]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $startedAt = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $finishedAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isPaused = false;

    #[ORM\Column(type: 'integer')]
    private int $totalPausedSeconds = 0;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $lastPausedAt = null;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
        $this->generateSlug();
        $this->bingoSquareValidated = new ArrayCollection();
        $this->bingoSquareRevealed = new ArrayCollection();
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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function generateSlug(): static
    {
        $this->slug = (string)ByteString::fromRandom(12);

        return $this;
    }

    public function getTeam(): ?Team
    {
        return $this->team;
    }

    public function setTeam(?Team $team): static
    {
        $this->team = $team;

        return $this;
    }

    public function getRound(): ?GameRound
    {
        return $this->round;
    }

    public function setRound(?GameRound $round): static
    {
        $this->round = $round;

        return $this;
    }

    /**
     * @return Collection<int, BingoSquareValidated>
     */
    public function getBingoSquareValidated(): Collection
    {
        return $this->bingoSquareValidated;
    }

    public function addBingoSquareValidated(BingoSquareValidated $bingoSquareValidated): static
    {
        if (!$this->bingoSquareValidated->contains($bingoSquareValidated)) {
            $this->bingoSquareValidated->add($bingoSquareValidated);
            $bingoSquareValidated->setGame($this);
        }

        return $this;
    }

    public function removeBingoSquareValidated(BingoSquareValidated $bingoSquareValidated): static
    {
        $this->bingoSquareValidated->removeElement($bingoSquareValidated);

        return $this;
    }

    /**
     * @return Collection<int, BingoSquareRevealed>
     */
    public function getBingoSquareRevealed(): Collection
    {
        return $this->bingoSquareRevealed;
    }

    public function addBingoSquareRevealed(BingoSquareRevealed $bingoSquareRevealed): static
    {
        if (!$this->bingoSquareRevealed->contains($bingoSquareRevealed)) {
            $this->bingoSquareRevealed->add($bingoSquareRevealed);
            $bingoSquareRevealed->setGame($this);
        }

        return $this;
    }

    public function removeBingoSquareRevealed(BingoSquareRevealed $bingoSquareRevealed): static
    {
        $this->bingoSquareRevealed->removeElement($bingoSquareRevealed);

        return $this;
    }

    public function getStartedAt(): ?DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(?DateTimeImmutable $startedAt): static
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getFinishedAt(): ?DateTimeImmutable
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(?DateTimeImmutable $finishedAt): static
    {
        $this->finishedAt = $finishedAt;

        return $this;
    }

    public function isReadOnly(): bool
    {
        return $this->startedAt !== null || $this->finishedAt !== null;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    public function isPaused(): bool
    {
        return $this->isPaused;
    }

    public function setIsPaused(bool $isPaused): static
    {
        $this->isPaused = $isPaused;

        return $this;
    }

    public function getTotalPausedSeconds(): int
    {
        return $this->totalPausedSeconds;
    }

    public function setTotalPausedSeconds(int $totalPausedSeconds): static
    {
        $this->totalPausedSeconds = $totalPausedSeconds;

        return $this;
    }

    public function getLastPausedAt(): ?DateTimeImmutable
    {
        return $this->lastPausedAt;
    }

    public function setLastPausedAt(?DateTimeImmutable $lastPausedAt): static
    {
        $this->lastPausedAt = $lastPausedAt;

        return $this;
    }
}
