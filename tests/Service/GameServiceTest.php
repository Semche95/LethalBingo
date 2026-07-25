<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\BingoSquareRevealed;
use App\Entity\BingoSquareValidated;
use App\Entity\Game;
use App\Service\GameService;
use App\Service\RefreshEvent;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mercure\HubInterface;

class GameServiceTest extends TestCase
{
    private GameService $gameService;
    private EntityManagerInterface $em;
    private HubInterface $hub;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->hub = $this->createMock(HubInterface::class);

        $this->gameService = new GameService($this->em, $this->hub);
    }

    public function testRefreshGameFramePublishesMercureUpdate(): void
    {
        $game = new Game();
        $game->setSlug('my-game-slug');

        $this->hub->expects($this->once())
            ->method('publish')
            ->with($this->callback(function ($update): bool {
                $this->assertSame(['game/my-game-slug'], $update->getTopics());

                $data = json_decode($update->getData(), true, 512, JSON_THROW_ON_ERROR);
                $this->assertSame('gameFrameRefresh', $data['event']);

                return true;
            }));

        $this->gameService->refreshGameFrame($game);
    }

    public function testRefreshGameFrameWithCustomEvent(): void
    {
        $game = new Game();
        $game->setSlug('my-game');

        $this->hub->expects($this->once())
            ->method('publish')
            ->with($this->callback(function ($update): bool {
                $data = json_decode($update->getData(), true, 512, JSON_THROW_ON_ERROR);
                $this->assertSame('bingoCardRefresh', $data['event']);

                return true;
            }));

        $this->gameService->refreshGameFrame($game, RefreshEvent::BingoCard);
    }

    public function testStartSetsStartedAtAndResetsPauseState(): void
    {
        $game = new Game();
        $game->setSlug('test');

        $this->em->expects($this->once())->method('persist')->with($game);
        $this->em->expects($this->once())->method('flush');
        $this->hub->expects($this->once())->method('publish');

        $this->gameService->start($game);

        $this->assertInstanceOf(DateTimeImmutable::class, $game->getStartedAt());
        $this->assertNull($game->getFinishedAt());
        $this->assertFalse($game->isPaused());
        $this->assertSame(0, $game->getTotalPausedSeconds());
        $this->assertNull($game->getLastPausedAt());
    }

    public function testStartClearsExistingFinishedAt(): void
    {
        $game = new Game();
        $game->setSlug('test');
        $game->setFinishedAt(new DateTimeImmutable());

        $this->em->method('persist');
        $this->em->method('flush');
        $this->hub->method('publish');

        $this->gameService->start($game);

        $this->assertNull($game->getFinishedAt());
    }

    public function testStopSetsFinishedAt(): void
    {
        $game = new Game();
        $game->setSlug('test');

        $this->em->expects($this->once())->method('persist')->with($game);
        $this->em->expects($this->once())->method('flush');
        $this->hub->expects($this->once())->method('publish');

        $this->gameService->stop($game);

        $this->assertInstanceOf(DateTimeImmutable::class, $game->getFinishedAt());
    }

    public function testResetClearsAllGameState(): void
    {
        $game = new Game();
        $game->setSlug('test');
        $game->setStartedAt(new DateTimeImmutable());
        $game->setFinishedAt(new DateTimeImmutable());
        $game->setIsPaused(true);
        $game->setTotalPausedSeconds(120);
        $game->setLastPausedAt(new DateTimeImmutable());
        $game->getBingoSquareValidated()->add(new BingoSquareValidated());
        $game->getBingoSquareValidated()->add(new BingoSquareValidated());

        $this->hub->expects($this->once())->method('publish');

        $removedEntities = [];
        $this->em->method('remove')->willReturnCallback(function ($entity) use (&$removedEntities): void {
            $removedEntities[] = $entity;
        });

        $this->em->method('persist')->with($game);
        $this->em->method('wrapInTransaction')->willReturnCallback(function (callable $callable) use ($game): void {
            $callable($this->em);
        });

        $this->gameService->reset($game);

        $this->assertNull($game->getStartedAt());
        $this->assertNull($game->getFinishedAt());
        $this->assertFalse($game->isPaused());
        $this->assertSame(0, $game->getTotalPausedSeconds());
        $this->assertNull($game->getLastPausedAt());
        $this->assertCount(2, $removedEntities);
        $this->assertInstanceOf(BingoSquareValidated::class, $removedEntities[0]);
    }

    public function testResetClearsBingoSquareRevealedCollection(): void
    {
        $game = new Game();
        $game->setSlug('test');
        $game->getBingoSquareRevealed()->add(new BingoSquareRevealed());
        $game->getBingoSquareRevealed()->add(new BingoSquareRevealed());

        $this->hub->method('publish');
        $this->em->method('wrapInTransaction')->willReturnCallback(function (callable $callable) use ($game): void {
            $callable($this->em);
        });

        $this->gameService->reset($game);

        $this->assertCount(0, $game->getBingoSquareRevealed());
    }
}
