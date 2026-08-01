<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\GithubAccount;
use App\Entity\GithubRepo;
use App\Service\Analysis\Shared\TechnologyHelper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GithubRepo>
 */
class GithubRepoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GithubRepo::class);
    }

    public function countByAccount(GithubAccount $account): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->join('r.contributors', 'c')
            ->where('c.id = :accountId')
            ->setParameter('accountId', $account->getId())
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return int[]
     */
    public function findRepoIdsByAccount(GithubAccount $account): array
    {
        $ids = $this->createQueryBuilder('r')
            ->select('r.id')
            ->join('r.contributors', 'c')
            ->where('c.id = :accountId')
            ->setParameter('accountId', $account->getId())
            ->getQuery()
            ->getSingleColumnResult();

        return array_map('intval', $ids);
    }

    /**
     * @return GithubRepo[]
     */
    public function findByAccount(GithubAccount $account): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.contributors', 'c')
            ->where('c.id = :accountId')
            ->setParameter('accountId', $account->getId())
            ->orderBy('r.stars', 'DESC')
            ->getQuery()
            ->getResult();
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

    /**
     * @param int[] $repoIds
     *
     * @return array<array{name: string, count: int}>
     */
    public function getTopTechnologies(array $repoIds): array
    {
        if (count($repoIds) === 0) {
            return [];
        }

        $conn = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT
                tech.value AS name,
                COUNT(*) AS cnt
            FROM commits c
            JOIN analysis_results a ON a.commit_id = c.id,
                 LATERAL json_array_elements_text(a.classification->'technologies_found') AS tech(value)
            WHERE c.repository_id IN (:ids)
            GROUP BY tech.value
            ORDER BY cnt DESC
            LIMIT 12
        ";

        $rows = $conn->executeQuery($sql, ['ids' => $repoIds], ['ids' => \Doctrine\DBAL\ArrayParameterType::INTEGER]);

        return array_map(
            fn ($r) => ['name' => TechnologyHelper::normalize($r['name']), 'count' => (int) $r['cnt']],
            $rows->fetchAllAssociative(),
        );
    }
}
