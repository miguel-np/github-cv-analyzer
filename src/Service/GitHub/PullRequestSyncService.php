<?php

declare(strict_types=1);

namespace App\Service\GitHub;

use App\Entity\GithubAccount;
use App\Entity\GithubRepo;
use App\Entity\PullRequest;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final readonly class PullRequestSyncService
{
    public function __construct(
        private GitHubClientInterface $client,
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
    ) {
    }

    public function syncPullRequests(GithubRepo $repo, GithubAccount $account): int
    {
        [$owner, $name] = GitHubSyncService::splitRepoFullName($repo->getFullName());
        $since = $repo->getLastSyncedAt()?->format('Y-m-d\TH:i:s\Z');

        $existingIds = $this->loadExistingPRIds($repo);
        $syncedCount = 0;

        foreach ($this->client->listPullRequests($owner, $name, 'all', $since) as $prData) {
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
            $pr->setMergedAt($prData['merged_at'] ? new DateTimeImmutable($prData['merged_at']) : null);

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
     * @param array<array{name?: string}> $labels
     *
     * @return string[]
     */
    private function extractClassificationLabels(array $labels): array
    {
        return array_map(fn ($l) => $l['name'] ?? '', $labels);
    }
}
