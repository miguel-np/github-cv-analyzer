<?php

declare(strict_types=1);

namespace App\Message;

final readonly class ProcessWebhookMessage
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public string $event,
        public array $payload,
    ) {
    }
}
