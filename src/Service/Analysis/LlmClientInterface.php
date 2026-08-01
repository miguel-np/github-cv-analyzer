<?php

declare(strict_types=1);

namespace App\Service\Analysis;

interface LlmClientInterface
{
    /**
     * @param array<string, mixed>|null $jsonSchema
     *
     * @return array<string, mixed>
     */
    public function chat(string $systemPrompt, string $userPrompt, ?array $jsonSchema = null): array;

    public function getProviderName(): string;

    public function getModelName(): string;
}
