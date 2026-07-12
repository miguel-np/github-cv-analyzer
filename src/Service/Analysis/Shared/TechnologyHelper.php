<?php

declare(strict_types=1);

namespace App\Service\Analysis\Shared;

final class TechnologyHelper
{
    public static function normalize(string $name): string
    {
        return mb_strtolower(trim($name));
    }

    public static function isValid(string $name): bool
    {
        return self::normalize($name) !== '';
    }
}
