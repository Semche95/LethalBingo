<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Entity\Game;
use App\Service\GameTimerService;
use App\Service\RefreshEvent;
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

    /** Duration added or removed by the referee, as "min:s" or "min". */
    #[LiveProp(writable: true)]
    public string $adjustDuration = '1:00';

    // Only for the render that follows the rejected entry, not kept afterwards.
    public bool $adjustDurationInvalid = false;

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
        // Nothing left to pause once the time is up.
        if (!$this->game?->isInProgress() || $this->game->isPaused() || $this->gameTimerService->isTimeUp($this->game)) {
            return;
        }

        $this->gameTimerService->pause($this->game);
        $this->persistGameChangesAndNotifyClients();
    }

    #[LiveAction]
    #[IsGranted('ROLE_REFEREE')]
    public function resumeGame(): void
    {
        if (!$this->game?->isInProgress() || !$this->game->isPaused()) {
            return;
        }

        $this->gameTimerService->resume($this->game);
        $this->persistGameChangesAndNotifyClients();
    }

    #[LiveAction]
    #[IsGranted('ROLE_REFEREE')]
    public function increaseTimer(): void
    {
        $this->adjustTimer(1);
    }

    #[LiveAction]
    #[IsGranted('ROLE_REFEREE')]
    public function decreaseTimer(): void
    {
        $this->adjustTimer(-1);
    }

    /**
     * Adds (sign 1) or removes (sign -1) the duration entered by the referee.
     */
    private function adjustTimer(int $sign): void
    {
        $seconds = self::parseDuration($this->adjustDuration);
        $this->adjustDurationInvalid = $seconds === null;

        if ($seconds === null || !$this->game?->isInProgress()) {
            return;
        }

        $this->gameTimerService->addSeconds($this->game, $sign * $seconds);
        $this->persistGameChangesAndNotifyClients();
    }

    /**
     * Seconds of a "min:s" (seconds on two digits) or "min" duration; null when
     * the entry is malformed or zero.
     */
    private static function parseDuration(string $duration): ?int
    {
        if (preg_match('/^(\d{1,3})(?::([0-5]\d))?$/', trim($duration), $matches) !== 1) {
            return null;
        }

        $seconds = (int) $matches[1] * 60 + (int) ($matches[2] ?? 0);

        return $seconds > 0 ? $seconds : null;
    }

    private function setTimerToSpecificValue(int $targetRemainingSeconds): void
    {
        if (!$this->game?->isInProgress()) {
            return;
        }

        $this->gameTimerService->setRemainingSeconds($this->game, $targetRemainingSeconds);
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
