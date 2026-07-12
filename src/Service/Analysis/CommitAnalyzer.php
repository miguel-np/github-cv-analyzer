<?php

declare(strict_types=1);

namespace App\Service\Analysis;

use App\Entity\AnalysisResult;
use App\Entity\Commit;
use App\Entity\User;
use App\Service\Analysis\Prompt\CommitAnalyzerPrompt;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final readonly class CommitAnalyzer
{
    public function __construct(
        private LlmFactory $llmFactory,
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
    ) {
    }

    public function analyze(Commit $commit, User $user): AnalysisResult
    {
        $llm = $this->llmFactory->create($user);
        $startTime = hrtime(true);

        try {
            $response = $llm->chat(
                CommitAnalyzerPrompt::getSystemPrompt(),
                CommitAnalyzerPrompt::getUserPrompt(
                    $commit->getMessage(),
                    $commit->getDiffStats()
                ),
                CommitAnalyzerPrompt::getJsonSchema()
            );

            $durationMs = (int) ((hrtime(true) - $startTime) / 1_000_000);

            $result = new AnalysisResult();
            $result->setCommit($commit);
            $result->setProvider($llm->getProviderName());
            $result->setModel($llm->getModelName());
            $result->setClassification($response);
            $result->setDurationMs($durationMs);

            $this->em->persist($result);

            $this->logger->info('Commit analyzed', [
                'sha' => substr($commit->getSha(), 0, 7),
                'classification' => $response['classification'] ?? 'unknown',
                'provider' => $llm->getProviderName(),
            ]);

            return $result;
        } catch (\Throwable $e) {
            $this->logger->error('Commit analysis failed', [
                'sha' => substr($commit->getSha(), 0, 7),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function analyzeBatch(array $commits, User $user): int
    {
        $analyzed = 0;

        foreach ($commits as $commit) {
            if ($commit->getAnalysisResult() !== null) {
                continue;
            }

            try {
                $this->analyze($commit, $user);
                ++$analyzed;
            } catch (\Throwable) {
                continue;
            }
        }

        $this->em->flush();

        return $analyzed;
    }
}
