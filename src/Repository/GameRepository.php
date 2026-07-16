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
     * Find games filtered by round, season, team, started status, and ended status
     *
     * @param int|null $round Round number to filter by
     * @param int|null $season Season number to filter by
     * @param int|null $team Team ID to filter by
     * @param bool|null $started Filter by started status (true = started, false = not started)
     * @param bool|null $ended Filter by ended status (true = ended, false = not ended)
     * @return array<string> List of games
     */
    public function findByFilters(
        ?int $round = null,
        ?int $season = null,
        ?int $team = null,
        ?bool $started = null,
        ?bool $ended = null
    ): array {
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

        if ($team !== null) {
            $queryBuilder->andWhere('t.id = :team')
                ->setParameter('team', $team);
        }

        if ($started !== null) {
            if ($started) {
                $queryBuilder->andWhere('g.startedAt IS NOT NULL');
            } else {
                $queryBuilder->andWhere('g.startedAt IS NULL');
            }
        }

        if ($ended !== null) {
            if ($ended) {
                $queryBuilder->andWhere('g.finishedAt IS NOT NULL');
            } else {
                $queryBuilder->andWhere('g.finishedAt IS NULL');
            }
        }

        return $queryBuilder
            ->orderBy('r.number', 'ASC')
            ->addOrderBy('r.season', 'ASC')
            ->addOrderBy('t.name', 'ASC')
            ->getQuery()
            ->getScalarResult();
    }

    /**
     * Find the most recently created games
     *
     * @return Game[]
     */
    public function findRecent(int $limit = 5): array
    {
        return $this->createQueryBuilder('g')
            ->join('g.round', 'r')
            ->join('g.team', 't')
            ->orderBy('g.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
