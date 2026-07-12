<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Service\Analysis\Shared\JsonHelper;
use PHPUnit\Framework\TestCase;

final class JsonHelperTest extends TestCase
{
    public function testExtractWithValidJsonWrappedInTextReturnsJson(): void
    {
        $input = "Here's the analysis:\n\n{\"key\": \"value\"}\n\nHope this helps!";
        $result = JsonHelper::extract($input);

        self::assertSame('{"key": "value"}', $result);
    }

    public function testExtractWithNestedJsonReturnsOuterJson(): void
    {
        $input = '{"outer": {"inner": "val"}, "items": [1, 2, 3]}';
        $result = JsonHelper::extract($input);

        self::assertSame($input, $result);
    }

    public function testExtractWithNoBracesReturnsOriginalText(): void
    {
        $input = 'Hello, this is plain text.';
        $result = JsonHelper::extract($input);

        self::assertSame($input, $result);
    }

    public function testExtractWithEmptyStringReturnsEmptyString(): void
    {
        $result = JsonHelper::extract('');

        self::assertSame('', $result);
    }

    public function testExtractWithOnlyOpeningBraceReturnsOriginal(): void
    {
        $input = 'Text with an opening { brace but no closing';

        $result = JsonHelper::extract($input);

        self::assertSame($input, $result);
    }
}
