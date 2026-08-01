<?php

declare(strict_types=1);

namespace App\Service\Analysis;

use App\Repository\CommitRepository;

final readonly class TrendAnalyzer
{
    public function __construct(
        private CommitRepository $commitRepo,
    ) {
    }

    /**
     * @param int[] $repoIds
     *
     * @return array<string, mixed>
     */
    public function analyze(array $repoIds): array
    {
        if (count($repoIds) === 0) {
            return [
                'quality_trend' => [],
                'commit_size_trend' => [],
                'technology_adoption' => [],
                'activity_cadence' => [],
            ];
        }

        return [
            'quality_trend' => $this->qualityTrend($repoIds),
            'commit_size_trend' => $this->commitSizeTrend($repoIds),
            'technology_adoption' => $this->technologyAdoption($repoIds),
            'activity_cadence' => $this->activityCadence($repoIds),
        ];
    }

    /**
     * @param int[] $repoIds
     *
     * @return array<array{month: string, avg_quality: float, bug_count: int, feature_count: int}>
     */
    public function qualityTrend(array $repoIds): array
    {
        return $this->commitRepo->qualityTrendByMonth($repoIds);
    }

    /**
     * @param int[] $repoIds
     *
     * @return array<array{month: string, avg_size: float}>
     */
    public function commitSizeTrend(array $repoIds): array
    {
        return $this->commitRepo->commitSizeTrendByMonth($repoIds);
    }

    /**
     * @param int[] $repoIds
     *
     * @return array<array{quarter: string, technology: string, first_seen: string}>
     */
    public function technologyAdoption(array $repoIds): array
    {
        return $this->commitRepo->newTechnologiesByQuarter($repoIds);
    }

    /**
     * @param int[] $repoIds
     *
     * @return array<array{week: string, days_with_commits: int}>
     */
    public function activityCadence(array $repoIds): array
    {
        return $this->commitRepo->activityCadenceByWeek($repoIds);
    }
}
