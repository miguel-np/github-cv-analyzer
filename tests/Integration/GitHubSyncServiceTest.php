<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\GithubAccount;
use App\Entity\GithubRepo;
use App\Service\GitHub\GitHubClientInterface;
use App\Service\GitHub\GitHubSyncService;
use App\Tests\Factory\GithubAccountFactory;
use App\Tests\Factory\GithubRepoFactory;
use Doctrine\ORM\EntityManagerInterface;
use Generator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class GitHubSyncServiceTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    private EntityManagerInterface $em;
    private GitHubSyncService $syncService;
    private GitHubClientInterface&\PHPUnit\Framework\MockObject\MockObject $gitHubClient;

    protected function setUp(): void
    {
        self::bootKernel();

        $em = self::getContainer()->get(EntityManagerInterface::class);
        \assert($em instanceof EntityManagerInterface);
        $this->em = $em;
        $this->gitHubClient = $this->createMock(GitHubClientInterface::class);

        $this->syncService = new GitHubSyncService(
            $this->gitHubClient,
            $this->em,
            $this->createMock(\Psr\Log\LoggerInterface::class),
        );
    }

    public function testSyncRepositoriesCreatesNewReposFromGitHubData(): void
    {
        $account = $this->createAccount();

        $this->gitHubClient->method('listRepositories')->willReturn($this->createGenerator([
            [
                'id' => 12345,
                'full_name' => 'octocat/test-repo',
                'name' => 'test-repo',
                'description' => 'A test repo',
                'language' => 'PHP',
                'stargazers_count' => 5,
                'forks_count' => 2,
                'fork' => false,
                'private' => false,
                'topics' => ['php', 'symfony'],
                'homepage' => null,
                'license' => ['spdx_id' => 'MIT'],
                'permissions' => ['push' => true, 'admin' => false],
            ],
        ]));

        $repos = $this->syncService->syncRepositories($account);

        self::assertCount(1, $repos);
        self::assertSame('octocat/test-repo', $repos[0]->getFullName());
        self::assertSame(12345, $repos[0]->getGithubId());
        self::assertTrue($account->getGithubRepos()->contains($repos[0]));
    }

    public function testSyncRepositoriesSkipsForksWithoutContributions(): void
    {
        $account = $this->createAccount();

        $this->gitHubClient->method('listRepositories')->willReturn($this->createGenerator([
            [
                'id' => 101,
                'full_name' => 'someone/someproject',
                'name' => 'someproject',
                'description' => null,
                'language' => 'Go',
                'stargazers_count' => 0,
                'forks_count' => 100,
                'fork' => true,
                'private' => false,
                'topics' => [],
                'homepage' => null,
                'license' => null,
                'permissions' => ['push' => false, 'admin' => false],
            ],
        ]));

        $repos = $this->syncService->syncRepositories($account);

        self::assertCount(0, $repos);
    }

    public function testSyncCommitsCreatesCommitsWithDiffStats(): void
    {
        $account = $this->createAccount();
        $repo = $this->persistRepo('octocat/hello-world');

        $this->gitHubClient->method('listCommits')
            ->willReturn($this->createGenerator([
                ['sha' => 'abc123def456', 'parents' => [['sha' => 'parent1']], 'commit' => [
                    'message' => 'feat: add feature X',
                    'author' => ['email' => 'dev@test.com', 'name' => 'Dev', 'date' => '2026-07-10T12:00:00Z'],
                ]],
            ]));

        $this->gitHubClient->method('getCommitDetail')
            ->willReturn([
                'stats' => ['additions' => 42, 'deletions' => 7],
                'files' => [['filename' => 'src/Main.php']],
            ]);

        $count = $this->syncService->syncCommits($repo, $account);

        self::assertSame(1, $count);

        $commits = $this->em->getRepository(\App\Entity\Commit::class)->findBy(['repository' => $repo]);
        self::assertCount(1, $commits);
        self::assertSame('abc123def456', $commits[0]->getSha());
        self::assertSame('feat: add feature X', $commits[0]->getMessage());
        self::assertSame(42, $commits[0]->getAdditions());
    }

    public function testSyncCommitsSkipsAlreadySyncedCommitsOnSecondRun(): void
    {
        $account = $this->createAccount();
        $repo = $this->persistRepo('octocat/duplicate-test');

        $commitData = ['sha' => 'already-synced-aaaa', 'parents' => [['sha' => 'p']], 'commit' => [
            'message' => 'old commit',
            'author' => ['email' => 'd@t.com', 'name' => 'D', 'date' => '2026-06-01T00:00:00Z'],
        ]];

        $this->gitHubClient->method('listCommits')
            ->willReturnCallback(function () use ($commitData): Generator {
                yield $commitData;
            });

        $this->gitHubClient->method('getCommitDetail')
            ->willReturn([
                'stats' => ['additions' => 1, 'deletions' => 0],
                'files' => [['filename' => 'f.txt']],
            ]);

        $firstCount = $this->syncService->syncCommits($repo, $account);
        self::assertSame(1, $firstCount);

        $secondCount = $this->syncService->syncCommits($repo, $account);
        self::assertSame(0, $secondCount);

        $commits = $this->em->getRepository(\App\Entity\Commit::class)->findBy(['repository' => $repo]);
        self::assertCount(1, $commits);
    }

    public function testSyncPullRequestsDeduplicatesByGithubId(): void
    {
        $account = $this->createAccount();
        $repo = $this->persistRepo('octocat/pr-test');

        $prData = [
            'id' => 1001,
            'title' => 'Add login',
            'body' => 'Implement OAuth login',
            'state' => 'merged',
            'merged_at' => '2026-07-01T10:00:00Z',
            'additions' => 50,
            'deletions' => 10,
            'changed_files' => 3,
            'user' => ['login' => 'dev'],
            'base' => ['ref' => 'main'],
            'labels' => [['name' => 'feature']],
        ];

        $this->gitHubClient->method('listPullRequests')
            ->willReturnCallback(function () use ($prData): Generator {
                yield $prData;
            });

        $this->syncService->syncPullRequests($repo, $account);
        $count = $this->syncService->syncPullRequests($repo, $account);

        self::assertSame(0, $count);

        $prs = $this->em->getRepository(\App\Entity\PullRequest::class)->findBy(['repository' => $repo]);
        self::assertCount(1, $prs);
        self::assertSame(1001, $prs[0]->getGithubId());
    }

    private function createAccount(): GithubAccount
    {
        return GithubAccountFactory::createOne([
            'githubUsername' => 'testuser',
        ])->_real();
    }

    private function persistRepo(string $fullName): GithubRepo
    {
        [$owner, $name] = explode('/', $fullName);

        return GithubRepoFactory::createOne([
            'githubId' => abs(crc32($name)) % 999999,
            'fullName' => $fullName,
            'name' => $name,
        ])->_real();
    }

    /**
     * @param array<array-key, mixed> $items
     */
    private function createGenerator(array $items): Generator
    {
        foreach ($items as $item) {
            yield $item;
        }
    }
}
