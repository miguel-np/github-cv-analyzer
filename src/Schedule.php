<?php

declare(strict_types=1);

namespace App;

use App\Message\SyncAccountMessage;
use App\Repository\GithubAccountRepository;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule as SymfonySchedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

#[AsSchedule]
class Schedule implements ScheduleProviderInterface
{
    public function __construct(
        private CacheInterface $cache,
        private GithubAccountRepository $accountRepo,
    ) {
    }

    public function getSchedule(): SymfonySchedule
    {
        $schedule = (new SymfonySchedule())
            ->stateful($this->cache)
            ->processOnlyLastMissedRun(true);

        $accounts = $this->accountRepo->findAll();

        foreach ($accounts as $account) {
            $schedule->add(
                RecurringMessage::every('12 hours', new SyncAccountMessage($account->getId()))
            );
        }

        return $schedule;
    }
}
