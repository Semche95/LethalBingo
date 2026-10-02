<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Dto\GameDto;
use App\Entity\BingoSquareRevealed;
use App\Entity\BingoSquareValidated;
use App\Entity\Game;
use App\Entity\GameRound;
use App\Entity\Team;
use App\Service\GameService;
use App\Service\RefreshEvent;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mercure\HubInterface;

class GameServiceTest extends TestCase
{
    /**
     * Builds the service, replacing every collaborator not given by a stub.
     */
    private function createGameService(?EntityManagerInterface $em = null, ?HubInterface $hub = null): GameService
    {
        return new GameService(
            $em ?? $this->createStub(EntityManagerInterface::class),
            $hub ?? $this->createStub(HubInterface::class),
        );
    }

    public function testRefreshGameFramePublishesMercureUpdate(): void
    {
        $game = new Game();
        $game->setSlug('my-game-slug');

        $hub = $this->createMock(HubInterface::class);
        $hub->expects($this->once())
            ->method('publish')
            ->with($this->callback(function ($update): bool {
                $this->assertSame(['game/my-game-slug'], $update->getTopics());

                $data = json_decode($update->getData(), true, 512, JSON_THROW_ON_ERROR);
                $this->assertSame('gameFrameRefresh', $data['event']);
                $this->assertIsFloat($data['serverTime']);

                return true;
            }));

        $this->createGameService(hub: $hub)->refreshGameFrame($game);
    }

    public function testRefreshGameFrameWithCustomEvent(): void
    {
        $game = new Game();
        $game->setSlug('my-game');

        $hub = $this->createMock(HubInterface::class);
        $hub->expects($this->once())
            ->method('publish')
            ->with($this->callback(function ($update): bool {
                $data = json_decode($update->getData(), true, 512, JSON_THROW_ON_ERROR);
                $this->assertSame('bingoCardRefresh', $data['event']);
                $this->assertIsFloat($data['serverTime']);

                return true;
            }));

        $this->createGameService(hub: $hub)->refreshGameFrame($game, RefreshEvent::BingoCard);
    }

    public function testStartSetsStartedAtAndResetsPauseState(): void
    {
        $game = new Game();
        $game->setSlug('test');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist')->with($game);
        $em->expects($this->once())->method('flush');
        $hub = $this->createMock(HubInterface::class);
        $hub->expects($this->once())->method('publish');

        $this->createGameService($em, $hub)->start($game);

        $this->assertInstanceOf(DateTimeImmutable::class, $game->getStartedAt());
        $this->assertNull($game->getFinishedAt());
        $this->assertFalse($game->isPaused());
        $this->assertSame(0.0, $game->getTotalPausedSeconds());
        $this->assertNull($game->getLastPausedAt());
    }

    public function testStartClearsExistingFinishedAt(): void
    {
        $game = new Game();
        $game->setSlug('test');
        $game->setFinishedAt(new DateTimeImmutable());

        $this->createGameService()->start($game);

        $this->assertNull($game->getFinishedAt());
    }

    public function testEntityToDtoCopiesTeamAndRound(): void
    {
        $team = new Team();
        $round = new GameRound();
        $game = new Game()->setTeam($team)->setRound($round);

        $gameDto = $this->createGameService()->entityToDto($game);

        $this->assertSame($team, $gameDto->team);
        $this->assertSame($round, $gameDto->round);
    }

    public function testCreatePersistsANewGameFromTheDto(): void
    {
        $team = new Team();
        $round = new GameRound();

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist')->with($this->isInstanceOf(Game::class));
        $em->expects($this->once())->method('flush');

        $game = $this->createGameService($em)->create(new GameDto($team, $round));

        $this->assertSame($team, $game->getTeam());
        $this->assertSame($round, $game->getRound());
    }

    public function testUpdateAppliesTheDtoToTheGame(): void
    {
        $game = new Game()->setTeam(new Team())->setRound(new GameRound());
        $team = new Team();
        $round = new GameRound();

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist')->with($game);
        $em->expects($this->once())->method('flush');

        $this->createGameService($em)->update(new GameDto($team, $round), $game);

        $this->assertSame($team, $game->getTeam());
        $this->assertSame($round, $game->getRound());
    }

    public function testStopSetsFinishedAt(): void
    {
        $game = new Game();
        $game->setSlug('test');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist')->with($game);
        $em->expects($this->once())->method('flush');
        $hub = $this->createMock(HubInterface::class);
        $hub->expects($this->once())->method('publish');

        $this->createGameService($em, $hub)->stop($game);

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

        $hub = $this->createMock(HubInterface::class);
        $hub->expects($this->once())->method('publish');

        $removedEntities = [];
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('remove')->willReturnCallback(function ($entity) use (&$removedEntities): void {
            $removedEntities[] = $entity;
        });

        $em->expects($this->once())->method('persist')->with($game);
        $em->method('wrapInTransaction')->willReturnCallback(static function (callable $callable) use ($em): void {
            $callable($em);
        });

        $this->createGameService($em, $hub)->reset($game);

        $this->assertNull($game->getStartedAt());
        $this->assertNull($game->getFinishedAt());
        $this->assertFalse($game->isPaused());
        $this->assertSame(0.0, $game->getTotalPausedSeconds());
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

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('wrapInTransaction')->willReturnCallback(static function (callable $callable) use ($em): void {
            $callable($em);
        });

        $this->createGameService($em)->reset($game);

        $this->assertCount(0, $game->getBingoSquareRevealed());
    }
}
