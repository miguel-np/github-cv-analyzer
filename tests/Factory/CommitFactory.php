<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\Commit;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Commit>
 */
final class CommitFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Commit::class;
    }

    protected function defaults(): array
    {
        return [
            'repository' => GithubRepoFactory::new(),
            'sha' => substr(self::faker()->sha256(), 0, 40),
            'authorEmail' => self::faker()->email(),
            'authorName' => self::faker()->name(),
            'message' => self::faker()->sentence(),
            'date' => \DateTimeImmutable::createFromMutable(self::faker()->dateTimeThisYear()),
            'additions' => self::faker()->numberBetween(1, 500),
            'deletions' => self::faker()->numberBetween(0, 200),
            'filesChanged' => self::faker()->numberBetween(1, 20),
            'isMergeCommit' => false,
            'diffStats' => [
                ['filename' => 'src/Controller/MainController.php', 'additions' => 25, 'deletions' => 5],
                ['filename' => 'src/Service/SomeService.php', 'additions' => 10, 'deletions' => 2],
            ],
        ];
    }
}
