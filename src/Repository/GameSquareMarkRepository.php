<?php

namespace App\Repository;

use App\Entity\Game;
use App\Entity\GameSquareMark;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @template T of GameSquareMark
 *
 * @extends ServiceEntityRepository<T>
 */
abstract class GameSquareMarkRepository extends ServiceEntityRepository
{
    /**
     * Ids of the squares marked in the game, in a single query.
     *
     * @return list<int>
     */
    public function findSquareIdsByGame(Game $game): array
    {
        return $this->createQueryBuilder('m')
            ->select('IDENTITY(m.bingoSquare)')
            ->where('m.game = :game')
            ->setParameter('game', $game)
            ->getQuery()
            ->getSingleColumnResult();
    }
}
