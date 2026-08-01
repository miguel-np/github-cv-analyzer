<?php

declare(strict_types=1);

namespace App\Service\GitHub;

use App\Entity\GithubAccount;
use App\Entity\GithubRepo;
use App\Entity\Issue;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final readonly class IssueSyncService
{
    public function __construct(
        private GitHubClientInterface $client,
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
    ) {
    }

    public function syncIssues(GithubRepo $repo, GithubAccount $account): int
    {
        [$owner, $name] = GitHubSyncService::splitRepoFullName($repo->getFullName());
        $since = $repo->getLastSyncedAt()?->format('Y-m-d\TH:i:s\Z');

        $existingIds = $this->loadExistingIssueIds($repo);
        $syncedCount = 0;

        foreach ($this->client->listIssues($owner, $name, 'all', $since) as $issueData) {
            if (in_array($issueData['id'], $existingIds, true)) {
                continue;
            }

            $issue = new Issue();
            $issue->setRepository($repo);
            $issue->setGithubId($issueData['id']);
            $issue->setTitle($issueData['title']);
            $issue->setBody($issueData['body'] ?? null);
            $issue->setState($issueData['state']);
            $issue->setClosedAt($issueData['closed_at'] ? new DateTimeImmutable($issueData['closed_at']) : null);
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

    /**
     * @param array<array{name?: string}> $labels
     *
     * @return string[]
     */
    private function extractClassificationLabels(array $labels): array
    {
        return array_map(fn ($l) => $l['name'] ?? '', $labels);
    }
}
