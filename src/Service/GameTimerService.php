<?php

namespace App\Service;

use App\Entity\Game;
use DateTimeImmutable;

readonly class GameTimerService
{
    public const DEFAULT_DURATION_SECONDS = 3600;

    public function __construct(
        private GameService $gameService,
    )
    {
    }

    public function refreshGameTimer(Game $game): float
    {
        return $this->gameService->refreshGameFrame($game, RefreshEvent::GameTimer);
    }

    /**
     * Time authority for the countdown, mirrored by calculateRemainingSeconds()
     * in game_timer_math.ts. $nowMs defaults to the real clock.
     */
    public function getRemainingSeconds(Game $game, ?float $nowMs = null): int
    {
        if (!$game->getStartedAt()) {
            return self::DEFAULT_DURATION_SECONDS;
        }

        if ($game->getFinishedAt()) {
            return 0;
        }

        $nowMs = $nowMs ?? microtime(true) * 1000;

        return max(0, (int)ceil($this->computeRawRemainingSeconds($game, $nowMs)));
    }

    /**
     * Folds any overtime deficit into totalPausedSeconds so the next mutation
     * applies on a remaining time of exactly zero (docs/TIMER.md section 3).
     * Must be called first by every action that changes the remaining time.
     */
    public function absorbDeficit(Game $game, ?float $nowMs = null): void
    {
        if (!$game->getStartedAt() || $game->getFinishedAt()) {
            return;
        }

        $nowMs = $nowMs ?? microtime(true) * 1000;
        $rawRemaining = $this->computeRawRemainingSeconds($game, $nowMs);

        if ($rawRemaining <= 0) {
            // Keep a 0.5ms margin so the raw value stays strictly negative and
            // the final ceil yields 0, never 1.
            $game->setTotalPausedSeconds($game->getTotalPausedSeconds() + floor(-$rawRemaining * 1000 - 0.5) / 1000);
        }
    }

    /**
     * Ends the current pause, folding its sub-second duration into totalPausedSeconds.
     */
    public function closeCurrentPause(Game $game, ?float $nowMs = null): void
    {
        if (!$game->isPaused()) {
            return;
        }

        if ($game->getLastPausedAt()) {
            $nowMs = $nowMs ?? microtime(true) * 1000;
            $pauseSeconds = ($nowMs - self::toMilliseconds($game->getLastPausedAt())) / 1000;
            $game->setTotalPausedSeconds($game->getTotalPausedSeconds() + $pauseSeconds);
        }

        $game
            ->setIsPaused(false)
            ->setLastPausedAt(null);
    }

    /**
     * Unclamped remaining seconds (may be negative), sub-second precision.
     */
    private function computeRawRemainingSeconds(Game $game, float $nowMs): float
    {
        $startedAtMs = self::toMilliseconds($game->getStartedAt());

        // While paused, use lastPausedAt directly instead of subtracting the
        // current pause from now: the float residue would make the ceil flicker.
        $endMs = $game->isPaused() && $game->getLastPausedAt()
            ? self::toMilliseconds($game->getLastPausedAt())
            : $nowMs;

        $elapsedSeconds = ($endMs - $startedAtMs) / 1000 - $game->getTotalPausedSeconds();

        return self::DEFAULT_DURATION_SECONDS - $elapsedSeconds + $game->getManualAdjustmentSeconds();
    }

    private static function toMilliseconds(DateTimeImmutable $date): float
    {
        return ((float)$date->format('U.u')) * 1000;
    }
}
