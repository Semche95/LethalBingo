<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Game;
use App\Entity\GameHighlight;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class GameHighlightTest extends TestCase
{
    public function testElapsedTimeIsCountedFromTheStartIncludingThePauses(): void
    {
        // Paused 5 minutes: the countdown zero moment moved back by as much.
        $game = new Game()->resetTimer(new DateTimeImmutable('2026-11-04 20:00:00'), new DateTimeImmutable('2026-11-04 21:05:00'));

        $highlight = new GameHighlight($game, new DateTimeImmutable('2026-11-04 20:12:20.900'));

        $this->assertSame(740, $highlight->getElapsedSeconds());
    }

    public function testElapsedTimeIncludesTheTimeSpentAtZero(): void
    {
        // At zero from 21:00 to 21:30, then the referee added time.
        $game = new Game()->resetTimer(new DateTimeImmutable('2026-11-04 20:00:00'), new DateTimeImmutable('2026-11-04 21:40:00'));

        $highlight = new GameHighlight($game, new DateTimeImmutable('2026-11-04 21:35:10'));

        $this->assertSame(5710, $highlight->getElapsedSeconds());
    }

    public function testElapsedTimeIsZeroForAGameNotStarted(): void
    {
        $this->assertSame(0, new GameHighlight(new Game())->getElapsedSeconds());
    }
}
