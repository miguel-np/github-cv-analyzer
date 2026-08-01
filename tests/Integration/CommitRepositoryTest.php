<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Repository\CommitRepository;
use App\Tests\Factory\AnalysisResultFactory;
use App\Tests\Factory\CommitFactory;
use App\Tests\Factory\GithubRepoFactory;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class CommitRepositoryTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    private CommitRepository $commitRepo;

    protected function setUp(): void
    {
        self::bootKernel();
        $repo = self::getContainer()->get(CommitRepository::class);
        \assert($repo instanceof CommitRepository);
        $this->commitRepo = $repo;
    }

    public function testCountByMonthReturnsMonthlyAggregation(): void
    {
        $repo = GithubRepoFactory::createOne()->_real();
        $repoId = (int) $repo->getId();

        CommitFactory::createOne([
            'repository' => $repo,
            'sha' => 'jan-sha',
            'date' => new DateTimeImmutable('2026-01-15 10:00:00'),
        ]);
        CommitFactory::createOne([
            'repository' => $repo,
            'sha' => 'jan-sha-2',
            'date' => new DateTimeImmutable('2026-01-20 10:00:00'),
        ]);
        CommitFactory::createOne([
            'repository' => $repo,
            'sha' => 'feb-sha',
            'date' => new DateTimeImmutable('2026-02-05 10:00:00'),
        ]);

        $result = $this->commitRepo->countByMonth([$repoId]);

        self::assertCount(2, $result);
        self::assertSame('2026-01', $result[0]['month']);
        self::assertSame(2, $result[0]['count']);
        self::assertSame('2026-02', $result[1]['month']);
        self::assertSame(1, $result[1]['count']);
    }

    public function testCountByMonthReturnsEmptyArrayForNoRepoIds(): void
    {
        $result = $this->commitRepo->countByMonth([]);

        self::assertSame([], $result);
    }

    public function testCountByClassificationReturnsBreakdownFromAnalyzedCommits(): void
    {
        $repo = GithubRepoFactory::createOne()->_real();
        $repoId = (int) $repo->getId();

        $commit1 = CommitFactory::createOne([
            'repository' => $repo,
            'sha' => 'class-sha-1',
        ])->_real();
        $commit2 = CommitFactory::createOne([
            'repository' => $repo,
            'sha' => 'class-sha-2',
        ])->_real();
        $commit3 = CommitFactory::createOne([
            'repository' => $repo,
            'sha' => 'class-sha-3',
        ])->_real();
        $commit4 = CommitFactory::createOne([
            'repository' => $repo,
            'sha' => 'class-sha-4',
        ])->_real();

        AnalysisResultFactory::createOne([
            'commit' => $commit1,
            'classification' => $this->buildClassification('feature'),
        ]);
        AnalysisResultFactory::createOne([
            'commit' => $commit2,
            'classification' => $this->buildClassification('feature'),
        ]);
        AnalysisResultFactory::createOne([
            'commit' => $commit3,
            'classification' => $this->buildClassification('bugfix'),
        ]);

        $result = $this->commitRepo->countByClassification([$repoId]);

        self::assertCount(2, $result);

        $types = array_column($result, 'classification');
        self::assertContains('feature', $types);
        self::assertContains('bugfix', $types);

        foreach ($result as $row) {
            if ($row['classification'] === 'feature') {
                self::assertSame(2, $row['count']);
            }
            if ($row['classification'] === 'bugfix') {
                self::assertSame(1, $row['count']);
            }
        }
    }

    public function testCountByClassificationReturnsEmptyForNoRepoIds(): void
    {
        $result = $this->commitRepo->countByClassification([]);

        self::assertSame([], $result);
    }

    public function testCountByRepoReturnsTopRepositoriesOrderedDescending(): void
    {
        $repo1 = GithubRepoFactory::createOne(['name' => 'alpha'])->_real();
        $repo2 = GithubRepoFactory::createOne(['name' => 'beta'])->_real();
        $repo3 = GithubRepoFactory::createOne(['name' => 'gamma'])->_real();

        CommitFactory::createMany(5, ['repository' => $repo1]);
        CommitFactory::createMany(3, ['repository' => $repo2]);
        CommitFactory::createMany(1, ['repository' => $repo3]);

        $ids = array_filter([$repo1->getId(), $repo2->getId(), $repo3->getId()], static fn ($id): bool => $id !== null);
        $result = $this->commitRepo->countByRepo(array_values($ids));

        self::assertCount(3, $result);
        self::assertSame('alpha', $result[0]['name']);
        self::assertSame(5, $result[0]['count']);
        self::assertSame('beta', $result[1]['name']);
        self::assertSame(3, $result[1]['count']);
        self::assertSame('gamma', $result[2]['name']);
        self::assertSame(1, $result[2]['count']);
    }

    public function testCountByRepoReturnsEmptyForNoRepoIds(): void
    {
        $result = $this->commitRepo->countByRepo([]);

        self::assertSame([], $result);
    }

    public function testFindByRepoWithAnalysisReturnsCommitsWithEagerLoadedResult(): void
    {
        $repo = GithubRepoFactory::createOne()->_real();
        $repoId = (int) $repo->getId();

        $commit1 = CommitFactory::createOne([
            'repository' => $repo,
            'sha' => 'eager-sha-1',
            'date' => new DateTimeImmutable('2026-01-01'),
        ])->_real();
        $commit2 = CommitFactory::createOne([
            'repository' => $repo,
            'sha' => 'eager-sha-2',
            'date' => new DateTimeImmutable('2026-06-01'),
        ])->_real();

        AnalysisResultFactory::createOne([
            'commit' => $commit2,
            'classification' => $this->buildClassification('feature'),
        ]);

        $commits = $this->commitRepo->findByRepoWithAnalysis($repoId);

        self::assertCount(2, $commits);
        self::assertNotNull($commits[0]->getAnalysisResult());
    }

    public function testFindByRepoWithAnalysisRespectsLimit(): void
    {
        $repo = GithubRepoFactory::createOne()->_real();
        $repoId = (int) $repo->getId();

        for ($i = 0; $i < 15; ++$i) {
            CommitFactory::createOne([
                'repository' => $repo,
                'sha' => sprintf('limit-sha-%d', $i),
                'date' => new DateTimeImmutable("-{$i} days"),
            ]);
        }

        $commits = $this->commitRepo->findByRepoWithAnalysis($repoId, 5);

        self::assertCount(5, $commits);
    }

    public function testFindByRepoWithAnalysisReturnsEmptyForRepoWithNoCommits(): void
    {
        $repo = GithubRepoFactory::createOne()->_real();
        $repoId = (int) $repo->getId();

        $commits = $this->commitRepo->findByRepoWithAnalysis($repoId);

        self::assertCount(0, $commits);
    }

    public function testCountAnalyzedByIdsReturnsOnlyAnalyzedCommitCount(): void
    {
        $repo = GithubRepoFactory::createOne()->_real();
        $repoId = (int) $repo->getId();

        $commit1 = CommitFactory::createOne([
            'repository' => $repo,
            'sha' => 'analyzed-1',
        ])->_real();
        $commit2 = CommitFactory::createOne([
            'repository' => $repo,
            'sha' => 'unanalyzed-1',
        ])->_real();

        AnalysisResultFactory::createOne([
            'commit' => $commit1,
            'classification' => $this->buildClassification('feature'),
        ]);

        $count = $this->commitRepo->countAnalyzedByIds([$repoId]);

        self::assertSame(1, $count);
    }

    public function testCountAnalyzedByIdsReturnsZeroForEmptyArray(): void
    {
        $count = $this->commitRepo->countAnalyzedByIds([]);

        self::assertSame(0, $count);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildClassification(string $type): array
    {
        return [
            'classification' => $type,
            'complexity_score' => 5,
            'summary' => 'Test commit',
            'impact_areas' => ['test'],
            'technologies_found' => ['PHP'],
            'patterns_used' => [],
            'code_quality_score' => 7,
            'tags' => [],
        ];
    }
}
