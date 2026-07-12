<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\CommitRepository;
use App\Repository\GithubRepoRepository;
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

        $commits = $commitRepo->findBy(
            ['repository' => $repo],
            ['date' => 'DESC'],
            50
        );

        $classificationCounts = [];
        $techCounts = [];

        foreach ($commits as $commit) {
            $analysis = $commit->getAnalysisResult();
            if ($analysis) {
                $clf = $analysis->getClassification();
                $type = $clf['classification'] ?? 'unknown';
                $classificationCounts[$type] = ($classificationCounts[$type] ?? 0) + 1;

                foreach ($clf['technologies_found'] ?? [] as $tech) {
                    $tech = mb_strtolower(trim((string) $tech));
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
            'techCounts' => array_slice($techCounts, 0, 15),
        ]);
    }
}
