<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\CommitRepository;
use App\Repository\GithubAccountRepository;
use App\Repository\GithubRepoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    public function index(
        GithubAccountRepository $accountRepo,
        GithubRepoRepository $repoRepo,
        CommitRepository $commitRepo,
    ): Response {
        $account = $accountRepo->findOneBy([]);

        $stats = [
            'repositories' => 0,
            'commits' => 0,
            'pull_requests' => 0,
            'issues' => 0,
        ];

        $chartData = [
            'timeline' => [],
            'classification' => [],
            'topRepos' => [],
            'technologies' => [],
        ];

        if ($account) {
            $stats['repositories'] = $account->getGithubRepos()->count();

            $repoIds = $account->getGithubRepos()->map(fn ($r) => $r->getId())->toArray();
            if (count($repoIds) > 0) {
                $stats['commits'] = $repoRepo->countCommitsByIds($repoIds);
                $stats['pull_requests'] = $repoRepo->countPullRequestsByIds($repoIds);
                $stats['issues'] = $repoRepo->countIssuesByIds($repoIds);

                $chartData['timeline'] = $commitRepo->countByMonth($repoIds);
                $chartData['classification'] = $commitRepo->countByClassification($repoIds);
                $chartData['topRepos'] = $commitRepo->countByRepo($repoIds);
                $chartData['technologies'] = $repoRepo->getTopTechnologies($repoIds);
            }
        }

        return $this->render('dashboard/index.html.twig', [
            'account' => $account,
            'stats' => $stats,
            'chartData' => $chartData,
        ]);
    }
}
