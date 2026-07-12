<?php

declare(strict_types=1);

namespace App\Service\GitHub;

use App\Entity\GithubAccount;
use App\Entity\GithubRepo;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final readonly class GitHubSyncService
{
    public function __construct(
        private GitHubClient $client,
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
    ) {
    }

    public function syncRepositories(GithubAccount $account): array
    {
        $this->client->authenticate($account->getEncryptedToken());

        $repos = $this->client->listRepositories();
        $synced = [];
        $skipped = 0;

        foreach ($repos as $repoData) {
            if ($repoData['fork'] && !$this->hasContributions($repoData)) {
                ++$skipped;
                continue;
            }

            $githubRepo = $this->findOrCreateRepo($repoData);
            $account->getGithubRepos()->contains($githubRepo) ?: $account->getGithubRepos()->add($githubRepo);

            $synced[] = $githubRepo;
        }

        $account->setLastSyncedAt(new \DateTimeImmutable());
        $this->em->flush();

        $this->logger->info('GitHub sync completed', [
            'account' => $account->getGithubUsername(),
            'synced' => count($synced),
            'skipped' => $skipped,
        ]);

        return $synced;
    }

    private function findOrCreateRepo(array $repoData): GithubRepo
    {
        $repo = $this->em->getRepository(GithubRepo::class)->findOneBy(['githubId' => $repoData['id']]);

        if (!$repo) {
            $repo = new GithubRepo();
            $this->em->persist($repo);
        }

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

        return $repo;
    }

    private function hasContributions(array $repoData): bool
    {
        return $repoData['permissions']['push'] ?? false
            || $repoData['permissions']['admin'] ?? false;
    }
}
