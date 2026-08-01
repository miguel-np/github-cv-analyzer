<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Service\GitHub\TokenEncryptionService;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class TokenEncryptionServiceTest extends TestCase
{
    private TokenEncryptionService $service;

    protected function setUp(): void
    {
        $this->service = new TokenEncryptionService('test-app-secret-32-chars-minimum!!');
    }

    public function testEncryptDecryptRoundtripWithValidTokenReturnsOriginal(): void
    {
        $original = 'ghp_abcdef1234567890abcdef1234567890';

        $encrypted = $this->service->encrypt($original);
        $decrypted = $this->service->decrypt($encrypted);

        self::assertSame($original, $decrypted);
    }

    public function testEncryptProducesDifferentOutputEachTimeWithSameInput(): void
    {
        $token = 'ghp_abcdef1234567890abcdef1234567890';

        $encrypted1 = $this->service->encrypt($token);
        $encrypted2 = $this->service->encrypt($token);

        self::assertNotSame($encrypted1, $encrypted2);
    }

    public function testDecryptWithInvalidBase64ThrowsRuntimeException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid encrypted token format');

        $this->service->decrypt('not-valid-base64!@#$%');
    }

    public function testDecryptWithTamperedDataThrowsRuntimeException(): void
    {
        $original = 'ghp_valid_token_1234567890abcdef';

        $encrypted = $this->service->encrypt($original);
        $decoded = base64_decode($encrypted);
        $decodedArray = str_split($decoded);
        $decodedArray[10] = chr(ord($decodedArray[10]) ^ 0xFF);
        $tampered = base64_encode(implode('', $decodedArray));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Token decryption failed');

        $this->service->decrypt($tampered);
    }

    public function testEncryptWithEmptyTokenProducesValidOutput(): void
    {
        $encrypted = $this->service->encrypt('');

        self::assertNotEmpty($encrypted);
        self::assertSame('', $this->service->decrypt($encrypted));
    }

    public function testEncryptWithSpecialCharactersTokenPreservesUnicode(): void
    {
        $original = 'token_ñáéíóú🦀%*$#@!';

        $encrypted = $this->service->encrypt($original);
        $decrypted = $this->service->decrypt($encrypted);

        self::assertSame($original, $decrypted);
    }
}
