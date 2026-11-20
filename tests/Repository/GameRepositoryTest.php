<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\Game;
use App\Repository\GameRepository;
use App\Tests\Controller\ControllerTestCase;

class GameRepositoryTest extends ControllerTestCase
{
    private GameRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = self::getContainer()->get(GameRepository::class);
    }

    public function testPreviousRoundGamesAreThoseOfTheSameTeamForTheRoundBeforeInTheSameSeason(): void
    {
        $game = $this->createGame($this->createRound(912, 3));
        $team = $game->getTeam();
        $previous = $this->persistGame(new Game()->setTeam($team)->setRound($this->createRound(912, 2)));
        // Same team, other rounds or other season; other team, same round.
        $this->persistGame(new Game()->setTeam($team)->setRound($this->createRound(912, 1)));
        $this->persistGame(new Game()->setTeam($team)->setRound($this->createRound(913, 2)));
        $this->persistGame(new Game()->setTeam($this->createTeam())->setRound($previous->getRound()));

        self::assertSame([$previous], $this->repository->findPreviousRoundGames($game));
    }

    public function testEveryGameOfTheTeamForADuplicatedPreviousRoundIsFound(): void
    {
        $game = $this->createGame($this->createRound(914, 2));
        $first = $this->persistGame(new Game()->setTeam($game->getTeam())->setRound($this->createRound(914, 1)));
        $second = $this->persistGame(new Game()->setTeam($game->getTeam())->setRound($this->createRound(914, 1)));

        self::assertEqualsCanonicalizing(
            [$first->getId(), $second->getId()],
            array_map(static fn (Game $previous): ?int => $previous->getId(), $this->repository->findPreviousRoundGames($game)),
        );
    }

    public function testThereIsNoPreviousGameWhenTheTeamHasNotPlayedTheRoundBefore(): void
    {
        $game = $this->createGame($this->createRound(915, 2));
        $this->persistGame(new Game()->setTeam($this->createTeam())->setRound($this->createRound(915, 1)));

        self::assertSame([], $this->repository->findPreviousRoundGames($game));
    }

    private function persistGame(Game $game): Game
    {
        $this->em->persist($game);
        $this->em->flush();

        return $game;
    }
}
