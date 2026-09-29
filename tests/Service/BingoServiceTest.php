<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Dto\BingoDto;
use App\Entity\BingoSquare;
use App\Entity\BingoSquareCategory;
use App\Entity\BingoSquareRevealed;
use App\Entity\BingoSquareValidated;
use App\Entity\Game;
use App\Entity\GameRound;
use App\Repository\BingoSquareRevealedRepository;
use App\Repository\BingoSquareValidatedRepository;
use App\Repository\GameRoundRepository;
use App\Service\BingoService;
use App\Service\GameService;
use App\Service\RefreshEvent;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mercure\HubInterface;

class BingoServiceTest extends TestCase
{
    private BingoService $bingoService;
    private EntityManagerInterface $em;
    private GameRoundRepository $gameRoundRepository;
    private BingoSquareRevealedRepository $revealedRepository;
    private BingoSquareValidatedRepository $validatedRepository;
    private GameService $gameService;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->gameRoundRepository = $this->createMock(GameRoundRepository::class);
        $this->revealedRepository = $this->createMock(BingoSquareRevealedRepository::class);
        $this->validatedRepository = $this->createMock(BingoSquareValidatedRepository::class);

        $hub = $this->createMock(HubInterface::class);
        $this->gameService = new GameService($this->em, $hub);

