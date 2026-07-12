<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\AnalysisResult;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<AnalysisResult>
 */
final class AnalysisResultFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return AnalysisResult::class;
    }

    protected function defaults(): array
    {
        return [
            'commit' => CommitFactory::new(),
            'provider' => 'ollama',
            'model' => 'llama3.2',
            'classification' => [
                'classification' => 'feature',
                'complexity_score' => 5,
                'summary' => 'Added new controller for user management',
                'impact_areas' => ['controller', 'routing'],
                'technologies_found' => ['PHP', 'Symfony', 'Doctrine'],
                'patterns_used' => ['dependency injection'],
                'code_quality_score' => 7,
                'tags' => ['backend', 'api'],
            ],
            'tokensUsed' => self::faker()->numberBetween(50, 2000),
            'cost' => self::faker()->optional()->randomFloat(4, 0.0001, 0.05),
            'durationMs' => self::faker()->numberBetween(100, 5000),
        ];
    }
}
