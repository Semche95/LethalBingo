<?php

namespace App\Entity;

use App\Doctrine\DateTimeMicroImmutableType;
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
    public const int DEFAULT_COUNTDOWN_DURATION_SECONDS = 5400;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $slug;

    #[ORM\ManyToOne(inversedBy: 'games')]
    #[ORM\JoinColumn(nullable: false)]
    private Team $team;

    #[ORM\ManyToOne(inversedBy: 'games')]
    #[ORM\JoinColumn(nullable: false)]
    private GameRound $round;

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

    /**
     * @var Collection<int, GameSquareAction>
     */
    #[ORM\OneToMany(targetEntity: GameSquareAction::class, mappedBy: 'game')]
    #[ORM\OrderBy(['occurredAt' => 'ASC', 'id' => 'ASC'])]
    private Collection $squareActions;

    /**
     * @var Collection<int, GameHighlight>
     */
    #[ORM\OneToMany(targetEntity: GameHighlight::class, mappedBy: 'game')]
    #[ORM\OrderBy(['markedAt' => 'DESC', 'id' => 'DESC'])]
    private Collection $highlights;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $startedAt = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $finishedAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isPaused = false;

    // Moment the countdown reaches zero while the game runs, null during a
    // pause; kept to the microsecond (docs/TIMER.md section 2).
    #[ORM\Column(type: DateTimeMicroImmutableType::NAME, nullable: true)]
    private ?DateTimeImmutable $countdownZeroAt = null;

    // Remaining time frozen during a pause.
    #[ORM\Column(type: 'float', options: ['default' => 0])]
    private float $pausedRemainingSeconds = 0.0;

    #[ORM\Column(length: 20, enumType: TimerMode::class, options: ['default' => TimerMode::Countdown->value])]
    private TimerMode $timerMode = TimerMode::Countdown;

    // Countdown only: time given when the game starts.
    #[ORM\Column(options: ['default' => self::DEFAULT_COUNTDOWN_DURATION_SECONDS])]
    private int $countdownDurationSeconds = self::DEFAULT_COUNTDOWN_DURATION_SECONDS;

    // Stopwatch only: start of the current pause, null outside a pause.
    #[ORM\Column(type: DateTimeMicroImmutableType::NAME, nullable: true)]
    private ?DateTimeImmutable $pausedAt = null;

    // Stopwatch only: total of the finished pauses.
    #[ORM\Column(type: 'float', options: ['default' => 0])]
    private float $pausedDurationSeconds = 0.0;

    public function __construct()
    {
        $this->generateSlug();
        $this->bingoSquareValidated = new ArrayCollection();
        $this->bingoSquareRevealed = new ArrayCollection();
        $this->squareActions = new ArrayCollection();
        $this->highlights = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSlug(): string
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
        $this->slug = (string) ByteString::fromRandom(12);

        return $this;
    }

    public function getTeam(): Team
    {
        return $this->team;
    }

    public function setTeam(Team $team): static
    {
        $this->team = $team;

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

    /**
     * @return Collection<int, GameSquareAction>
     */
    public function getSquareActions(): Collection
    {
        return $this->squareActions;
    }

    /**
     * @return Collection<int, GameHighlight>
     */
    public function getHighlights(): Collection
    {
        return $this->highlights;
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

    // Puts the timer back to a fresh state, started at $startedAt (or not started).
    public function resetTimer(?DateTimeImmutable $startedAt, ?DateTimeImmutable $countdownZeroAt = null): static
    {
        return $this
            ->setStartedAt($startedAt)
            ->setFinishedAt(null)
            ->setIsPaused(false)
            ->setCountdownZeroAt($countdownZeroAt)
            ->setPausedRemainingSeconds(0)
            ->setPausedAt(null)
            ->setPausedDurationSeconds(0);
    }

    public function isStarted(): bool
    {
        return $this->startedAt !== null;
    }

    public function isFinished(): bool
    {
        return $this->finishedAt !== null;
    }

    public function isReadOnly(): bool
    {
        return $this->isStarted() || $this->isFinished();
    }

    public function isInProgress(): bool
    {
        return $this->isStarted() && !$this->isFinished();
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

    public function getCountdownZeroAt(): ?DateTimeImmutable
    {
        return $this->countdownZeroAt;
    }

    public function setCountdownZeroAt(?DateTimeImmutable $countdownZeroAt): static
    {
        $this->countdownZeroAt = $countdownZeroAt;

        return $this;
    }

    public function getPausedRemainingSeconds(): float
    {
        return $this->pausedRemainingSeconds;
    }

    public function setPausedRemainingSeconds(float $pausedRemainingSeconds): static
    {
        $this->pausedRemainingSeconds = $pausedRemainingSeconds;

        return $this;
    }

    public function getTimerMode(): TimerMode
    {
        return $this->timerMode;
    }

    public function setTimerMode(TimerMode $timerMode): static
    {
        $this->timerMode = $timerMode;

        return $this;
    }

    public function isStopwatch(): bool
    {
        return $this->timerMode === TimerMode::Stopwatch;
    }

    public function getCountdownDurationSeconds(): int
    {
        return $this->countdownDurationSeconds;
    }

    public function setCountdownDurationSeconds(int $countdownDurationSeconds): static
    {
        $this->countdownDurationSeconds = $countdownDurationSeconds;

        return $this;
    }

    public function getPausedAt(): ?DateTimeImmutable
    {
        return $this->pausedAt;
    }

    public function setPausedAt(?DateTimeImmutable $pausedAt): static
    {
        $this->pausedAt = $pausedAt;

        return $this;
    }

    public function getPausedDurationSeconds(): float
    {
        return $this->pausedDurationSeconds;
    }

    public function setPausedDurationSeconds(float $pausedDurationSeconds): static
    {
        $this->pausedDurationSeconds = $pausedDurationSeconds;

        return $this;
    }
}
