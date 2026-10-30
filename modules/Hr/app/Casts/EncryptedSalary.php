<?php

namespace Modules\Hr\Casts;

use Modules\Hr\Services\SalaryCipher;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\ComparesCastableAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Stores a money amount as ciphertext and reads it back as a "0.00" string.
 *
 * The string form matches what `decimal:2` produced before, so every caller
 * that formatted, compared or echoed a salary keeps working unchanged — this
 * cast is a drop-in for it.
 *
 * @implements CastsAttributes<string|null, mixed>
 */
class EncryptedSalary implements CastsAttributes, ComparesCastableAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        $plain = $this->cipher()->decrypt(is_string($value) ? $value : null);

        return $plain === null ? null : $this->normalise($plain);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        $stored = $attributes[$key] ?? null;

        if ($value === null || $value === '') {
            // An unreadable figure is never blanked out. On a database copy
            // without the key every salary reads as null, and a save made
            // there — a developer fixing an unrelated field on a contract —
            // would otherwise write that null straight over the real
            // ciphertext and destroy it. Clearing a salary still works
            // wherever the salary can actually be read, which is the only
            // place the intent to clear it can have come from.
            if (is_string($stored) && $stored !== '' && $this->cipher()->decrypt($stored) === null) {
                return [$key => $stored];
            }

            return [$key => null];
        }

        return [$key => $this->cipher()->encrypt($this->normalise($value))];
    }

    /**
     * Compare on the plaintext, not the ciphertext.
     *
     * Every encryption produces a fresh IV, so the same salary written twice
     * yields two different strings. Without this, isDirty() would call every
     * save a salary change: the contract would be rewritten needlessly and the
     * activity log would report a pay change on contracts whose pay never
     * moved (see RedactsSensitiveActivityLog, which reads getDirty()).
     */
    public function compare(Model $model, string $key, mixed $firstValue, mixed $secondValue): bool
    {
        $first = $this->cipher()->decrypt(is_string($firstValue) ? $firstValue : null);
        $second = $this->cipher()->decrypt(is_string($secondValue) ? $secondValue : null);

        // Two values that both failed to decrypt are equal only if their
        // ciphertexts are identical — "both unreadable" is not "both the same".
        if ($first === null || $second === null) {
            return $firstValue === $secondValue;
        }

        return $this->normalise($first) === $this->normalise($second);
    }

    private function normalise(mixed $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }

    private function cipher(): SalaryCipher
    {
        return app(SalaryCipher::class);
    }
}
