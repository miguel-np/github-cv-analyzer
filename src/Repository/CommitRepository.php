<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Commit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CommitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commit::class);
    }

    /**
     * @param int[] $repoIds
     * @return array<array{month: string, count: int}>
     */
    public function countByMonth(array $repoIds): array
    {
        if (count($repoIds) === 0) {
            return [];
        }

        return $this->createQueryBuilder('c')
            ->select("TO_CHAR(c.date, 'YYYY-MM') AS month, COUNT(c.id) AS cnt")
            ->where('c.repository IN (:ids)')
            ->setParameter('ids', $repoIds)
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @param int[] $repoIds
     * @return array<array{classification: string, count: int}>
     */
    public function countByClassification(array $repoIds): array
    {
        if (count($repoIds) === 0) {
            return [];
        }

        $conn = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT
                COALESCE(a.classification->>'classification', 'unknown') AS type,
                COUNT(*) AS cnt
            FROM commits c
            JOIN analysis_results a ON a.commit_id = c.id
            WHERE c.repository_id IN (:ids)
            GROUP BY type
            ORDER BY cnt DESC
        ";

        $rows = $conn->executeQuery($sql, ['ids' => $repoIds], ['ids' => \Doctrine\DBAL\ArrayParameterType::INTEGER]);

        return array_map(fn ($r) => ['classification' => $r['type'], 'count' => (int) $r['cnt']], $rows->fetchAllAssociative());
    }

    /**
     * @param int[] $repoIds
     * @return array<array{name: string, count: int}>
     */
    public function countByRepo(array $repoIds): array
    {
        if (count($repoIds) === 0) {
            return [];
        }

        return $this->createQueryBuilder('c')
            ->select('r.name, COUNT(c.id) AS cnt')
            ->join('c.repository', 'r')
            ->where('c.repository IN (:ids)')
            ->setParameter('ids', $repoIds)
            ->groupBy('r.name')
            ->orderBy('cnt', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @param int $repoId
     * @return Commit[]
     */
    public function findByRepoWithAnalysis(int $repoId, int $limit = 50): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.analysisResult', 'a')
            ->addSelect('a')
            ->where('c.repository = :repoId')
            ->setParameter('repoId', $repoId)
            ->orderBy('c.date', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param int[] $repoIds
     */
    public function countAnalyzedByIds(array $repoIds): int
    {
        if (count($repoIds) === 0) {
            return 0;
        }

        $conn = $this->getEntityManager()->getConnection();

        return (int) $conn->executeQuery(
            'SELECT COUNT(*) FROM commits c JOIN analysis_results a ON a.commit_id = c.id WHERE c.repository_id IN (:ids)',
            ['ids' => $repoIds],
            ['ids' => \Doctrine\DBAL\ArrayParameterType::INTEGER]
        )->fetchOne();
    }
}
