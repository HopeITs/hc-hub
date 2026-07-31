<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * Encrypts a column and rejects plaintext reads after migration.
 *
 * Laravel's built-in `encrypted` cast throws a DecryptException the moment it
 * meets a value that is not ciphertext. That makes a phased rollout impossible:
 * between deploying the code and finishing `security:encrypt-pii`, every row is
 * in one state or the other, and a hard failure there takes the application
 * down mid-migration.
 *
 * Writes are always encrypted. Plaintext reads are permitted only when the
 * operator explicitly enables the short-lived migration flag; the production
 * default fails closed so accidental plaintext regressions are visible.
 */
class EncryptedPii implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            if (config('security.allow_plaintext_pii_reads', false)) {
                return $value;
            }

            // Once migration is complete, silently accepting plaintext makes
            // accidental regressions invisible and allows unencrypted values
            // to persist indefinitely. Fail closed and alert instead.
            throw new DecryptException("Encrypted PII column [{$key}] contains plaintext or invalid ciphertext.");
        }
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Crypt::encryptString((string) $value);
    }
}
