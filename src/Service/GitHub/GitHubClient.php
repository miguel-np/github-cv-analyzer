<?php

declare(strict_types=1);

namespace App\Service\GitHub;

use Generator;
use Github\Client;
use Github\Exception\RuntimeException as GithubRuntimeException;
use Github\HttpClient\Builder;
use RuntimeException;
use Symfony\Component\HttpClient\HttplugClient;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Throwable;

final class GitHubClient implements GitHubClientInterface
{
    private const PER_PAGE = 100;
    private const RATE_LIMIT_CACHE_KEY = 'github_rate_limit_remaining';

    private readonly Client $client;
    private bool $authenticated = false;

    public function __construct(
        private readonly TokenEncryptionService $tokenEncryption,
        private readonly ?CacheInterface $cache = null,
    ) {
        $builder = new Builder(new HttplugClient());
        $this->client = new Client($builder);
    }

    public function authenticate(string $encryptedToken): void
    {
        $token = $this->tokenEncryption->decrypt($encryptedToken);
        $this->client->authenticate($token, null, Client::AUTH_ACCESS_TOKEN);
        $this->authenticated = true;
    }

    public function isAuthenticated(): bool
    {
        return $this->authenticated;
    }

    public function verifyToken(): array
    {
        try {
            $user = $this->client->currentUser()->show();

            return ['valid' => true, 'username' => $user['login']];
        } catch (GithubRuntimeException $e) {
            if ($e->getCode() === 401) {
                return ['valid' => false, 'error' => 'Invalid or expired token.'];
            }

            return ['valid' => false, 'error' => 'Could not verify token: '.$e->getMessage()];
        }
    }

    public function getCurrentUsername(): string
    {
        $result = $this->verifyToken();
        if (!$result['valid']) {
            throw new GithubRuntimeException('Not authenticated');
        }

        return $result['username'] ?? '';
    }

    /**
     * @return Generator<array<string, mixed>>
     */
    public function listRepositories(): Generator
    {
        $this->checkRateLimit();
        $page = 1;

        do {
            $repos = $this->client->currentUser()->repositories('all', 'updated', 'asc', $page);
            foreach ($repos as $repo) {
                yield $repo;
            }
            ++$page;
        } while (count($repos) === self::PER_PAGE);
    }

    /**
     * @return Generator<array<string, mixed>>
     */
    public function listCommits(string $owner, string $repo, ?string $since = null, ?string $author = null): Generator
    {
        $this->checkRateLimit();
        $page = 1;
        $params = ['per_page' => self::PER_PAGE];

        if ($since !== null) {
            $params['since'] = $since;
        }

        if ($author !== null) {
            $params['author'] = $author;
        }

        do {
            $params['page'] = $page;
            $commits = $this->client->repo()->commits()->all($owner, $repo, $params);
            foreach ($commits as $commit) {
                yield $commit;
            }
            ++$page;
        } while (count($commits) === self::PER_PAGE);
    }

    public function getCommitDetail(string $owner, string $repo, string $sha): array
    {
        $this->checkRateLimit();

        return $this->client->repo()->commits()->show($owner, $repo, $sha);
    }

    /**
     * @return Generator<array<string, mixed>>
     */
    public function listPullRequests(string $owner, string $repo, string $state = 'all', ?string $since = null): Generator
    {
        $this->checkRateLimit();
        $page = 1;
        $params = [
            'state' => $state,
            'per_page' => self::PER_PAGE,
            'sort' => 'updated',
            'direction' => 'desc',
        ];

        if ($since !== null) {
            $params['since'] = $since;
        }

        do {
            $params['page'] = $page;
            $prs = $this->client->pullRequest()->all($owner, $repo, $params);
            foreach ($prs as $pr) {
                yield $pr;
            }
            ++$page;
        } while (count($prs) === self::PER_PAGE);
    }

    /**
     * @return Generator<array<string, mixed>>
     */
    public function listIssues(string $owner, string $repo, string $state = 'all', ?string $since = null): Generator
    {
        $this->checkRateLimit();
        $page = 1;
        $params = [
            'state' => $state,
            'filter' => 'all',
            'per_page' => self::PER_PAGE,
            'sort' => 'updated',
            'direction' => 'desc',
        ];

        if ($since !== null) {
            $params['since'] = $since;
        }

        do {
            $params['page'] = $page;
            $issues = $this->client->issues()->all($owner, $repo, $params);
            foreach ($issues as $issue) {
                if (isset($issue['pull_request'])) {
                    continue;
                }
                yield $issue;
            }
            ++$page;
        } while (count($issues) === self::PER_PAGE);
    }

    public function getRepository(string $owner, string $name): array
    {
        $this->checkRateLimit();

        return $this->client->repository()->show($owner, $name);
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    private function checkRateLimit(): void
    {
        if ($this->cache === null) {
            return;
        }

        $remaining = $this->cache->get(self::RATE_LIMIT_CACHE_KEY, function (ItemInterface $item): int {
            $item->expiresAfter(60);

            try {
                $response = $this->client->api('rate_limit')->getRateLimits();

                return $response['resources']['core']['remaining'] ?? 5000;
            } catch (Throwable) {
                return 5000;
            }
        });

        if ($remaining < 50) {
            throw new RuntimeException('GitHub API rate limit nearly exceeded. Try again in a few minutes.');
        }
    }
}
