<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\GithubAccount;
use App\Repository\GithubAccountRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    public function index(GithubAccountRepository $accountRepo, EntityManagerInterface $em): Response
    {
        $account = $accountRepo->findOneBy([]);

        $stats = [
            'repositories' => 0,
            'commits' => 0,
            'pull_requests' => 0,
            'issues' => 0,
        ];

        if ($account) {
            $stats['repositories'] = $account->getGithubRepos()->count();

            $repoIds = $account->getGithubRepos()->map(fn ($r) => $r->getId())->toArray();
            if (count($repoIds) > 0) {
                $stats['commits'] = (int) $em->createQuery(
                    'SELECT COUNT(c.id) FROM App\Entity\Commit c WHERE c.repository IN (:ids)'
                )->setParameter('ids', $repoIds)->getSingleScalarResult();

                $stats['pull_requests'] = (int) $em->createQuery(
                    'SELECT COUNT(p.id) FROM App\Entity\PullRequest p WHERE p.repository IN (:ids)'
                )->setParameter('ids', $repoIds)->getSingleScalarResult();

                $stats['issues'] = (int) $em->createQuery(
                    'SELECT COUNT(i.id) FROM App\Entity\Issue i WHERE i.repository IN (:ids)'
                )->setParameter('ids', $repoIds)->getSingleScalarResult();
            }
        }

        return $this->render('dashboard/index.html.twig', [
            'account' => $account,
            'stats' => $stats,
        ]);
    }
}
