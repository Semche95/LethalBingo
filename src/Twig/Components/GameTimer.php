<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Entity\Game;
use App\Service\GameTimerService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;

#[AsLiveComponent]
final class GameTimer extends AbstractController
{
    use ComponentToolsTrait;

    #[LiveProp]
    public ?Game $game = null;

    #[LiveProp]
    public string $timerDisplay = '';

    public function __construct(
        private readonly GameTimerService       $gameTimerService,
        private readonly EntityManagerInterface $entityManager,
    )
    {
    }

    #[LiveListener('gameFrameRefresh')]
    #[LiveListener('gameTimerRefresh')]
    public function __invoke(): void
    {
        $this->timerDisplay = $this->gameTimerService->formatGameDuration($this->game);
    }

    /**
     * Persists game changes and notifies clients of the update
     */
    private function persistGameChangesAndNotifyClients(): void
    {
        $this->entityManager->persist($this->game);
        $this->entityManager->flush();

        // Notify clients of the game update
        $this->gameTimerService->refreshGameTimer($this->game);
    }

    #[LiveAction]
    #[IsGranted('ROLE_REFEREE')]
    public function pauseGame(): void
    {
        if (!$this->game || !$this->game->getStartedAt() || $this->game->getFinishedAt() || $this->game->isPaused()) {
            return;
        }

        $this->game
            ->setIsPaused(true)
            ->setLastPausedAt(new DateTimeImmutable());

        $this->persistGameChangesAndNotifyClients();
    }

    #[LiveAction]
    #[IsGranted('ROLE_REFEREE')]
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

        $this->persistGameChangesAndNotifyClients();
    }

    #[LiveAction]
    #[IsGranted('ROLE_REFEREE')]
    public function increaseTimer(): void
    {
        if (!$this->game || !$this->game->getStartedAt() || $this->game->getFinishedAt()) {
            return;
        }

        // Add 1 minute (60 seconds) by adjusting the start time
        $startedAt = $this->game->getStartedAt();
        $newTimestamp = $startedAt->getTimestamp() + 60;
        $newStartedAt = new DateTimeImmutable()->setTimestamp($newTimestamp);

        $this->game->setStartedAt($newStartedAt);

        $this->persistGameChangesAndNotifyClients();
    }

    #[LiveAction]
    #[IsGranted('ROLE_REFEREE')]
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
        $newStartedAt = new DateTimeImmutable()->setTimestamp($newTimestamp);

        $this->game->setStartedAt($newStartedAt);

        $this->persistGameChangesAndNotifyClients();
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

        $newStartedAt = new DateTimeImmutable()->setTimestamp($newTimestamp);
        $this->game->setStartedAt($newStartedAt);

        $this->persistGameChangesAndNotifyClients();
    }

    #[LiveAction]
    #[IsGranted('ROLE_ADMIN')]
    public function setTimerTo2003(): void
    {
        // 00:20:03 = 20 minutes and 3 seconds = 1203 seconds
        $this->setTimerToSpecificValue(1203);
    }

    #[LiveAction]
    #[IsGranted('ROLE_ADMIN')]
    public function setTimerTo1003(): void
    {
        // 00:10:03 = 10 minutes and 3 seconds = 603 seconds
        $this->setTimerToSpecificValue(603);
    }

    #[LiveAction]
    #[IsGranted('ROLE_ADMIN')]
    public function setTimerTo0503(): void
    {
        // 00:05:03 = 5 minutes and 3 seconds = 303 seconds
        $this->setTimerToSpecificValue(303);
    }

    #[LiveAction]
    #[IsGranted('ROLE_ADMIN')]
    public function setTimerTo0303(): void
    {
        // 00:03:03 = 3 minutes and 3 seconds = 183 seconds
        $this->setTimerToSpecificValue(183);
    }

    #[LiveAction]
    #[IsGranted('ROLE_ADMIN')]
    public function setTimerTo0103(): void
    {
        // 00:01:03 = 1 minute and 3 seconds = 63 seconds
        $this->setTimerToSpecificValue(63);
    }

    #[LiveAction]
    #[IsGranted('ROLE_ADMIN')]
    public function setTimerTo0003(): void
    {
        // 00:00:03 = 3 seconds
        $this->setTimerToSpecificValue(3);
    }
}
