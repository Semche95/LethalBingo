<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Game;
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

        $this->assertIsString($slug);
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
        $this->assertNull($game->getLastPausedAt());
        $this->assertFalse($game->isPaused());
        $this->assertSame(0, $game->getTotalPausedSeconds());
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
