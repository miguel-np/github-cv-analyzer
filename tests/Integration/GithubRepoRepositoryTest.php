<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Repository\GithubRepoRepository;
use App\Tests\Factory\CommitFactory;
use App\Tests\Factory\GithubRepoFactory;
use App\Tests\Factory\PullRequestFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class GithubRepoRepositoryTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    private GithubRepoRepository $repo;

    protected function setUp(): void
    {
        self::bootKernel();
        $repo = self::getContainer()->get(GithubRepoRepository::class);
        \assert($repo instanceof GithubRepoRepository);
        $this->repo = $repo;
    }

    public function testCountCommitsByIdsReturnsCorrectTotal(): void
    {
        $repo1 = GithubRepoFactory::createOne()->_real();
        $repo2 = GithubRepoFactory::createOne()->_real();

        CommitFactory::createMany(3, ['repository' => $repo1]);
        CommitFactory::createMany(2, ['repository' => $repo2]);

        $ids = array_filter([$repo1->getId(), $repo2->getId()], static fn ($id): bool => $id !== null);
        $total = $this->repo->countCommitsByIds(array_values($ids));

        self::assertSame(5, $total);
    }

    public function testCountPullRequestsByIdsReturnsCorrectTotal(): void
    {
        $repo1 = GithubRepoFactory::createOne()->_real();
        $repo2 = GithubRepoFactory::createOne()->_real();

        PullRequestFactory::createMany(2, ['repository' => $repo1]);
        PullRequestFactory::createMany(1, ['repository' => $repo2]);

        $ids = array_filter([$repo1->getId(), $repo2->getId()], static fn ($id): bool => $id !== null);
        $total = $this->repo->countPullRequestsByIds(array_values($ids));

        self::assertSame(3, $total);
    }

    public function testCountIssuesByIdsReturnsZeroForEmptyArray(): void
    {
        $total = $this->repo->countIssuesByIds([]);

        self::assertSame(0, $total);
    }

    public function testCountCommitsByIdsReturnsZeroForEmptyArray(): void
    {
        $total = $this->repo->countCommitsByIds([]);

        self::assertSame(0, $total);
    }

    public function testCountPullRequestsByIdsReturnsZeroForEmptyArray(): void
    {
        $total = $this->repo->countPullRequestsByIds([]);

        self::assertSame(0, $total);
    }
}
