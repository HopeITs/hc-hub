<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Recruitment\Media;

use BackedEnum;
use finfo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Stores one file for a record and writes its hr_media row.
 */
final class HrMediaAdder
{
    private ?string $name = null;

    private ?string $fileName = null;

    /** @var array<string, mixed> */
    private array $properties = [];

    private bool $keepOriginal = false;

    public function __construct(private readonly Model $owner, private readonly string|UploadedFile $file) {}

    public function usingName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function usingFileName(string $fileName): self
    {
        $this->fileName = $fileName;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    public function withCustomProperties(array $properties): self
    {
        $this->properties = array_map(fn (mixed $value): mixed => $value instanceof BackedEnum ? $value->value : $value, $properties);

        return $this;
    }

    public function preservingOriginal(): self
    {
        $this->keepOriginal = true;

        return $this;
    }

    public function toMediaCollection(string $collection = 'default', string $disk = 'local'): HrMedia
    {
        $path = $this->file instanceof UploadedFile ? (string) $this->file->getRealPath() : $this->file;
        $original = $this->file instanceof UploadedFile ? $this->file->getClientOriginalName() : basename($path);
        $fileName = $this->fileName ?? $original;

        $media = HrMedia::query()->create([
            'model_type' => $this->owner::class,
            'model_id' => $this->owner->getKey(),
            'uuid' => (string) Str::uuid(),
            'collection_name' => $collection,
            'name' => $this->name ?? pathinfo($original, PATHINFO_FILENAME),
            'file_name' => $fileName,
            'mime_type' => (new finfo(FILEINFO_MIME_TYPE))->file($path) ?: null,
            'disk' => $disk,
            'conversions_disk' => $disk,
            'size' => (int) filesize($path),
            'manipulations' => [],
            'custom_properties' => $this->properties,
            'generated_conversions' => [],
            'responsive_images' => [],
        ]);

        $stream = fopen($path, 'rb');

        try {
            $media->storage()->writeStream($media->getPathRelativeToRoot(), $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        if (! $this->keepOriginal && ! $this->file instanceof UploadedFile && is_file($path)) {
            unlink($path);
        }

        return $media;
    }
}
