<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Game;
use App\Service\GameService;
use App\Service\GameTimerService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mercure\HubInterface;

class GameTimerServiceTest extends TestCase
{
    private GameTimerService $timerService;

    protected function setUp(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $hub = $this->createMock(HubInterface::class);
        $gameService = new GameService($em, $hub);

        $this->timerService = new GameTimerService($gameService);
    }

    public function testFormatGameDurationReturnsFullHourWhenNotStarted(): void
    {
        $game = new Game();

        $result = $this->timerService->formatGameDuration($game);

        $this->assertSame('01:00:00', $result);
    }

    public function testFormatGameDurationReturnsZeroWhenFinished(): void
    {
        $game = new Game();
        $game->setStartedAt(new DateTimeImmutable('-30 minutes'));
        $game->setFinishedAt(new DateTimeImmutable());

        $result = $this->timerService->formatGameDuration($game);

        $this->assertSame('00:00:00', $result);
    }

    public function testFormatGameDurationWithNoPauseAndRecentlyStarted(): void
    {
        $game = new Game();
        $game->setStartedAt(new DateTimeImmutable('-10 minutes'));
        $game->setIsPaused(false);
        $game->setTotalPausedSeconds(0);

        $result = $this->timerService->formatGameDuration($game);

        $this->assertMatchesRegularExpression('/^00:\d{2}:\d{2}$/', $result);
        $this->assertStringStartsWith('00:5', $result, 'Should show ~50 minutes remaining');
    }

    public function testFormatGameDurationAccountsForPausedTime(): void
    {
        $startedAt = new DateTimeImmutable('-50 minutes');
        $game = new Game();
        $game->setStartedAt($startedAt);
        $game->setTotalPausedSeconds(1800); // 30 minutes paused
        $game->setIsPaused(false);

        $result = $this->timerService->formatGameDuration($game);

        $elapsedSinceStart = (new DateTimeImmutable())->getTimestamp() - $startedAt->getTimestamp();
        $effectiveElapsed = $elapsedSinceStart - 1800;
        $expectedRemaining = max(0, 3600 - $effectiveElapsed);
        $hours = (int) floor($expectedRemaining / 3600);
        $minutes = (int) floor(($expectedRemaining % 3600) / 60);
        $seconds = $expectedRemaining % 60;

        $this->assertSame(
            sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds),
            $result,
        );
    }

    public function testFormatGameDurationAccountsForCurrentPause(): void
    {
        $startedAt = new DateTimeImmutable('-50 minutes');
        $lastPausedAt = new DateTimeImmutable('-2 minutes');

        $game = new Game();
        $game->setStartedAt($startedAt);
        $game->setIsPaused(true);
        $game->setLastPausedAt($lastPausedAt);
        $game->setTotalPausedSeconds(0);

        $result = $this->timerService->formatGameDuration($game);

        $elapsedSinceStart = (new DateTimeImmutable())->getTimestamp() - $startedAt->getTimestamp();
        $currentPause = (new DateTimeImmutable())->getTimestamp() - $lastPausedAt->getTimestamp();
        $effectiveElapsed = $elapsedSinceStart - $currentPause;
        $expectedRemaining = max(0, 3600 - $effectiveElapsed);
        $hours = (int) floor($expectedRemaining / 3600);
        $minutes = (int) floor(($expectedRemaining % 3600) / 60);
        $seconds = $expectedRemaining % 60;

        $this->assertSame(
            sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds),
            $result,
        );
    }

    public function testFormatGameDurationReturnsZeroWhenTimeIsUp(): void
    {
        $game = new Game();
        $game->setStartedAt(new DateTimeImmutable('-2 hours'));
        $game->setIsPaused(false);
        $game->setTotalPausedSeconds(0);

        $result = $this->timerService->formatGameDuration($game);

        $this->assertSame('00:00:00', $result);
    }

    public function testFormatGameDurationDoesNotGoNegative(): void
    {
        $game = new Game();
        $game->setStartedAt(new DateTimeImmutable('-3 hours'));
        $game->setTotalPausedSeconds(0);
        $game->setIsPaused(false);

        $result = $this->timerService->formatGameDuration($game);

        $this->assertSame('00:00:00', $result);
    }

    public function testFormatGameDurationWithMinimalTimeRemaining(): void
    {
        $game = new Game();
        $game->setStartedAt(new DateTimeImmutable('-59 minutes -59 seconds'));
        $game->setTotalPausedSeconds(0);
        $game->setIsPaused(false);

        $result = $this->timerService->formatGameDuration($game);

        $this->assertMatchesRegularExpression('/^00:00:\d{2}$/', $result);
    }
}
