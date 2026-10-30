<?php

namespace Modules\Hr\Services;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Encrypts and decrypts pay figures under their own key.
 *
 * Kept separate from Crypt/APP_KEY on purpose: see config/salary.php. The one
 * behaviour worth knowing before reading anything else here is that a value
 * this instance cannot decrypt comes back as null rather than as an exception.
 * A database copy taken without SALARY_ENCRYPTION_KEY is the expected case,
 * not an error, and the application has to stay usable on it — every screen
 * then shows the salary as absent. The corresponding write-side guarantee
 * lives in Modules\Hr\Casts\EncryptedSalary: a figure that could not be read is never
 * overwritten, so working on such a copy cannot destroy the originals.
 */
class SalaryCipher
{
    private ?Encrypter $encrypter = null;

    private bool $resolved = false;

    private bool $usesDedicatedKey = false;

    private bool $warned = false;

    public function encrypt(string $plain): string
    {
        return $this->encrypter()->encryptString($plain);
    }

    /**
     * @return string|null the plaintext, or null when it cannot be read
     */
    public function decrypt(?string $payload): ?string
    {
        if ($payload === null || $payload === '') {
            return null;
        }

        try {
            return $this->encrypter()->decryptString($payload);
        } catch (DecryptException) {
            $this->warnOnce();

            return null;
        }
    }

    /**
     * Does this value already carry a ciphertext envelope?
     *
     * Used by the migration to stay re-runnable: a partially converted table
     * must not have its ciphertext encrypted a second time. Deliberately a
     * shape check and not a decrypt attempt — on a host without the key every
     * decrypt fails, and answering "not encrypted" there would hand the
     * migration a ciphertext to re-encrypt as though it were a number.
     */
    public function looksEncrypted(?string $value): bool
    {
        if ($value === null || $value === '' || is_numeric($value)) {
            return false;
        }

        $decoded = base64_decode($value, true);

        if ($decoded === false) {
            return false;
        }

        $payload = json_decode($decoded, true);

        return is_array($payload)
            && isset($payload['iv'], $payload['value'], $payload['mac']);
    }

    /** True when a SALARY_ENCRYPTION_KEY is configured rather than APP_KEY reused. */
    public function usesDedicatedKey(): bool
    {
        $this->encrypter();

        return $this->usesDedicatedKey;
    }

    private function encrypter(): Encrypter
    {
        if ($this->resolved) {
            return $this->encrypter;
        }

        $dedicated = $this->parseKey(config('salary.key'));
        $this->usesDedicatedKey = $dedicated !== null;

        $key = $dedicated ?? $this->parseKey(config('app.key'));
        $cipher = $dedicated !== null
            ? (string) config('salary.cipher', 'AES-256-CBC')
            : (string) config('app.cipher', 'AES-256-CBC');

        if ($key === null) {
            throw new RuntimeException(
                'No salary encryption key available. Set SALARY_ENCRYPTION_KEY '
                .'(php artisan salary:key:generate) or APP_KEY.',
            );
        }

        if (! Encrypter::supported($key, $cipher)) {
            throw new RuntimeException(
                "The salary encryption key is not valid for the [{$cipher}] cipher. "
                .'Regenerate it with: php artisan salary:key:generate',
            );
        }

        $this->resolved = true;

        return $this->encrypter = new Encrypter($key, $cipher);
    }

    private function parseKey(mixed $key): ?string
    {
        if (! is_string($key) || $key === '') {
            return null;
        }

        return Str::startsWith($key, 'base64:')
            ? base64_decode(Str::after($key, 'base64:'), true) ?: null
            : $key;
    }

    /**
     * Logged once per request, not once per row: a page listing a hundred
     * contracts on a copy without the key would otherwise write a hundred
     * identical lines and bury whatever else is in the log.
     */
    private function warnOnce(): void
    {
        if ($this->warned) {
            return;
        }

        $this->warned = true;

        Log::warning('Stored salary could not be decrypted with the configured key.', [
            'dedicated_key' => $this->usesDedicatedKey,
        ]);
    }
}
