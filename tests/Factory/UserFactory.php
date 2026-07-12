<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\User;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<User>
 */
final class UserFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return User::class;
    }

    protected function defaults(): array
    {
        return [
            'email' => self::faker()->unique()->safeEmail(),
            'settings' => [
                'llm_provider' => 'ollama',
                'llm_enabled' => true,
                'ollama_host' => 'http://localhost:11434',
                'llm_model' => 'llama3.2',
            ],
        ];
    }
}
