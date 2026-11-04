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
        $game = new Game()
            ->setStartedAt(new DateTimeImmutable('2026-11-04 20:00:00'))
            ->setTotalPausedSeconds(300);

        $highlight = new GameHighlight($game, new DateTimeImmutable('2026-11-04 20:12:20.900'));

        $this->assertSame(740, $highlight->getElapsedSeconds());
    }

    public function testElapsedTimeIsZeroForAGameNotStarted(): void
    {
        $this->assertSame(0, new GameHighlight(new Game())->getElapsedSeconds());
    }
}
