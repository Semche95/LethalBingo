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
     * All existing games with their round and team info for the grid.
     * Only returns rows where a game exists — seasons, rounds and teams
     * are all derived from games, never from game_round or team directly.
     *
     * @return list<array{season: int, round_number: int, team_id: int, team_name: string, game_id: int, startedAt: ?string, finishedAt: ?string}>
     */
    public function findGamesForGrid(): array
    {
        return $this->createQueryBuilder('g')
            ->select(
                'r.season AS season',
                'r.number AS round_number',
                't.id AS team_id',
                't.name AS team_name',
                'g.id AS game_id',
                'g.startedAt AS startedAt',
                'g.finishedAt AS finishedAt',
            )
            ->join('g.round', 'r')
            ->join('g.team', 't')
            ->orderBy('r.season', 'DESC')
            ->addOrderBy('r.number', 'ASC')
            ->addOrderBy('t.id', 'ASC')
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
            ->orderBy('g.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
