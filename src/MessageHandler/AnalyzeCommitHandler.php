<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Commit;
use App\Message\AnalyzeCommitMessage;
use App\Repository\UserRepository;
use App\Service\Analysis\CommitAnalyzer;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class AnalyzeCommitHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private CommitAnalyzer $commitAnalyzer,
        private UserRepository $userRepo,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(AnalyzeCommitMessage $message): void
    {
        $commit = $this->em->getRepository(Commit::class)->find($message->commitId);

        if (!$commit) {
            $this->logger->warning('AnalyzeCommitHandler: commit not found', ['id' => $message->commitId]);

            return;
        }

        if ($commit->getAnalysisResult() !== null) {
            $this->logger->info('Commit already analyzed, skipping', [
                'sha' => substr($commit->getSha(), 0, 7),
            ]);

            return;
        }

        $user = $this->userRepo->findOneBy([]);

        if (!$user) {
            $this->logger->warning('AnalyzeCommitHandler: no user configured');

            return;
        }

        $llmEnabled = $user->getSettings()['llm_enabled'] ?? false;

        if (!$llmEnabled) {
            $this->logger->info('LLM analysis disabled, skipping');

            return;
        }

        try {
            $this->commitAnalyzer->analyze($commit, $user);
            $this->em->flush();

            $this->logger->info('Commit analyzed successfully', [
                'sha' => substr($commit->getSha(), 0, 7),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to analyze commit', [
                'sha' => substr($commit->getSha(), 0, 7),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
