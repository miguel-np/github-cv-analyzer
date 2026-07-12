<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\GithubAccount;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<GithubAccount>
 */
final class GithubAccountFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return GithubAccount::class;
    }

    protected function defaults(): array
    {
        return [
            'user' => UserFactory::new(),
            'githubUsername' => self::faker()->unique()->userName(),
            'encryptedToken' => base64_encode(random_bytes(64)),
        ];
    }
}
