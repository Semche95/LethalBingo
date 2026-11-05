<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\BingoSquare;
use App\Entity\Game;
use App\Entity\GameSquareAction;
use App\Entity\SquareActionType;
use App\Repository\GameSquareActionRepository;
use App\Tests\Controller\ControllerTestCase;
use DateTimeImmutable;

class GameSquareActionRepositoryTest extends ControllerTestCase
{
    public function testFindsTheActionsOfTheGameFromTheOldestToTheNewest(): void
    {
        $game = $this->createGame();
        $sameDate = new DateTimeImmutable('-1 minute');
        $newest = $this->createAction($game, 1, SquareActionType::Validate, new DateTimeImmutable());
        $oldest = $this->createAction($game, 1, SquareActionType::Reveal, new DateTimeImmutable('-10 minutes'));
        $first = $this->createAction($game, 2, SquareActionType::Reveal, $sameDate);
        $second = $this->createAction($game, 2, SquareActionType::Conceal, $sameDate);
        $this->createAction($this->createGame(), 1, SquareActionType::Reveal, new DateTimeImmutable('-5 minutes'));
        $this->em->clear();

        $actions = self::getContainer()->get(GameSquareActionRepository::class)->findByGameWithSquare($game);

        self::assertSame(
            [$oldest->getId(), $first->getId(), $second->getId(), $newest->getId()],
            array_map(static fn (GameSquareAction $action): ?int => $action->getId(), $actions),
            'Actions done in the same second keep the order in which they were recorded.',
        );
    }

    public function testFindsNothingForAGameWithoutAction(): void
    {
        $actions = self::getContainer()->get(GameSquareActionRepository::class)->findByGameWithSquare($this->createGame());

        self::assertSame([], $actions);
    }

    private function createAction(Game $game, int $col, SquareActionType $type, DateTimeImmutable $occurredAt): GameSquareAction
    {
        $square = $this->em->getRepository(BingoSquare::class)->findOneBy(['round' => $game->getRound(), 'row' => 1, 'col' => $col]);
        $action = new GameSquareAction($game, $square, $type)->setOccurredAt($occurredAt);
        $this->em->persist($action);
        $this->em->flush();

        return $action;
    }
}
