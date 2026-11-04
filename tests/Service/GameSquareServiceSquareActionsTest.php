<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\BingoSquare;
use App\Entity\GameSquareAction;
use App\Entity\SquareActionType;
use App\Service\GameSquareService;
use App\Tests\Controller\ControllerTestCase;
use DateTimeImmutable;

/**
 * History of the square actions, as stored in the database.
 */
class GameSquareServiceSquareActionsTest extends ControllerTestCase
{
    public function testEveryOccurrenceOfARepeatedActionIsKeptWithItsDate(): void
    {
        $game = $this->createGame()->setStartedAt(new DateTimeImmutable('-5 minutes'));
        $this->em->flush();
        $square = $this->em->getRepository(BingoSquare::class)->findOneBy(['round' => $game->getRound(), 'row' => 1, 'col' => 1]);
        self::assertInstanceOf(BingoSquare::class, $square);
        $squareId = (int) $square->getId();

        $gameSquares = self::getContainer()->get(GameSquareService::class);
        $gameSquares->reveal($game, $squareId);
        $gameSquares->conceal($game, $squareId);
        $gameSquares->reveal($game, $squareId);
        $gameSquares->validate($game, $squareId);
        $gameSquares->invalidate($game, $squareId);
        $gameSquares->validate($game, $squareId);

        $this->em->clear();
        $actions = $this->em->getRepository(GameSquareAction::class)->findBy(['game' => $game->getId()], ['id' => 'ASC']);

        self::assertSame(
            [
                SquareActionType::Reveal,
                SquareActionType::Conceal,
                SquareActionType::Reveal,
                SquareActionType::Validate,
                SquareActionType::Invalidate,
                SquareActionType::Validate,
            ],
            array_map(static fn (GameSquareAction $action): SquareActionType => $action->getType(), $actions),
        );
        foreach ($actions as $action) {
            self::assertSame($squareId, $action->getBingoSquare()->getId());
            self::assertEqualsWithDelta(time(), $action->getOccurredAt()->getTimestamp(), 5);
        }
    }

    public function testAnUnmarkedSquareRecordsNoAction(): void
    {
        $game = $this->createGame()->setStartedAt(new DateTimeImmutable('-5 minutes'));
        $this->em->flush();
        $square = $this->em->getRepository(BingoSquare::class)->findOneBy(['round' => $game->getRound(), 'row' => 1, 'col' => 1]);
        self::assertInstanceOf(BingoSquare::class, $square);

        $gameSquares = self::getContainer()->get(GameSquareService::class);
        $gameSquares->conceal($game, (int) $square->getId());
        $gameSquares->invalidate($game, (int) $square->getId());

        self::assertSame([], $this->em->getRepository(GameSquareAction::class)->findBy(['game' => $game]));
    }
}
