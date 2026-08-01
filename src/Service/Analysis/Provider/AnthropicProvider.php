<?php

declare(strict_types=1);

namespace App\Service\Analysis\Provider;

use App\Service\Analysis\LlmClientInterface;
use App\Service\Analysis\Shared\JsonHelper;
use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class AnthropicProvider implements LlmClientInterface
{
    private const DEFAULT_MODEL = 'claude-3-5-haiku-latest';

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $apiKey,
        private string $model = self::DEFAULT_MODEL,
    ) {
    }

    /**
     * @param array<string, mixed>|null $jsonSchema
     *
     * @return array<string, mixed>
     */
    public function chat(string $systemPrompt, string $userPrompt, ?array $jsonSchema = null): array
    {
        $messages = [];

        if ($userPrompt !== '') {
            $messages[] = ['role' => 'user', 'content' => $userPrompt];
        }

        if ($jsonSchema !== null) {
            $messages[] = [
                'role' => 'assistant',
                'content' => '{',
            ];
        }

        $body = [
            'model' => $this->model,
            'max_tokens' => 1024,
            'messages' => $messages,
        ];

        if ($systemPrompt !== '') {
            if ($jsonSchema !== null) {
                $body['system'] = $systemPrompt."\n\nRespond ONLY with valid JSON matching this schema:\n".json_encode($jsonSchema, JSON_PRETTY_PRINT)."\nDo not include any other text.";
            } else {
                $body['system'] = $systemPrompt;
            }
        }

        $response = $this->httpClient->request('POST', 'https://api.anthropic.com/v1/messages', [
            'headers' => [
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ],
            'json' => $body,
            'timeout' => 120,
        ]);

        $data = $response->toArray();

        $content = $data['content'][0]['text'] ?? '';
        $decoded = json_decode(JsonHelper::extract($content), true);

        if (!is_array($decoded)) {
            throw new RuntimeException('Anthropic returned invalid JSON: '.$content);
        }

        return $decoded;
    }

    public function getProviderName(): string
    {
        return 'anthropic';
    }

    public function getModelName(): string
    {
        return $this->model;
    }
}
