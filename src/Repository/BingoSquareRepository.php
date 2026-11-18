<?php

namespace App\Repository;

use App\Entity\BingoSquare;
use App\Entity\Game;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
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
     * Squares of the round of the game that have a category and are not
     * validated in this game, all categories at once (grouping by category is
     * left to the caller). Another game on the same round validates its own
     * squares.
     *
     * @return list<BingoSquare>
     */
    public function findNotValidatedWithCategory(Game $game): array
    {
        $qb = $this->createQueryBuilder('s');

        return $qb
            ->leftJoin('s.bingoSquareValidated', 'v', Join::WITH, $qb->expr()->eq('v.game', ':game'))
            ->andWhere($qb->expr()->eq('s.round', ':round'))
            ->andWhere($qb->expr()->isNotNull('s.category'))
            ->andWhere($qb->expr()->isNull('v.id'))
            ->setParameter('game', $game)
            ->setParameter('round', $game->getRound())
            ->orderBy('s.row', 'ASC')
            ->addOrderBy('s.col', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
