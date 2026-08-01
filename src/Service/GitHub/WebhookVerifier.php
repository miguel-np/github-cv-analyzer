<?php

declare(strict_types=1);

namespace App\Service\GitHub;

use Psr\Log\LoggerInterface;

final readonly class WebhookVerifier
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param array<string, string> $headers
     */
    public function verify(string $payload, array $headers): bool
    {
        $secret = $_ENV['GITHUB_WEBHOOK_SECRET'] ?? $_SERVER['GITHUB_WEBHOOK_SECRET'] ?? null;

        if ($secret === null || $secret === '') {
            $this->logger->warning('GITHUB_WEBHOOK_SECRET not configured');

            return false;
        }

        $signatureHeader = $headers['x-hub-signature-256'] ?? null;

        if ($signatureHeader === null) {
            $this->logger->warning('Missing X-Hub-Signature-256 header');

            return false;
        }

        $expected = 'sha256='.hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, $signatureHeader);
    }
}
