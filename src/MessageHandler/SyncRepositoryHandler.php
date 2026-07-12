<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\GithubAccount;
use App\Entity\GithubRepo;
use App\Message\AnalyzeCommitMessage;
use App\Message\SyncRepositoryMessage;
use App\Service\GitHub\GitHubSyncService;
use App\Service\GitHub\SyncJobManager;
use App\Repository\CommitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final readonly class SyncRepositoryHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private GitHubSyncService $syncService,
        private SyncJobManager $jobManager,
        private MessageBusInterface $bus,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(SyncRepositoryMessage $message): void
    {
        $repo = $this->em->getRepository(GithubRepo::class)->find($message->repositoryId);
        $account = $this->em->getRepository(GithubAccount::class)->find($message->githubAccountId);

        if (!$repo || !$account) {
            $this->logger->warning('SyncRepositoryHandler: repo or account not found', [
                'repoId' => $message->repositoryId,
                'accountId' => $message->githubAccountId,
            ]);

            return;
        }

        $repoJob = $this->jobManager->start($account, 'repo_data');
        $this->logger->info('Starting data collection', ['repo' => $repo->getFullName()]);
        $totalSynced = 0;

        try {
            $commitsSynced = $this->syncService->syncCommits($repo, $account);
            $this->logger->info('Commits synced', ['repo' => $repo->getFullName(), 'count' => $commitsSynced]);
            $totalSynced += $commitsSynced;

            $prsSynced = $this->syncService->syncPullRequests($repo, $account);
            $this->logger->info('PRs synced', ['repo' => $repo->getFullName(), 'count' => $prsSynced]);
            $totalSynced += $prsSynced;

            $issuesSynced = $this->syncService->syncIssues($repo, $account);
            $this->logger->info('Issues synced', ['repo' => $repo->getFullName(), 'count' => $issuesSynced]);
            $totalSynced += $issuesSynced;

            $this->jobManager->complete($repoJob, $totalSynced);

            $unanalyzedCommits = $this->em->getRepository(CommitRepository::class)
                ->createQueryBuilder('c')
                ->leftJoin('c.analysisResult', 'a')
                ->where('c.repository = :repo')
                ->andWhere('a.id IS NULL')
                ->setParameter('repo', $repo)
                ->setMaxResults(50)
                ->getQuery()
                ->getResult();

            foreach ($unanalyzedCommits as $commit) {
                $this->bus->dispatch(new AnalyzeCommitMessage($commit->getId()));
            }
        } catch (\Throwable $e) {
            $this->logger->error('SyncRepositoryHandler failed', [
                'repo' => $repo->getFullName(),
                'error' => $e->getMessage(),
            ]);

            $this->jobManager->fail($repoJob, $e->getMessage());
        }
    }
}
