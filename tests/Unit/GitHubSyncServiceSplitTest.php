<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Service\GitHub\GitHubSyncService;
use PHPUnit\Framework\TestCase;

final class GitHubSyncServiceSplitTest extends TestCase
{
    public function testSplitStandardFullNameReturnsOwnerAndName(): void
    {
        [$owner, $name] = GitHubSyncService::splitRepoFullName('octocat/Hello-World');

        self::assertSame('octocat', $owner);
        self::assertSame('Hello-World', $name);
    }

    public function testSplitFullNameWithNoSlashReturnsOwnerAndNull(): void
    {
        /** @var string|null $name */
        [$owner, $name] = @GitHubSyncService::splitRepoFullName('single-repo');

        self::assertSame('single-repo', $owner);
        self::assertNull($name);
    }

    public function testSplitFullNameWithMultipleSlashesSplitsFirstOnly(): void
    {
        [$owner, $name] = GitHubSyncService::splitRepoFullName('org/repo/subfolder');

        self::assertSame('org', $owner);
        self::assertSame('repo', $name);
    }
}
