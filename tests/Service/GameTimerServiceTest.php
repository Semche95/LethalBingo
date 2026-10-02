<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Game;
use App\Service\GameService;
use App\Service\GameTimerService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mercure\HubInterface;

class GameTimerServiceTest extends TestCase
{
    private GameTimerService $timerService;

    protected function setUp(): void
    {
        $em = $this->createStub(EntityManagerInterface::class);
        $hub = $this->createStub(HubInterface::class);
        $gameService = new GameService($em, $hub);

        $this->timerService = new GameTimerService($gameService);
    }

    public function testGetRemainingSecondsReturnsFullHourWhenNotStarted(): void
    {
        $game = new Game();

        $this->assertSame(3600, $this->timerService->getRemainingSeconds($game));
    }

    public function testGetRemainingSecondsReturnsZeroWhenFinished(): void
    {
        $game = new Game();
        $game->setStartedAt(new DateTimeImmutable('-30 minutes'));
        $game->setFinishedAt(new DateTimeImmutable());

        $this->assertSame(0, $this->timerService->getRemainingSeconds($game));
    }

    public function testGetRemainingSecondsCountsDownLinearly(): void
    {
        $startedAt = new DateTimeImmutable('-10 minutes');
        $game = new Game();
        $game->setStartedAt($startedAt);

        $remaining = $this->timerService->getRemainingSeconds($game);

        $elapsed = (int) floor(microtime(true) - $startedAt->getTimestamp());
        $this->assertSame(max(0, 3600 - $elapsed), $remaining);
    }

    public function testGetRemainingSecondsAccountsForPausedTime(): void
    {
        $startedAt = new DateTimeImmutable('-50 minutes');
        $game = new Game();
        $game->setStartedAt($startedAt);
        $game->setTotalPausedSeconds(1800);
        $game->setIsPaused(false);

        $remaining = $this->timerService->getRemainingSeconds($game);

        $elapsed = (int) floor(microtime(true) - $startedAt->getTimestamp());
        $this->assertSame(max(0, 3600 - ($elapsed - 1800)), $remaining);
    }

    public function testGetRemainingSecondsAccountsForCurrentPause(): void
    {
        $startedAt = new DateTimeImmutable('-50 minutes');
        $lastPausedAt = new DateTimeImmutable('-2 minutes');

        $game = new Game();
        $game->setStartedAt($startedAt);
        $game->setIsPaused(true);
        $game->setLastPausedAt($lastPausedAt);
        $game->setTotalPausedSeconds(0);

        $remaining = $this->timerService->getRemainingSeconds($game);

        $now = microtime(true);
        $elapsed = $now - $startedAt->getTimestamp();
        $currentPause = $now - $lastPausedAt->getTimestamp();
        $this->assertSame((int) ceil(max(0, 3600 - ($elapsed - $currentPause))), $remaining);
    }

    public function testGetRemainingSecondsReturnsZeroWhenTimeIsUp(): void
    {
        $game = new Game();
        $game->setStartedAt(new DateTimeImmutable('-2 hours'));
        $game->setIsPaused(false);
        $game->setTotalPausedSeconds(0);

        $this->assertSame(0, $this->timerService->getRemainingSeconds($game));
    }

    public function testGetRemainingSecondsDoesNotGoNegative(): void
    {
        $game = new Game();
        $game->setStartedAt(new DateTimeImmutable('-3 hours'));
        $game->setTotalPausedSeconds(0);
        $game->setIsPaused(false);

        $this->assertSame(0, $this->timerService->getRemainingSeconds($game));
    }

    public function testGetRemainingSecondsWithManualAdjustment(): void
    {
        $startedAt = new DateTimeImmutable('-10 minutes');
        $game = new Game();
        $game->setStartedAt($startedAt);
        $game->setManualAdjustmentSeconds(60);

        $base = $this->timerService->getRemainingSeconds(clone $game);
        $game->setManualAdjustmentSeconds(0);
        $without = $this->timerService->getRemainingSeconds($game);

        $this->assertSame(60, $base - $without);
    }

