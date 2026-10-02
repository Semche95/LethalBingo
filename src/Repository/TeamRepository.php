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

    /**
     * Find teams with their members aggregated as JSON, optionally filtered by search term.
     *
     * @return list<array{id: int, name: string, members: string}>
     */
    public function findWithMembers(?string $search = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->select('t.id AS id, t.name AS name')
            ->addSelect("JSON_AGG(JSON_BUILD_OBJECT('name', m.name, 'captain', m.captain) ORDER BY m.captain DESC, m.name ASC) FILTER (WHERE m.id IS NOT NULL) AS members")
            ->leftJoin('t.members', 'm')
            ->groupBy('t.id, t.name')
            ->orderBy('t.name', 'ASC');

        if ($search !== null && $search !== '') {
            $normalizedTerm = '%' . mb_strtolower($search) . '%';

            $subDql = $this->createQueryBuilder('t2')
                ->select('t2.id')
                ->leftJoin('t2.members', 'm2')
                ->where('unaccent(LOWER(t2.name)) LIKE unaccent(:term)')
                ->orWhere('unaccent(LOWER(m2.name)) LIKE unaccent(:term)')
                ->getDQL();

            $qb->andWhere("t.id IN ($subDql)")
                ->setParameter('term', $normalizedTerm);
        }

        return $qb->getQuery()->getScalarResult();
    }

    /**
     * Find the most recently created teams.
     *
     * @return Team[]
     */
    public function findRecent(int $limit = 5): array
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
