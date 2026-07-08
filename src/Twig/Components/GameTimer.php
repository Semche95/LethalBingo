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
        $this->timerDisplay = $this->gameTimerService->formatGameDuration($this->game);
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
        $this->entityManager->persist($this->game);
        $this->entityManager->flush();

        // Refresh the timer display
        $this->timerDisplay = $this->gameTimerService->formatGameDuration($this->game);
        $this->gameTimerService->refreshGameTimer($this->game);
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
        $this->entityManager->persist($this->game);
        $this->entityManager->flush();

        // Refresh the timer display
        $this->timerDisplay = $this->gameTimerService->formatGameDuration($this->game);
        $this->gameTimerService->refreshGameTimer($this->game);
    }
}
