<?php

namespace App\Repository;

use App\Entity\BingoSquareValidated;
use App\Entity\Game;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BingoSquareValidated>
 */
class BingoSquareValidatedRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BingoSquareValidated::class);
    }

    /**
     * Ids of the squares validated in the game, in a single query.
     *
     * @return list<int>
     */
    public function findSquareIdsByGame(Game $game): array
    {
        return $this->createQueryBuilder('v')
            ->select('IDENTITY(v.bingoSquare)')
            ->where('v.game = :game')
            ->setParameter('game', $game)
            ->getQuery()
            ->getSingleColumnResult();
    }
}
