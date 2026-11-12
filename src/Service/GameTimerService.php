<?php

namespace App\Service;

use App\Entity\Game;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Time authority for the countdown and the stopwatch (docs/TIMER.md sections 2,
 * 3 and 8). Every
 * $nowMs parameter is in milliseconds and defaults to the real clock.
 */
readonly class GameTimerService
{
    public function __construct(
        private GameService $gameService,
    ) {
    }

    public function refreshGameTimer(Game $game): float
    {
        return $this->gameService->refreshGameFrame($game, RefreshEvent::GameTimer);
    }

    /**
     * Whether the countdown of a game in progress has reached zero, when the
     * pause is refused. Mirrored by isTimeUp() in game_timer_math.ts.
     */
    public function isTimeUp(Game $game, ?float $nowMs = null): bool
    {
        if (!$game->isInProgress() || $game->isStopwatch()) {
            return false;
        }

        if ($game->isPaused()) {
            return $game->getPausedRemainingSeconds() <= 0;
        }

        $countdownZeroAt = $game->getCountdownZeroAt();

        return $countdownZeroAt === null || self::toMilliseconds($countdownZeroAt) <= ($nowMs ?? self::now());
    }

    public function start(Game $game, ?float $nowMs = null): void
    {
        $nowMs ??= self::now();

        $this->gameService->start(
            $game,
            self::fromMilliseconds($nowMs),
            $game->isStopwatch() ? null : self::countdownZeroAt($nowMs, $game->getCountdownDurationSeconds()),
        );
    }

    /**
     * A game stopped after its time is up ends when the countdown reached zero.
     */
    public function stop(Game $game, ?float $nowMs = null): void
    {
        $nowMs ??= self::now();
        $countdownZeroAt = $game->getCountdownZeroAt();

        if (!$game->isPaused() && $countdownZeroAt !== null) {
            $nowMs = min($nowMs, self::toMilliseconds($countdownZeroAt));
        }

        $this->gameService->stop($game, self::fromMilliseconds($nowMs));
    }

    public function pause(Game $game, ?float $nowMs = null): void
    {
        if (!$game->isInProgress() || $game->isPaused()) {
            return;
        }

        $nowMs ??= self::now();

        if ($game->isStopwatch()) {
            $game
                ->setPausedAt(self::fromMilliseconds($nowMs))
                ->setIsPaused(true);

            return;
        }

        $game
            ->setPausedRemainingSeconds($this->computeExactRemainingSeconds($game, $nowMs))
            ->setCountdownZeroAt(null)
            ->setIsPaused(true);
    }

    public function resume(Game $game, ?float $nowMs = null): void
    {
        if (!$game->isInProgress() || !$game->isPaused()) {
            return;
        }

        $nowMs ??= self::now();

        if ($game->isStopwatch()) {
            $pausedAt = $game->getPausedAt();
            $pauseSeconds = $pausedAt === null ? 0.0 : max(0.0, ($nowMs - self::toMilliseconds($pausedAt)) / 1000);
            $game
                ->setPausedDurationSeconds($game->getPausedDurationSeconds() + $pauseSeconds)
                ->setPausedAt(null)
                ->setIsPaused(false);

            return;
        }

        $game
            ->setCountdownZeroAt(self::countdownZeroAt($nowMs, $game->getPausedRemainingSeconds()))
            ->setPausedRemainingSeconds(0)
            ->setIsPaused(false);
    }

    /**
     * Adds (or removes, when negative) seconds to the remaining time, never
     * going below zero. A stopwatch has no remaining time to adjust.
     */
    public function addSeconds(Game $game, int $seconds, ?float $nowMs = null): void
    {
        if (!$game->isInProgress() || $game->isStopwatch()) {
            return;
        }

        $nowMs ??= self::now();
        $this->applyRemainingSeconds($game, $this->computeExactRemainingSeconds($game, $nowMs) + $seconds, $nowMs);
    }

    public function setRemainingSeconds(Game $game, int $seconds, ?float $nowMs = null): void
    {
        if (!$game->isInProgress() || $game->isStopwatch()) {
            return;
        }

        $this->applyRemainingSeconds($game, $seconds, $nowMs ?? self::now());
    }

    private function applyRemainingSeconds(Game $game, float $remainingSeconds, float $nowMs): void
    {
        $remainingSeconds = max(0.0, $remainingSeconds);

        if ($game->isPaused()) {
            $game->setPausedRemainingSeconds($remainingSeconds);

            return;
        }

        $game->setCountdownZeroAt(self::countdownZeroAt($nowMs, $remainingSeconds));
    }

    /**
     * Elapsed seconds of a stopwatch, pauses excluded, rounded down and never
     * negative. Frozen at the start of the current pause, or at the end of the
     * game. Mirrored by calculateElapsedSeconds() in game_timer_math.ts.
     */
    public function elapsedSeconds(Game $game, ?float $nowMs = null): int
    {
        $startedAt = $game->getStartedAt();
        if ($startedAt === null) {
            return 0;
        }

        $endMs = $nowMs ?? self::now();
        $pausedAt = $game->getPausedAt();
        $finishedAt = $game->getFinishedAt();
        if ($game->isPaused() && $pausedAt !== null) {
            $endMs = self::toMilliseconds($pausedAt);
        } elseif ($finishedAt !== null) {
            $endMs = self::toMilliseconds($finishedAt);
        }

        return max(0, (int) floor(($endMs - self::toMilliseconds($startedAt)) / 1000 - $game->getPausedDurationSeconds()));
    }

    /**
     * Remaining seconds of a game in progress, sub-second precision, never negative.
     */
    private function computeExactRemainingSeconds(Game $game, float $nowMs): float
    {
        if ($game->isPaused()) {
            return max(0.0, $game->getPausedRemainingSeconds());
        }

        $countdownZeroAt = $game->getCountdownZeroAt();
        if ($countdownZeroAt === null) {
            return 0.0;
        }

        return max(0.0, (self::toMilliseconds($countdownZeroAt) - $nowMs) / 1000);
    }

    private static function now(): float
    {
        return microtime(true) * 1000;
    }

    private static function toMilliseconds(DateTimeImmutable $date): float
    {
        return ((float) $date->format('U.u')) * 1000;
    }

    /**
     * Moment the countdown reaches zero, $remainingSeconds after $nowMs. Set
     * one microsecond early: once rounded to the microsecond and read back as a
     * float, it could otherwise give a remaining time a hair above the target,
     * which the ceil would turn into one more second.
     */
    private static function countdownZeroAt(float $nowMs, float $remainingSeconds): DateTimeImmutable
    {
        return self::fromMicroseconds(floor(($nowMs + $remainingSeconds * 1000) * 1000) - 1);
    }

    private static function fromMilliseconds(float $milliseconds): DateTimeImmutable
    {
        return self::fromMicroseconds(round($milliseconds * 1000));
    }

    private static function fromMicroseconds(float $microseconds): DateTimeImmutable
    {
        return new DateTimeImmutable(\sprintf('@%.6F', $microseconds / 1_000_000))
            ->setTimezone(new DateTimeZone(date_default_timezone_get()));
    }
}
