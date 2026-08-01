<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\CommitRepository;
use App\Repository\GithubAccountRepository;
use App\Repository\GithubRepoRepository;
use App\Repository\UserRepository;
use App\Service\Analysis\CvImprovementAnalyzer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

class CvAnalysisController extends AbstractController
{
    private const SESSION_KEY = 'cv_analysis_result';
    private const SESSION_TTL = 86400;

    #[Route('/cv-analysis', name: 'app_cv_analysis')]
    public function index(
        GithubAccountRepository $accountRepo,
        GithubRepoRepository $repoRepo,
        CommitRepository $commitRepo,
        UserRepository $userRepo,
        CvImprovementAnalyzer $analyzer,
        Request $request,
    ): Response {
        $user = $userRepo->findOneBy([]);
        $account = $accountRepo->findOneBy([]);

        if (!$user || !$account) {
            return $this->redirectToRoute('app_dashboard');
        }

        $llmEnabled = $user->getSettings()['llm_enabled'] ?? false;
        $repoIds = $repoRepo->findRepoIdsByAccount($account);

        $stats = $this->buildStats($repoRepo, $commitRepo, $repoIds);

        $analysis = null;
        $regenerate = $request->query->getBoolean('regenerate');
        $session = $request->getSession();

        if ($llmEnabled && $stats['repositories'] > 0) {
            $cached = $this->getCachedAnalysis($session);

            if ($regenerate || $cached === null) {
                try {
                    $analysis = $analyzer->analyze($user);
                    if ($analysis !== null) {
                        $this->cacheAnalysis($session, $analysis);
                    }
                } catch (Throwable) {
                    $this->addFlash('error', 'Failed to generate CV analysis. Check your LLM configuration.');
                }
            } else {
                $analysis = $cached;
            }
        }

        return $this->render('cv_analysis/index.html.twig', [
            'account' => $account,
            'stats' => $stats,
            'analysis' => $analysis,
            'llmEnabled' => $llmEnabled,
        ]);
    }

    /**
     * @param int[] $repoIds
     *
     * @return array<string, mixed>
     */
    private function buildStats(GithubRepoRepository $repoRepo, CommitRepository $commitRepo, array $repoIds): array
    {
        $stats = [
            'repositories' => count($repoIds),
            'commits' => 0,
            'pull_requests' => 0,
            'issues' => 0,
            'analyzed' => 0,
            'classification_breakdown' => [],
            'months' => [],
            'first_commit' => null,
            'last_commit' => null,
        ];

        if (count($repoIds) === 0) {
            return $stats;
        }

        $stats['commits'] = $repoRepo->countCommitsByIds($repoIds);
        $stats['pull_requests'] = $repoRepo->countPullRequestsByIds($repoIds);
        $stats['issues'] = $repoRepo->countIssuesByIds($repoIds);

        $months = $commitRepo->countByMonth($repoIds);
        $stats['months'] = $months;

        if (count($months) > 0) {
            $stats['first_commit'] = $months[0]['month'];
            $stats['last_commit'] = $months[count($months) - 1]['month'];
        }

        $classification = $commitRepo->countByClassification($repoIds);
        $stats['classification_breakdown'] = $classification;
        $stats['analyzed'] = $commitRepo->countAnalyzedByIds($repoIds);

        return $stats;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getCachedAnalysis(SessionInterface $session): ?array
    {
        $cached = $session->get(self::SESSION_KEY);

        if (!is_array($cached)) {
            return null;
        }

        if (($cached['_ts'] ?? 0) + self::SESSION_TTL < time()) {
            $session->remove(self::SESSION_KEY);

            return null;
        }

        unset($cached['_ts']);

        return $cached;
    }

    /**
     * @param array<string, mixed> $analysis
     */
    private function cacheAnalysis(SessionInterface $session, array $analysis): void
    {
        $analysis['_ts'] = time();
        $session->set(self::SESSION_KEY, $analysis);
    }
}
