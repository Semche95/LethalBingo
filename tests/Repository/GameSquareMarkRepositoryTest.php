<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\Game;
use App\Repository\BingoSquareRevealedRepository;
use App\Repository\BingoSquareValidatedRepository;
use App\Tests\Controller\ControllerTestCase;

class GameSquareMarkRepositoryTest extends ControllerTestCase
{
    public function testFindsTheRevealedSquaresOfTheGameOnly(): void
    {
        $game = $this->createPlayedGame();
        $this->revealSquare($this->createGame(), 3, 3);

        $ids = self::getContainer()->get(BingoSquareRevealedRepository::class)->findSquareIdsByGame($game);

        self::assertEqualsCanonicalizing($this->squareIds($game, [[1, 1], [2, 2]]), $ids);
    }

    public function testFindsTheValidatedSquaresOfTheGame(): void
    {
        $game = $this->createPlayedGame();

        $ids = self::getContainer()->get(BingoSquareValidatedRepository::class)->findSquareIdsByGame($game);

        self::assertEqualsCanonicalizing($this->squareIds($game, [[1, 3], [2, 2]]), $ids);
    }

    public function testFindsNothingForAnUnmarkedGame(): void
    {
        $ids = self::getContainer()->get(BingoSquareRevealedRepository::class)->findSquareIdsByGame($this->createGame());

        self::assertSame([], $ids);
    }

    /**
     * @param list<array{int, int}> $coords
     *
     * @return list<int>
     */
    private function squareIds(Game $game, array $coords): array
    {
        $ids = [];
        foreach ($game->getRound()->getBingoSquares() as $square) {
            if (in_array([$square->getRow(), $square->getCol()], $coords, true)) {
                $ids[] = (int) $square->getId();
            }
        }

        return $ids;
    }
}
