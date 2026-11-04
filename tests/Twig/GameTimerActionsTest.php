<?php

declare(strict_types=1);

namespace App\Tests\Twig;

use App\Entity\Game;
use App\Tests\Controller\ControllerTestCase;
use DateTimeImmutable;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

class GameTimerActionsTest extends ControllerTestCase
{
    use InteractsWithLiveComponents;

    public function testRefereePausesAndResumesTheGame(): void
    {
        $game = $this->createGame()->resetTimer(new DateTimeImmutable('-12 minutes'), new DateTimeImmutable('+48 minutes'));
        $this->em->flush();

        $this->callAction($game, 'pauseGame');

        $game = $this->reload(Game::class, $game->getId());
        self::assertTrue($game->isPaused());
        self::assertNull($game->getCountdownZeroAt());
        self::assertEqualsWithDelta(48 * 60, $game->getPausedRemainingSeconds(), 2);

        $this->callAction($game, 'resumeGame');

        $game = $this->reload(Game::class, $game->getId());
        self::assertFalse($game->isPaused());
        self::assertEqualsWithDelta(new DateTimeImmutable('+48 minutes')->getTimestamp(), $game->getCountdownZeroAt()?->getTimestamp(), 2);
    }

    public function testThePauseIsRefusedOnceTheTimeIsUp(): void
    {
        $game = $this->createGame()->resetTimer(new DateTimeImmutable('-2 hours'), new DateTimeImmutable('-1 hour'));
        $this->em->flush();

        $this->callAction($game, 'pauseGame');

        $game = $this->reload(Game::class, $game->getId());
        self::assertFalse($game->isPaused());
        self::assertNotNull($game->getCountdownZeroAt());
    }

    public function testAddingAMinuteAtZeroRestartsTheCountdown(): void
    {
        $game = $this->createGame()->resetTimer(new DateTimeImmutable('-2 hours'), new DateTimeImmutable('-1 hour'));
        $this->em->flush();

        $this->callAction($game, 'increaseTimer');

        $game = $this->reload(Game::class, $game->getId());
        self::assertEqualsWithDelta(new DateTimeImmutable('+1 minute')->getTimestamp(), $game->getCountdownZeroAt()?->getTimestamp(), 2);
    }

    private function callAction(Game $game, string $action): void
    {
        $this->createLiveComponent('GameTimer', ['game' => $game], $this->client)
            ->actingAs($this->createUser(['ROLE_REFEREE']))
            ->call($action);
    }
}
