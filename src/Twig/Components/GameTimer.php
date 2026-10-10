<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Entity\Game;
use App\Service\GameTimerService;
use App\Service\RefreshEvent;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
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
    public float $serverTime = 0.0;

    public function __construct(
        private readonly GameTimerService $gameTimerService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function mount(): void
    {
        // The first render has no Mercure message yet. Every later refresh
        // receives the shared timestamp carried by that message.
        if ($this->serverTime === 0.0) {
            $this->serverTime = microtime(true);
        }
    }

    #[LiveListener(RefreshEvent::GameFrame->value)]
    #[LiveListener(RefreshEvent::GameTimer->value)]
    public function __invoke(#[LiveArg] ?float $serverTime = null): void
    {
        // Time display is owned exclusively by the client (rAF loop). The
        // timestamp comes from Mercure so every client receives the same anchor.
        $this->serverTime = $serverTime ?? microtime(true);
    }

    /**
     * Persists game changes and notifies clients of the update.
     */
    private function persistGameChangesAndNotifyClients(): void
    {
        $this->entityManager->persist($this->game);
        $this->entityManager->flush();

        // Notify clients of the game update
        $this->serverTime = $this->gameTimerService->refreshGameTimer($this->game);
    }

    #[LiveAction]
    #[IsGranted('ROLE_REFEREE')]
    public function pauseGame(): void
    {
        if (!$this->game?->isInProgress() || $this->game->isPaused()) {
            return;
        }

        $this->gameTimerService->absorbDeficit($this->game);

        $this->game
            ->setIsPaused(true)
            ->setLastPausedAt(new DateTimeImmutable());

        $this->persistGameChangesAndNotifyClients();
    }

    #[LiveAction]
    #[IsGranted('ROLE_REFEREE')]
    public function resumeGame(): void
    {
        if (!$this->game?->isInProgress() || !$this->game->isPaused()) {
            return;
        }

        $this->gameTimerService->absorbDeficit($this->game);
        $this->gameTimerService->closeCurrentPause($this->game);

        $this->persistGameChangesAndNotifyClients();
    }

    #[LiveAction]
    #[IsGranted('ROLE_REFEREE')]
    public function increaseTimer(): void
    {
        if (!$this->game?->isInProgress()) {
            return;
        }

        $this->gameTimerService->absorbDeficit($this->game);

        // Add 1 minute (60 seconds) by incrementing the cumulative manual
        // adjustment counter. startedAt is never touched.
        $this->game->setManualAdjustmentSeconds($this->game->getManualAdjustmentSeconds() + 60);

        $this->persistGameChangesAndNotifyClients();
    }

    #[LiveAction]
    #[IsGranted('ROLE_REFEREE')]
    public function decreaseTimer(): void
    {
        if (!$this->game?->isInProgress()) {
            return;
        }

        $this->gameTimerService->absorbDeficit($this->game);

        // Remove 1 minute (60 seconds) by decrementing the cumulative manual
        // adjustment counter. startedAt is never touched. A negative counter
        // reduces the remaining time; a fresh deficit is absorbed first.
        $this->game->setManualAdjustmentSeconds($this->game->getManualAdjustmentSeconds() - 60);

        $this->persistGameChangesAndNotifyClients();
    }

    /**
     * Sets the timer to a specific value by adjusting the cumulative manual
     * adjustment counter (never by touching startedAt).
     */
    private function setTimerToSpecificValue(int $targetRemainingSeconds): void
    {
        if (!$this->game?->isInProgress()) {
            return;
        }

        $this->gameTimerService->absorbDeficit($this->game);

        $currentRemainingSeconds = $this->gameTimerService->getRemainingSeconds($this->game);
        $newAdjustment = $this->game->getManualAdjustmentSeconds() + ($targetRemainingSeconds - $currentRemainingSeconds);

        $this->game->setManualAdjustmentSeconds($newAdjustment);

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
