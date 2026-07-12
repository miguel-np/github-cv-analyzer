<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\GithubRepo;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<GithubRepo>
 */
final class GithubRepoFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return GithubRepo::class;
    }

    protected function defaults(): array
    {
        $name = self::faker()->unique()->slug(2);

        return [
            'githubId' => self::faker()->unique()->randomNumber(),
            'fullName' => 'owner/' . $name,
            'name' => $name,
            'description' => self::faker()->sentence(),
            'language' => self::faker()->randomElement(['PHP', 'TypeScript', 'Python', 'Go', 'Rust']),
            'stars' => self::faker()->numberBetween(0, 100),
            'forks' => self::faker()->numberBetween(0, 20),
            'isFork' => false,
            'isPrivate' => false,
            'metadata' => [
                'topics' => [self::faker()->word()],
                'homepage' => null,
                'license' => 'MIT',
            ],
        ];
    }
}
