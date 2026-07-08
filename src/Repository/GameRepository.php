<?php

namespace App\Repository;

use App\Entity\Game;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Game>
 */
class GameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Game::class);
    }

    /**
     * Find all games sorted by round, season, then team
     *
     * @return Game[]
     */
    public function findAllSorted(): array
    {
        return $this->createQueryBuilder('g')
            ->join('g.round', 'r')
            ->join('g.team', 't')
            ->orderBy('r.number', 'ASC')
            ->addOrderBy('r.season', 'ASC')
            ->addOrderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
