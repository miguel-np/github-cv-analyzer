<?php

declare(strict_types=1);

namespace App\Service\GitHub;

use Github\Client;
use Github\HttpClient\Builder;
use Symfony\Component\HttpClient\HttplugClient;

final readonly class GitHubClient
{
    private Client $client;

    public function __construct(
        private TokenEncryptionService $tokenEncryption,
    ) {
        $builder = new Builder(new HttplugClient());
        $this->client = new Client($builder);
    }

    public function authenticate(string $encryptedToken): void
    {
        $token = $this->tokenEncryption->decrypt($encryptedToken);
        $this->client->authenticate($token, null, Client::AUTH_ACCESS_TOKEN);
    }

    public function isAuthenticated(): bool
    {
        try {
            $this->client->currentUser()->show();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function getCurrentUsername(): string
    {
        return $this->client->currentUser()->show()['login'];
    }

    public function listRepositories(): array
    {
        $allRepos = [];
        $page = 1;

        do {
            $repos = $this->client->currentUser()->repositories('all', 'updated', 'asc', $page);
            $allRepos = array_merge($allRepos, $repos);
            ++$page;
        } while (count($repos) === 100);

        return $allRepos;
    }

    public function getRepository(string $owner, string $name): array
    {
        return $this->client->repository()->show($owner, $name);
    }

    public function getClient(): Client
    {
        return $this->client;
    }
}
