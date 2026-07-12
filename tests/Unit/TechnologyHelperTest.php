<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Service\Analysis\Shared\TechnologyHelper;
use PHPUnit\Framework\TestCase;

final class TechnologyHelperTest extends TestCase
{
    public function testNormalizeLowercasesAndTrimsInput(): void
    {
        $result = TechnologyHelper::normalize('  PHP  ');

        self::assertSame('php', $result);
    }

    public function testNormalizePreservesUnicode(): void
    {
        $result = TechnologyHelper::normalize('Ñoño');

        self::assertSame('ñoño', $result);
    }

    public function testNormalizeWithEmptyStringReturnsEmptyString(): void
    {
        $result = TechnologyHelper::normalize('');

        self::assertSame('', $result);
    }

    public function testNormalizeWithOnlyWhitespaceReturnsEmptyString(): void
    {
        $result = TechnologyHelper::normalize('   ');

        self::assertSame('', $result);
    }

    public function testNormalizeHandlesMixedCaseIds(): void
    {
        $result = TechnologyHelper::normalize('TypeScript');

        self::assertSame('typescript', $result);
    }

    public function testIsValidReturnsTrueForNonEmptyValue(): void
    {
        $result = TechnologyHelper::isValid('PHP');

        self::assertTrue($result);
    }

    public function testIsValidReturnsFalseForEmptyString(): void
    {
        $result = TechnologyHelper::isValid('');

        self::assertFalse($result);
    }

    public function testIsValidReturnsFalseForWhitespaceOnly(): void
    {
        $result = TechnologyHelper::isValid('   ');

        self::assertFalse($result);
    }
}
