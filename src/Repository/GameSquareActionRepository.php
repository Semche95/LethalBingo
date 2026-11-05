<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Game;
use App\Entity\GameSquareAction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GameSquareAction>
 */
class GameSquareActionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GameSquareAction::class);
    }

    /**
     * Actions of the game from the oldest to the newest, with their square,
     * in a single query.
     *
     * @return list<GameSquareAction>
     */
    public function findByGameWithSquare(Game $game): array
    {
        return $this->createQueryBuilder('a')
            ->addSelect('s')
            ->join('a.bingoSquare', 's')
            ->where('a.game = :game')
            ->setParameter('game', $game)
            ->orderBy('a.occurredAt', 'ASC')
            ->addOrderBy('a.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
