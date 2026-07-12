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

        $results = $this->createQueryBuilder('c')
            ->select('a.classification')
            ->join('c.analysisResult', 'a')
            ->where('c.repository IN (:ids)')
            ->setParameter('ids', $repoIds)
            ->getQuery()
            ->getArrayResult();

        $counts = [];

        foreach ($results as $row) {
            $type = $row['classification']['classification'] ?? 'unknown';
            $counts[$type] = ($counts[$type] ?? 0) + 1;
        }

        return array_map(fn ($k, $v) => ['classification' => $k, 'count' => $v], array_keys($counts), $counts);
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
}
