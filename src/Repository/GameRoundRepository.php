<?php

namespace App\Repository;

use App\Entity\GameRound;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GameRound>
 */
class GameRoundRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GameRound::class);
    }

    public function findByNumberAndSeason(?int $number, ?int $season): ?GameRound
    {
        return $this->createQueryBuilder('gr')
            ->where('gr.number = :number')
            ->andWhere('gr.season = :season')
            ->setParameter('number', $number, Types::INTEGER)
            ->setParameter('season', $season, Types::INTEGER)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find game rounds grouped by season, aggregated as JSON
     *
     * @return list<array{season: int, rounds: string}>
     */
    public function findGroupedBySeason(): array
    {
        return $this->createQueryBuilder('r')
            ->select('r.season AS season')
            ->addSelect("JSON_AGG(JSON_BUILD_OBJECT('id', r.id, 'number', r.number) ORDER BY r.number) AS rounds")
            ->groupBy('r.season')
            ->orderBy('r.season', 'DESC')
            ->getQuery()
            ->getScalarResult();
    }

    /**
     * Find the most recently created game rounds
     *
     * @return GameRound[]
     */
    public function findRecent(int $limit = 5): array
    {
        return $this->createQueryBuilder('gr')
            ->orderBy('gr.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
