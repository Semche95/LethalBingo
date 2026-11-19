<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\Game;
use App\Repository\TeamRepository;
use App\Tests\Controller\ControllerTestCase;

class TeamRepositoryTest extends ControllerTestCase
{
    private TeamRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = self::getContainer()->get(TeamRepository::class);
    }

    public function testPlayedRoundIdsAreIndexedByTeam(): void
    {
        $game = $this->createGame($this->createRound(911, 1, complete: true));
        $team = $game->getTeam();
        $otherRound = $this->createRound(911, 2, complete: true);
        $this->em->persist(new Game()->setTeam($team)->setRound($otherRound));
        $this->em->flush();

        $playedRoundIds = $this->repository->findPlayedRoundIdsIndexedById();

        self::assertSame($team->getId(), $playedRoundIds[$team->getId()]['team_id']);
        self::assertEqualsCanonicalizing(
            [$game->getRound()->getId(), $otherRound->getId()],
            json_decode($playedRoundIds[$team->getId()]['round_ids'], true, 512, \JSON_THROW_ON_ERROR),
        );
    }

    public function testTheExceptedGameIsLeftOut(): void
    {
        $game = $this->createGame();

        $playedRoundIds = $this->repository->findPlayedRoundIdsIndexedById($game);

        self::assertArrayNotHasKey($game->getTeam()->getId(), $playedRoundIds);
    }
}
