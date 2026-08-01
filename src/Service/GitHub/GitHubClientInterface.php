<?php

declare(strict_types=1);

namespace App\Service\GitHub;

use Generator;

/**
 * @method \Github\Client getClient()
 */
interface GitHubClientInterface
{
    public function authenticate(string $encryptedToken): void;

    public function isAuthenticated(): bool;

    /**
     * @return array{valid: bool, username?: string, error?: string}
     */
    public function verifyToken(): array;

    public function getCurrentUsername(): string;

    /**
     * @return Generator<array<string, mixed>>
     */
    public function listRepositories(): Generator;

    /**
     * @return Generator<array<string, mixed>>
     */
    public function listCommits(string $owner, string $repo, ?string $since = null, ?string $author = null): Generator;

    /**
     * @return array<string, mixed>
     */
    public function getCommitDetail(string $owner, string $repo, string $sha): array;

    /**
     * @return Generator<array<string, mixed>>
     */
    public function listPullRequests(string $owner, string $repo, string $state = 'all', ?string $since = null): Generator;

    /**
     * @return Generator<array<string, mixed>>
     */
    public function listIssues(string $owner, string $repo, string $state = 'all', ?string $since = null): Generator;

    /**
     * @return array<string, mixed>
     */
    public function getRepository(string $owner, string $name): array;
}
