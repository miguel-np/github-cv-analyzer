<?php

declare(strict_types=1);

namespace App\Service\GitHub;

use App\Entity\GithubAccount;
use App\Entity\GithubRepo;

final readonly class GitHubSyncService
{
    public function __construct(
        private RepoSyncService $repoSync,
        private CommitSyncService $commitSync,
        private PullRequestSyncService $prSync,
        private IssueSyncService $issueSync,
    ) {
    }

    /**
     * @return GithubRepo[]
     */
    public function syncRepositories(GithubAccount $account): array
    {
        return $this->repoSync->syncRepositories($account);
    }

    public function syncCommits(GithubRepo $repo, GithubAccount $account): int
    {
        return $this->commitSync->syncCommits($repo, $account);
    }

    public function syncPullRequests(GithubRepo $repo, GithubAccount $account): int
    {
        return $this->prSync->syncPullRequests($repo, $account);
    }

    public function syncIssues(GithubRepo $repo, GithubAccount $account): int
    {
        return $this->issueSync->syncIssues($repo, $account);
    }

    /**
     * @return array{0: string, 1: string}
     */
    public static function splitRepoFullName(string $fullName): array
    {
        $parts = explode('/', $fullName);

        return [$parts[0], $parts[1]];
    }
}
