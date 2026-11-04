<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\BingoSquare;
use App\Entity\BingoSquareCategory;
use App\Entity\BingoSquareRevealed;
use App\Entity\BingoSquareValidated;
use App\Entity\Game;
use App\Entity\GameRound;
use App\Entity\GameSquareAction;
use App\Entity\SquareActionType;
use App\Repository\BingoSquareRepository;
use App\Repository\BingoSquareRevealedRepository;
use App\Repository\BingoSquareValidatedRepository;
use App\Repository\GameRoundRepository;
use App\Service\BingoService;
use App\Service\GameService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\InMemoryStore;
use Symfony\Component\Mercure\HubInterface;

class BingoServiceTest extends TestCase
{
    /**
     * Builds the service, replacing every collaborator not given by a stub.
     */
    private function createBingoService(
        ?EntityManagerInterface $em = null,
        ?BingoSquareRevealedRepository $revealedRepository = null,
        ?BingoSquareValidatedRepository $validatedRepository = null,
        ?HubInterface $hub = null,
        ?BingoSquareRepository $squareRepository = null,
        ?LockFactory $lockFactory = null,
    ): BingoService {
        $em ??= $this->createStub(EntityManagerInterface::class);

        return new BingoService(
            $em,
            $this->createStub(GameRoundRepository::class),
            $squareRepository ?? $this->createStub(BingoSquareRepository::class),
            $revealedRepository ?? $this->createStub(BingoSquareRevealedRepository::class),
            $validatedRepository ?? $this->createStub(BingoSquareValidatedRepository::class),
            new GameService($em, $hub ?? $this->createStub(HubInterface::class)),
            $lockFactory ?? new LockFactory(new InMemoryStore()),
        );
    }

    #[DataProvider('visibleSquaresProvider')]
    public function testIsSquareVisibleReturnsTrueForCrossPattern(int $row, int $col): void
    {
        $this->assertTrue(BingoService::isSquareVisible($row, $col));
    }

    /**
     * @return array<string, array{int, int}>
     */
    public static function visibleSquaresProvider(): array
    {
        return [
            'row 1 col 3' => [1, 3],
            'row 2 col 4' => [2, 4],
            'row 3 col 1' => [3, 1],
            'row 3 col 3' => [3, 3],
            'row 3 col 5' => [3, 5],
            'row 4 col 2' => [4, 2],
            'row 5 col 3' => [5, 3],
        ];
    }

    #[DataProvider('hiddenSquaresProvider')]
    public function testIsSquareVisibleReturnsFalseForNonCrossPattern(int $row, int $col): void
    {
        $this->assertFalse(BingoService::isSquareVisible($row, $col));
    }

    /**
     * @return array<string, array{int, int}>
     */
    public static function hiddenSquaresProvider(): array
    {
        return [
            'row 1 col 1' => [1, 1],
            'row 1 col 2' => [1, 2],
            'row 2 col 1' => [2, 1],
            'row 2 col 2' => [2, 2],
            'row 4 col 1' => [4, 1],
            'row 5 col 1' => [5, 1],
            'row 5 col 5' => [5, 5],
        ];
    }

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

        $result = $this->createBingoService(revealedRepository: $revealedRepository)->getRevealedSquare($game, $squareId);

