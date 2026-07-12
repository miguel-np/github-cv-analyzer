<?php

declare(strict_types=1);

namespace App\Service\Analysis;

use App\Entity\GithubRepo;
use App\Entity\User;
use App\Repository\CommitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final readonly class TechnologyDetector
{
    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Detect technologies used in a repository based on analyzed commits.
     *
     * @return array<string, int> technology name => occurrence count
     */
    public function detect(GithubRepo $repo): array
    {
        $commits = $this->em->getRepository(CommitRepository::class)->createQueryBuilder('c')
            ->join('c.analysisResult', 'a')
            ->where('c.repository = :repo')
            ->setParameter('repo', $repo)
            ->getQuery()
            ->getResult();

        $techCounts = [];

        foreach ($commits as $commit) {
            $analysis = $commit->getAnalysisResult();
            if ($analysis === null) {
                continue;
            }

            $classification = $analysis->getClassification();
            $technologies = $classification['technologies_found'] ?? [];

            foreach ($technologies as $tech) {
                $tech = mb_strtolower(trim((string) $tech));
                if ($tech === '') {
                    continue;
                }
                $techCounts[$tech] = ($techCounts[$tech] ?? 0) + 1;
            }
        }

        arsort($techCounts);

        $this->logger->info('Technologies detected', [
            'repo' => $repo->getFullName(),
            'technologies' => array_keys($techCounts),
        ]);

        return $techCounts;
    }
}
