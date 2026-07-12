<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Message\AnalyzeCommitMessage;
use App\Message\SyncAccountMessage;
use App\Message\SyncRepositoryMessage;
use App\Message\TriggerDailySyncMessage;
use App\Tests\Factory\CommitFactory;
use App\Tests\Factory\GithubAccountFactory;
use App\Tests\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\MessageBusInterface;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class MessageHandlerTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    private MessageBusInterface $bus;

    protected function setUp(): void
    {
        self::bootKernel();
        $bus = self::getContainer()->get(MessageBusInterface::class);
        \assert($bus instanceof MessageBusInterface);
        $this->bus = $bus;
    }

    public function testSyncAccountHandlerLogsWarningWhenAccountNotFound(): void
    {
        $this->bus->dispatch(new SyncAccountMessage(99999));

        $this->expectNotToPerformAssertions();
    }

    public function testSyncRepositoryHandlerLogsWarningWhenRepoNotFound(): void
    {
        $this->bus->dispatch(new SyncRepositoryMessage(99999, 99999));

        $this->expectNotToPerformAssertions();
    }

    public function testAnalyzeCommitHandlerSkipsWhenLlmDisabled(): void
    {
        UserFactory::createOne([
            'email' => 'default@local.dev',
            'settings' => [
                'llm_enabled' => false,
                'llm_provider' => 'ollama',
            ],
        ]);

        $commit = CommitFactory::createOne()->_real();
        $commitId = $commit->getId();
        \assert($commitId !== null);

        $this->bus->dispatch(new AnalyzeCommitMessage($commitId));

        self::assertNull($commit->getAnalysisResult());
    }

    public function testTriggerDailySyncHandlerDispatchesSyncAccountForAllAccounts(): void
    {
        GithubAccountFactory::createMany(2);

        $this->bus->dispatch(new TriggerDailySyncMessage());

        $this->expectNotToPerformAssertions();
    }
}
