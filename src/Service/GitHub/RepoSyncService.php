<?php

declare(strict_types=1);

namespace App\Service\GitHub;

use App\Entity\GithubAccount;
use App\Entity\GithubRepo;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final readonly class RepoSyncService
{
    public function __construct(
        private GitHubClientInterface $client,
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @return GithubRepo[]
     */
    public function syncRepositories(GithubAccount $account): array
    {
        $existingByGithubId = $this->loadExistingReposByAccount($account);
        $synced = [];

        foreach ($this->client->listRepositories() as $repoData) {
            if ($repoData['fork'] && !$this->hasContributions($repoData)) {
                continue;
            }

            $githubRepo = $existingByGithubId[$repoData['id']] ?? null;

            if ($githubRepo === null) {
                $githubRepo = new GithubRepo();
                $this->em->persist($githubRepo);
                $existingByGithubId[$repoData['id']] = $githubRepo;
            }

            $this->updateRepo($githubRepo, $repoData);
            $account->getGithubRepos()->contains($githubRepo) ?: $account->getGithubRepos()->add($githubRepo);

            $synced[] = $githubRepo;
        }

        $account->setLastSyncedAt(new DateTimeImmutable());
        $this->em->flush();

        $this->logger->info('GitHub sync completed', [
            'account' => $account->getGithubUsername(),
            'synced' => count($synced),
        ]);

        return $synced;
    }

    /**
     * @return array<int, GithubRepo>
     */
    private function loadExistingReposByAccount(GithubAccount $account): array
    {
        $repos = $this->em->getRepository(GithubRepo::class)
            ->createQueryBuilder('r')
            ->join('r.contributors', 'c')
            ->where('c.id = :accountId')
            ->setParameter('accountId', $account->getId())
            ->getQuery()
            ->getResult();

        $indexed = [];

        foreach ($repos as $repo) {
            $indexed[$repo->getGithubId()] = $repo;
        }

        return $indexed;
    }

    /**
     * @param array<string, mixed> $repoData
     */
    private function updateRepo(GithubRepo $repo, array $repoData): void
    {
        $repo->setGithubId($repoData['id']);
        $repo->setFullName($repoData['full_name']);
        $repo->setName($repoData['name']);
        $repo->setDescription($repoData['description'] ?? null);
        $repo->setLanguage($repoData['language'] ?? null);
        $repo->setStars($repoData['stargazers_count'] ?? 0);
        $repo->setForks($repoData['forks_count'] ?? 0);
        $repo->setIsFork($repoData['fork'] ?? false);
        $repo->setIsPrivate($repoData['private'] ?? false);
        $repo->setMetadata([
            'topics' => $repoData['topics'] ?? [],
            'homepage' => $repoData['homepage'] ?? null,
            'license' => $repoData['license']['spdx_id'] ?? null,
        ]);
    }

    /**
     * @param array<string, mixed> $repoData
     */
    private function hasContributions(array $repoData): bool
    {
        return ($repoData['permissions']['push'] ?? false)
            || ($repoData['permissions']['admin'] ?? false);
    }
}
