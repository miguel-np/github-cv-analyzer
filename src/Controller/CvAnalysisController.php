<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\GithubAccountRepository;
use App\Repository\GithubRepoRepository;
use App\Repository\UserRepository;
use App\Service\Analysis\CvImprovementAnalyzer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CvAnalysisController extends AbstractController
{
    #[Route('/cv-analysis', name: 'app_cv_analysis')]
    public function index(
        GithubAccountRepository $accountRepo,
        GithubRepoRepository $repoRepo,
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

        if (count($repoIds) > 0) {
            $stats['commits'] = $repoRepo->countCommitsByIds($repoIds);
            $stats['pull_requests'] = $repoRepo->countPullRequestsByIds($repoIds);
            $stats['issues'] = $repoRepo->countIssuesByIds($repoIds);
        }

        $analysis = null;
        $regenerate = $request->query->getBoolean('regenerate');

        if ($llmEnabled && $stats['repositories'] > 0 && ($regenerate || $request->getSession()->get('cv_analysis_done') === null)) {
            try {
                $analysis = $analyzer->analyze($user);
                $request->getSession()->set('cv_analysis_done', true);
            } catch (\Throwable) {
                $this->addFlash('error', 'Failed to generate CV analysis. Check your LLM configuration.');
            }
        }

        return $this->render('cv_analysis/index.html.twig', [
            'account' => $account,
            'stats' => $stats,
            'analysis' => $analysis,
            'llmEnabled' => $llmEnabled,
        ]);
    }
}
