<?php

namespace App\Repository;

use App\Entity\BingoSquare;
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

    /**
     * Squares of the round that have a category and are not validated, all
     * categories at once (grouping by category is left to the caller).
     *
     * @return list<BingoSquare>
     */
    public function findNotValidatedWithCategory(GameRound $round): array
    {
        $qb = $this->createQueryBuilder('s');

        return $qb
            ->leftJoin('s.bingoSquareValidated', 'v')
            ->andWhere($qb->expr()->eq('s.round', ':round'))
            ->andWhere($qb->expr()->isNotNull('s.category'))
            ->andWhere($qb->expr()->isNull('v.id'))
            ->setParameter('round', $round)
            ->orderBy('s.row', 'ASC')
            ->addOrderBy('s.col', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
