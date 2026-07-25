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

    /**
     * @return list<array{round_id: int, square_count: int, incomplete_count: int}>
     */
    public function findSquareCompleteness(): array
    {
        return $this->createQueryBuilder('r')
            ->select('r.id AS round_id')
            ->addSelect('COUNT(bs.id) AS square_count')
            ->addSelect('SUM(CASE WHEN bs.label IS NULL OR bs.label = \'\' OR bs.category IS NULL THEN 1 ELSE 0 END) AS incomplete_count')
            ->leftJoin('r.bingoSquares', 'bs')
            ->groupBy('r.id')
            ->getQuery()
            ->getArrayResult();
    }
}
