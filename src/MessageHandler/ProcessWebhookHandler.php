<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\GithubAccount;
use App\Entity\GithubRepo;
use App\Message\ProcessWebhookMessage;
use App\Message\SyncRepositoryMessage;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final readonly class ProcessWebhookHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private MessageBusInterface $bus,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(ProcessWebhookMessage $message): void
    {
        $this->logger->info('Processing webhook', [
            'event' => $message->event,
        ]);

        $repoFullName = $message->payload['repository']['full_name'] ?? null;

        if ($repoFullName === null) {
            return;
        }

        $repo = $this->em->getRepository(GithubRepo::class)->findOneBy(['fullName' => $repoFullName]);

        if ($repo === null) {
            $this->logger->info('Repository not found for webhook', ['repo' => $repoFullName]);

            return;
        }

        $account = $this->em->getRepository(GithubAccount::class)->findOneBy([]);

        if ($account === null) {
            return;
        }

        $this->bus->dispatch(new SyncRepositoryMessage((int) $repo->getId(), (int) $account->getId()));

        $this->logger->info('Dispatched sync from webhook', [
            'repo' => $repoFullName,
            'event' => $message->event,
        ]);
    }
}
