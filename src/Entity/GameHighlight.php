<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * Moment marked by the referee during a game, to find it again when editing
 * the final video. The elapsed time is counted from the start of the game and
 * includes the pauses and the time spent at zero: the recording goes on.
 */
#[ORM\Entity]
class GameHighlight
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $elapsedSeconds;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $note = null;

    public function __construct(
        #[ORM\ManyToOne(inversedBy: 'highlights')]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private Game $game,
        #[ORM\Column]
        private DateTimeImmutable $markedAt = new DateTimeImmutable(),
    ) {
        // Both dates are stored to the second, so the difference is taken on whole seconds.
        $this->elapsedSeconds = max(0, $markedAt->getTimestamp() - ($game->getStartedAt() ?? $markedAt)->getTimestamp());
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGame(): Game
    {
        return $this->game;
    }

    public function getMarkedAt(): DateTimeImmutable
    {
        return $this->markedAt;
    }

    public function getElapsedSeconds(): int
    {
        return $this->elapsedSeconds;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;

        return $this;
    }
}
