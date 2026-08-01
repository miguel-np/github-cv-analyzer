<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Entity\AnalysisResult;
use App\Entity\Commit;
use App\Entity\User;
use App\Service\Analysis\CommitAnalyzer;
use App\Service\Analysis\LlmClientInterface;
use App\Service\Analysis\LlmFactoryInterface;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class CommitAnalyzerTest extends TestCase
{
    private LlmFactoryInterface&MockObject $llmFactory;
    private EntityManagerInterface&MockObject $em;
    private LoggerInterface&MockObject $logger;
    private CommitAnalyzer $analyzer;

    protected function setUp(): void
    {
        $this->llmFactory = $this->createMock(LlmFactoryInterface::class);

        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->analyzer = new CommitAnalyzer($this->llmFactory, $this->em, $this->logger);
    }

    public function testAnalyzeCreatesAnalysisResultWithCorrectProvider(): void
    {
        $user = new User();
        $commit = $this->createCommit();

        $this->llmFactory->method('create')->willReturn($this->createMockLlm());

        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (AnalysisResult $result) use ($commit): bool {
                return $result->getCommit() === $commit
                    && $result->getProvider() === 'ollama'
                    && $result->getModel() === 'llama3.2';
            }));

        $result = $this->analyzer->analyze($commit, $user);

        self::assertSame($commit, $result->getCommit());
        self::assertSame('ollama', $result->getProvider());
    }

    public function testAnalyzeMeasuresDurationInMs(): void
    {
        $user = new User();
        $commit = $this->createCommit();

        $mockLlm = $this->createMockLlm();
        $this->llmFactory->method('create')->willReturn($mockLlm);

        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (AnalysisResult $r): bool {
                return $r->getDurationMs() >= 0;
            }));

        $result = $this->analyzer->analyze($commit, $user);

        self::assertNotNull($result->getDurationMs());
    }

    public function testAnalyzeBatchSkipsAlreadyAnalyzedCommits(): void
    {
        $user = new User();
        $analyzedCommit = $this->createCommit();
        $unanalyzedCommit = $this->createCommit();

        $analysisResult = new AnalysisResult();
        $analyzedCommit->setAnalysisResult($analysisResult);

        $this->llmFactory->method('create')->willReturn($this->createMockLlm());

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $count = $this->analyzer->analyzeBatch([$analyzedCommit, $unanalyzedCommit], $user);

        self::assertSame(1, $count);
    }

    public function testAnalyzeBatchReturnsCorrectAnalyzedCount(): void
    {
        $user = new User();
        $commits = [$this->createCommit(), $this->createCommit(), $this->createCommit()];

        $this->llmFactory->method('create')->willReturn($this->createMockLlm());

        $this->em->expects($this->exactly(3))->method('persist');
        $this->em->expects($this->once())->method('flush');

        $count = $this->analyzer->analyzeBatch($commits, $user);

        self::assertSame(3, $count);
    }

    private function createMockLlm(): LlmClientInterface&MockObject
    {
        $llm = $this->createMock(LlmClientInterface::class);
        $llm->method('getProviderName')->willReturn('ollama');
        $llm->method('getModelName')->willReturn('llama3.2');
        $llm->method('chat')->willReturn([
            'classification' => 'feature',
            'complexity_score' => 3,
            'summary' => 'test',
            'impact_areas' => ['tests'],
            'technologies_found' => [],
            'patterns_used' => [],
            'code_quality_score' => 7,
            'tags' => [],
        ]);

        return $llm;
    }

    private function createCommit(): Commit
    {
        $commit = new Commit();
        $commit->setSha('abc123');
        $commit->setMessage('feat: add login');
        $commit->setDiffStats([
            ['filename' => 'src/Login.php', 'additions' => 10, 'deletions' => 2],
        ]);
        $commit->setAuthorEmail('dev@example.com');
        $commit->setAuthorName('Dev');
        $commit->setDate(new DateTimeImmutable());
        $commit->setAdditions(10);
        $commit->setDeletions(2);
        $commit->setFilesChanged(1);
        $commit->setIsMergeCommit(false);

        return $commit;
    }
}
