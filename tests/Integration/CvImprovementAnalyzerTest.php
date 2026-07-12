<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\User;
use App\Service\Analysis\CvImprovementAnalyzer;
use App\Service\Analysis\LlmClientInterface;
use App\Service\Analysis\LlmFactoryInterface;
use App\Tests\Factory\AnalysisResultFactory;
use App\Tests\Factory\CommitFactory;
use App\Tests\Factory\GithubAccountFactory;
use App\Tests\Factory\GithubRepoFactory;
use App\Tests\Factory\UserFactory;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class CvImprovementAnalyzerTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    public function testAnalyzeReturnsNullWhenNoAccountLinked(): void
    {
        $user = UserFactory::createOne([
            'email' => 'default@local.dev',
            'settings' => ['llm_enabled' => true, 'llm_provider' => 'ollama'],
        ])->_real();

        $analyzer = $this->createAnalyzer();

        $result = $analyzer->analyze($user);

        self::assertNull($result);
    }

    public function testAnalyzeReturnsNullWhenNoRepositories(): void
    {
        $user = UserFactory::createOne([
            'email' => 'default@local.dev',
            'settings' => ['llm_enabled' => true, 'llm_provider' => 'ollama'],
        ])->_real();
        GithubAccountFactory::createOne([
            'user' => $user,
            'githubUsername' => 'empty-user',
        ]);

        $analyzer = $this->createAnalyzer();

        $result = $analyzer->analyze($user);

        self::assertNull($result);
    }

    public function testAnalyzeReturnsAnalysisWhenLlmsucceeds(): void
    {
        $user = UserFactory::createOne([
            'email' => 'default@local.dev',
            'settings' => ['llm_enabled' => true, 'llm_provider' => 'ollama'],
        ])->_real();

        $account = GithubAccountFactory::createOne([
            'user' => $user,
            'githubUsername' => 'cv-test-user',
        ])->_real();

        $repo = GithubRepoFactory::createOne([
            'fullName' => 'cv-test/project',
            'name' => 'cv-project',
        ])->_real();
        $account->getGithubRepos()->add($repo);
        $repo->getContributors()->add($account);

        $em = self::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class);
        \assert($em instanceof \Doctrine\ORM\EntityManagerInterface);
        $em->flush();

        $commit = CommitFactory::createOne([
            'repository' => $repo,
            'sha' => 'cv-analysis-sha',
            'date' => new \DateTimeImmutable('-7 days'),
        ])->_real();

        AnalysisResultFactory::createOne([
            'commit' => $commit,
            'classification' => [
                'classification' => 'feature',
                'complexity_score' => 5,
                'summary' => 'Added CV feature',
                'impact_areas' => ['analysis'],
                'technologies_found' => ['PHP', 'Symfony'],
                'patterns_used' => ['dependency injection'],
                'code_quality_score' => 8,
                'tags' => ['backend'],
            ],
        ]);

        $em = self::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class);
        \assert($em instanceof \Doctrine\ORM\EntityManagerInterface);
        $em->flush();

        $container = self::getContainer();

        $mockLlm = $this->createMock(LlmClientInterface::class);
        $mockLlm->method('chat')->willReturn([
            'overall_assessment' => 'Strong PHP developer with diverse experience.',
            'strengths' => ['PHP expertise', 'Symfony knowledge'],
            'gaps' => [
                ['area' => 'Testing', 'severity' => 'medium', 'suggestion' => 'Add more test coverage'],
            ],
            'improvements' => [
                ['action' => 'Learn Docker', 'reason' => 'Container skills needed', 'priority' => 'high'],
            ],
        ]);
        $mockLlm->method('getProviderName')->willReturn('ollama');
        $mockLlm->method('getModelName')->willReturn('llama3.2');

        $mockFactory = $this->createMock(LlmFactoryInterface::class);
        $mockFactory->method('create')->willReturn($mockLlm);

        $analyzer = new CvImprovementAnalyzer(
            $mockFactory,
            $container->get(\App\Repository\GithubAccountRepository::class),
            $container->get(\App\Repository\GithubRepoRepository::class),
            $container->get(\App\Repository\CommitRepository::class),
            new NullLogger(),
        );

        $result = $analyzer->analyze($user);

        self::assertNotNull($result);
        self::assertArrayHasKey('overall_assessment', $result);
        self::assertArrayHasKey('strengths', $result);
        self::assertArrayHasKey('gaps', $result);
        self::assertArrayHasKey('improvements', $result);
        self::assertSame('Strong PHP developer with diverse experience.', $result['overall_assessment']);
        self::assertCount(2, $result['strengths']);
        self::assertCount(1, $result['gaps']);
        self::assertCount(1, $result['improvements']);
    }

    public function testAnalyzeReturnsNullWhenLlmThrowsException(): void
    {
        $user = UserFactory::createOne([
            'email' => 'default@local.dev',
            'settings' => ['llm_enabled' => true, 'llm_provider' => 'ollama'],
        ])->_real();

        $account = GithubAccountFactory::createOne([
            'user' => $user,
            'githubUsername' => 'failing-llm-user',
        ])->_real();

        $repo = GithubRepoFactory::createOne([
            'fullName' => 'fail/project',
            'name' => 'fail-project',
        ])->_real();
        $account->getGithubRepos()->add($repo);
        $repo->getContributors()->add($account);

        CommitFactory::createOne(['repository' => $repo, 'sha' => 'fail-sha']);

        $em = self::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class);
        \assert($em instanceof \Doctrine\ORM\EntityManagerInterface);
        $em->flush();

        $container = self::getContainer();

        $mockLlm = $this->createMock(LlmClientInterface::class);
        $mockLlm->method('chat')->willThrowException(new \RuntimeException('LLM unavailable'));
        $mockLlm->method('getProviderName')->willReturn('ollama');
        $mockLlm->method('getModelName')->willReturn('llama3.2');

        $mockFactory = $this->createMock(LlmFactoryInterface::class);
        $mockFactory->method('create')->willReturn($mockLlm);

        $analyzer = new CvImprovementAnalyzer(
            $mockFactory,
            $container->get(\App\Repository\GithubAccountRepository::class),
            $container->get(\App\Repository\GithubRepoRepository::class),
            $container->get(\App\Repository\CommitRepository::class),
            new NullLogger(),
        );

        $result = $analyzer->analyze($user);

        self::assertNull($result);
    }

    private function createAnalyzer(): CvImprovementAnalyzer
    {
        $container = self::getContainer();

        $mockLlm = $this->createMock(LlmClientInterface::class);
        $mockLlm->method('chat')->willReturn([
            'overall_assessment' => 'test',
            'strengths' => [],
            'gaps' => [],
            'improvements' => [],
        ]);
        $mockLlm->method('getProviderName')->willReturn('ollama');
        $mockLlm->method('getModelName')->willReturn('llama3.2');

        $mockFactory = $this->createMock(LlmFactoryInterface::class);
        $mockFactory->method('create')->willReturn($mockLlm);

        return new CvImprovementAnalyzer(
            $mockFactory,
            $container->get(\App\Repository\GithubAccountRepository::class),
            $container->get(\App\Repository\GithubRepoRepository::class),
            $container->get(\App\Repository\CommitRepository::class),
            new NullLogger(),
        );
    }
}