        $this->bingoService = new BingoService(
            $this->em,
            $this->gameRoundRepository,
            $this->revealedRepository,
            $this->validatedRepository,
            $this->gameService,
        );
    }

    /**
     * @dataProvider visibleSquaresProvider
     */
    public function testIsSquareVisibleReturnsTrueForCrossPattern(int $row, int $col): void
    {
        $this->assertTrue(BingoService::isSquareVisible($row, $col));
    }

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

    /**
     * @dataProvider hiddenSquaresProvider
     */
    public function testIsSquareVisibleReturnsFalseForNonCrossPattern(int $row, int $col): void
    {
        $this->assertFalse(BingoService::isSquareVisible($row, $col));
    }

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
        $game = new Game();
        $squareId = 42;
        $expected = new BingoSquareRevealed();

        $this->revealedRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['game' => $game, 'bingoSquare' => $squareId])
            ->willReturn($expected);

        $result = $this->bingoService->getRevealedSquare($game, $squareId);

        $this->assertSame($expected, $result);
    }

    public function testGetRevealedSquareReturnsNullWhenNotFound(): void
    {
        $game = new Game();

        $this->revealedRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $result = $this->bingoService->getRevealedSquare($game, 1);

        $this->assertNull($result);
    }

    public function testGetValidatedSquareDelegatesToRepository(): void
    {
        $game = new Game();
        $squareId = 42;
        $expected = new BingoSquareValidated();

        $this->validatedRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['game' => $game, 'bingoSquare' => $squareId])
            ->willReturn($expected);

        $result = $this->bingoService->getValidatedSquare($game, $squareId);

        $this->assertSame($expected, $result);
    }

    public function testGetValidatedSquareReturnsNullWhenNotFound(): void
    {
        $game = new Game();

        $this->validatedRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $result = $this->bingoService->getValidatedSquare($game, 1);

        $this->assertNull($result);
    }

    public function testGetRevealedSquareIdsReturnsTheIdsAsKeysFromASingleQuery(): void
    {
        $game = new Game();

        $this->revealedRepository->expects($this->once())
            ->method('findSquareIdsByGame')
            ->with($game)
            ->willReturn([4, 9]);

        $ids = $this->bingoService->getRevealedSquareIds($game);

        $this->assertTrue(isset($ids[4], $ids[9]));
        $this->assertFalse(isset($ids[5]));
    }

    public function testGetValidatedSquareIdsReturnsTheIdsAsKeysFromASingleQuery(): void
    {
        $game = new Game();

        $this->validatedRepository->expects($this->once())
            ->method('findSquareIdsByGame')
            ->with($game)
            ->willReturn([7]);

        $ids = $this->bingoService->getValidatedSquareIds($game);

        $this->assertTrue(isset($ids[7]));
        $this->assertFalse(isset($ids[4]));
    }

    public function testEntityToDtoConvertsRoundToDto(): void
    {
        $round = new GameRound();
        $round->setSeason(2)->setNumber(3);

        $category = $this->createConfiguredMock(BingoSquareCategory::class, [
            'getId' => 1,
        ]);

        $square = new BingoSquare();
        $square->setRow(1)->setCol(1)->setLabel('Test')->setVisible(true)->setCategory($category);
        $round->addBingoSquare($square);

        $dto = $this->bingoService->entityToDto($round);

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

        $category = $this->createConfiguredMock(BingoSquareCategory::class, [
            'getId' => 1,
        ]);

        $square = new BingoSquare();
        $square->setRow(2)->setCol(3)->setLabel('Hidden')->setVisible(false)->setCategory($category);
        $round->addBingoSquare($square);

        $dto = $this->bingoService->entityToDto($round);

        $this->assertSame(0, $dto->squares[2][3]['visible']);
    }

    public function testEntityToDtoReturnsEmptySquaresForEmptyRound(): void
    {
        $round = new GameRound();
        $round->setSeason(1)->setNumber(1);

        $dto = $this->bingoService->entityToDto($round);

        $this->assertSame([], $dto->squares);
    }

    public function testCreateEmptyRoundPersistsAndFlushes(): void
    {
        $this->em->expects($this->once())->method('persist')->with($this->isInstanceOf(GameRound::class));
        $this->em->expects($this->once())->method('flush');

        $result = $this->bingoService->createEmptyRound(2, 5);

        $this->assertSame(2, $result->getSeason());
        $this->assertSame(5, $result->getNumber());
    }

    public function testRefreshBingoCardDispatchesBothEvents(): void
    {
        $game = new Game();
        $game->setSlug('test-slug');

        $hub = $this->createMock(HubInterface::class);
        $hub->expects($this->exactly(2))->method('publish');
        $gameService = new GameService($this->em, $hub);
        $bingoService = new BingoService(
            $this->em,
            $this->gameRoundRepository,
            $this->revealedRepository,
            $this->validatedRepository,
            $gameService,
        );

        $bingoService->refreshBingoCard($game);
    }

    public function testRevealCreatesBingoSquareRevealedAndFlushes(): void
    {
        $game = new Game();
        $game->setSlug('test-slug');
        $squareId = 42;

        $squareReference = new BingoSquare();

        $this->em->expects($this->atLeastOnce())
            ->method('getReference')
            ->with(BingoSquare::class, $squareId)
            ->willReturn($squareReference);

        $persisted = [];
        $this->em->expects($this->once())
            ->method('persist')
            ->willReturnCallback(function ($entity) use (&$persisted): void {
                $persisted[] = $entity;
            });

        $this->em->expects($this->once())->method('flush');

        $this->bingoService->reveal($game, $squareId);

        $this->assertCount(1, $persisted);
        $this->assertInstanceOf(BingoSquareRevealed::class, $persisted[0]);
    }

    public function testConcealRemovesBingoSquareRevealedWhenFound(): void
    {
        $game = new Game();
        $game->setSlug('test-slug');
        $squareId = 42;

        $revealedSquare = new BingoSquareRevealed();

        $this->revealedRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($revealedSquare);

        $removed = [];
        $this->em->expects($this->once())
            ->method('remove')
            ->willReturnCallback(function ($entity) use (&$removed): void {
                $removed[] = $entity;
            });

        $this->em->expects($this->once())->method('flush');

        $this->bingoService->conceal($game, $squareId);

        $this->assertSame([$revealedSquare], $removed);
    }

    public function testConcealDoesNotRemoveWhenNotFound(): void
    {
        $game = new Game();
        $game->setSlug('test-slug');

        $this->revealedRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $this->em->expects($this->never())->method('remove');
        $this->em->expects($this->never())->method('flush');

        $this->bingoService->conceal($game, 1);
    }

    public function testValidateCreatesBingoSquareValidatedAndFlushes(): void
    {
        $game = new Game();
        $game->setSlug('test-slug');
        $squareId = 10;

        $squareReference = new BingoSquare();

        $this->em->expects($this->once())
            ->method('getReference')
            ->with(BingoSquare::class, $squareId)
            ->willReturn($squareReference);

        $persisted = [];
        $this->em->expects($this->once())
            ->method('persist')
            ->willReturnCallback(function ($entity) use (&$persisted): void {
                $persisted[] = $entity;
            });

        $this->em->expects($this->once())->method('flush');

        $this->bingoService->validate($game, $squareId);

        $this->assertCount(1, $persisted);
        $this->assertInstanceOf(BingoSquareValidated::class, $persisted[0]);
    }

    public function testInvalidateRemovesBingoSquareValidatedWhenFound(): void
    {
        $game = new Game();
        $game->setSlug('test-slug');
        $squareId = 10;

        $validatedSquare = new BingoSquareValidated();

        $this->validatedRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($validatedSquare);

        $removed = [];
        $this->em->expects($this->once())
            ->method('remove')
            ->willReturnCallback(function ($entity) use (&$removed): void {
                $removed[] = $entity;
            });

        $this->em->expects($this->once())->method('flush');

        $this->bingoService->invalidate($game, $squareId);

        $this->assertSame([$validatedSquare], $removed);
    }

    public function testInvalidateDoesNotRemoveWhenNotFound(): void
    {
        $game = new Game();
        $game->setSlug('test-slug');

        $this->validatedRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $this->em->expects($this->never())->method('remove');
        $this->em->expects($this->never())->method('flush');

        $this->bingoService->invalidate($game, 1);
    }
}
