<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Service\Analysis\Prompt\CommitAnalyzerPrompt;
use PHPUnit\Framework\TestCase;

final class CommitAnalyzerPromptTest extends TestCase
{
    public function testGetSystemPromptReturnsNonEmptyString(): void
    {
        $prompt = CommitAnalyzerPrompt::getSystemPrompt();

        self::assertNotEmpty($prompt);
        self::assertStringContainsString('software engineer', $prompt);
        self::assertStringContainsString('JSON', $prompt);
    }

    public function testGetUserPromptIncludesCommitMessageAndDiffStats(): void
    {
        $message = 'Add user authentication';
        $diffStats = [
            ['filename' => 'src/Auth/LoginController.php', 'additions' => 30, 'deletions' => 5],
            ['filename' => 'config/routes.yaml', 'additions' => 5, 'deletions' => 0],
        ];

        $prompt = CommitAnalyzerPrompt::getUserPrompt($message, $diffStats);

        self::assertStringContainsString($message, $prompt);
        self::assertStringContainsString('LoginController.php', $prompt);
        self::assertStringContainsString('routes.yaml', $prompt);
        self::assertStringContainsString('35', $prompt);
    }

    public function testGetUserPromptTruncatesFileListTo20(): void
    {
        $files = [];
        for ($i = 1; $i <= 25; ++$i) {
            $files[] = ['filename' => "src/File{$i}.php", 'additions' => $i, 'deletions' => 0];
        }

        $prompt = CommitAnalyzerPrompt::getUserPrompt('Big commit', $files);

        self::assertStringContainsString('(25 total, showing first 20)', $prompt);
        self::assertStringContainsString('File1.php', $prompt);
        self::assertStringContainsString('File20.php', $prompt);
        self::assertStringNotContainsString('File21.php', $prompt);
    }

    public function testGetJsonSchemaContainsAllRequiredFields(): void
    {
        $schema = CommitAnalyzerPrompt::getJsonSchema();

        self::assertContains('classification', $schema['required']);
        self::assertContains('complexity_score', $schema['required']);
        self::assertContains('summary', $schema['required']);
        self::assertContains('impact_areas', $schema['required']);
        self::assertContains('technologies_found', $schema['required']);
        self::assertContains('code_quality_score', $schema['required']);
        self::assertFalse($schema['additionalProperties']);
    }

    public function testGetJsonSchemaClassificationEnumHasAllValues(): void
    {
        $schema = CommitAnalyzerPrompt::getJsonSchema();
        $enum = $schema['properties']['classification']['enum'];

        self::assertContains('feature', $enum);
        self::assertContains('bugfix', $enum);
        self::assertContains('refactor', $enum);
        self::assertContains('docs', $enum);
        self::assertContains('test', $enum);
        self::assertContains('chore', $enum);
        self::assertContains('perf', $enum);
    }
}
