<?php

declare(strict_types=1);

namespace App\Message;

final readonly class SyncRepositoryMessage
{
    public function __construct(
        public int $repositoryId,
        public int $githubAccountId,
    ) {
    }
}
