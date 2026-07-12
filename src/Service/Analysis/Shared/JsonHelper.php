<?php

declare(strict_types=1);

namespace App\Service\Analysis\Shared;

final class JsonHelper
{
    public static function extract(string $text): string
    {
        $start = strpos($text, '{');
        $end = strrpos($text, '}');

        if ($start !== false && $end !== false && $end > $start) {
            return substr($text, $start, $end - $start + 1);
        }

        return $text;
    }
}
