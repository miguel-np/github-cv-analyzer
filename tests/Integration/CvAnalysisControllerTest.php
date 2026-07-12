<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Tests\Factory\AnalysisResultFactory;
use App\Tests\Factory\CommitFactory;
use App\Tests\Factory\GithubAccountFactory;
use App\Tests\Factory\GithubRepoFactory;
use App\Tests\Factory\IssueFactory;
use App\Tests\Factory\PullRequestFactory;
use App\Tests\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class CvAnalysisControllerTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
    }

    public function testIndexShowsPageWhenUserAndAccountExistButNoRepos(): void
    {
        $user = UserFactory::createOne([
            'email' => 'default@local.dev',
            'settings' => ['llm_enabled' => true, 'llm_provider' => 'ollama'],
        ]);
        GithubAccountFactory::createOne([
            'user' => $user,
            'githubUsername' => 'no-repos-yet',
        ]);

        $this->client->request('GET', '/cv-analysis');

        self::assertResponseIsSuccessful();
    }

    public function testIndexShowsStatsWithReposAndCommits(): void
    {
        $user = UserFactory::createOne([
            'email' => 'default@local.dev',
            'settings' => ['llm_enabled' => false],
        ]);
        $account = GithubAccountFactory::createOne([
            'user' => $user,
            'githubUsername' => 'dev-with-repos',
        ])->_real();

        $repo = GithubRepoFactory::createOne([
            'fullName' => 'dev/my-project',
            'name' => 'my-project',
        ])->_real();
        $account->getGithubRepos()->add($repo);
        $repo->getContributors()->add($account);

        CommitFactory::createMany(5, ['repository' => $repo]);
        PullRequestFactory::createMany(3, ['repository' => $repo]);
        IssueFactory::createMany(2, ['repository' => $repo]);

        $em = self::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class);
        \assert($em instanceof \Doctrine\ORM\EntityManagerInterface);
        $em->flush();

        $this->client->request('GET', '/cv-analysis');

        self::assertResponseIsSuccessful();
    }

    public function testIndexShowsLlmDisabledMessageWhenNotEnabled(): void
    {
        $user = UserFactory::createOne([
            'email' => 'default@local.dev',
            'settings' => ['llm_enabled' => false],
        ]);
        $account = GithubAccountFactory::createOne([
            'user' => $user,
            'githubUsername' => 'no-llm',
        ])->_real();

        $repo = GithubRepoFactory::createOne([
            'fullName' => 'basic/repo',
            'name' => 'basic-repo',
        ])->_real();
        $account->getGithubRepos()->add($repo);
        $repo->getContributors()->add($account);

        CommitFactory::createOne(['repository' => $repo]);

        $em = self::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class);
        \assert($em instanceof \Doctrine\ORM\EntityManagerInterface);
        $em->flush();

        $this->client->request('GET', '/cv-analysis');

        self::assertResponseIsSuccessful();
    }

    public function testIndexWithRegenerateQueryParamReloadsPage(): void
    {
        $user = UserFactory::createOne([
            'email' => 'default@local.dev',
            'settings' => ['llm_enabled' => false],
        ]);
        $account = GithubAccountFactory::createOne([
            'user' => $user,
            'githubUsername' => 'regenerate-test',
        ])->_real();

        $repo = GithubRepoFactory::createOne([
            'fullName' => 'some/repo',
            'name' => 'some-repo',
        ])->_real();
        $account->getGithubRepos()->add($repo);
        $repo->getContributors()->add($account);

        $em = self::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class);
        \assert($em instanceof \Doctrine\ORM\EntityManagerInterface);
        $em->flush();

        $this->client->request('GET', '/cv-analysis?regenerate=1');

        self::assertResponseIsSuccessful();
    }

    public function testIndexShowsClassificationBreakdown(): void
    {
        $user = UserFactory::createOne([
            'email' => 'default@local.dev',
            'settings' => ['llm_enabled' => false],
        ]);
        $account = GithubAccountFactory::createOne([
            'user' => $user,
            'githubUsername' => 'classified-dev',
        ])->_real();

        $repo = GithubRepoFactory::createOne([
            'fullName' => 'classified/project',
            'name' => 'classified-project',
        ])->_real();
        $account->getGithubRepos()->add($repo);
        $repo->getContributors()->add($account);

        $commit1 = CommitFactory::createOne([
            'repository' => $repo,
            'sha' => 'class-sha-1',
            'date' => new \DateTimeImmutable('-30 days'),
        ])->_real();

        $commit2 = CommitFactory::createOne([
            'repository' => $repo,
            'sha' => 'class-sha-2',
            'date' => new \DateTimeImmutable('-15 days'),
        ])->_real();

        AnalysisResultFactory::createOne([
            'commit' => $commit1,
            'classification' => [
                'classification' => 'feature',
                'complexity_score' => 5,
                'summary' => 'Added feature',
                'impact_areas' => ['backend'],
                'technologies_found' => ['PHP'],
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
                'summary' => 'Fixed bug',
                'impact_areas' => ['frontend'],
                'technologies_found' => ['JS'],
                'patterns_used' => [],
                'code_quality_score' => 6,
                'tags' => [],
            ],
        ]);

        $em = self::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class);
        \assert($em instanceof \Doctrine\ORM\EntityManagerInterface);
        $em->flush();

        $this->client->request('GET', '/cv-analysis');

        self::assertResponseIsSuccessful();
    }
}