    public function testAbsorbDeficitBringsOvertimeBackToExactlyZero(): void
    {
        $startedAt = new DateTimeImmutable('@1000000000');
        $game = new Game();
        $game->setStartedAt($startedAt);

        // 5 minutes of overtime: the raw remaining time is -300s (clamped to 0).
        $nowMs = (1_000_003_600 + 300) * 1000;
        $this->assertSame(0, $this->timerService->getRemainingSeconds($game, $nowMs));

        $this->timerService->absorbDeficit($game, $nowMs);

        // The whole deficit is absorbed, minus the sub-millisecond safety margin.
        $this->assertEqualsWithDelta(300.0, $game->getTotalPausedSeconds(), 0.002);
        $this->assertSame(0, $this->timerService->getRemainingSeconds($game, $nowMs));
    }

    public function testPlusOneMinuteAfterFractionalOvertimeLastsAFullSecondOnOneMinute(): void
    {
        $game = new Game();
        $game->setStartedAt(new DateTimeImmutable('@1000000000'));

        // 300.4s of overtime, then +1 minute.
        $nowMs = 1_000_003_900_400.0;
        $this->timerService->absorbDeficit($game, $nowMs);
        $game->setManualAdjustmentSeconds($game->getManualAdjustmentSeconds() + 60);

        // 00:01:00 must still be shown 900ms later: absorbing only the whole
        // seconds of the deficit would leave 59.6s and drop to 00:00:59 already.
        $this->assertSame(60, $this->timerService->getRemainingSeconds($game, $nowMs));
        $this->assertSame(60, $this->timerService->getRemainingSeconds($game, $nowMs + 900));
    }

    public function testAbsorbDeficitNeverLandsOnOneSecondWhateverTheOvertimeFraction(): void
    {
        for ($fractionMs = 0; $fractionMs < 1000; $fractionMs += 7) {
            $game = new Game();
            $game->setStartedAt(new DateTimeImmutable('@1000000000'));
            $nowMs = 1_000_003_900_000.0 + $fractionMs + 0.123;

            $this->timerService->absorbDeficit($game, $nowMs);

            $this->assertSame(0, $this->timerService->getRemainingSeconds($game, $nowMs), "fraction {$fractionMs}ms");
        }
    }

    public function testCloseCurrentPauseKeepsTheSubSecondPartOfThePause(): void
    {
        $game = new Game();
        $game
            ->setStartedAt(new DateTimeImmutable('@1000000000'))
            ->setIsPaused(true)
            ->setLastPausedAt(new DateTimeImmutable('@1000000600'))
            ->setTotalPausedSeconds(12.25);

        // Resumed 90.7s after the pause started: truncating "now" to whole
        // seconds would only count 90s and shift the remaining time.
        $nowMs = 1_000_000_690_700.0;
        $frozenRemaining = $this->timerService->getRemainingSeconds($game, $nowMs);

        $this->timerService->closeCurrentPause($game, $nowMs);

        $this->assertFalse($game->isPaused());
        $this->assertNull($game->getLastPausedAt());
        $this->assertEqualsWithDelta(12.25 + 90.7, $game->getTotalPausedSeconds(), 1e-6);
        // Resuming never changes the displayed remaining time.
        $this->assertSame($frozenRemaining, $this->timerService->getRemainingSeconds($game, $nowMs));
    }

    public function testCloseCurrentPauseDoesNothingWhenNotPaused(): void
    {
        $game = new Game();
        $game
            ->setStartedAt(new DateTimeImmutable('@1000000000'))
            ->setTotalPausedSeconds(5.5);

        $this->timerService->closeCurrentPause($game, 1_000_000_690_700.0);

        $this->assertSame(5.5, $game->getTotalPausedSeconds());
    }

