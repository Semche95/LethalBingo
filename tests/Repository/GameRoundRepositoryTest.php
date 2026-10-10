<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\GameRound;
use App\Repository\GameRoundRepository;
use App\Tests\Controller\ControllerTestCase;

class GameRoundRepositoryTest extends ControllerTestCase
{
    private GameRoundRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = self::getContainer()->get(GameRoundRepository::class);
    }

    public function testSeasonQueries(): void
    {
        $this->createRound(901, 1);
        $this->createRound(901, 4);
        $this->createRound(902, 2);

        self::assertSame(4, $this->repository->findMaxNumberForSeason(901));
        self::assertSame(902, $this->repository->findMaxSeason());
        self::assertEqualsCanonicalizing([1, 4], $this->repository->findNumbersForSeason(901));
    }

    public function testEmptySeason(): void
    {
        self::assertSame(0, $this->repository->findMaxNumberForSeason(999));
        self::assertSame([], $this->repository->findNumbersForSeason(999));
    }

    public function testBlankLabelCountsAsIncomplete(): void
    {
        $round = $this->createRound(903, 1, complete: true);
        $round->getBingoSquares()->first()->setLabel('   ');
        $this->em->flush();

        $completeness = array_column($this->repository->findSquareCompleteness(), null, 'round_id');

        self::assertSame(GameRound::EXPECTED_SQUARES, (int) $completeness[$round->getId()]['square_count']);
        self::assertSame(1, (int) $completeness[$round->getId()]['incomplete_count']);
        self::assertTrue($round->hasIncompleteContent());
    }
}
