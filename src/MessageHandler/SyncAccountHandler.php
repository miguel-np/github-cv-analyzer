<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\GithubAccount;
use App\Message\SyncAccountMessage;
use App\Message\SyncRepositoryMessage;
use App\Service\GitHub\GitHubSyncService;
use App\Service\GitHub\SyncJobManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final readonly class SyncAccountHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private GitHubSyncService $syncService,
        private SyncJobManager $jobManager,
        private MessageBusInterface $bus,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(SyncAccountMessage $message): void
    {
        $account = $this->em->getRepository(GithubAccount::class)->find($message->githubAccountId);

        if (!$account) {
            $this->logger->warning('SyncAccountHandler: account not found', ['id' => $message->githubAccountId]);

            return;
        }

        $job = $this->jobManager->start($account);

        try {
            $repos = $this->syncService->syncRepositories($account);
            $this->logger->info('Repositories synced', [
                'account' => $account->getGithubUsername(),
                'count' => count($repos),
            ]);

            foreach ($repos as $repo) {
                $this->bus->dispatch(new SyncRepositoryMessage($repo->getId(), $message->githubAccountId));
            }

            $this->jobManager->complete($job, count($repos));
        } catch (\Throwable $e) {
            $this->logger->error('SyncAccountHandler failed', [
                'account' => $account->getGithubUsername(),
                'error' => $e->getMessage(),
            ]);

            $this->jobManager->fail($job, $e->getMessage());
        }
    }
}
