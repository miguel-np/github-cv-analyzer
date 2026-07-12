<?php

declare(strict_types=1);

namespace App\Service\GitHub;

use App\Entity\GithubAccount;
use App\Entity\SyncJob;
use Doctrine\ORM\EntityManagerInterface;

final readonly class SyncJobManager
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    public function start(GithubAccount $account, string $type = SyncJob::TYPE_FULL): SyncJob
    {
        $job = new SyncJob();
        $job->setGithubAccount($account);
        $job->setType($type);
        $job->setStatus(SyncJob::STATUS_RUNNING);
        $job->setStartedAt(new \DateTimeImmutable());

        $this->em->persist($job);
        $this->em->flush();

        return $job;
    }

    public function complete(SyncJob $job, int $itemsProcessed): void
    {
        $job->setStatus(SyncJob::STATUS_COMPLETED);
        $job->setItemsProcessed($itemsProcessed);
        $job->setFinishedAt(new \DateTimeImmutable());

        $this->em->flush();
    }

    public function fail(SyncJob $job, string $error): void
    {
        $job->setStatus(SyncJob::STATUS_FAILED);
        $job->setErrorLog(['error' => $error, 'failed_at' => (new \DateTimeImmutable())->format('c')]);
        $job->setFinishedAt(new \DateTimeImmutable());

        $this->em->flush();
    }

    public function getLatestRunning(GithubAccount $account): ?SyncJob
    {
        return $this->em->getRepository(SyncJob::class)->findOneBy(
            ['githubAccount' => $account, 'status' => SyncJob::STATUS_RUNNING],
            ['startedAt' => 'DESC']
        );
    }
}
