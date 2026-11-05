<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\BingoSquare;
use App\Entity\BingoSquareRevealed;
use App\Entity\BingoSquareValidated;
use App\Entity\Game;
use App\Entity\GameRound;
use App\Repository\BingoSquareRepository;
use App\Repository\BingoSquareRevealedRepository;
use App\Repository\BingoSquareValidatedRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\InMemoryStore;

/**
 * When the square actions are refused or applied, depending on the game.
 */
class GameSquareServiceRulesTest extends TestCase
{
    use GameSquareServiceFactory;

    public function testSquaresCannotBeRevealedOrConcealedOnceTheGameIsFinished(): void
    {
        $game = new Game()->setStartedAt(new DateTimeImmutable())->setFinishedAt(new DateTimeImmutable());

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('flush');

        $gameSquares = $this->createGameSquareService(em: $em);
        $gameSquares->reveal($game, 1);
        $gameSquares->conceal($game, 1);
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

        $gameSquares = $this->createGameSquareService(em: $em);
        $gameSquares->validate($game, 1);
        $gameSquares->invalidate($game, 1);
    }

    public function testSquaresCanBeRevealedValidatedAndInvalidatedOnceTheTimeIsUp(): void
    {
        $game = new Game()
            ->setRound(new GameRound())
            ->resetTimer(new DateTimeImmutable('-2 hours'), new DateTimeImmutable('-1 hour'));

        $squareRepository = $this->createStub(BingoSquareRepository::class);
        $squareRepository->method('findOneBy')->willReturn(new BingoSquare());
        $validatedRepository = $this->createStub(BingoSquareValidatedRepository::class);
        $validatedRepository->method('findOneBy')->willReturnOnConsecutiveCalls(null, new BingoSquareValidated()->setBingoSquare(new BingoSquare()));

        $em = $this->createMock(EntityManagerInterface::class);
        // Revealed and validated square plus their action, then the invalidate action.
        $em->expects($this->exactly(5))->method('persist');
        $em->expects($this->once())->method('remove');

        $gameSquares = $this->createGameSquareService(em: $em, validatedRepository: $validatedRepository, squareRepository: $squareRepository);
        $gameSquares->reveal($game, 1);
        $gameSquares->validate($game, 1);
        $gameSquares->invalidate($game, 1);
    }

    public function testSquaresOfAnotherBingoCannotBeRevealedOrValidated(): void
    {
        $game = new Game()->setRound(new GameRound())->setStartedAt(new DateTimeImmutable());

        $squareRepository = $this->createStub(BingoSquareRepository::class);
        $squareRepository->method('findOneBy')->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('persist');

        $gameSquares = $this->createGameSquareService(em: $em, squareRepository: $squareRepository);
        $gameSquares->reveal($game, 1);
        $gameSquares->validate($game, 1);
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

        $gameSquares = $this->createGameSquareService(em: $em, revealedRepository: $revealedRepository, validatedRepository: $validatedRepository);
        $gameSquares->reveal($game, 1);
        $gameSquares->validate($game, 1);
    }

    public function testTheLockOfTheGameIsReleasedAfterEachAction(): void
    {
        $game = new Game()->setRound(new GameRound())->setStartedAt(new DateTimeImmutable());
        $lockFactory = new LockFactory(new InMemoryStore());

        $gameSquares = $this->createGameSquareService(lockFactory: $lockFactory);
        $gameSquares->reveal($game, 1);
        $gameSquares->conceal($game, 1);
        $gameSquares->validate($game, 1);
        $gameSquares->invalidate($game, 1);

        $this->assertTrue($lockFactory->createLock('game-squares-' . $game->getId())->acquire());
    }
}
