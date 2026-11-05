<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\BingoSquare;
use App\Entity\BingoSquareRevealed;
use App\Entity\BingoSquareValidated;
use App\Entity\Game;
use App\Entity\GameRound;
use App\Entity\GameSquareAction;
use App\Entity\SquareActionType;
use App\Repository\BingoSquareRepository;
use App\Repository\BingoSquareRevealedRepository;
use App\Repository\BingoSquareValidatedRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mercure\HubInterface;

class GameSquareServiceTest extends TestCase
{
    use GameSquareServiceFactory;

    public function testGetRevealedSquareDelegatesToRepository(): void
    {
        $revealedRepository = $this->createMock(BingoSquareRevealedRepository::class);

        $game = new Game();
        $squareId = 42;
        $expected = new BingoSquareRevealed();

        $revealedRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['game' => $game, 'bingoSquare' => $squareId])
            ->willReturn($expected);

        $result = $this->createGameSquareService(revealedRepository: $revealedRepository)->getRevealedSquare($game, $squareId);

        $this->assertSame($expected, $result);
    }

    public function testGetRevealedSquareReturnsNullWhenNotFound(): void
    {
        $revealedRepository = $this->createMock(BingoSquareRevealedRepository::class);

        $game = new Game();

        $revealedRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $result = $this->createGameSquareService(revealedRepository: $revealedRepository)->getRevealedSquare($game, 1);

        $this->assertNull($result);
    }

    public function testGetValidatedSquareDelegatesToRepository(): void
    {
        $validatedRepository = $this->createMock(BingoSquareValidatedRepository::class);

        $game = new Game();
        $squareId = 42;
        $expected = new BingoSquareValidated();

        $validatedRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['game' => $game, 'bingoSquare' => $squareId])
            ->willReturn($expected);

        $result = $this->createGameSquareService(validatedRepository: $validatedRepository)->getValidatedSquare($game, $squareId);

        $this->assertSame($expected, $result);
    }

    public function testGetValidatedSquareReturnsNullWhenNotFound(): void
    {
        $validatedRepository = $this->createMock(BingoSquareValidatedRepository::class);

        $game = new Game();

        $validatedRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $result = $this->createGameSquareService(validatedRepository: $validatedRepository)->getValidatedSquare($game, 1);

        $this->assertNull($result);
    }

    public function testGetRevealedSquareIdsReturnsTheIdsAsKeysFromASingleQuery(): void
    {
        $revealedRepository = $this->createMock(BingoSquareRevealedRepository::class);

        $game = new Game();

        $revealedRepository->expects($this->once())
            ->method('findSquareIdsByGame')
            ->with($game)
            ->willReturn([4, 9]);

        $ids = $this->createGameSquareService(revealedRepository: $revealedRepository)->getRevealedSquareIds($game);

        $this->assertTrue(isset($ids[4], $ids[9]));
        $this->assertFalse(isset($ids[5]));
    }

    public function testGetValidatedSquareIdsReturnsTheIdsAsKeysFromASingleQuery(): void
    {
        $validatedRepository = $this->createMock(BingoSquareValidatedRepository::class);

        $game = new Game();

        $validatedRepository->expects($this->once())
            ->method('findSquareIdsByGame')
            ->with($game)
            ->willReturn([7]);

        $ids = $this->createGameSquareService(validatedRepository: $validatedRepository)->getValidatedSquareIds($game);

        $this->assertTrue(isset($ids[7]));
        $this->assertFalse(isset($ids[4]));
    }

    public function testRefreshBingoCardDispatchesBothEvents(): void
    {
        $game = new Game();
        $game->setSlug('test-slug');

        $hub = $this->createMock(HubInterface::class);
        $hub->expects($this->exactly(2))->method('publish');

        $this->createGameSquareService(hub: $hub)->refreshBingoCard($game);
    }

    public function testRevealCreatesBingoSquareRevealedAndRecordsTheAction(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);

        $game = new Game();
        $game->setSlug('test-slug');
        $squareId = 42;

        $round = new GameRound();
        $game->setRound($round);
        $squareRepository = $this->createMock(BingoSquareRepository::class);
        $squareRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['id' => $squareId, 'round' => $round])
            ->willReturn($square = new BingoSquare());

        $persisted = [];
        $em->expects($this->exactly(2))
            ->method('persist')
            ->willReturnCallback(function ($entity) use (&$persisted): void {
                $persisted[] = $entity;
            });

        $em->expects($this->once())->method('flush');

        $this->createGameSquareService(em: $em, squareRepository: $squareRepository)->reveal($game, $squareId);

        $this->assertCount(2, $persisted);
        $this->assertInstanceOf(BingoSquareRevealed::class, $persisted[0]);
        $this->assertAction(SquareActionType::Reveal, $game, $square, $persisted[1]);
    }

    public function testConcealRemovesBingoSquareRevealedAndRecordsTheActionWhenFound(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $revealedRepository = $this->createMock(BingoSquareRevealedRepository::class);

        $game = new Game();
        $game->setSlug('test-slug');
        $squareId = 42;

        $revealedSquare = new BingoSquareRevealed()->setBingoSquare($square = new BingoSquare());

        $revealedRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($revealedSquare);

        $removed = [];
        $em->expects($this->once())
            ->method('remove')
            ->willReturnCallback(function ($entity) use (&$removed): void {
                $removed[] = $entity;
            });

        $persisted = [];
        $em->expects($this->once())
            ->method('persist')
            ->willReturnCallback(function ($entity) use (&$persisted): void {
                $persisted[] = $entity;
            });

        $em->expects($this->once())->method('flush');

        $this->createGameSquareService(em: $em, revealedRepository: $revealedRepository)->conceal($game, $squareId);

        $this->assertSame([$revealedSquare], $removed);
        $this->assertCount(1, $persisted);
        $this->assertAction(SquareActionType::Conceal, $game, $square, $persisted[0]);
    }

    public function testConcealDoesNotRemoveWhenNotFound(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $revealedRepository = $this->createMock(BingoSquareRevealedRepository::class);

        $game = new Game();
        $game->setSlug('test-slug');

        $revealedRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $em->expects($this->never())->method('remove');
        $em->expects($this->never())->method('persist');
        $em->expects($this->never())->method('flush');

        $this->createGameSquareService(em: $em, revealedRepository: $revealedRepository)->conceal($game, 1);
    }

    public function testValidateCreatesBingoSquareValidatedAndRecordsTheAction(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);

        $game = new Game();
        $game->setSlug('test-slug');
        $game->setStartedAt(new DateTimeImmutable());
        $squareId = 10;

        $round = new GameRound();
        $game->setRound($round);
        $squareRepository = $this->createMock(BingoSquareRepository::class);
        $squareRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['id' => $squareId, 'round' => $round])
            ->willReturn($square = new BingoSquare());

        $persisted = [];
        $em->expects($this->exactly(2))
            ->method('persist')
            ->willReturnCallback(function ($entity) use (&$persisted): void {
                $persisted[] = $entity;
            });

        $em->expects($this->once())->method('flush');

        $this->createGameSquareService(em: $em, squareRepository: $squareRepository)->validate($game, $squareId);

        $this->assertCount(2, $persisted);
        $this->assertInstanceOf(BingoSquareValidated::class, $persisted[0]);
        $this->assertAction(SquareActionType::Validate, $game, $square, $persisted[1]);
    }

    public function testInvalidateRemovesBingoSquareValidatedAndRecordsTheActionWhenFound(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $validatedRepository = $this->createMock(BingoSquareValidatedRepository::class);

        $game = new Game();
        $game->setSlug('test-slug');
        $game->setStartedAt(new DateTimeImmutable());
        $squareId = 10;

        $validatedSquare = new BingoSquareValidated()->setBingoSquare($square = new BingoSquare());

        $validatedRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($validatedSquare);

        $removed = [];
        $em->expects($this->once())
            ->method('remove')
            ->willReturnCallback(function ($entity) use (&$removed): void {
                $removed[] = $entity;
            });

        $persisted = [];
        $em->expects($this->once())
            ->method('persist')
            ->willReturnCallback(function ($entity) use (&$persisted): void {
                $persisted[] = $entity;
            });

        $em->expects($this->once())->method('flush');

        $this->createGameSquareService(em: $em, validatedRepository: $validatedRepository)->invalidate($game, $squareId);

        $this->assertSame([$validatedSquare], $removed);
        $this->assertCount(1, $persisted);
        $this->assertAction(SquareActionType::Invalidate, $game, $square, $persisted[0]);
    }

    public function testInvalidateDoesNotRemoveWhenNotFound(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $validatedRepository = $this->createMock(BingoSquareValidatedRepository::class);

        $game = new Game();
        $game->setSlug('test-slug');
        $game->setStartedAt(new DateTimeImmutable());

        $validatedRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $em->expects($this->never())->method('remove');
        $em->expects($this->never())->method('persist');
        $em->expects($this->never())->method('flush');

        $this->createGameSquareService(em: $em, validatedRepository: $validatedRepository)->invalidate($game, 1);
    }

    private function assertAction(SquareActionType $type, Game $game, BingoSquare $square, object $action): void
    {
        $this->assertInstanceOf(GameSquareAction::class, $action);
        $this->assertSame($type, $action->getType());
        $this->assertSame($game, $action->getGame());
        $this->assertSame($square, $action->getBingoSquare());
    }
}
