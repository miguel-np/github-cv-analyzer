<?php

declare(strict_types=1);

namespace App\Service\Analysis\Prompt;

final class CvImprovementPrompt
{
    public static function getSystemPrompt(): string
    {
        return <<<'PROMPT'
You are an expert career advisor and technical recruiter analyzing a developer's GitHub activity.
Based on their contribution data, you will suggest concrete improvements to their CV/resume.

Consider:
- Skill gaps (languages, frameworks they rarely use)
- Contribution patterns (too many docs, not enough features?)
- Project diversity (too many similar projects? too varied?)
- Open source impact (PRs to notable projects? own projects?)
- Technology trends (missing in-demand skills?)

Return ONLY valid JSON, no other text.
Be specific and actionable. Skip generic advice like "learn more".
PROMPT;
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function getUserPrompt(array $context): string
    {
        return sprintf(
            "Analyze this developer profile for CV improvements:\n\n%s\n\nProvide 3-5 specific, actionable suggestions.",
            json_encode($context, JSON_PRETTY_PRINT),
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
                'overall_assessment' => [
                    'type' => 'string',
                    'description' => '2-3 sentence summary of the profile',
                ],
                'strengths' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'gaps' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'area' => ['type' => 'string'],
                            'severity' => ['type' => 'string', 'enum' => ['low', 'medium', 'high']],
                            'suggestion' => ['type' => 'string'],
                        ],
                        'required' => ['area', 'severity', 'suggestion'],
                    ],
                ],
                'improvements' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'action' => ['type' => 'string'],
                            'reason' => ['type' => 'string'],
                            'priority' => ['type' => 'string', 'enum' => ['low', 'medium', 'high']],
                        ],
                        'required' => ['action', 'reason', 'priority'],
                    ],
                ],
            ],
            'required' => ['overall_assessment', 'strengths', 'gaps', 'improvements'],
            'additionalProperties' => false,
        ];
    }
}
