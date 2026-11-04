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
    // 2001-09-09 01:46:40 UTC, in milliseconds.
    private const float START_MS = 1_000_000_000_000.0;

    private GameTimerService $timerService;

    protected function setUp(): void
    {
        $em = $this->createStub(EntityManagerInterface::class);
        $hub = $this->createStub(HubInterface::class);
        $gameService = new GameService($em, $hub);

        $this->timerService = new GameTimerService($gameService);
    }

    /**
     * Game started at START_MS, its countdown reaching zero $durationSeconds later.
     */
    private function createRunningGame(float $durationSeconds = GameTimerService::DEFAULT_DURATION_SECONDS): Game
    {
        return new Game()->resetTimer(self::date(self::START_MS), self::date(self::START_MS + $durationSeconds * 1000));
    }

    private static function date(float $milliseconds): DateTimeImmutable
    {
        return new DateTimeImmutable(\sprintf('@%.6F', $milliseconds / 1000));
    }

    private static function toMilliseconds(?DateTimeImmutable $date): float
    {
        return (float) $date?->format('U.u') * 1000;
    }

    /**
     * Exact remaining seconds of a game in progress, read from its stored state.
     */
    private static function remainingSeconds(Game $game, float $nowMs): float
    {
        if ($game->isPaused()) {
            return $game->getPausedRemainingSeconds();
        }

        return max(0.0, (self::toMilliseconds($game->getCountdownZeroAt()) - $nowMs) / 1000);
    }

    public function testRealClockIsUsedByDefault(): void
    {
        $now = new DateTimeImmutable();

        $this->assertFalse($this->timerService->isTimeUp(new Game()->resetTimer($now, $now->modify('+90 seconds'))));
        $this->assertTrue($this->timerService->isTimeUp(new Game()->resetTimer($now, $now->modify('-1 second'))));
    }

    public function testStartSetsTheCountdownZeroMomentOneHourLater(): void
    {
        $game = new Game()->setSlug('test');

        $this->timerService->start($game, self::START_MS + 0.25);

        $this->assertEqualsWithDelta(self::START_MS + 0.25, self::toMilliseconds($game->getStartedAt()), 0.001);
        $this->assertEqualsWithDelta(self::START_MS + 3_600_000.25, self::toMilliseconds($game->getCountdownZeroAt()), 0.01);
    }

    public function testPauseFreezesTheRemainingTimeAndResumeStartsAgainFromIt(): void
    {
        $game = $this->createRunningGame();

        $this->timerService->pause($game, self::START_MS + 600_300);

        $this->assertTrue($game->isPaused());
        $this->assertNull($game->getCountdownZeroAt());
        $this->assertEqualsWithDelta(2999.7, $game->getPausedRemainingSeconds(), 1e-6);
        $this->assertFalse($this->timerService->isTimeUp($game, self::START_MS + 3_600_000 * 5));

        $this->timerService->resume($game, self::START_MS + 900_000);

        $this->assertFalse($game->isPaused());
        $this->assertEqualsWithDelta(self::START_MS + 900_000 + 2_999_700, self::toMilliseconds($game->getCountdownZeroAt()), 0.01);
        $this->assertSame(0.0, $game->getPausedRemainingSeconds());
    }

    public function testAddingAMinuteStartsFromTheExactRemainingTime(): void
    {
        $game = $this->createRunningGame();
        $nowMs = self::START_MS + 600_300;

        $this->timerService->addSeconds($game, 60, $nowMs);
        $this->assertEqualsWithDelta(3059.7, self::remainingSeconds($game, $nowMs), 0.001);

        $this->timerService->addSeconds($game, -60, $nowMs);
        $this->timerService->addSeconds($game, -60, $nowMs);
        $this->assertEqualsWithDelta(2939.7, self::remainingSeconds($game, $nowMs), 0.001);
    }

    public function testRemovingAMinuteNeverGoesBelowZero(): void
    {
        $game = $this->createRunningGame(30.5);

        $this->timerService->addSeconds($game, -60, self::START_MS);

        $this->assertEqualsWithDelta(self::START_MS, self::toMilliseconds($game->getCountdownZeroAt()), 0.01);
        $this->assertTrue($this->timerService->isTimeUp($game, self::START_MS));
    }

    public function testAddingAMinuteAtZeroGivesExactlyOneMinute(): void
    {
        foreach ([0.4, 300_000.0, 86_400_123.7] as $atZeroMs) {
            $game = $this->createRunningGame();
            $nowMs = self::START_MS + 3_600_000 + $atZeroMs;

            $this->timerService->addSeconds($game, 60, $nowMs);

            // Never above 60: the displayed time, rounded up, would show 61.
            $this->assertLessThanOrEqual(60.0, self::remainingSeconds($game, $nowMs), "at zero for {$atZeroMs}ms");
            $this->assertEqualsWithDelta(60.0, self::remainingSeconds($game, $nowMs), 0.001, "at zero for {$atZeroMs}ms");
        }
    }

    public function testRemovingAMinuteAtZeroStaysAtZero(): void
    {
        $game = $this->createRunningGame();
        $nowMs = self::START_MS + 3_900_000;

        $this->timerService->addSeconds($game, -60, $nowMs);

        $this->assertSame(0.0, self::remainingSeconds($game, $nowMs));

        // Adding time later restarts the countdown from zero.
        $this->timerService->addSeconds($game, 60, $nowMs + 100_000);
        $this->assertEqualsWithDelta(60.0, self::remainingSeconds($game, $nowMs + 100_000), 0.001);
    }

    public function testAdjustingDuringAPauseChangesTheFrozenTimeOnly(): void
    {
        $game = $this->createRunningGame();
        $this->timerService->pause($game, self::START_MS + 3_570_000);

        $this->timerService->addSeconds($game, 60, self::START_MS + 4_000_000);
        $this->assertEqualsWithDelta(90.0, $game->getPausedRemainingSeconds(), 1e-6);
        $this->assertNull($game->getCountdownZeroAt());

        $this->timerService->addSeconds($game, -60, self::START_MS + 4_000_000);
        $this->timerService->addSeconds($game, -60, self::START_MS + 4_000_000);
        $this->assertSame(0.0, $game->getPausedRemainingSeconds());
        $this->assertTrue($this->timerService->isTimeUp($game));

        // Adding time to a pause at zero gives exactly that time.
        $this->timerService->addSeconds($game, 60, self::START_MS + 5_000_000);
        $this->assertSame(60.0, $game->getPausedRemainingSeconds());
    }

    public function testSetRemainingSecondsSetsTheExactValueEvenAtZeroOrPaused(): void
    {
        $game = $this->createRunningGame();

        $this->timerService->setRemainingSeconds($game, 603, self::START_MS + 600_300);
        $this->assertEqualsWithDelta(603.0, self::remainingSeconds($game, self::START_MS + 600_300), 0.001);

        $zeroMs = self::START_MS + 600_300 + 603_000;
        $this->timerService->setRemainingSeconds($game, 3, $zeroMs + 20_000);
        $this->assertEqualsWithDelta(3.0, self::remainingSeconds($game, $zeroMs + 20_000), 0.001);

        $this->timerService->pause($game, $zeroMs + 21_000);
        $this->timerService->setRemainingSeconds($game, 183, $zeroMs + 30_000);
        $this->assertSame(183.0, $game->getPausedRemainingSeconds());
    }

    public function testAdjustmentsDoNothingOnAGameNotInProgress(): void
    {
        $game = $this->createRunningGame()->setFinishedAt(self::date(self::START_MS));
        $countdownZeroAt = $game->getCountdownZeroAt();

        $this->timerService->addSeconds($game, 60, self::START_MS);
        $this->timerService->setRemainingSeconds($game, 60, self::START_MS);
        $this->timerService->pause($game, self::START_MS);

        $this->assertSame($countdownZeroAt, $game->getCountdownZeroAt());
        $this->assertFalse($game->isPaused());
    }

    public function testIsTimeUp(): void
    {
        $game = $this->createRunningGame();

        $this->assertFalse($this->timerService->isTimeUp(new Game()));
        $this->assertFalse($this->timerService->isTimeUp($game, self::START_MS + 3_599_999));
        $this->assertTrue($this->timerService->isTimeUp($game, self::START_MS + 3_600_000));

        $this->timerService->pause($game, self::START_MS + 3_599_000);
        $this->assertFalse($this->timerService->isTimeUp($game, self::START_MS + 9_000_000));

        $finished = $this->createRunningGame()->setFinishedAt(self::date(self::START_MS));
        $this->assertFalse($this->timerService->isTimeUp($finished, self::START_MS + 9_000_000));
    }

    public function testStopBeforeZeroEndsTheGameNow(): void
    {
        $game = $this->createRunningGame()->setSlug('test');

        $this->timerService->stop($game, self::START_MS + 1_000_500);

        $this->assertEqualsWithDelta(self::START_MS + 1_000_500, self::toMilliseconds($game->getFinishedAt()), 0.001);
    }

    public function testStopAfterZeroEndsTheGameWhenTheCountdownReachedZero(): void
    {
        $game = $this->createRunningGame()->setSlug('test');

        $this->timerService->stop($game, self::START_MS + 3_600_000 + 172_800_000);

        $this->assertEqualsWithDelta(self::START_MS + 3_600_000, self::toMilliseconds($game->getFinishedAt()), 0.01);
    }

    public function testStopDuringAPauseEndsTheGameNow(): void
    {
        $game = $this->createRunningGame()->setSlug('test');
        $this->timerService->pause($game, self::START_MS + 1_000_000);

        $this->timerService->stop($game, self::START_MS + 9_000_000);

        $this->assertEqualsWithDelta(self::START_MS + 9_000_000, self::toMilliseconds($game->getFinishedAt()), 0.001);
    }

    #[DataProvider('provideParityFixture')]
    public function testPhpMatchesCommittedTsFixture(
        ?float $gameStartedAt,
        ?float $gameFinishedAt,
        ?float $gameCountdownZeroAt,
        float $gamePausedRemainingSeconds,
        bool $gameIsPaused,
        float $currentServerTime,
        bool $timeUp,
    ): void {
        $game = new Game()
            ->setIsPaused($gameIsPaused)
            ->setPausedRemainingSeconds($gamePausedRemainingSeconds);

        if ($gameStartedAt !== null) {
            $game->setStartedAt(self::date($gameStartedAt * 1000));
        }
        if ($gameFinishedAt !== null) {
            $game->setFinishedAt(self::date($gameFinishedAt * 1000));
        }
        if ($gameCountdownZeroAt !== null) {
            $game->setCountdownZeroAt(self::date($gameCountdownZeroAt * 1000));
        }

        // $currentServerTime is in milliseconds, like isTimeUp() expects.
        $this->assertSame($timeUp, $this->timerService->isTimeUp($game, $currentServerTime));
    }

    /**
     * @return array<string, array{float|null, float|null, float|null, float, bool, float, bool}>
     */
    public static function provideParityFixture(): array
    {
        $fixtureFile = dirname(__DIR__, 2) . '/tests/fixtures/game_timer_parity.json';
        $cases = json_decode((string) file_get_contents($fixtureFile), true, 512, JSON_THROW_ON_ERROR);
        $rows = [];

        foreach ($cases as $index => $case) {
            $rows['case #' . $index] = [
                $case['gameStartedAt'] === null ? null : (float) $case['gameStartedAt'],
                $case['gameFinishedAt'] === null ? null : (float) $case['gameFinishedAt'],
                $case['gameCountdownZeroAt'] === null ? null : (float) $case['gameCountdownZeroAt'],
                (float) $case['gamePausedRemainingSeconds'],
                $case['gameIsPaused'],
                (float) $case['currentServerTime'],
                $case['timeUp'],
            ];
        }

        return $rows;
    }
}
