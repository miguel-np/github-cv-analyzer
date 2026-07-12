<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\Issue;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Issue>
 */
final class IssueFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Issue::class;
    }

    protected function defaults(): array
    {
        return [
            'repository' => GithubRepoFactory::new(),
            'githubId' => self::faker()->unique()->randomNumber(),
            'title' => self::faker()->sentence(4),
            'body' => self::faker()->paragraph(),
            'state' => self::faker()->randomElement(['open', 'closed']),
            'closedAt' => self::faker()->optional()->dateTimeThisYear(),
            'labels' => ['bug', 'help wanted'],
            'metadata' => [
                'user_login' => self::faker()->userName(),
                'assignees' => [self::faker()->userName()],
            ],
        ];
    }
}
