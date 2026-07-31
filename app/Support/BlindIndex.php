<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Deterministic, keyed lookup value for an encrypted column.
 *
 * Laravel's `encrypted` cast is non-deterministic — the same input produces
 * different ciphertext every time, which is what stops an attacker with the
 * database from spotting that two employees share a value. It also makes
 * `WHERE national_number = ?` and a UNIQUE constraint impossible.
 *
 * A blind index restores exactly those two operations and nothing else: the
 * column stores HMAC-SHA256(app key, normalized value), so equality lookups and
 * uniqueness work, while the value itself remains unrecoverable from the index.
 *
 * What it deliberately does not give back is range queries, sorting, or partial
 * matching — `LIKE '%123%'` cannot work against a hash. That is why only
 * national_number is indexed: it is the one encrypted field the application
 * looks up by exact value.
 *
 * The key is derived from APP_KEY rather than being APP_KEY itself, so the
 * index and the ciphertext do not share key material.
 */
class BlindIndex
{
    public static function of(?string $value): ?string
    {
        $normalized = self::normalize($value);

        if ($normalized === null) {
            return null;
        }

        return hash_hmac('sha256', $normalized, self::key());
    }

    /**
     * Fold away differences that should not create a distinct identity.
     *
     * A national number typed with spaces or Arabic-Indic digits is the same
     * number, and must collide with itself in the index — otherwise the
     * uniqueness constraint silently stops working.
     */
    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = Str::of(en_digits($value))->trim()->replace([' ', '-', '_'], '')->lower()->value();

        return $value === '' ? null : $value;
    }

    private static function key(): string
    {
        $appKey = (string) config('app.key');

        if (Str::startsWith($appKey, 'base64:')) {
            $appKey = base64_decode(Str::after($appKey, 'base64:'));
        }

        // Domain separation: a distinct derived key per purpose means the index
        // key cannot be used to attack the encryption key or vice versa.
        return hash_hmac('sha256', 'blind-index-v1', $appKey, true);
    }
}
