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

    /**
     * Checks if the remaining time is at a 10-minute interval (50, 40, 30, 20, 10 minutes)
     */
    public function isAtTenMinuteInterval(int $remainingSeconds): bool
    {
        // Convert to minutes
        $remainingMinutes = (int)floor($remainingSeconds / 60);
        $remainingSecondsInMinute = $remainingSeconds % 60;

        // Check if it's exactly at a 10-minute mark (50, 40, 30, 20, 10 minutes)
        // Require exactly 0 seconds to ensure precise timing
        return $remainingMinutes > 0 && $remainingMinutes <= 50 && $remainingMinutes % 10 === 0 && $remainingSecondsInMinute === 0;
    }

    /**
     * Checks if the remaining time is at 5 minutes (00:05:00)
     */
    public function isAtFiveMinutesExactly(int $remainingSeconds): bool
    {
        // Convert to minutes
        $remainingMinutes = (int)floor($remainingSeconds / 60);
        $remainingSecondsInMinute = $remainingSeconds % 60;

        // Check if it's exactly at 5 minutes (00:05:00)
        return $remainingMinutes === 5 && $remainingSecondsInMinute === 0;
    }

    /**
     * Checks if the remaining time is at 3 minutes (00:03:00)
     */
    public function isAtThreeMinutesExactly(int $remainingSeconds): bool
    {
        // Convert to minutes
        $remainingMinutes = (int)floor($remainingSeconds / 60);
        $remainingSecondsInMinute = $remainingSeconds % 60;

        // Check if it's exactly at 3 minutes (00:03:00)
        return $remainingMinutes === 3 && $remainingSecondsInMinute === 0;
    }

    /**
     * Checks if the remaining time is exactly 1 minute (00:01:00) or less
     */
    public function isOneMinuteOrLess(int $remainingSeconds): bool
    {
        // Convert to minutes
        $remainingMinutes = (int)floor($remainingSeconds / 60);
        $remainingSecondsInMinute = $remainingSeconds % 60;

        // Check if it's exactly 1 minute (00:01:00) or less
        return ($remainingMinutes === 1 && $remainingSecondsInMinute === 0) || $remainingMinutes === 0;
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