    public function testPlusOneMinuteAfterArbitraryOvertimeLandsOnExactlyOneMinute(): void
    {
        // Whatever the overtime duration (simulated, not just a few seconds), a
        // single +1 minute applied on top of the absorption must land back on
        // exactly 00:01:00. The result must not depend on the simulated delay
        // between the zero-crossing and the click.
        foreach ([0, 30, 300, 3600, 7200] as $overtimeSeconds) {
            $startedAt = new DateTimeImmutable('@1000000000');
            $game = new Game();
            $game->setStartedAt($startedAt);

            // "now": 1 hour after start (the timer hit zero) plus the overtime delay.
            $nowMs = (1_000_003_600 + $overtimeSeconds) * 1000;

            // Simulate the +1 minute action flow: absorb first, then add 60.
            $this->timerService->absorbDeficit($game, $nowMs);
            $game->setManualAdjustmentSeconds($game->getManualAdjustmentSeconds() + 60);

            $this->assertSame(60, $this->timerService->getRemainingSeconds($game, $nowMs), "overtime {$overtimeSeconds}s");
        }
    }

    public function testMultipleMutationsAfterOvertimeRemainCoherent(): void
    {
        // Sequence: +1 minute, then −1 minute, then a set-to-value. Each step
        // starts by absorbing any deficit, so the remaining time stays coherent
        // at every stage.
        $startedAt = new DateTimeImmutable('@1000000000');
        $game = new Game();
        $game->setStartedAt($startedAt);

        $nowMs = (1_000_003_600 + 300) * 1000; // 5 minutes overtime

        // +1 minute: absorbs the 300s deficit, then adds 60.
        $this->timerService->absorbDeficit($game, $nowMs);
        $game->setManualAdjustmentSeconds($game->getManualAdjustmentSeconds() + 60);
        $this->assertSame(60, $this->timerService->getRemainingSeconds($game, $nowMs));

        // −1 minute: no deficit to absorb (60 > 0), then removes 60.
        $this->timerService->absorbDeficit($game, $nowMs);
        $game->setManualAdjustmentSeconds($game->getManualAdjustmentSeconds() - 60);
        $this->assertSame(0, $this->timerService->getRemainingSeconds($game, $nowMs));

        // Set-to-value 30: still no deficit, set the adjustment so 30 remain.
        $this->timerService->absorbDeficit($game, $nowMs);
        $current = $this->timerService->getRemainingSeconds($game, $nowMs);
        $game->setManualAdjustmentSeconds($game->getManualAdjustmentSeconds() + (30 - $current));
        $this->assertSame(30, $this->timerService->getRemainingSeconds($game, $nowMs));
    }

    #[DataProvider('provideParityFixture')]
    public function testPhpMatchesCommittedTsFixture(
        ?int $gameStartedAt,
        ?int $gameFinishedAt,
        int $gameTotalPausedSeconds,
        int $gameManualAdjustmentSeconds,
        ?int $gameLastPausedAt,
        bool $gameIsPaused,
        float $currentServerTime,
        int $expected,
    ): void {
        $game = new Game();

        if ($gameStartedAt !== null) {
            $game->setStartedAt(new DateTimeImmutable('@' . $gameStartedAt));
        }
        if ($gameFinishedAt !== null) {
            $game->setFinishedAt(new DateTimeImmutable('@' . $gameFinishedAt));
        }
        $game
            ->setTotalPausedSeconds($gameTotalPausedSeconds)
            ->setManualAdjustmentSeconds($gameManualAdjustmentSeconds)
            ->setIsPaused($gameIsPaused);
        if ($gameLastPausedAt !== null) {
            $game->setLastPausedAt(new DateTimeImmutable('@' . $gameLastPausedAt));
        }

        // $currentServerTime is in milliseconds; getRemainingSeconds expects ms.
        $remaining = $this->timerService->getRemainingSeconds($game, $currentServerTime);

        $this->assertSame($expected, $remaining);
    }

    public static function provideParityFixture(): array
    {
        $fixtureFile = dirname(__DIR__, 2) . '/tests/fixtures/game_timer_parity.json';
        $cases = json_decode((string) file_get_contents($fixtureFile), true, 512, JSON_THROW_ON_ERROR);
        $rows = [];

        foreach ($cases as $index => $case) {
            $rows['case #' . $index] = [
                $case['gameStartedAt'],
                $case['gameFinishedAt'],
                $case['gameTotalPausedSeconds'],
                $case['gameManualAdjustmentSeconds'],
                $case['gameLastPausedAt'],
                $case['gameIsPaused'],
                $case['currentServerTime'],
                $case['expected'],
            ];
        }

        return $rows;
    }
}
