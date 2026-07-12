<?php

declare(strict_types=1);

namespace App\Service\GitHub;

use App\Entity\Commit;
use App\Entity\GithubAccount;
use App\Entity\GithubRepo;
use App\Entity\Issue;
use App\Entity\PullRequest;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final readonly class GitHubSyncService
{
    public function __construct(
        private GitHubClient $client,
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
    ) {
    }

    public function syncRepositories(GithubAccount $account): array
    {
        $this->client->authenticate($account->getEncryptedToken());

        $existingByGithubId = $this->loadExistingRepos();
        $synced = [];

        foreach ($this->client->listRepositories() as $repoData) {
            if ($repoData['fork'] && !$this->hasContributions($repoData)) {
                continue;
            }

            $githubRepo = $existingByGithubId[$repoData['id']] ?? null;

            if ($githubRepo === null) {
                $githubRepo = new GithubRepo();
                $this->em->persist($githubRepo);
                $existingByGithubId[$repoData['id']] = $githubRepo;
            }

            $this->updateRepo($githubRepo, $repoData);
            $account->getGithubRepos()->contains($githubRepo) ?: $account->getGithubRepos()->add($githubRepo);

            $synced[] = $githubRepo;
        }

        $account->setLastSyncedAt(new \DateTimeImmutable());
        $this->em->flush();

        $this->logger->info('GitHub sync completed', [
            'account' => $account->getGithubUsername(),
            'synced' => count($synced),
        ]);

        return $synced;
    }

    public function syncCommits(GithubRepo $repo, GithubAccount $account): int
    {
        $this->client->authenticate($account->getEncryptedToken());

        [$owner, $name] = explode('/', $repo->getFullName());
        $since = $repo->getLastSyncedAt()?->format('Y-m-d\TH:i:s\Z');

        $syncedCount = 0;
        $username = $account->getGithubUsername();

        foreach ($this->client->listCommits($owner, $name, $since, $username) as $commitData) {
            $sha = $commitData['sha'];

            $existing = $this->em->getRepository(Commit::class)->findOneBy(['sha' => $sha]);
            if ($existing !== null) {
                continue;
            }

            $detail = $this->client->getCommitDetail($owner, $name, $sha);

            $commit = new Commit();
            $commit->setRepository($repo);
            $commit->setSha($sha);
            $commit->setMessage($commitData['commit']['message']);
            $commit->setAuthorEmail($commitData['commit']['author']['email'] ?? '');
            $commit->setAuthorName($commitData['commit']['author']['name'] ?? '');
            $commit->setDate(new \DateTimeImmutable($commitData['commit']['author']['date']));
            $commit->setAdditions($detail['stats']['additions'] ?? 0);
            $commit->setDeletions($detail['stats']['deletions'] ?? 0);
            $commit->setFilesChanged(count($detail['files'] ?? []));
            $commit->setIsMergeCommit(count($commitData['parents'] ?? []) > 1);
            $commit->setDiffStats($detail['files'] ?? []);

            $this->em->persist($commit);
            ++$syncedCount;

            if ($syncedCount % 20 === 0) {
                $this->em->flush();
            }
        }

        $repo->setLastSyncedAt(new \DateTimeImmutable());
        $this->em->flush();

        $this->logger->info('Commit sync completed', [
            'repo' => $repo->getFullName(),
            'synced' => $syncedCount,
        ]);

        return $syncedCount;
    }

    public function syncPullRequests(GithubRepo $repo, GithubAccount $account): int
    {
        $this->client->authenticate($account->getEncryptedToken());

        [$owner, $name] = explode('/', $repo->getFullName());
        $existingIds = $this->loadExistingPRIds($repo);
        $syncedCount = 0;

        foreach ($this->client->listPullRequests($owner, $name) as $prData) {
            if (in_array($prData['id'], $existingIds, true)) {
                continue;
            }

            $pr = new PullRequest();
            $pr->setRepository($repo);
            $pr->setGithubId($prData['id']);
            $pr->setTitle($prData['title']);
            $pr->setBody($prData['body'] ?? null);
            $pr->setState($prData['state']);
            $pr->setMerged($prData['merged_at'] !== null);
            $pr->setAdditions($prData['additions'] ?? 0);
            $pr->setDeletions($prData['deletions'] ?? 0);
            $pr->setChangedFiles($prData['changed_files'] ?? 0);
            $pr->setMergedAt($prData['merged_at'] ? new \DateTimeImmutable($prData['merged_at']) : null);

            $cls = $this->extractClassificationLabels($prData['labels'] ?? []);
            $pr->setMetadata([
                'labels' => $cls,
                'user_login' => $prData['user']['login'] ?? null,
                'base_ref' => $prData['base']['ref'] ?? null,
            ]);

            $this->em->persist($pr);
            $existingIds[] = $prData['id'];
            ++$syncedCount;
        }

        $this->em->flush();

        $this->logger->info('PR sync completed', [
            'repo' => $repo->getFullName(),
            'synced' => $syncedCount,
        ]);

        return $syncedCount;
    }

    public function syncIssues(GithubRepo $repo, GithubAccount $account): int
    {
        $this->client->authenticate($account->getEncryptedToken());

        [$owner, $name] = explode('/', $repo->getFullName());
        $existingIds = $this->loadExistingIssueIds($repo);
        $syncedCount = 0;

        foreach ($this->client->listIssues($owner, $name) as $issueData) {
            if (in_array($issueData['id'], $existingIds, true)) {
                continue;
            }

            $issue = new Issue();
            $issue->setRepository($repo);
            $issue->setGithubId($issueData['id']);
            $issue->setTitle($issueData['title']);
            $issue->setBody($issueData['body'] ?? null);
            $issue->setState($issueData['state']);
            $issue->setClosedAt($issueData['closed_at'] ? new \DateTimeImmutable($issueData['closed_at']) : null);
            $issue->setLabels($this->extractClassificationLabels($issueData['labels'] ?? []));
            $issue->setMetadata([
                'user_login' => $issueData['user']['login'] ?? null,
                'assignees' => array_map(fn ($a) => $a['login'] ?? '', $issueData['assignees'] ?? []),
            ]);

            $this->em->persist($issue);
            $existingIds[] = $issueData['id'];
            ++$syncedCount;
        }

        $this->em->flush();

        $this->logger->info('Issue sync completed', [
            'repo' => $repo->getFullName(),
            'synced' => $syncedCount,
        ]);

        return $syncedCount;
    }

    /**
     * @return array<int, GithubRepo>
     */
    private function loadExistingRepos(): array
    {
        $repos = $this->em->getRepository(GithubRepo::class)->findAll();
        $indexed = [];

        foreach ($repos as $repo) {
            $indexed[$repo->getGithubId()] = $repo;
        }

        return $indexed;
    }

    /**
     * @return int[]
     */
    private function loadExistingPRIds(GithubRepo $repo): array
    {
        $prs = $this->em->getRepository(PullRequest::class)->createQueryBuilder('p')
            ->select('p.githubId')
            ->where('p.repository = :repo')
            ->setParameter('repo', $repo)
            ->getQuery()
            ->getSingleColumnResult();

        return array_map('intval', $prs);
    }

    /**
     * @return int[]
     */
    private function loadExistingIssueIds(GithubRepo $repo): array
    {
        $issues = $this->em->getRepository(Issue::class)->createQueryBuilder('i')
            ->select('i.githubId')
            ->where('i.repository = :repo')
            ->setParameter('repo', $repo)
            ->getQuery()
            ->getSingleColumnResult();

        return array_map('intval', $issues);
    }

    private function extractClassificationLabels(array $labels): array
    {
        return array_map(fn ($l) => $l['name'] ?? '', $labels);
    }

    private function updateRepo(GithubRepo $repo, array $repoData): void
    {
        $repo->setGithubId($repoData['id']);
        $repo->setFullName($repoData['full_name']);
        $repo->setName($repoData['name']);
        $repo->setDescription($repoData['description'] ?? null);
        $repo->setLanguage($repoData['language'] ?? null);
        $repo->setStars($repoData['stargazers_count'] ?? 0);
        $repo->setForks($repoData['forks_count'] ?? 0);
        $repo->setIsFork($repoData['fork'] ?? false);
        $repo->setIsPrivate($repoData['private'] ?? false);
        $repo->setMetadata([
            'topics' => $repoData['topics'] ?? [],
            'homepage' => $repoData['homepage'] ?? null,
            'license' => $repoData['license']['spdx_id'] ?? null,
        ]);
    }

    private function hasContributions(array $repoData): bool
    {
        return ($repoData['permissions']['push'] ?? false)
            || ($repoData['permissions']['admin'] ?? false);
    }
}
