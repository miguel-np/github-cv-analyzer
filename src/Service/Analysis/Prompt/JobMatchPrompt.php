<?php

declare(strict_types=1);

namespace App\Service\Analysis\Prompt;

final class JobMatchPrompt
{
    public static function getSystemPrompt(): string
    {
        return <<<'PROMPT'
You are an expert technical recruiter and career coach. You compare a candidate's GitHub profile
against a job description or reference profile and determine how well they match.

Analyze the candidate's actual experience against the requirements. Be honest and specific.
Don't inflate the match percentage. Focus on what's demonstrable from their GitHub activity.

Return ONLY valid JSON, no other text.
PROMPT;
    }

    /**
     * @param array<string, mixed> $candidateProfile
     * @param array<string, mixed> $targetProfile
     */
    public static function getUserPrompt(array $candidateProfile, array $targetProfile): string
    {
        $targetTitle = $targetProfile['title'] ?? 'the target role';

        return sprintf(
            "Compare this candidate's GitHub profile against %s:\n\nCANDIDATE PROFILE:\n%s\n\nTARGET REQUIREMENTS:\n%s\n\nAnalyze the match and provide specific, actionable feedback.",
            $targetTitle,
            json_encode($candidateProfile, JSON_PRETTY_PRINT),
            json_encode($targetProfile, JSON_PRETTY_PRINT),
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
                'overall_match_percentage' => [
                    'type' => 'integer',
                    'description' => 'Overall match percentage (0-100)',
                    'minimum' => 0,
                    'maximum' => 100,
                ],
                'summary' => [
                    'type' => 'string',
                    'description' => '1-2 sentence summary of the match',
                ],
                'matching_skills' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'skill' => ['type' => 'string'],
                            'evidence' => ['type' => 'string'],
                        ],
                        'required' => ['skill', 'evidence'],
                    ],
                ],
                'missing_skills' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'skill' => ['type' => 'string'],
                            'priority' => ['type' => 'string', 'enum' => ['critical', 'high', 'medium', 'low']],
                            'learning_suggestion' => ['type' => 'string'],
                        ],
                        'required' => ['skill', 'priority', 'learning_suggestion'],
                    ],
                ],
                'recommendations' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'action' => ['type' => 'string'],
                            'impact' => ['type' => 'string'],
                            'timeline' => ['type' => 'string', 'enum' => ['short-term', 'medium-term', 'long-term']],
                        ],
                        'required' => ['action', 'impact', 'timeline'],
                    ],
                ],
            ],
            'required' => ['overall_match_percentage', 'summary', 'matching_skills', 'missing_skills', 'recommendations'],
            'additionalProperties' => false,
        ];
    }
}
