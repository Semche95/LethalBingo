<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Game;
use App\Entity\TimerMode;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class GameTest extends TestCase
{
    public function testIsReadOnlyReturnsFalseWhenNeitherStartedNorFinished(): void
    {
        $game = new Game();

        $this->assertFalse($game->isReadOnly());
    }

    public function testIsReadOnlyReturnsTrueWhenStarted(): void
    {
        $game = new Game();
        $game->setStartedAt(new DateTimeImmutable());

        $this->assertTrue($game->isReadOnly());
    }

    public function testIsReadOnlyReturnsTrueWhenFinished(): void
    {
        $game = new Game();
        $game->setFinishedAt(new DateTimeImmutable());

        $this->assertTrue($game->isReadOnly());
    }

    public function testIsReadOnlyReturnsTrueWhenBothStartedAndFinished(): void
    {
        $game = new Game();
        $game->setStartedAt(new DateTimeImmutable());
        $game->setFinishedAt(new DateTimeImmutable());

        $this->assertTrue($game->isReadOnly());
    }

    public function testGenerateSlugProduces12CharacterString(): void
    {
        $game = new Game();
        $slug = $game->getSlug();

        $this->assertSame(12, strlen($slug));
    }

    public function testGenerateSlugChangesValueOnCall(): void
    {
        $game = new Game();
        $firstSlug = $game->getSlug();

        $game->generateSlug();
        $secondSlug = $game->getSlug();

        $this->assertNotSame($firstSlug, $secondSlug);
    }

    public function testSetSlugOverridesGeneratedSlug(): void
    {
        $game = new Game();
        $game->setSlug('custom-slug');

        $this->assertSame('custom-slug', $game->getSlug());
    }

    public function testDefaultValues(): void
    {
        $game = new Game();

        $this->assertNull($game->getId());
        $this->assertNull($game->getStartedAt());
        $this->assertNull($game->getFinishedAt());
        $this->assertNull($game->getNotes());
        $this->assertFalse($game->isPaused());
        $this->assertNull($game->getCountdownZeroAt());
        $this->assertSame(0.0, $game->getPausedRemainingSeconds());
        $this->assertSame(TimerMode::Countdown, $game->getTimerMode());
        $this->assertFalse($game->isStopwatch());
        $this->assertSame(5400, $game->getCountdownDurationSeconds());
        $this->assertNull($game->getPausedAt());
        $this->assertSame(0.0, $game->getPausedDurationSeconds());
    }

    public function testResetTimerClearsTheStopwatchPausesAndKeepsTheMode(): void
    {
        $startedAt = new DateTimeImmutable();
        $game = new Game()
            ->setTimerMode(TimerMode::Stopwatch)
            ->setPausedAt(new DateTimeImmutable())
            ->setPausedDurationSeconds(42.5)
            ->resetTimer($startedAt);

        $this->assertSame($startedAt, $game->getStartedAt());
        $this->assertNull($game->getPausedAt());
        $this->assertSame(0.0, $game->getPausedDurationSeconds());
        $this->assertTrue($game->isStopwatch());
    }

    public function testSetNotes(): void
    {
        $game = new Game();
        $game->setNotes('Some notes');

        $this->assertSame('Some notes', $game->getNotes());
    }

    public function testSetNullNotes(): void
    {
        $game = new Game();
        $game->setNotes('Some notes');
        $game->setNotes(null);

        $this->assertNull($game->getNotes());
    }
}
