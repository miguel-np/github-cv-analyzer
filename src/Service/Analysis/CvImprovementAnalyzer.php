<?php

declare(strict_types=1);

namespace App\Service\Analysis;

use App\Entity\GithubAccount;
use App\Entity\User;
use App\Repository\CommitRepository;
use App\Repository\GithubAccountRepository;
use App\Repository\GithubRepoRepository;
use App\Service\Analysis\Prompt\CvImprovementPrompt;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class CvImprovementAnalyzer
{
    public function __construct(
        private LlmFactoryInterface $llmFactory,
        private GithubAccountRepository $accountRepo,
        private GithubRepoRepository $repoRepo,
        private CommitRepository $commitRepo,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function analyze(User $user): ?array
    {
        $llm = $this->llmFactory->create($user);

        $account = $this->accountRepo->findOneBy(['user' => $user]);
        if (!$account) {
            return null;
        }

        $repoIds = $this->repoRepo->findRepoIdsByAccount($account);
        if (count($repoIds) === 0) {
            return null;
        }

        $context = $this->buildContext($account, $repoIds);

        try {
            $response = $llm->chat(
                CvImprovementPrompt::getSystemPrompt(),
                CvImprovementPrompt::getUserPrompt($context),
                CvImprovementPrompt::getJsonSchema(),
            );

            $this->logger->info('CV analysis completed', [
                'account' => $account->getGithubUsername(),
                'gaps' => count($response['gaps'] ?? []),
            ]);

            return $response;
        } catch (Throwable $e) {
            $this->logger->error('CV analysis failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @param int[] $repoIds
     *
     * @return array<string, mixed>
     */
    private function buildContext(GithubAccount $account, array $repoIds): array
    {
        $repoCount = count($repoIds);
        $totalCommits = $this->repoRepo->countCommitsByIds($repoIds);
        $totalPRs = $this->repoRepo->countPullRequestsByIds($repoIds);
        $totalIssues = $this->repoRepo->countIssuesByIds($repoIds);
        $classification = $this->commitRepo->countByClassification($repoIds);
        $timeline = $this->commitRepo->countByMonth($repoIds);
        $topRepos = $this->commitRepo->countByRepo($repoIds);
        $technologies = $this->repoRepo->getTopTechnologies($repoIds);

        return [
            'github_username' => $account->getGithubUsername(),
            'total_repositories' => $repoCount,
            'total_commits' => $totalCommits,
            'total_pull_requests' => $totalPRs,
            'total_issues' => $totalIssues,
            'classification_breakdown' => $classification,
            'top_repositories' => $topRepos,
            'top_technologies' => $technologies,
            'activity_timeline' => array_slice($timeline, -12),
            'last_synced' => $account->getLastSyncedAt()?->format('Y-m-d'),
        ];
    }
}
