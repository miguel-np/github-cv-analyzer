<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\GithubAccount;
use App\Entity\GithubRepo;
use App\Message\SyncRepositoryMessage;
use App\Service\GitHub\GitHubSyncService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class SyncRepositoryHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private GitHubSyncService $syncService,
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

        $this->logger->info('Starting data collection', ['repo' => $repo->getFullName()]);

        try {
            $commitsSynced = $this->syncService->syncCommits($repo, $account);
            $this->logger->info('Commits synced', ['repo' => $repo->getFullName(), 'count' => $commitsSynced]);

            $prsSynced = $this->syncService->syncPullRequests($repo, $account);
            $this->logger->info('PRs synced', ['repo' => $repo->getFullName(), 'count' => $prsSynced]);

            $issuesSynced = $this->syncService->syncIssues($repo, $account);
            $this->logger->info('Issues synced', ['repo' => $repo->getFullName(), 'count' => $issuesSynced]);
        } catch (\Throwable $e) {
            $this->logger->error('SyncRepositoryHandler failed', [
                'repo' => $repo->getFullName(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
