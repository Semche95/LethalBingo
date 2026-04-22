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
}
