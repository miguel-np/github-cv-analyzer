<?php

declare(strict_types=1);

namespace App\Service\Analysis;

use App\Entity\User;
use App\Service\Analysis\Provider\AnthropicProvider;
use App\Service\Analysis\Provider\OllamaProvider;
use App\Service\Analysis\Provider\OpenAiProvider;
use App\Service\GitHub\TokenEncryptionService;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class LlmFactory
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private TokenEncryptionService $tokenEncryption,
    ) {
    }

    public function create(User $user): LlmClientInterface
    {
        $settings = $user->getSettings();
        $provider = $settings['llm_provider'] ?? 'ollama';
        $model = $settings['llm_model'] ?? null;
        $apiKeyEncrypted = $settings['llm_api_key'] ?? null;
        $apiKey = $apiKeyEncrypted ? $this->tokenEncryption->decrypt($apiKeyEncrypted) : null;

        return match ($provider) {
            'openai' => new OpenAiProvider(
                $this->httpClient,
                $apiKey ?? throw new \RuntimeException('OpenAI API key not configured'),
                $model ?? 'gpt-4o-mini',
            ),
            'anthropic' => new AnthropicProvider(
                $this->httpClient,
                $apiKey ?? throw new \RuntimeException('Anthropic API key not configured'),
                $model ?? 'claude-3-5-haiku-latest',
            ),
            default => new OllamaProvider(
                $this->httpClient,
                $settings['ollama_host'] ?? 'http://localhost:11434',
                $model ?? 'llama3.2',
            ),
        };
    }
}
