<?php

namespace App\Repository;

use App\Entity\BingoSquareRevealed;
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
}
