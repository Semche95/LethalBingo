<?php

declare(strict_types=1);

namespace App\Tests\Twig;

use App\Entity\Game;
use App\Tests\Controller\ControllerTestCase;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;
use Symfony\UX\LiveComponent\Test\TestLiveComponent;

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

    public function testRefereeAddsTheEnteredDuration(): void
    {
        $game = $this->createGame()->resetTimer(new DateTimeImmutable('-12 minutes'), new DateTimeImmutable('+48 minutes'));
        $this->em->flush();

        $this->callAction($game, 'increaseTimer', '2:30');

        $game = $this->reload(Game::class, $game->getId());
        self::assertEqualsWithDelta(new DateTimeImmutable('+50 minutes 30 seconds')->getTimestamp(), $game->getCountdownZeroAt()?->getTimestamp(), 2);
    }

    public function testRefereeRemovesTheEnteredDurationWithoutGoingBelowZero(): void
    {
        $game = $this->createGame()->resetTimer(new DateTimeImmutable('-12 minutes'), new DateTimeImmutable('+48 minutes'));
        $this->em->flush();

        $this->callAction($game, 'pauseGame');
        $this->callAction($this->reload(Game::class, $game->getId()), 'decreaseTimer', '10:15');

        $game = $this->reload(Game::class, $game->getId());
        self::assertEqualsWithDelta(48 * 60 - 615, $game->getPausedRemainingSeconds(), 2);

        $this->callAction($game, 'decreaseTimer', '999');

        self::assertSame(0.0, $this->reload(Game::class, $game->getId())->getPausedRemainingSeconds());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidDurations(): iterable
    {
        yield 'seconds above 59' => ['2:75'];
        yield 'single-digit seconds' => ['2:5'];
        yield 'zero' => ['0:00'];
        yield 'text' => ['abc'];
        yield 'empty' => [''];
    }

    #[DataProvider('invalidDurations')]
    public function testAnInvalidDurationLeavesTheTimerUnchangedAndIsFlagged(string $duration): void
    {
        $game = $this->createGame()->resetTimer(new DateTimeImmutable('-12 minutes'), new DateTimeImmutable('+48 minutes'));
        $this->em->flush();

        $this->callAction($game, 'pauseGame');
        $component = $this->callAction($this->reload(Game::class, $game->getId()), 'increaseTimer', $duration);

        self::assertEqualsWithDelta(48 * 60, $this->reload(Game::class, $game->getId())->getPausedRemainingSeconds(), 2);
        self::assertStringContainsString('is-invalid', (string) $component->render());
    }

    private function callAction(Game $game, string $action, ?string $duration = null): TestLiveComponent
    {
        $component = $this->createLiveComponent('GameTimer', ['game' => $game], $this->client)
            ->actingAs($this->createUser(['ROLE_REFEREE']));

        if ($duration !== null) {
            $component->set('adjustDuration', $duration);
        }

        return $component->call($action);
    }
}
