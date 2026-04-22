<?php

namespace App\Repository;

use App\Entity\BingoSquareValidated;
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
}
