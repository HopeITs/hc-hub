<?php

namespace App\Services;

/**
 * A re-encoded image waiting to be stored, produced by
 * SecureImageUploadService. Callers must call discard() once the bytes have
 * been handed to the media library.
 */
class ProcessedImage
{
    public function __construct(
        public readonly string $path,
        public readonly string $filename,
        public readonly string $basename,
        public readonly string $mime,
        public readonly int $width,
        public readonly int $height,
    ) {}

    public function discard(): void
    {
        if (is_file($this->path)) {
            @unlink($this->path);
        }
    }
}
