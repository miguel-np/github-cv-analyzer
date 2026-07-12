<?php

declare(strict_types=1);

namespace App\Service\GitHub;

use Github\Client;
use Github\Exception\RuntimeException as GithubRuntimeException;
use Github\HttpClient\Builder;
use Symfony\Component\HttpClient\HttplugClient;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class GitHubClient
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

            return ['valid' => false, 'error' => 'Could not verify token: ' . $e->getMessage()];
        }
    }

    public function getCurrentUsername(): string
    {
        $result = $this->verifyToken();
        if (!$result['valid']) {
            throw new GithubRuntimeException('Not authenticated');
        }

        return $result['username'];
    }

    /**
     * @return \Generator<array>
     */
    public function listRepositories(): \Generator
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
            } catch (\Throwable) {
                return 5000;
            }
        });

        if ($remaining < 50) {
            throw new \RuntimeException('GitHub API rate limit nearly exceeded. Try again in a few minutes.');
        }
    }
}
