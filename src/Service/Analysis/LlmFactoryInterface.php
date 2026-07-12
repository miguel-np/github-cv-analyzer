<?php

declare(strict_types=1);

namespace App\Service\Analysis;

use App\Entity\User;

interface LlmFactoryInterface
{
    public function create(User $user): LlmClientInterface;
}
