<?php

namespace App\Service;

use App\Entity\Game;
use DateTimeImmutable;

readonly class GameTimerService
{
    public function __construct(
        private GameService $gameService,
    )
    {
    }

    public function refreshGameTimer(Game $game): void
    {
        $this->gameService->refreshGameFrame($game, RefreshEvent::GameTimer);
    }

    public function formatGameDuration(Game $game): string
    {
        if (!$game->getStartedAt()) {
            return '01:00:00';
        } elseif ($game->getFinishedAt()) {
            return '00:00:00';
        }

        // Calculate elapsed seconds since game started
        $elapsedSeconds = new DateTimeImmutable()->getTimestamp() - $game->getStartedAt()->getTimestamp();

        // Subtract total paused time
        $elapsedSeconds -= $game->getTotalPausedSeconds();

        // If the game is currently paused, subtract the time since it was paused
        if ($game->isPaused() && $game->getLastPausedAt()) {
            $pausedSeconds = new DateTimeImmutable()->getTimestamp() - $game->getLastPausedAt()->getTimestamp();
            $elapsedSeconds -= $pausedSeconds;
        }

        // Calculate remaining seconds (don't go below zero)
        $remainingSeconds = max(0, 3600 - $elapsedSeconds);

        // If time is up, return 00:00:00
        if ($remainingSeconds <= 0) {
            return '00:00:00';
        }

        // Convert remaining seconds to hours, minutes, seconds
        $hours = floor($remainingSeconds / 3600);
        $minutes = floor(($remainingSeconds % 3600) / 60);
        $seconds = $remainingSeconds % 60;

        return sprintf(
            '%02d:%02d:%02d',
            $hours,
            $minutes,
            $seconds
        );
    }
}
