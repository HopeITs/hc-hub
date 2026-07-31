<?php

namespace App\Services;

use App\Exceptions\UnsafeImageException;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * The single entry point for every user-supplied image in the application.
 *
 * The threat this closes is not "someone uploads a text file called .jpg" —
 * Laravel's image validation already blocks that. It is that a file can be
 * simultaneously a structurally valid image and a valid PHP script (a
 * polyglot), and the previous code stored it under the client's own extension
 * (`program-logo.{$file->getClientOriginalExtension()}`) on a publicly served
 * disk. That combination is remote code execution.
 *
 * Three independent properties defeat it here:
 *
 *  1. The stored bytes are produced by the server's encoder, not copied from
 *     the request. Re-encoding discards everything that is not pixel data,
 *     including appended PHP, EXIF payloads, and trailing archive members.
 *  2. The extension and basename come from the profile and a random string.
 *     The client's filename is never read.
 *  3. The decoder is selected from the *detected* image type, so a WebP
 *     container claiming to be a PNG is rejected rather than handed to a
 *     second decoder to reinterpret.
 */
class SecureImageUploadService
{
    /**
     * Detected image type => [decoder, canonical mime, extension].
     */
    private const DECODERS = [
        IMAGETYPE_JPEG => ['imagecreatefromjpeg', 'image/jpeg', 'jpg'],
        IMAGETYPE_PNG => ['imagecreatefrompng', 'image/png', 'png'],
        IMAGETYPE_WEBP => ['imagecreatefromwebp', 'image/webp', 'webp'],
    ];

    /**
     * Validate, re-encode, and attach an upload to a media collection.
     *
     * The temporary re-encoded file is always removed, including when the
     * media library throws.
     */
    public function attachTo(
        HasMedia $model,
        UploadedFile $file,
        string $collection,
        string $profile,
        string $disk,
    ): Media {
        $image = $this->process($file, $profile);

        try {
            return $model->addMedia($image->path)
                ->usingFileName($image->filename)
                ->usingName($image->basename)
                ->toMediaCollection($collection, $disk);
        } finally {
            $image->discard();
        }
    }

    /**
     * Run every check and return a re-encoded, server-named temporary file.
     *
     * @throws UnsafeImageException
     */
    public function process(UploadedFile $file, string $profile): ProcessedImage
    {
        $rules = $this->profile($profile);

        [$type, $width, $height] = $this->assertAcceptable($file, $profile);

        [$decoder, $mime, $extension] = self::DECODERS[$type];

        $source = $this->decode($decoder, $file->getRealPath());

        try {
            $canvas = $this->repaint($source, $width, $height, $rules['output']);
        } finally {
            imagedestroy($source);
        }

        try {
            return $this->encode($canvas, $rules, $mime, $extension);
        } finally {
            imagedestroy($canvas);
        }
    }

    /**
     * Run every cheap check — the ones that do not allocate a decoded bitmap.
     *
     * Exposed separately so App\Rules\SafeImage can turn a rejection into a
     * field-level validation message during the normal form pass, instead of
     * the user meeting an exception at save time.
     *
     * @return array{0: int, 1: int, 2: int} type, width, height
     *
     * @throws UnsafeImageException
     */
    public function assertAcceptable(UploadedFile $file, string $profile): array
    {
        $rules = $this->profile($profile);

        if (! $file->isValid()) {
            throw UnsafeImageException::make('unreadable');
        }

        $path = $file->getRealPath();

        if ($path === false || ! is_file($path)) {
            throw UnsafeImageException::make('unreadable');
        }

        // Trust the bytes on disk, never the client-declared Content-Length.
        $bytes = filesize($path);

        if ($bytes === false || $bytes === 0) {
            throw UnsafeImageException::make('unreadable');
        }

        if ($bytes > $rules['max_bytes']) {
            throw UnsafeImageException::make('too_large', [
                'max' => $this->humanBytes($rules['max_bytes']),
            ]);
        }

        [$type, $width, $height] = $this->inspect($path);

        $this->assertDimensions($width, $height, $rules);

        return [$type, $width, $height];
    }

