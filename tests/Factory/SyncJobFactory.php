<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\SyncJob;
use DateTimeImmutable;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<SyncJob>
 */
final class SyncJobFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return SyncJob::class;
    }

    protected function defaults(): array
    {
        return [
            'githubAccount' => GithubAccountFactory::new(),
            'type' => SyncJob::TYPE_FULL,
            'status' => SyncJob::STATUS_COMPLETED,
            'itemsProcessed' => self::faker()->numberBetween(1, 50),
            'startedAt' => new DateTimeImmutable('-1 hour'),
            'finishedAt' => new DateTimeImmutable(),
            'errorLog' => [],
        ];
    }
}
