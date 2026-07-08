<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Entity\Game;
use App\Service\GameTimerService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;

#[AsLiveComponent(defaultAction: 'refreshTimer')]
final class GameTimer extends AbstractController
{
    use ComponentToolsTrait;

    #[LiveProp]
    public ?Game $game = null;

    #[LiveProp]
    public string $timerDisplay = '';

    #[LiveProp]
    public bool $showControls = false;

    #[LiveProp]
    public int $autoRefreshInterval = 1000; // 1 second

    #[LiveProp]
    public bool $isHighlighted = false;

    #[LiveProp]
    public ?int $highlightUntil = null;

    #[LiveProp]
    public string $highlightType = 'warning'; // 'warning' for 10-minute intervals, 'danger' for 5, 3, and 1-minute thresholds

    #[LiveProp]
    public bool $cancelSounds = false;

    #[LiveProp]
    public bool $timerReachedZero = false;

    public function __construct(
        private readonly GameTimerService       $gameTimerService,
        private readonly EntityManagerInterface $entityManager,
    )
    {
    }

    #[LiveAction]
    #[LiveListener('gameFrameRefresh')]
    #[LiveListener('gameTimerRefresh')]
    public function refreshTimer(): void
    {
        // Reset cancelSounds flag after it's been used
        if ($this->cancelSounds) {
            $this->cancelSounds = false;
        }

        $this->timerDisplay = $this->gameTimerService->formatGameDuration($this->game);

        // Check if timer has reached zero
        $this->timerReachedZero = $this->timerDisplay === '00:00:00';

        // Check if the game is active
        if ($this->game && $this->game->getStartedAt() && !$this->game->getFinishedAt() && !$this->game->isPaused()) {
            // Calculate remaining seconds
            $now = new DateTimeImmutable();
            $elapsedSeconds = $now->getTimestamp() - $this->game->getStartedAt()->getTimestamp();
            $elapsedSeconds -= $this->game->getTotalPausedSeconds();
            $remainingSeconds = max(0, 3600 - $elapsedSeconds);

            // Check if we're in the last minute (red text for the entire last minute)
            if ($this->gameTimerService->isOneMinuteOrLess($remainingSeconds)) {
                $this->isHighlighted = true;
                $this->highlightType = 'danger';
                $this->highlightUntil = null; // No expiration for the last minute
            }
            // Check if we're at 3 minutes exactly
            elseif ($this->gameTimerService->isAtThreeMinutesExactly($remainingSeconds)) {
                // Set highlight for 3 seconds
                $this->isHighlighted = true;
                $this->highlightType = 'danger';
                $this->highlightUntil = $now->getTimestamp() + 3;
            }
            // Check if we're at 5 minutes exactly
            elseif ($this->gameTimerService->isAtFiveMinutesExactly($remainingSeconds)) {
                // Set highlight for 3 seconds
                $this->isHighlighted = true;
                $this->highlightType = 'danger';
                $this->highlightUntil = $now->getTimestamp() + 3;
            }
            // Check if we're at a 10-minute interval
            elseif ($this->gameTimerService->isAtTenMinuteInterval($remainingSeconds)) {
                // Set highlight for 3 seconds
                $this->isHighlighted = true;
                $this->highlightType = 'warning';
                $this->highlightUntil = $now->getTimestamp() + 3;
            }
            // Remove highlight if time has passed (except for the last minute)
            elseif ($this->isHighlighted && $this->highlightUntil && $now->getTimestamp() > $this->highlightUntil) {
                $this->isHighlighted = false;
                $this->highlightUntil = null;
                $this->highlightType = 'warning'; // Reset to default
            }
        }
    }

    /**
     * Persists game changes and refreshes the timer display
     */
    private function persistGameChangesAndRefreshTimer(): void
    {
        $this->entityManager->persist($this->game);
        $this->entityManager->flush();

        // Refresh the timer display
        $this->timerDisplay = $this->gameTimerService->formatGameDuration($this->game);
        $this->gameTimerService->refreshGameTimer($this->game);
    }

    #[LiveAction]
    public function pauseGame(): void
    {
        if (!$this->game || !$this->game->getStartedAt() || $this->game->getFinishedAt() || $this->game->isPaused()) {
            return;
        }

        $this->game
            ->setIsPaused(true)
            ->setLastPausedAt(new DateTimeImmutable());

        $this->persistGameChangesAndRefreshTimer();
    }

    #[LiveAction]
    public function resumeGame(): void
    {
        if (!$this->game || !$this->game->getStartedAt() || $this->game->getFinishedAt() || !$this->game->isPaused()) {
            return;
        }

        // Calculate additional paused time
        if ($this->game->getLastPausedAt()) {
            $additionalPausedSeconds = new DateTimeImmutable()->getTimestamp() - $this->game->getLastPausedAt()->getTimestamp();
            $this->game->setTotalPausedSeconds($this->game->getTotalPausedSeconds() + $additionalPausedSeconds);
        }

        $this->game
            ->setIsPaused(false)
            ->setLastPausedAt(null);

        $this->persistGameChangesAndRefreshTimer();
    }

