<?php

namespace App\Repository;

use App\Entity\Game;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Game>
 */
class GameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Game::class);
    }

    /**
     * Find all games sorted by round, season, then team
     *
     * @return Game[]
     */
    public function findAllSorted(): array
    {
        return $this->createQueryBuilder('g')
            ->join('g.round', 'r')
            ->join('g.team', 't')
            ->orderBy('r.number', 'ASC')
            ->addOrderBy('r.season', 'ASC')
            ->addOrderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find games filtered by round and/or season
     *
     * @param int|null $round Round number to filter by
     * @param int|null $season Season number to filter by
     * @return array<string> List of games
     */
    public function findByRoundAndSeason(?int $round = null, ?int $season = null): array
    {
        $queryBuilder = $this->createQueryBuilder('g')
            ->select('g.id, r.number as round_number, r.season as round_season, t.name as team_name, g.startedAt, g.finishedAt')
            ->join('g.round', 'r')
            ->join('g.team', 't');

        if ($round !== null) {
            $queryBuilder->andWhere('r.number = :round')
                ->setParameter('round', $round);
        }

        if ($season !== null) {
            $queryBuilder->andWhere('r.season = :season')
                ->setParameter('season', $season);
        }

        return $queryBuilder
            ->orderBy('r.number', 'ASC')
            ->addOrderBy('r.season', 'ASC')
            ->addOrderBy('t.name', 'ASC')
            ->getQuery()
            ->getScalarResult();
    }
}
