<?php

namespace App\Repository;

use App\Entity\BingoSquareRevealed;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends GameSquareMarkRepository<BingoSquareRevealed>
 */
class BingoSquareRevealedRepository extends GameSquareMarkRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BingoSquareRevealed::class);
    }
}
