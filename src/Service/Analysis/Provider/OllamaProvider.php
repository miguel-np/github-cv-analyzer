<?php

declare(strict_types=1);

namespace App\Service\Analysis\Provider;

use App\Service\Analysis\LlmClientInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class OllamaProvider implements LlmClientInterface
{
    private const DEFAULT_HOST = 'http://localhost:11434';
    private const DEFAULT_MODEL = 'llama3.2';

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $host = self::DEFAULT_HOST,
        private string $model = self::DEFAULT_MODEL,
    ) {
    }

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
            'stream' => false,
        ];

        if ($jsonSchema !== null) {
            $body['format'] = $jsonSchema;
        }

        $response = $this->httpClient->request('POST', $this->host . '/api/chat', [
            'json' => $body,
            'timeout' => 120,
        ]);

        $data = $response->toArray();

        $content = $data['message']['content'] ?? '';
        $decoded = json_decode($this->extractJson($content), true);

        if (!is_array($decoded)) {
            throw new \RuntimeException('Ollama returned invalid JSON: ' . $content);
        }

        return $decoded;
    }

    public function supportsStructuredOutput(): bool
    {
        return true;
    }

    public function getProviderName(): string
    {
        return 'ollama';
    }

    public function getModelName(): string
    {
        return $this->model;
    }

    private function extractJson(string $text): string
    {
        $start = strpos($text, '{');
        $end = strrpos($text, '}');

        if ($start !== false && $end !== false) {
            return substr($text, $start, $end - $start + 1);
        }

        return $text;
    }
}
