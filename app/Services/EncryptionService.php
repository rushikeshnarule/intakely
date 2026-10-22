<?php
/**
 * Intakely — Encryption Service
 * Field-level encryption at rest using libsodium (XChaCha20-Poly1305).
 */

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class EncryptionService
{
    private string $key;

    public function __construct(?string $key = null)
    {
        $rawKey = $key ?? env('ENCRYPTION_KEY', env('APP_KEY', ''));
        if (strlen($rawKey) < 32) {
            // Pad or derive 32-byte key
            $this->key = hash('sha256', $rawKey, true);
        } else {
            $this->key = substr(base64_decode($rawKey) ?: $rawKey, 0, 32);
        }
    }

    /**
     * Encrypt a string or array into a base64 ciphertext string.
     */
    public function encrypt(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $plaintext = is_array($value) ? json_encode($value) : (string) $value;
        $nonce = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
        $ciphertext = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt(
            $plaintext,
            '',
            $nonce,
            $this->key
        );

        return base64_encode($nonce . $ciphertext);
    }

    /**
     * Decrypt a base64 ciphertext string.
     */
    public function decrypt(string $payload): ?string
    {
        if ($payload === '') {
            return '';
        }

        $decoded = base64_decode($payload, true);
        if ($decoded === false) {
            return null;
        }

        $nonceLength = SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES;
        if (strlen($decoded) <= $nonceLength) {
            return null;
        }

        $nonce = substr($decoded, 0, $nonceLength);
        $ciphertext = substr($decoded, $nonceLength);

        $decrypted = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(
            $ciphertext,
            '',
            $nonce,
            $this->key
        );

        return $decrypted !== false ? $decrypted : null;
    }
}
