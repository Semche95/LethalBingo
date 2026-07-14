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
     * @return array<int, int> List of distinct round numbers
     */
    public function findDistinctRoundNumbers(): array
    {
        return $this->createQueryBuilder('gr')
            ->select('gr.number')
            ->distinct()
            ->orderBy('gr.number', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();
    }

    /**
     * @return array<int, int> List of distinct seasons
     */
    public function findDistinctSeasons(): array
    {
        return $this->createQueryBuilder('gr')
            ->select('gr.season')
            ->distinct()
            ->orderBy('gr.season', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();
    }

    /**
     * Find game rounds filtered by round number and/or season
     *
     * @param int|null $round Round number to filter by
     * @param int|null $season Season number to filter by
     * @return array<string, mixed> List of game rounds
     */
    public function findByRoundAndSeason(?int $round = null, ?int $season = null): array
    {
        $queryBuilder = $this->createQueryBuilder('gr')
            ->select('gr.id, gr.number, gr.season')
            ->orderBy('gr.number', 'ASC')
            ->addOrderBy('gr.season', 'ASC');

        if ($round !== null) {
            $queryBuilder->andWhere('gr.number = :round')
                ->setParameter('round', $round);
        }

        if ($season !== null) {
            $queryBuilder->andWhere('gr.season = :season')
                ->setParameter('season', $season);
        }

        return $queryBuilder->getQuery()->getScalarResult();
    }
}
