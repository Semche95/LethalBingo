<?php

namespace App\Repository;

use App\Entity\Team;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Team>
 */
class TeamRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Team::class);
    }

    public function findAllWithMembers(): array
    {
        return $this->createQueryBuilder('t')
            ->select('t.id, t.name')
            ->addSelect('STRING_AGG(m.name, \', \' ORDER BY m.captain DESC, m.name) AS members')
            ->leftJoin('t.members', 'm')
            ->groupBy('t.id')
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Find teams matching a search term on team name or member name (accent-insensitive).
     *
     * @return Team[]
     */
    public function findBySearchTerm(string $term): array
    {
        $normalizedTerm = '%' . mb_strtolower($term) . '%';

        return $this->createQueryBuilder('t')
            ->leftJoin('t.members', 'm')
            ->where('unaccent(LOWER(t.name)) LIKE unaccent(:term)')
            ->orWhere('unaccent(LOWER(m.name)) LIKE unaccent(:term)')
            ->setParameter('term', $normalizedTerm)
            ->distinct(true)
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find the most recently created teams
     *
     * @return Team[]
     */
    public function findRecent(int $limit = 5): array
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
