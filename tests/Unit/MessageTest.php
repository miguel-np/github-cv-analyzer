<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Message\AnalyzeCommitMessage;
use App\Message\SyncAccountMessage;
use App\Message\SyncRepositoryMessage;
use App\Message\TriggerDailySyncMessage;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

final class MessageTest extends TestCase
{
    public function testAnalyzeCommitMessageIsFinalReadonlyWithCommitId(): void
    {
        $reflection = new ReflectionClass(AnalyzeCommitMessage::class);

        self::assertTrue($reflection->isFinal());
        self::assertTrue($reflection->isReadOnly());

        $message = new AnalyzeCommitMessage(42);

        self::assertSame(42, $message->commitId);
    }

    public function testSyncAccountMessageIsFinalReadonlyWithAccountId(): void
    {
        $reflection = new ReflectionClass(SyncAccountMessage::class);

        self::assertTrue($reflection->isFinal());
        self::assertTrue($reflection->isReadOnly());

        $message = new SyncAccountMessage(7);

        self::assertSame(7, $message->githubAccountId);
    }

    public function testSyncRepositoryMessageIsFinalReadonlyWithRepoAndAccountId(): void
    {
        $reflection = new ReflectionClass(SyncRepositoryMessage::class);

        self::assertTrue($reflection->isFinal());
        self::assertTrue($reflection->isReadOnly());

        $message = new SyncRepositoryMessage(100, 200);

        self::assertSame(100, $message->repositoryId);
        self::assertSame(200, $message->githubAccountId);
    }

    public function testTriggerDailySyncMessageIsFinalReadonlyWithNoProperties(): void
    {
        $reflection = new ReflectionClass(TriggerDailySyncMessage::class);

        self::assertTrue($reflection->isFinal());
        self::assertTrue($reflection->isReadOnly());

        $message = new TriggerDailySyncMessage();

        $props = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
        self::assertCount(0, $props);
    }

    public function testAllMessagesAreReadonly(): void
    {
        $messageClasses = [
            AnalyzeCommitMessage::class,
            SyncAccountMessage::class,
            SyncRepositoryMessage::class,
            TriggerDailySyncMessage::class,
        ];

        foreach ($messageClasses as $class) {
            $reflection = new ReflectionClass($class);
            self::assertTrue($reflection->isReadOnly(), "{$class} should be readonly");
            self::assertTrue($reflection->isFinal(), "{$class} should be final");
        }
    }
}
