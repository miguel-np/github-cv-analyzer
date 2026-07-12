<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\PullRequest;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<PullRequest>
 */
final class PullRequestFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return PullRequest::class;
    }

    protected function defaults(): array
    {
        return [
            'repository' => GithubRepoFactory::new(),
            'githubId' => self::faker()->unique()->randomNumber(),
            'title' => self::faker()->sentence(4),
            'body' => self::faker()->paragraph(),
            'state' => self::faker()->randomElement(['open', 'closed', 'merged']),
            'merged' => self::faker()->boolean(),
            'additions' => self::faker()->numberBetween(1, 300),
            'deletions' => self::faker()->numberBetween(0, 100),
            'changedFiles' => self::faker()->numberBetween(1, 10),
            'mergedAt' => self::faker()->optional()->dateTimeThisYear(),
            'metadata' => [
                'labels' => ['bug', 'enhancement'],
                'user_login' => self::faker()->userName(),
                'base_ref' => 'main',
            ],
        ];
    }
}
