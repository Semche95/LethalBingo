<?php

namespace App\Repository;

use App\Entity\TeamMember;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TeamMember>
 */
class TeamMemberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TeamMember::class);
    }

    /**
     * @return string[]
     */
    public function findNamesByTeamOrdered(int $teamId): array
    {
        return $this->createQueryBuilder('m')
            ->select('m.name')
            ->where('m.team = :teamId')
            ->setParameter('teamId', $teamId)
            ->orderBy('m.captain', 'DESC')
            ->addOrderBy('m.name', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();
    }
}
