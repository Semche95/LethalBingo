<?php

namespace App\Repository;

use App\Entity\BingoSquare;
use App\Entity\BingoSquareCategory;
use App\Entity\GameRound;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BingoSquare>
 */
class BingoSquareRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BingoSquare::class);
    }

    public function findNotValidated(GameRound $round, BingoSquareCategory $category): array
    {
        $qb = $this->createQueryBuilder('s');
        return $qb
            ->leftJoin('s.bingoSquareValidated', 'v')
            ->andWhere($qb->expr()->eq('s.round', ':round'))
            ->andWhere($qb->expr()->eq('s.category', ':category'))
            ->andWhere($qb->expr()->isNull('v.id'))
            ->setParameter('round', $round)
            ->setParameter('category', $category)
            ->getQuery()
            ->getResult();
    }
}
