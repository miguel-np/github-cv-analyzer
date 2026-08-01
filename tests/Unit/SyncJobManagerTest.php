<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Entity\GithubAccount;
use App\Entity\SyncJob;
use App\Service\GitHub\SyncJobManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SyncJobManagerTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private SyncJobManager $manager;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->manager = new SyncJobManager($this->em);
    }

    public function testStartCreatesJobWithRunningStatusAndStartedAt(): void
    {
        $account = $this->createMock(GithubAccount::class);

        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (SyncJob $job) use ($account): bool {
                return $job->getStatus() === SyncJob::STATUS_RUNNING
                    && $job->getType() === SyncJob::TYPE_FULL
                    && $job->getGithubAccount() === $account
                    && $job->getStartedAt() !== null;
            }));

        $this->em->expects($this->once())->method('flush');

        $job = $this->manager->start($account);

        self::assertSame(SyncJob::STATUS_RUNNING, $job->getStatus());
        self::assertNotNull($job->getStartedAt());
    }

    public function testCompleteSetsStatusCompletedAndFinishedAt(): void
    {
        $job = new SyncJob();

        $this->em->expects($this->once())->method('flush');

        $this->manager->complete($job, 42);

        self::assertSame(SyncJob::STATUS_COMPLETED, $job->getStatus());
        self::assertSame(42, $job->getItemsProcessed());
        self::assertNotNull($job->getFinishedAt());
    }

    public function testFailSetsStatusFailedAndErrorLog(): void
    {
        $job = new SyncJob();
        $error = 'Rate limit exceeded';

        $this->em->expects($this->once())->method('flush');

        $this->manager->fail($job, $error);

        self::assertSame(SyncJob::STATUS_FAILED, $job->getStatus());
        self::assertNotNull($job->getFinishedAt());
        self::assertSame($error, $job->getErrorLog()['error'] ?? null);
    }

    public function testGetLatestRunningReturnsNullWhenNoRunningJobs(): void
    {
        $account = $this->createMock(GithubAccount::class);
        $account->method('getId')->willReturn(1);

        $repo = $this->createMock(EntityRepository::class);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->with(
                ['githubAccount' => $account, 'status' => SyncJob::STATUS_RUNNING],
                ['startedAt' => 'DESC'],
            )
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(SyncJob::class)
            ->willReturn($repo);

        $result = $this->manager->getLatestRunning($account);
        self::assertNull($result);
    }

    public function testGetLatestRunningReturnsMostRecentRunningJob(): void
    {
        $account = $this->createMock(GithubAccount::class);
        $job = new SyncJob();

        $repo = $this->createMock(EntityRepository::class);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->willReturn($job);

        $this->em->method('getRepository')->willReturn($repo);

        $result = $this->manager->getLatestRunning($account);
        self::assertSame($job, $result);
    }
}
