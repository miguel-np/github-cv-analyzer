<?php

declare(strict_types=1);

namespace App\Service\Analysis;

interface LlmClientInterface
{
    public function chat(string $systemPrompt, string $userPrompt, ?array $jsonSchema = null): array;

    public function supportsStructuredOutput(): bool;

    public function getProviderName(): string;

    public function getModelName(): string;
}
