<?php

namespace App\Repository;

use App\Entity\BingoSquareRevealed;
use App\Entity\Game;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BingoSquareRevealed>
 */
class BingoSquareRevealedRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BingoSquareRevealed::class);
    }

    /**
     * Ids of the squares revealed in the game, in a single query.
     *
     * @return list<int>
     */
    public function findSquareIdsByGame(Game $game): array
    {
        return $this->createQueryBuilder('r')
            ->select('IDENTITY(r.bingoSquare)')
            ->where('r.game = :game')
            ->setParameter('game', $game)
            ->getQuery()
            ->getSingleColumnResult();
    }
}
