<?php

declare(strict_types=1);

namespace App\Service\GitHub;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class TokenEncryptionService
{
    private string $key;

    public function __construct(
        #[Autowire('%env(APP_SECRET)%')]
        string $appSecret,
    ) {
        $this->key = sodium_crypto_generichash($appSecret, '', SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES);
    }

    public function encrypt(string $token): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);

        $ciphertext = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt(
            $token,
            '',
            $nonce,
            $this->key
        );

        return base64_encode($nonce . $ciphertext);
    }

    public function decrypt(string $encrypted): string
    {
        $decoded = base64_decode($encrypted, true);
        if ($decoded === false) {
            throw new \RuntimeException('Invalid encrypted token format');
        }

        $nonce = mb_substr($decoded, 0, SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES, '8bit');
        $ciphertext = mb_substr($decoded, SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES, null, '8bit');

        $decrypted = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(
            $ciphertext,
            '',
            $nonce,
            $this->key
        );

        if ($decrypted === false) {
            throw new \RuntimeException('Token decryption failed');
        }

        return $decrypted;
    }
}
