<?php

declare(strict_types=1);

namespace App\Service\Analysis\Provider;

use App\Service\Analysis\LlmClientInterface;
use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class OpenAiProvider implements LlmClientInterface
{
    private const DEFAULT_MODEL = 'gpt-4o-mini';

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

        if ($systemPrompt !== '') {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }

        $messages[] = ['role' => 'user', 'content' => $userPrompt];

        $body = [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => 0.1,
        ];

        if ($jsonSchema !== null) {
            $body['response_format'] = [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'analysis',
                    'strict' => true,
                    'schema' => $jsonSchema,
                ],
            ];
        }

        $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer '.$this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => $body,
            'timeout' => 120,
        ]);

        $data = $response->toArray();

        $content = $data['choices'][0]['message']['content'] ?? '';
        $decoded = json_decode($content, true);

        if (!is_array($decoded)) {
            throw new RuntimeException('OpenAI returned invalid JSON: '.$content);
        }

        return $decoded;
    }

    public function getProviderName(): string
    {
        return 'openai';
    }

    public function getModelName(): string
    {
        return $this->model;
    }
}