    #[LiveAction]
    public function increaseTimer(): void
    {
        if (!$this->game || !$this->game->getStartedAt() || $this->game->getFinishedAt()) {
            return;
        }

        // Add 1 minute (60 seconds) by adjusting the start time
        $startedAt = $this->game->getStartedAt();
        $newTimestamp = $startedAt->getTimestamp() + 60;
        $newStartedAt = new DateTimeImmutable()->setTimestamp($newTimestamp);

        // Reset color states and cancel sounds when manually changing the timer
        $this->isHighlighted = false;
        $this->highlightUntil = null;
        $this->highlightType = 'warning';
        $this->cancelSounds = true;

        $this->game->setStartedAt($newStartedAt);

        $this->persistGameChangesAndRefreshTimer();
    }

    #[LiveAction]
    public function decreaseTimer(): void
    {
        if (!$this->game || !$this->game->getStartedAt() || $this->game->getFinishedAt()) {
            return;
        }

        // Calculate current remaining time
        $now = new DateTimeImmutable();
        $elapsedSeconds = $now->getTimestamp() - $this->game->getStartedAt()->getTimestamp();
        $elapsedSeconds -= $this->game->getTotalPausedSeconds();

        // If the game is currently paused, subtract the time since it was paused
        if ($this->game->isPaused() && $this->game->getLastPausedAt()) {
            $pausedSeconds = $now->getTimestamp() - $this->game->getLastPausedAt()->getTimestamp();
            $elapsedSeconds -= $pausedSeconds;
        }

        $remainingSeconds = 3600 - $elapsedSeconds;

        // Calculate how much time we can safely decrease
        $decreaseAmount = min(60, $remainingSeconds);

        // If no time to decrease, return early
        if ($decreaseAmount <= 0) {
            return;
        }

        // Remove the calculated amount of seconds by adjusting the start time
        $startedAt = $this->game->getStartedAt();
        $newTimestamp = $startedAt->getTimestamp() - $decreaseAmount;
        // Reset color states and cancel sounds when manually changing the timer
        $this->isHighlighted = false;
        $this->highlightUntil = null;
        $this->highlightType = 'warning';
        $this->cancelSounds = true;

        $newStartedAt = new DateTimeImmutable()->setTimestamp($newTimestamp);

        $this->game->setStartedAt($newStartedAt);

        $this->persistGameChangesAndRefreshTimer();
    }

    /**
     * Sets the timer to a specific value by adjusting the game's start time
     */
    private function setTimerToSpecificValue(int $targetRemainingSeconds): void
    {
        if (!$this->game || !$this->game->getStartedAt() || $this->game->getFinishedAt()) {
            return;
        }

        // Calculate the new startedAt timestamp
        $now = new DateTimeImmutable();
        $targetElapsedSeconds = 3600 - $targetRemainingSeconds;
        $newTimestamp = $now->getTimestamp() - $targetElapsedSeconds;

        // Adjust for paused time
        if ($this->game->isPaused() && $this->game->getLastPausedAt()) {
            // If the game is paused, we need to account for both the total paused seconds
            // and the current pause duration
            $currentPauseDuration = $now->getTimestamp() - $this->game->getLastPausedAt()->getTimestamp();
            $newTimestamp = $newTimestamp - $this->game->getTotalPausedSeconds() - $currentPauseDuration;
        } else {
            // If the game is not paused, we only need to account for the total paused seconds
            $newTimestamp = $newTimestamp - $this->game->getTotalPausedSeconds();
        }

        // Reset color states and cancel sounds when manually changing the timer
        $this->isHighlighted = false;
        $this->highlightUntil = null;
        $this->highlightType = 'warning';
        $this->cancelSounds = true;

        $newStartedAt = new DateTimeImmutable()->setTimestamp($newTimestamp);
        $this->game->setStartedAt($newStartedAt);

        $this->persistGameChangesAndRefreshTimer();
    }

    #[LiveAction]
    public function setTimerTo2003(): void
    {
        // 00:20:03 = 20 minutes and 3 seconds = 1203 seconds
        $this->setTimerToSpecificValue(1203);
    }

    #[LiveAction]
    public function setTimerTo1003(): void
    {
        // 00:10:03 = 10 minutes and 3 seconds = 603 seconds
        $this->setTimerToSpecificValue(603);
    }

    #[LiveAction]
    public function setTimerTo0503(): void
    {
        // 00:05:03 = 5 minutes and 3 seconds = 303 seconds
        $this->setTimerToSpecificValue(303);
    }

    #[LiveAction]
    public function setTimerTo0303(): void
    {
        // 00:03:03 = 3 minutes and 3 seconds = 183 seconds
        $this->setTimerToSpecificValue(183);
    }

    #[LiveAction]
    public function setTimerTo0103(): void
    {
        // 00:01:03 = 1 minute and 3 seconds = 63 seconds
        $this->setTimerToSpecificValue(63);
    }

    #[LiveAction]
    public function setTimerTo0003(): void
    {
        // 00:00:03 = 3 seconds
        $this->setTimerToSpecificValue(3);
    }
}
