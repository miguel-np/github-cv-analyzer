<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Service\Analysis\Prompt\CvImprovementPrompt;
use PHPUnit\Framework\TestCase;

final class CvImprovementPromptTest extends TestCase
{
    public function testGetSystemPromptReturnsRelevantInstructions(): void
    {
        $prompt = CvImprovementPrompt::getSystemPrompt();

        self::assertNotEmpty($prompt);
        self::assertStringContainsString('career advisor', $prompt);
        self::assertStringContainsString('CV', $prompt);
        self::assertStringContainsString('JSON', $prompt);
    }

    public function testGetUserPromptEncodesContextAsJson(): void
    {
        $context = ['repos' => 5, 'languages' => ['PHP', 'TypeScript']];

        $prompt = CvImprovementPrompt::getUserPrompt($context);

        self::assertStringContainsString('developer profile', $prompt);
        self::assertStringContainsString('5', $prompt);
        self::assertStringContainsString('PHP', $prompt);
    }

    public function testGetJsonSchemaHasGapsAndImprovementsArrays(): void
    {
        $schema = CvImprovementPrompt::getJsonSchema();

        self::assertArrayHasKey('gaps', $schema['properties']);
        self::assertArrayHasKey('improvements', $schema['properties']);
        self::assertContains('overall_assessment', $schema['required']);
        self::assertContains('strengths', $schema['required']);
        self::assertContains('gaps', $schema['required']);
        self::assertContains('improvements', $schema['required']);
    }
}
