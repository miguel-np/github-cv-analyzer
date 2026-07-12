<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\GithubAccount;
use App\Tests\Factory\CommitFactory;
use App\Tests\Factory\GithubAccountFactory;
use App\Tests\Factory\GithubRepoFactory;
use App\Tests\Factory\IssueFactory;
use App\Tests\Factory\PullRequestFactory;
use App\Tests\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class DashboardControllerTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
    }

    public function testDashboardIndexShowsZeroStatsWhenNoAccount(): void
    {
        $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('html', '0');
    }

    public function testDashboardIndexShowsRepoCountWhenAccountExists(): void
    {
        $account = GithubAccountFactory::createOne()->_real();
        $repo = GithubRepoFactory::createOne()->_real();
        $account->getGithubRepos()->add($repo);
        $repo->getContributors()->add($account);

        $em = self::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class);
        \assert($em instanceof \Doctrine\ORM\EntityManagerInterface);
        $em->flush();

        $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('html', '1');
    }
}
