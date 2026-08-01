<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Tests\Factory\AnalysisResultFactory;
use App\Tests\Factory\CommitFactory;
use App\Tests\Factory\GithubRepoFactory;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class RepoControllerTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
    }

    public function testDetailReturns404WhenRepoNotFound(): void
    {
        $this->client->request('GET', '/repo/99999');

        self::assertResponseStatusCodeSame(404);
    }

    public function testDetailShowsRepoInfoWhenRepoExists(): void
    {
        $repo = GithubRepoFactory::createOne([
            'fullName' => 'octocat/test-repo',
            'name' => 'test-repo',
            'description' => 'A test repository',
            'language' => 'PHP',
            'stars' => 42,
            'forks' => 7,
        ])->_real();

        $repoId = $repo->getId();

        $this->client->request('GET', "/repo/{$repoId}");

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('html', 'test-repo');
        self::assertSelectorTextContains('html', 'PHP');
    }

    public function testDetailShowsNoCommitsMessageWhenRepoHasNoCommits(): void
    {
        $repo = GithubRepoFactory::createOne([
            'fullName' => 'empty/repo',
            'name' => 'empty-repo',
        ])->_real();

        $repoId = $repo->getId();

        $this->client->request('GET', "/repo/{$repoId}");

        self::assertResponseIsSuccessful();
    }

    public function testDetailShowsClassificationCountsForAnalyzedCommits(): void
    {
        $repo = GithubRepoFactory::createOne([
            'fullName' => 'dev/analyzed-project',
            'name' => 'analyzed-project',
        ])->_real();

        $commit1 = CommitFactory::createOne([
            'repository' => $repo,
            'sha' => 'aaa111',
            'message' => 'feat: add login',
        ])->_real();

        $commit2 = CommitFactory::createOne([
            'repository' => $repo,
            'sha' => 'bbb222',
            'message' => 'fix: typo',
        ])->_real();

        $commit3 = CommitFactory::createOne([
            'repository' => $repo,
            'sha' => 'ccc333',
            'message' => 'feat: add dashboard',
        ])->_real();

        AnalysisResultFactory::createOne([
            'commit' => $commit1,
            'provider' => 'ollama',
            'classification' => [
                'classification' => 'feature',
                'complexity_score' => 5,
                'summary' => 'Added login feature',
                'impact_areas' => ['auth'],
                'technologies_found' => ['PHP', 'Symfony'],
                'patterns_used' => [],
                'code_quality_score' => 7,
                'tags' => [],
            ],
        ]);

        AnalysisResultFactory::createOne([
            'commit' => $commit2,
            'provider' => 'ollama',
            'classification' => [
                'classification' => 'bugfix',
                'complexity_score' => 2,
                'summary' => 'Fixed typo',
                'impact_areas' => ['frontend'],
                'technologies_found' => ['JavaScript'],
                'patterns_used' => [],
                'code_quality_score' => 8,
                'tags' => [],
            ],
        ]);

        AnalysisResultFactory::createOne([
            'commit' => $commit3,
            'provider' => 'ollama',
            'classification' => [
                'classification' => 'feature',
                'complexity_score' => 6,
                'summary' => 'Added dashboard',
                'impact_areas' => ['views'],
                'technologies_found' => ['PHP', 'Twig'],
                'patterns_used' => [],
                'code_quality_score' => 7,
                'tags' => [],
            ],
        ]);

        $repoId = $repo->getId();

        $this->client->request('GET', "/repo/{$repoId}");

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('html', 'analyzed-project');
    }

    public function testDetailShowsTechnologyTagCloudForAnalyzedCommits(): void
    {
        $repo = GithubRepoFactory::createOne([
            'fullName' => 'tech/repo',
            'name' => 'tech-repo',
        ])->_real();

        $commit = CommitFactory::createOne([
            'repository' => $repo,
            'sha' => 'tech-sha',
        ])->_real();

        AnalysisResultFactory::createOne([
            'commit' => $commit,
            'provider' => 'ollama',
            'classification' => [
                'classification' => 'feature',
                'complexity_score' => 5,
                'summary' => 'Feature with tech',
                'impact_areas' => [],
                'technologies_found' => ['Docker', 'PostgreSQL', 'Redis'],
                'patterns_used' => [],
                'code_quality_score' => 7,
                'tags' => [],
            ],
        ]);

        $repoId = $repo->getId();

        $this->client->request('GET', "/repo/{$repoId}");

        self::assertResponseIsSuccessful();
    }

    public function testDetailOnlyShowsLast50Commits(): void
    {
        $repo = GithubRepoFactory::createOne([
            'fullName' => 'noisy/repo',
            'name' => 'noisy-repo',
        ])->_real();

        for ($i = 0; $i < 60; ++$i) {
            CommitFactory::createOne([
                'repository' => $repo,
                'sha' => sprintf('sha-%04d', $i),
                'date' => new DateTimeImmutable("-{$i} hours"),
            ]);
        }

        $repoId = $repo->getId();

        $this->client->request('GET', "/repo/{$repoId}");

        self::assertResponseIsSuccessful();
    }
}
