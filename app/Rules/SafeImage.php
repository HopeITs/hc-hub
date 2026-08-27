<?php

namespace App\Rules;

use App\Exceptions\UnsafeImageException;
use App\Services\SecureImageUploadService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Applies the SecureImageUploadService allow-list during normal validation.
 *
 * This is a usability layer, not the security boundary: the service re-runs
 * every check at store time, so a payload that bypasses validation still
 * cannot be written to disk.
 */
class SafeImage implements ValidationRule
{
    public function __construct(private readonly string $profile) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        // Edit forms seed the property with the existing media URL; only a
        // freshly uploaded file needs inspecting.
        if (! $value instanceof UploadedFile) {
            return;
        }

        try {
            app(SecureImageUploadService::class)->assertAcceptable($value, $this->profile);
        } catch (UnsafeImageException $e) {
            $fail($e->getMessage());
        }
    }
}
