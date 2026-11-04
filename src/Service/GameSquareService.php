<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\BingoSquare;
use App\Entity\BingoSquareRevealed;
use App\Entity\BingoSquareValidated;
use App\Entity\Game;
use App\Entity\GameSquareAction;
use App\Entity\GameSquareMark;
use App\Entity\SquareActionType;
use App\Repository\BingoSquareRepository;
use App\Repository\BingoSquareRevealedRepository;
use App\Repository\BingoSquareValidatedRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Lock\LockFactory;

/**
 * Squares of a game: revealed and validated, with the dated history of these
 * actions.
 */
readonly class GameSquareService
{
    public function __construct(
        private EntityManagerInterface $em,
        private BingoSquareRepository $bingoSquareRepository,
        private BingoSquareRevealedRepository $bingoSquareRevealedRepository,
        private BingoSquareValidatedRepository $bingoSquareValidatedRepository,
        private GameService $gameService,
        private LockFactory $lockFactory,
    ) {
    }

    public function getRevealedSquare(Game $game, int $squareId): ?BingoSquareRevealed
    {
        return $this->bingoSquareRevealedRepository->findOneBy(['game' => $game, 'bingoSquare' => $squareId]);
    }

    public function getValidatedSquare(Game $game, int $squareId): ?BingoSquareValidated
    {
        return $this->bingoSquareValidatedRepository->findOneBy(['game' => $game, 'bingoSquare' => $squareId]);
    }

    /**
     * The square, only if it belongs to the bingo played in the game.
     */
    private function findGameSquare(Game $game, int $squareId): ?BingoSquare
    {
        return $this->bingoSquareRepository->findOneBy(['id' => $squareId, 'round' => $game->getRound()]);
    }

    /**
     * Ids of the squares revealed in the game, as keys for isset() lookups.
     *
     * @return array<int, int>
     */
    public function getRevealedSquareIds(Game $game): array
    {
        return array_flip($this->bingoSquareRevealedRepository->findSquareIdsByGame($game));
    }

    /**
     * Ids of the squares validated in the game, as keys for isset() lookups.
     *
     * @return array<int, int>
     */
    public function getValidatedSquareIds(Game $game): array
    {
        return array_flip($this->bingoSquareValidatedRepository->findSquareIdsByGame($game));
    }

    public function refreshBingoCard(Game $game): void
    {
        $this->gameService->refreshGameFrame($game, RefreshEvent::BingoCard);
        $this->gameService->refreshGameFrame($game, RefreshEvent::GameSteps);
    }

    public function reveal(Game $game, int $squareId): void
    {
        if ($game->isFinished()) {
            return;
        }

        $this->withSquaresLock($game, function () use ($game, $squareId): void {
            if ($this->getRevealedSquare($game, $squareId) === null) {
                $this->mark($game, $squareId, new BingoSquareRevealed(), SquareActionType::Reveal);
            }
        });
    }

    public function conceal(Game $game, int $squareId): void
    {
        if ($game->isFinished()) {
            return;
        }

        $this->withSquaresLock($game, function () use ($game, $squareId): void {
            $this->unmark($game, $this->getRevealedSquare($game, $squareId), SquareActionType::Conceal);
        });
    }

    public function validate(Game $game, int $squareId): void
    {
        if (!$game->isInProgress()) {
            return;
        }

        $this->withSquaresLock($game, function () use ($game, $squareId): void {
            if ($this->getValidatedSquare($game, $squareId) === null) {
                $this->mark($game, $squareId, new BingoSquareValidated(), SquareActionType::Validate);
            }
        });
    }

    public function invalidate(Game $game, int $squareId): void
    {
        if (!$game->isInProgress()) {
            return;
        }

        $this->withSquaresLock($game, function () use ($game, $squareId): void {
            $this->unmark($game, $this->getValidatedSquare($game, $squareId), SquareActionType::Invalidate);
        });
    }

    /**
     * Runs a square action of the game under a lock, so that simultaneous
     * actions (double click, two referees) are applied one after the other:
     * each one checks the state of the square left by the previous one, and
     * an action is never recorded twice in the history.
     */
    private function withSquaresLock(Game $game, callable $action): void
    {
        $lock = $this->lockFactory->createLock('game-squares-' . $game->getId());
        $lock->acquire(true);

        try {
            $action();
        } finally {
            $lock->release();
        }
    }

    /**
     * Marks the square and records the action, in the same transaction.
     */
    private function mark(Game $game, int $squareId, GameSquareMark $mark, SquareActionType $action): void
    {
        $bingoSquare = $this->findGameSquare($game, $squareId);
        if ($bingoSquare === null) {
            return;
        }

        try {
            $this->em->persist($mark->setGame($game)->setBingoSquare($bingoSquare));
            $this->em->persist(new GameSquareAction($game, $bingoSquare, $action));
            $this->em->flush();
            $this->refreshBingoCard($game);
        } catch (UniqueConstraintViolationException) {
            // Safety net: the lock already prevents a concurrent request from marking it.
        }
    }

    /**
     * Removes the mark and records the action, in the same transaction.
     */
    private function unmark(Game $game, ?GameSquareMark $mark, SquareActionType $action): void
    {
        if ($mark !== null) {
            $this->em->remove($mark);
            $this->em->persist(new GameSquareAction($game, $mark->getBingoSquare(), $action));
            $this->em->flush();
        }
        $this->refreshBingoCard($game);
    }
}
