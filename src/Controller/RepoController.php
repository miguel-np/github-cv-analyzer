<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\CommitRepository;
use App\Repository\GithubRepoRepository;
use App\Service\Analysis\Shared\TechnologyHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RepoController extends AbstractController
{
    #[Route('/repo/{id}', name: 'app_repo_detail', requirements: ['id' => '\d+'])]
    public function detail(
        int $id,
        GithubRepoRepository $repoRepo,
        CommitRepository $commitRepo,
    ): Response {
        $repo = $repoRepo->find($id);

        if (!$repo) {
            throw $this->createNotFoundException('Repository not found');
        }

        $commits = $commitRepo->findByRepoWithAnalysis($id);

        $classificationCounts = [];
        $techCounts = [];
        $classificationTotal = 0;

        foreach ($commits as $commit) {
            $analysis = $commit->getAnalysisResult();
            if ($analysis) {
                $clf = $analysis->getClassification();
                $type = $clf['classification'] ?? 'unknown';
                $classificationCounts[$type] = ($classificationCounts[$type] ?? 0) + 1;
                ++$classificationTotal;

                foreach ($clf['technologies_found'] ?? [] as $tech) {
                    $tech = TechnologyHelper::normalize($tech);
                    if ($tech !== '') {
                        $techCounts[$tech] = ($techCounts[$tech] ?? 0) + 1;
                    }
                }
            }
        }

        arsort($classificationCounts);
        arsort($techCounts);

        return $this->render('repo/detail.html.twig', [
            'repo' => $repo,
            'commits' => $commits,
            'classificationCounts' => $classificationCounts,
            'classificationTotal' => $classificationTotal,
            'techCounts' => array_slice($techCounts, 0, 15),
        ]);
    }
}
