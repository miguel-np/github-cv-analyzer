<?php

declare(strict_types=1);

namespace App\Service\GitHub;

use App\Entity\Commit;
use App\Entity\GithubAccount;
use App\Entity\GithubRepo;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final readonly class CommitSyncService
{
    private const BATCH_SIZE = 100;
    private const FLUSH_INTERVAL = 20;

    public function __construct(
        private GitHubClientInterface $client,
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
    ) {
    }

    public function syncCommits(GithubRepo $repo, GithubAccount $account): int
    {
        [$owner, $name] = GitHubSyncService::splitRepoFullName($repo->getFullName());
        $since = $repo->getLastSyncedAt()?->format('Y-m-d\TH:i:s\Z');
        $username = $account->getGithubUsername();
        $syncedCount = 0;
        $batch = [];

        foreach ($this->client->listCommits($owner, $name, $since, $username) as $commitData) {
            $batch[] = $commitData;

            if (count($batch) >= self::BATCH_SIZE) {
                $syncedCount += $this->processBatch($repo, $batch);
                $batch = [];
            }
        }

        if (count($batch) > 0) {
            $syncedCount += $this->processBatch($repo, $batch);
        }

        $repo->setLastSyncedAt(new DateTimeImmutable());
        $this->em->flush();

        $this->logger->info('Commit sync completed', [
            'repo' => $repo->getFullName(),
            'synced' => $syncedCount,
        ]);

        return $syncedCount;
    }

    /**
     * @param array<int, array<string, mixed>> $batch
     */
    private function processBatch(GithubRepo $repo, array $batch): int
    {
        $batch = array_filter($batch, fn (array $item) => isset($item['sha']));
        if ($batch === []) {
            return 0;
        }

        $shas = array_column($batch, 'sha');

        $existingShas = $this->em->getRepository(Commit::class)
            ->createQueryBuilder('c')
            ->select('c.sha')
            ->where('c.repository = :repo')
            ->andWhere('c.sha IN (:shas)')
            ->setParameter('repo', $repo)
            ->setParameter('shas', $shas)
            ->getQuery()
            ->getSingleColumnResult();

        $existing = array_flip($existingShas);
        $count = 0;

        foreach ($batch as $commitData) {
            $sha = $commitData['sha'];

            if (isset($existing[$sha])) {
                continue;
            }

            $commit = new Commit();
            $commit->setRepository($repo);
            $commit->setSha($sha);
            $commit->setMessage($commitData['commit']['message'] ?? '');
            $commit->setAuthorEmail($commitData['commit']['author']['email'] ?? '');
            $commit->setAuthorName($commitData['commit']['author']['name'] ?? '');
            $commit->setDate(new DateTimeImmutable($commitData['commit']['author']['date'] ?? 'now'));
            $commit->setAdditions(0);
            $commit->setDeletions(0);
            $commit->setFilesChanged(0);
            $commit->setIsMergeCommit(count($commitData['parents'] ?? []) > 1);
            $commit->setDiffStats([]);

            $this->em->persist($commit);
            ++$count;

            if ($count % self::FLUSH_INTERVAL === 0) {
                $this->em->flush();
            }
        }

        $this->em->flush();

        return $count;
    }
}
