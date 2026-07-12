<?php

declare(strict_types=1);

namespace App\Message;

final readonly class AnalyzeCommitMessage
{
    public function __construct(
        public int $commitId,
    ) {
    }
}