    /**
     * Identify the file from its own content and confirm the container, the
     * magic bytes, and the allow-list all agree.
     *
     * @return array{0: int, 1: int, 2: int} type, width, height
     */
    private function inspect(string $path): array
    {
        $info = @getimagesize($path);

        if ($info === false || ! isset($info[0], $info[1], $info[2])) {
            throw UnsafeImageException::make('unsupported');
        }

        [$width, $height, $type] = $info;

        if (! isset(self::DECODERS[$type])) {
            // Everything that is not JPEG/PNG/WebP lands here: SVG, GIF, BMP,
            // TIFF, ICO, and any non-image the caller renamed to .jpg.
            throw UnsafeImageException::make('unsupported');
        }

        // Independent second opinion. getimagesize() reads magic bytes only;
        // finfo applies its own rule set, so a container crafted to satisfy one
        // of them still has to satisfy the other.
        $detected = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        $allowed = config('uploads.allowed_image_mimes', []);

        if (! is_string($detected) || ! in_array($detected, $allowed, true)) {
            throw UnsafeImageException::make('unsupported');
        }

        if ($detected !== self::DECODERS[$type][1]) {
            throw UnsafeImageException::make('unsupported');
        }

        if ($width < 1 || $height < 1) {
            throw UnsafeImageException::make('unsupported');
        }

        return [$type, $width, $height];
    }

    private function assertDimensions(int $width, int $height, array $rules): void
    {
        if ($width > $rules['max_width'] || $height > $rules['max_height']) {
            throw UnsafeImageException::make('too_big', [
                'width' => $rules['max_width'],
                'height' => $rules['max_height'],
            ]);
        }

        // Checked before any decode call, because the decode is what allocates
        // width * height * 4 bytes of memory.
        if ($width * $height > $rules['max_pixels']) {
            throw UnsafeImageException::make('too_many_pixels');
        }

        $longest = max($width, $height);
        $shortest = min($width, $height);

        if ($longest / $shortest > $rules['max_aspect_ratio']) {
            throw UnsafeImageException::make('aspect_ratio', [
                'ratio' => $rules['max_aspect_ratio'],
            ]);
        }
    }

    private function decode(string $decoder, string $path): GdImage
    {
        // Truncated and structurally malformed files reach the decoder as
        // warnings rather than exceptions; suppress and check the return value.
        $image = @$decoder($path);

        if (! $image instanceof GdImage) {
            throw UnsafeImageException::make('unreadable');
        }

        return $image;
    }

    /**
     * Copy the decoded pixels onto a brand-new canvas.
     *
     * Nothing but pixel data crosses this boundary: no EXIF, no ICC profile, no
     * comment chunk, no bytes appended after the image trailer.
     */
    private function repaint(GdImage $source, int $width, int $height, string $output): GdImage
    {
        $canvas = imagecreatetruecolor($width, $height);

        if (! $canvas instanceof GdImage) {
            throw UnsafeImageException::make('unreadable');
        }

        if ($output === 'jpeg') {
            // JPEG has no alpha channel. Compositing onto white first keeps
            // transparent source regions from decoding as black.
            $white = imagecolorallocate($canvas, 255, 255, 255);
            imagefilledrectangle($canvas, 0, 0, $width - 1, $height - 1, $white);
            imagealphablending($canvas, true);
        } else {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefilledrectangle($canvas, 0, 0, $width - 1, $height - 1, $transparent);
        }

        if (! imagecopy($canvas, $source, 0, 0, 0, 0, $width, $height)) {
            imagedestroy($canvas);

            throw UnsafeImageException::make('unreadable');
        }

        return $canvas;
    }

    private function encode(GdImage $canvas, array $rules, string $mime, string $extension): ProcessedImage
    {
        if ($rules['output'] !== 'preserve') {
            [$mime, $extension] = match ($rules['output']) {
                'jpeg' => ['image/jpeg', 'jpg'],
                'png' => ['image/png', 'png'],
                'webp' => ['image/webp', 'webp'],
                default => throw UnsafeImageException::make('unreadable'),
            };
        }

        $target = tempnam(sys_get_temp_dir(), 'img');

        if ($target === false) {
            throw UnsafeImageException::make('unreadable');
        }

        $written = match ($mime) {
            'image/jpeg' => imagejpeg($canvas, $target, $rules['quality']),
            'image/png' => imagepng($canvas, $target, 6),
            'image/webp' => imagewebp($canvas, $target, $rules['quality']),
            default => false,
        };

        if (! $written || ! is_file($target) || filesize($target) === 0) {
            @unlink($target);

            throw UnsafeImageException::make('unreadable');
        }

        // The stored name is entirely server-controlled. The client's original
        // basename and extension are discarded and never reach the filesystem.
        $basename = Str::lower(Str::random(40));

        return new ProcessedImage(
            path: $target,
            filename: "{$basename}.{$extension}",
            basename: $basename,
            mime: $mime,
            width: imagesx($canvas),
            height: imagesy($canvas),
        );
    }

    private function profile(string $profile): array
    {
        $rules = config("uploads.images.{$profile}");

        if (! is_array($rules)) {
            throw new \InvalidArgumentException("Unknown image upload profile [{$profile}].");
        }

        return $rules;
    }

    private function humanBytes(int $bytes): string
    {
        return round($bytes / 1024 / 1024, 1).' MB';
    }
}
