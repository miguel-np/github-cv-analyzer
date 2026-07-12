<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Entity\User;
use App\Service\Analysis\LlmClientInterface;
use App\Service\Analysis\LlmFactory;
use App\Service\Analysis\Provider\AnthropicProvider;
use App\Service\Analysis\Provider\OllamaProvider;
use App\Service\Analysis\Provider\OpenAiProvider;
use App\Service\GitHub\TokenEncryptionService;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class LlmFactoryTest extends TestCase
{
    private HttpClientInterface $httpClient;
    private TokenEncryptionService $tokenEncryption;
    private LlmFactory $factory;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->tokenEncryption = new TokenEncryptionService('test-app-secret-32-chars-minimum!!');
        $this->factory = new LlmFactory($this->httpClient, $this->tokenEncryption);
    }

    public function testCreateWithOllamaProviderReturnsOllamaProvider(): void
    {
        $user = new User();
        $user->setSettings([
            'llm_provider' => 'ollama',
            'llm_enabled' => true,
            'ollama_host' => 'http://localhost:11434',
            'llm_model' => 'llama3.2',
        ]);

        $provider = $this->factory->create($user);

        self::assertInstanceOf(OllamaProvider::class, $provider);
        self::assertSame('ollama', $provider->getProviderName());
    }

    public function testCreateWithOpenAiProviderReturnsOpenAiProvider(): void
    {
        $apiKey = $this->tokenEncryption->encrypt('sk-test-key-123');

        $user = new User();
        $user->setSettings([
            'llm_provider' => 'openai',
            'llm_enabled' => true,
            'llm_api_key' => $apiKey,
        ]);

        $provider = $this->factory->create($user);

        self::assertInstanceOf(OpenAiProvider::class, $provider);
        self::assertSame('openai', $provider->getProviderName());
    }

    public function testCreateWithAnthropicProviderReturnsAnthropicProvider(): void
    {
        $apiKey = $this->tokenEncryption->encrypt('sk-ant-test-key-456');

        $user = new User();
        $user->setSettings([
            'llm_provider' => 'anthropic',
            'llm_enabled' => true,
            'llm_api_key' => $apiKey,
        ]);

        $provider = $this->factory->create($user);

        self::assertInstanceOf(AnthropicProvider::class, $provider);
        self::assertSame('anthropic', $provider->getProviderName());
    }

    public function testCreateWithOpenAiMissingApiKeyThrowsRuntimeException(): void
    {
        $user = new User();
        $user->setSettings([
            'llm_provider' => 'openai',
            'llm_enabled' => true,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('OpenAI API key not configured');

        $this->factory->create($user);
    }

    public function testCreateWithAnthropicMissingApiKeyThrowsRuntimeException(): void
    {
        $user = new User();
        $user->setSettings([
            'llm_provider' => 'anthropic',
            'llm_enabled' => true,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Anthropic API key not configured');

        $this->factory->create($user);
    }

    public function testCreateWithUnknownProviderDefaultsToOllama(): void
    {
        $user = new User();
        $user->setSettings([
            'llm_provider' => 'unknown_provider',
            'llm_enabled' => true,
        ]);

        $provider = $this->factory->create($user);

        self::assertInstanceOf(OllamaProvider::class, $provider);
        self::assertSame('ollama', $provider->getProviderName());
    }
}
