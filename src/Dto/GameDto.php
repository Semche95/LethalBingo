<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Game;
use App\Entity\GameRound;
use App\Entity\Team;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * A game as edited in the referee form: team and round are null until chosen.
 */
class GameDto
{
    public const int MAX_COUNTDOWN_HOURS = 99;

    public const int MAX_COUNTDOWN_MINUTES = 59;

    // Usual countdown durations offered in the form, as [hours, minutes].
    public const array COUNTDOWN_PRESETS = [[0, 30], [1, 0], [1, 30], [2, 0], [2, 30], [3, 0]];

    public function __construct(
        #[Assert\NotNull(message: 'Veuillez sélectionner une équipe.')]
        public ?Team $team = null,
        #[Assert\NotNull(message: 'Veuillez sélectionner un bingo.')]
        public ?GameRound $round = null,
        // Unchecked: no time limit, the timer counts the elapsed time.
        public bool $hasCountdown = true,
        // Countdown duration, ignored without countdown; an empty field counts as 0.
        // Default: Game::DEFAULT_COUNTDOWN_DURATION_SECONDS, in hours and minutes.
        public ?int $countdownHours = (Game::DEFAULT_COUNTDOWN_DURATION_SECONDS - Game::DEFAULT_COUNTDOWN_DURATION_SECONDS % 3600) / 3600,
        public ?int $countdownMinutes = Game::DEFAULT_COUNTDOWN_DURATION_SECONDS % 3600 / 60,
    ) {
    }

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context, mixed $payload): void
    {
        if ($this->round !== null && !$this->round->isComplete()) {
            $context->buildViolation('Le bingo sélectionné est incomplet et ne peut pas être utilisé pour une partie.')
                ->atPath('round')
                ->addViolation();
        }

        if (!$this->hasCountdown) {
            return;
        }

        $hours = $this->countdownHours ?? 0;
        $minutes = $this->countdownMinutes ?? 0;

        if ($hours < 0 || $hours > self::MAX_COUNTDOWN_HOURS) {
            $context->buildViolation(\sprintf('Les heures vont de 0 à %d.', self::MAX_COUNTDOWN_HOURS))
                ->atPath('countdownHours')
                ->addViolation();
        }

        if ($minutes < 0 || $minutes > self::MAX_COUNTDOWN_MINUTES) {
            $context->buildViolation(\sprintf('Les minutes vont de 0 à %d.', self::MAX_COUNTDOWN_MINUTES))
                ->atPath('countdownMinutes')
                ->addViolation();
        }

        if ($hours === 0 && $minutes === 0) {
            $context->buildViolation('La durée doit être supérieure à zéro.')
                ->atPath('countdownHours')
                ->addViolation();
        }
    }

    public function getCountdownDurationSeconds(): int
    {
        return (($this->countdownHours ?? 0) * 60 + ($this->countdownMinutes ?? 0)) * 60;
    }

    public function setCountdownDurationSeconds(int $seconds): static
    {
        $this->countdownHours = intdiv($seconds, 3600);
        $this->countdownMinutes = intdiv($seconds, 60) % 60;

        return $this;
    }
}
