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
     *
     * @return array<array{month: string, count: int}>
     */
    public function countByMonth(array $repoIds): array
    {
        if (count($repoIds) === 0) {
            return [];
        }

        $conn = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT TO_CHAR(c.date, 'YYYY-MM') AS month, COUNT(c.id) AS cnt
            FROM commits c
            WHERE c.repository_id IN (:ids)
            GROUP BY month
            ORDER BY month ASC
        ";

        $rows = $conn->executeQuery($sql, ['ids' => $repoIds], ['ids' => \Doctrine\DBAL\ArrayParameterType::INTEGER]);

        return array_map(fn ($r): array => ['month' => $r['month'], 'count' => (int) $r['cnt']], $rows->fetchAllAssociative());
    }

    /**
     * @param int[] $repoIds
     *
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
     *
     * @return array<array{name: string, count: int}>
     */
    public function countByRepo(array $repoIds): array
    {
        if (count($repoIds) === 0) {
            return [];
        }

        return $this->createQueryBuilder('c')
            ->select('r.name, COUNT(c.id) AS count')
            ->join('c.repository', 'r')
            ->where('c.repository IN (:ids)')
            ->setParameter('ids', $repoIds)
            ->groupBy('r.name')
            ->orderBy('count', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getArrayResult();
    }

    /**
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
            ['ids' => \Doctrine\DBAL\ArrayParameterType::INTEGER],
        )->fetchOne();
    }

    /**
     * @param int[] $repoIds
     *
     * @return array<array{month: string, avg_quality: float, avg_complexity: float, bug_count: int, feature_count: int}>
     */
    public function qualityTrendByMonth(array $repoIds): array
    {
        if (count($repoIds) === 0) {
            return [];
        }

        $conn = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT
                TO_CHAR(c.date, 'YYYY-MM') AS month,
                ROUND(AVG(COALESCE((a.classification->>'code_quality_score')::int, 0)), 1) AS avg_quality,
                COUNT(*) FILTER (WHERE a.classification->>'classification' = 'bugfix') AS bug_count,
                COUNT(*) FILTER (WHERE a.classification->>'classification' = 'feature') AS feature_count
            FROM commits c
            JOIN analysis_results a ON a.commit_id = c.id
            WHERE c.repository_id IN (:ids)
            GROUP BY month
            ORDER BY month ASC
        ";

        $rows = $conn->executeQuery($sql, ['ids' => $repoIds], ['ids' => \Doctrine\DBAL\ArrayParameterType::INTEGER]);

        return array_map(fn ($r) => [
            'month' => $r['month'],
            'avg_quality' => (float) $r['avg_quality'],
            'bug_count' => (int) $r['bug_count'],
            'feature_count' => (int) $r['feature_count'],
        ], $rows->fetchAllAssociative());
    }

    /**
     * @param int[] $repoIds
     *
     * @return array<array{month: string, avg_size: float}>
     */
    public function commitSizeTrendByMonth(array $repoIds): array
    {
        if (count($repoIds) === 0) {
            return [];
        }

        $conn = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT
                TO_CHAR(c.date, 'YYYY-MM') AS month,
                ROUND(AVG(c.additions + c.deletions), 1) AS avg_size
            FROM commits c
            WHERE c.repository_id IN (:ids)
                AND c.is_merge_commit = false
            GROUP BY month
            ORDER BY month ASC
        ";

        $rows = $conn->executeQuery($sql, ['ids' => $repoIds], ['ids' => \Doctrine\DBAL\ArrayParameterType::INTEGER]);

        return array_map(fn ($r) => [
            'month' => $r['month'],
            'avg_size' => (float) $r['avg_size'],
        ], $rows->fetchAllAssociative());
    }

    /**
     * @param int[] $repoIds
     *
     * @return array<array{quarter: string, technology: string, first_seen: string}>
     */
    public function newTechnologiesByQuarter(array $repoIds): array
    {
        if (count($repoIds) === 0) {
            return [];
        }

        $conn = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT
                tech.value AS technology,
                TO_CHAR(MIN(c.date), 'YYYY-\"Q\"Q') AS quarter,
                MIN(TO_CHAR(c.date, 'YYYY-MM')) AS first_seen
            FROM commits c
            JOIN analysis_results a ON a.commit_id = c.id,
                 LATERAL json_array_elements_text(a.classification->'technologies_found') AS tech(value)
            WHERE c.repository_id IN (:ids)
            GROUP BY tech.value
            ORDER BY first_seen ASC
        ";

        $rows = $conn->executeQuery($sql, ['ids' => $repoIds], ['ids' => \Doctrine\DBAL\ArrayParameterType::INTEGER]);

        return array_map(fn ($r) => [
            'quarter' => $r['quarter'],
            'technology' => $r['technology'],
            'first_seen' => $r['first_seen'],
        ], $rows->fetchAllAssociative());
    }

    /**
     * @param int[] $repoIds
     *
     * @return array<array{week: string, days_with_commits: int}>
     */
    public function activityCadenceByWeek(array $repoIds): array
    {
        if (count($repoIds) === 0) {
            return [];
        }

        $conn = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT
                TO_CHAR(DATE_TRUNC('week', c.date), 'YYYY-MM-DD') AS week,
                COUNT(DISTINCT DATE(c.date)) AS days_with_commits
            FROM commits c
            WHERE c.repository_id IN (:ids)
            GROUP BY week
            ORDER BY week ASC
        ";

        $rows = $conn->executeQuery($sql, ['ids' => $repoIds], ['ids' => \Doctrine\DBAL\ArrayParameterType::INTEGER]);

        return array_map(fn ($r) => [
            'week' => $r['week'],
            'days_with_commits' => (int) $r['days_with_commits'],
        ], $rows->fetchAllAssociative());
    }
}
