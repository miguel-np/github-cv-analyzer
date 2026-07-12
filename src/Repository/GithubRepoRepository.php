<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\GithubRepo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class GithubRepoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GithubRepo::class);
    }

    /**
     * @param int[] $repoIds
     */
    public function countCommitsByIds(array $repoIds): int
    {
        if (count($repoIds) === 0) {
            return 0;
        }

        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(c.id)')
            ->join('r.commits', 'c')
            ->where('r.id IN (:ids)')
            ->setParameter('ids', $repoIds)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param int[] $repoIds
     */
    public function countPullRequestsByIds(array $repoIds): int
    {
        if (count($repoIds) === 0) {
            return 0;
        }

        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(p.id)')
            ->join('r.pullRequests', 'p')
            ->where('r.id IN (:ids)')
            ->setParameter('ids', $repoIds)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param int[] $repoIds
     */
    public function countIssuesByIds(array $repoIds): int
    {
        if (count($repoIds) === 0) {
            return 0;
        }

        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(i.id)')
            ->join('r.issues', 'i')
            ->where('r.id IN (:ids)')
            ->setParameter('ids', $repoIds)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
