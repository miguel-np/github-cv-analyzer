<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\Technology;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Technology>
 */
final class TechnologyFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Technology::class;
    }

    protected function defaults(): array
    {
        return [
            'name' => self::faker()->unique()->randomElement([
                'PHP', 'Symfony', 'Doctrine', 'PostgreSQL',
                'TypeScript', 'React', 'Node.js', 'Docker',
                'Python', 'Rust', 'Go', 'Kubernetes',
            ]),
            'category' => self::faker()->randomElement([
                'language', 'framework', 'database', 'tool',
            ]),
            'version' => self::faker()->optional()->numerify('#.#'),
        ];
    }
}
