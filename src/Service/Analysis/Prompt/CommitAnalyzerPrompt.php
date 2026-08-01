<?php

declare(strict_types=1);

namespace App\Service\Analysis\Prompt;

final class CommitAnalyzerPrompt
{
    public static function getSystemPrompt(): string
    {
        return <<<'PROMPT'
You are an expert software engineer analyzing Git commits.
For each commit, classify it and extract technologies and patterns.
Return ONLY valid JSON, no other text.

Classification options:
- feature: new functionality or enhancement
- bugfix: fixing a bug or error
- refactor: restructuring code without changing behavior
- docs: documentation changes
- test: adding or modifying tests
- chore: maintenance, dependencies, config
- perf: performance optimization

Complexity score: 1 (trivial typo fix) to 10 (major architecture change).
Code quality score: 1 (poor, introduces issues) to 10 (excellent, best practices).

Be concise in the summary (max 120 chars).
PROMPT;
    }

    /**
     * @param array<array{filename?: string, additions?: int, deletions?: int}> $diffStats
     */
    public static function getUserPrompt(string $message, array $diffStats): string
    {
        $fileList = array_slice(array_column($diffStats, 'filename'), 0, 20);
        $extensions = self::extractExtensions($fileList);

        return sprintf(
            "Analyze this commit:\n\nMessage: %s\n\nFiles changed (%d total, showing first 20):\n%s\n\nFile extensions: %s\n\nAdditions: +%d / Deletions: -%d across %d files",
            $message,
            count($diffStats),
            implode("\n", $fileList),
            implode(', ', $extensions),
            array_sum(array_column($diffStats, 'additions')),
            array_sum(array_column($diffStats, 'deletions')),
            count($diffStats),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function getJsonSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'classification' => [
                    'type' => 'string',
                    'enum' => ['feature', 'bugfix', 'refactor', 'docs', 'test', 'chore', 'perf'],
                ],
                'complexity_score' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 10,
                ],
                'summary' => [
                    'type' => 'string',
                    'maxLength' => 120,
                ],
                'impact_areas' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'technologies_found' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'patterns_used' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'code_quality_score' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 10,
                ],
                'tags' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
            ],
            'required' => ['classification', 'complexity_score', 'summary', 'impact_areas', 'technologies_found', 'code_quality_score'],
            'additionalProperties' => false,
        ];
    }

    /**
     * @param string[] $files
     *
     * @return string[]
     */
    private static function extractExtensions(array $files): array
    {
        $exts = [];

        foreach ($files as $file) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            if ($ext !== '') {
                $exts[$ext] = true;
            }
        }

        return array_keys($exts);
    }
}
