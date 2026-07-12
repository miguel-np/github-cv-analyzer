<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\SyncAccountMessage;
use App\Message\TriggerDailySyncMessage;
use App\Repository\GithubAccountRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final readonly class TriggerDailySyncHandler
{
    public function __construct(
        private GithubAccountRepository $accountRepo,
        private MessageBusInterface $bus,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(TriggerDailySyncMessage $message): void
    {
        $accounts = $this->accountRepo->findAll();
        $this->logger->info('Daily sync triggered', ['accounts' => count($accounts)]);

        foreach ($accounts as $account) {
            $this->bus->dispatch(new SyncAccountMessage($account->getId()));
        }
    }
}
