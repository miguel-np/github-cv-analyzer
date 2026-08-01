<?php

declare(strict_types=1);

namespace App\Service\Analysis;

use App\Entity\User;
use App\Repository\CommitRepository;
use App\Repository\GithubAccountRepository;
use App\Repository\GithubRepoRepository;
use App\Service\Analysis\Prompt\JobMatchPrompt;
use App\Service\Analysis\Shared\TechnologyHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\Yaml\Yaml;
use Throwable;

final readonly class JobMatchAnalyzer
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
    public function compareWithReference(User $user, string $profileKey): ?array
    {
        $profile = $this->loadReferenceProfile($profileKey);
        if ($profile === null) {
            return null;
        }

        $candidateProfile = $this->buildCandidateProfile($user);
        if ($candidateProfile === null) {
            return null;
        }

        return $this->runComparison($user, $candidateProfile, $profile);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function compareWithJobDescription(User $user, string $jobDescription): ?array
    {
        $candidateProfile = $this->buildCandidateProfile($user);
        if ($candidateProfile === null) {
            return null;
        }

        $target = [
            'title' => 'the job description',
            'description' => 'Custom job description',
            'expected' => ['raw_job_description' => $jobDescription],
        ];

        return $this->runComparison($user, $candidateProfile, $target);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function loadReferenceProfile(string $key): ?array
    {
        $files = [
            __DIR__.'/../../../config/reference-profiles/backend-roles.yaml',
        ];

        foreach ($files as $file) {
            if (!file_exists($file)) {
                continue;
            }

            $data = Yaml::parseFile($file);
            if (is_array($data) && isset($data[$key])) {
                return $data[$key];
            }
        }

        return null;
    }

    /**
     * @return string[]
     */
    public function getAvailableProfiles(): array
    {
        $files = [
            __DIR__.'/../../../config/reference-profiles/backend-roles.yaml',
        ];

        $profiles = [];
        foreach ($files as $file) {
            if (!file_exists($file)) {
                continue;
            }

            $data = Yaml::parseFile($file);
            if (is_array($data)) {
                foreach ($data as $key => $profile) {
                    $profiles[$key] = $profile['title'] ?? $key;
                }
            }
        }

        return $profiles;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildCandidateProfile(User $user): ?array
    {
        $account = $this->accountRepo->findOneBy(['user' => $user]);
        if (!$account) {
            return null;
        }

        $repoIds = $this->repoRepo->findRepoIdsByAccount($account);
        if (count($repoIds) === 0) {
            return null;
        }

        $classification = $this->commitRepo->countByClassification($repoIds);
        $technologies = $this->repoRepo->getTopTechnologies($repoIds);
        $repos = $this->commitRepo->countByRepo($repoIds);

        return [
            'username' => $account->getGithubUsername(),
            'total_commits' => $this->repoRepo->countCommitsByIds($repoIds),
            'total_prs' => $this->repoRepo->countPullRequestsByIds($repoIds),
            'total_issues' => $this->repoRepo->countIssuesByIds($repoIds),
            'languages' => $this->extractLanguages($technologies),
            'technologies' => array_map(fn ($t) => $t['name'], $technologies),
            'classification_breakdown' => $classification,
            'top_repositories' => $repos,
            'last_synced' => $account->getLastSyncedAt()?->format('Y-m-d'),
        ];
    }

    /**
     * @param array<array{name: string, count: int}> $technologies
     *
     * @return string[]
     */
    private function extractLanguages(array $technologies): array
    {
        $knownLanguages = [
            'php', 'python', 'go', 'java', 'javascript', 'typescript',
            'ruby', 'rust', 'c', 'cpp', 'csharp', 'kotlin', 'swift',
            'scala', 'elixir', 'haskell', 'clojure', 'bash', 'hcl',
        ];

        $languages = [];
        foreach ($technologies as $tech) {
            $normalized = TechnologyHelper::normalize($tech['name']);
            if (in_array($normalized, $knownLanguages, true)) {
                $languages[] = $normalized;
            }
        }

        return array_unique($languages);
    }

    /**
     * @param array<string, mixed> $candidateProfile
     * @param array<string, mixed> $targetProfile
     *
     * @return array<string, mixed>|null
     */
    private function runComparison(User $user, array $candidateProfile, array $targetProfile): ?array
    {
        $llm = $this->llmFactory->create($user);

        try {
            $response = $llm->chat(
                JobMatchPrompt::getSystemPrompt(),
                JobMatchPrompt::getUserPrompt($candidateProfile, $targetProfile),
                JobMatchPrompt::getJsonSchema(),
            );

            $this->logger->info('Job match analysis completed', [
                'username' => $candidateProfile['username'] ?? 'unknown',
                'match' => $response['overall_match_percentage'] ?? 0,
            ]);

            return $response;
        } catch (Throwable $e) {
            $this->logger->error('Job match analysis failed', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
