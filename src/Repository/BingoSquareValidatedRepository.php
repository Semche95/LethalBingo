<?php

namespace App\Repository;

use App\Entity\BingoSquareValidated;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends GameSquareMarkRepository<BingoSquareValidated>
 */
class BingoSquareValidatedRepository extends GameSquareMarkRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BingoSquareValidated::class);
    }
}