        $this->assertSame($expected, $result);
    }

    public function testGetRevealedSquareReturnsNullWhenNotFound(): void
    {
        $revealedRepository = $this->createMock(BingoSquareRevealedRepository::class);

        $game = new Game();

        $revealedRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $result = $this->createBingoService(revealedRepository: $revealedRepository)->getRevealedSquare($game, 1);

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

        $result = $this->createBingoService(validatedRepository: $validatedRepository)->getValidatedSquare($game, $squareId);

        $this->assertSame($expected, $result);
    }

    public function testGetValidatedSquareReturnsNullWhenNotFound(): void
    {
        $validatedRepository = $this->createMock(BingoSquareValidatedRepository::class);

        $game = new Game();

        $validatedRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $result = $this->createBingoService(validatedRepository: $validatedRepository)->getValidatedSquare($game, 1);

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

        $ids = $this->createBingoService(revealedRepository: $revealedRepository)->getRevealedSquareIds($game);

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

        $ids = $this->createBingoService(validatedRepository: $validatedRepository)->getValidatedSquareIds($game);

        $this->assertTrue(isset($ids[7]));
        $this->assertFalse(isset($ids[4]));
    }

    public function testEntityToDtoConvertsRoundToDto(): void
    {
        $round = new GameRound();
        $round->setSeason(2)->setNumber(3);

        $category = $this->createConfiguredStub(BingoSquareCategory::class, [
            'getId' => 1,
        ]);

        $square = new BingoSquare();
        $square->setRow(1)->setCol(1)->setLabel('Test')->setVisible(true)->setCategory($category);
        $round->addBingoSquare($square);

        $dto = $this->createBingoService()->entityToDto($round);

        $this->assertSame(2, $dto->season);
        $this->assertSame(3, $dto->round);
        $this->assertArrayHasKey(1, $dto->squares);
        $this->assertArrayHasKey(1, $dto->squares[1]);
        $this->assertNull($dto->squares[1][1]['id']);
        $this->assertSame(1, $dto->squares[1][1]['category']);
        $this->assertSame('Test', $dto->squares[1][1]['label']);
        $this->assertSame(1, $dto->squares[1][1]['visible']);
    }

    public function testEntityToDtoEncodesInvisibleAsZero(): void
    {
        $round = new GameRound();
        $round->setSeason(1)->setNumber(1);

        $category = $this->createConfiguredStub(BingoSquareCategory::class, [
            'getId' => 1,
        ]);

        $square = new BingoSquare();
        $square->setRow(2)->setCol(3)->setLabel('Hidden')->setVisible(false)->setCategory($category);
        $round->addBingoSquare($square);

        $dto = $this->createBingoService()->entityToDto($round);

        $this->assertSame(0, $dto->squares[2][3]['visible']);
    }

    public function testEntityToDtoReturnsEmptySquaresForEmptyRound(): void
    {
        $round = new GameRound();
        $round->setSeason(1)->setNumber(1);

        $dto = $this->createBingoService()->entityToDto($round);

        $this->assertSame([], $dto->squares);
    }

    public function testCreateEmptyRoundPersistsAndFlushes(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist')->with($this->isInstanceOf(GameRound::class));
        $em->expects($this->once())->method('flush');

        $result = $this->createBingoService(em: $em)->createEmptyRound(2, 5);

        $this->assertSame(2, $result->getSeason());
        $this->assertSame(5, $result->getNumber());
    }

    public function testRefreshBingoCardDispatchesBothEvents(): void
    {
        $game = new Game();
        $game->setSlug('test-slug');

        $hub = $this->createMock(HubInterface::class);
        $hub->expects($this->exactly(2))->method('publish');

        $this->createBingoService(hub: $hub)->refreshBingoCard($game);
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

        $this->createBingoService(em: $em, squareRepository: $squareRepository)->reveal($game, $squareId);

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

        $this->createBingoService(em: $em, revealedRepository: $revealedRepository)->conceal($game, $squareId);

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

        $this->createBingoService(em: $em, revealedRepository: $revealedRepository)->conceal($game, 1);
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

        $this->createBingoService(em: $em, squareRepository: $squareRepository)->validate($game, $squareId);

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

        $this->createBingoService(em: $em, validatedRepository: $validatedRepository)->invalidate($game, $squareId);

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

        $this->createBingoService(em: $em, validatedRepository: $validatedRepository)->invalidate($game, 1);
    }

    public function testSquaresCannotBeRevealedOrConcealedOnceTheGameIsFinished(): void
    {
        $game = new Game()->setStartedAt(new DateTimeImmutable())->setFinishedAt(new DateTimeImmutable());

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('flush');

        $bingoService = $this->createBingoService(em: $em);
        $bingoService->reveal($game, 1);
        $bingoService->conceal($game, 1);
    }

    /**
     * @return iterable<string, array{Game}>
     */
    public static function gamesNotInProgressProvider(): iterable
    {
        yield 'not started' => [new Game()];
        yield 'finished' => [new Game()->setStartedAt(new DateTimeImmutable())->setFinishedAt(new DateTimeImmutable())];
    }

    #[DataProvider('gamesNotInProgressProvider')]
    public function testSquaresCanOnlyBeValidatedOrInvalidatedWhileTheGameIsInProgress(Game $game): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('flush');

        $bingoService = $this->createBingoService(em: $em);
        $bingoService->validate($game, 1);
        $bingoService->invalidate($game, 1);
    }

    public function testSquaresOfAnotherBingoCannotBeRevealedOrValidated(): void
    {
        $game = new Game()->setRound(new GameRound())->setStartedAt(new DateTimeImmutable());

        $squareRepository = $this->createStub(BingoSquareRepository::class);
        $squareRepository->method('findOneBy')->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('persist');

        $bingoService = $this->createBingoService(em: $em, squareRepository: $squareRepository);
        $bingoService->reveal($game, 1);
        $bingoService->validate($game, 1);
    }

    private function assertAction(SquareActionType $type, Game $game, BingoSquare $square, object $action): void
    {
        $this->assertInstanceOf(GameSquareAction::class, $action);
        $this->assertSame($type, $action->getType());
        $this->assertSame($game, $action->getGame());
        $this->assertSame($square, $action->getBingoSquare());
    }

    public function testAnAlreadyMarkedSquareIsNotMarkedNorRecordedAgain(): void
    {
        $game = new Game()->setRound(new GameRound())->setStartedAt(new DateTimeImmutable());

        $revealedRepository = $this->createStub(BingoSquareRevealedRepository::class);
        $revealedRepository->method('findOneBy')->willReturn(new BingoSquareRevealed());
        $validatedRepository = $this->createStub(BingoSquareValidatedRepository::class);
        $validatedRepository->method('findOneBy')->willReturn(new BingoSquareValidated());

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('persist');
        $em->expects($this->never())->method('flush');

        $bingoService = $this->createBingoService(em: $em, revealedRepository: $revealedRepository, validatedRepository: $validatedRepository);
        $bingoService->reveal($game, 1);
        $bingoService->validate($game, 1);
    }

    public function testTheLockOfTheGameIsReleasedAfterEachAction(): void
    {
        $game = new Game()->setRound(new GameRound())->setStartedAt(new DateTimeImmutable());
        $lockFactory = new LockFactory(new InMemoryStore());

        $bingoService = $this->createBingoService(lockFactory: $lockFactory);
        $bingoService->reveal($game, 1);
        $bingoService->conceal($game, 1);
        $bingoService->validate($game, 1);
        $bingoService->invalidate($game, 1);

        $this->assertTrue($lockFactory->createLock('game-squares-' . $game->getId())->acquire());
    }
}
