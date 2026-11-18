<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Rule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Rule>
 */
class RuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rule::class);
    }

    /**
     * Computed rules first, then the free rules in their creation order.
     *
     * @return list<Rule>
     */
    public function findAllOrdered(): array
    {
        /* @var list<Rule> */
        return $this->createQueryBuilder('r')
            ->addSelect('CASE WHEN r.computedMeasure IS NULL THEN 1 ELSE 0 END AS HIDDEN freeRule')
            ->orderBy('freeRule', 'ASC')
            ->addOrderBy('r.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
