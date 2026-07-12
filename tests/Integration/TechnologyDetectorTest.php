<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Repository\CommitRepository;
use App\Service\Analysis\TechnologyDetector;
use App\Tests\Factory\AnalysisResultFactory;
use App\Tests\Factory\CommitFactory;
use App\Tests\Factory\GithubRepoFactory;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class TechnologyDetectorTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    private TechnologyDetector $detector;

    protected function setUp(): void
    {
        self::bootKernel();

        $commitRepo = self::getContainer()->get(CommitRepository::class);
        \assert($commitRepo instanceof CommitRepository);
        $this->detector = new TechnologyDetector($commitRepo, new NullLogger());
    }

    public function testDetectAggregatesTechnologiesFromAnalyzedCommits(): void
    {
        $repo = GithubRepoFactory::createOne()->_real();

        $commit1 = CommitFactory::createOne(['repository' => $repo, 'sha' => 'sha1'])->_real();
        $commit2 = CommitFactory::createOne(['repository' => $repo, 'sha' => 'sha2'])->_real();

        AnalysisResultFactory::createOne([
            'commit' => $commit1,
            'classification' => [
                'classification' => 'feature',
                'complexity_score' => 3,
                'summary' => 'test',
                'impact_areas' => ['backend'],
                'technologies_found' => ['PHP', 'Symfony'],
                'patterns_used' => [],
                'code_quality_score' => 7,
                'tags' => [],
            ],
        ]);

        AnalysisResultFactory::createOne([
            'commit' => $commit2,
            'classification' => [
                'classification' => 'bugfix',
                'complexity_score' => 2,
                'summary' => 'fix',
                'impact_areas' => ['frontend'],
                'technologies_found' => ['PHP', 'TypeScript'],
                'patterns_used' => [],
                'code_quality_score' => 6,
                'tags' => [],
            ],
        ]);

        $techs = $this->detector->detect($repo);

        self::assertArrayHasKey('php', $techs);
        self::assertSame(2, $techs['php']);
        self::assertArrayHasKey('symfony', $techs);
        self::assertSame(1, $techs['symfony']);
        self::assertArrayHasKey('typescript', $techs);
        self::assertSame(1, $techs['typescript']);
    }

    public function testDetectReturnsEmptyArrayWhenNoCommitsAnalyzed(): void
    {
        $repo = GithubRepoFactory::createOne()->_real();

        CommitFactory::createOne(['repository' => $repo, 'sha' => 'unanalyzed']);

        $techs = $this->detector->detect($repo);

        self::assertEmpty($techs);
    }

    public function testDetectSortsTechnologiesByOccurrenceDescending(): void
    {
        $repo = GithubRepoFactory::createOne()->_real();

        $commit = CommitFactory::createOne(['repository' => $repo, 'sha' => 'sha-sort'])->_real();

        AnalysisResultFactory::createOne([
            'commit' => $commit,
            'classification' => [
                'classification' => 'feature',
                'complexity_score' => 3,
                'summary' => 'test',
                'impact_areas' => [],
                'technologies_found' => ['Rare', 'Common', 'Common', 'Common', 'Rare'],
                'patterns_used' => [],
                'code_quality_score' => 7,
                'tags' => [],
            ],
        ]);

        $techs = $this->detector->detect($repo);

        self::assertSame('common', array_key_first($techs));
        self::assertSame(3, array_values($techs)[0]);
        self::assertSame(2, array_values($techs)[1]);
    }
}
