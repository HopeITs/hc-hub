<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when an uploaded file cannot be accepted as an image.
 *
 * The message is user-facing (it is surfaced through validation), so it must
 * never leak storage paths, original filenames, or decoder internals.
 */
class UnsafeImageException extends RuntimeException
{
    public static function make(string $key, array $replace = []): self
    {
        return new self(trans("validation.secure_image.{$key}", $replace));
    }
}
