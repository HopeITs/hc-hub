<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Recruitment\Media;

use BackedEnum;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

/**
 * One stored file of a recruitment record: a row of hr_media, the table the
 * old HR app filled through its media library. Only the small part of that
 * library recruitment used is kept: where the file is, and its properties.
 *
 * @property int $id
 * @property string $model_type
 * @property int $model_id
 * @property string $collection_name
 * @property string $name
 * @property string $file_name
 * @property string|null $mime_type
 * @property string $disk
 * @property int $size
 * @property array<string, mixed>|null $custom_properties
 */
class HrMedia extends Model
{
    protected $table = 'hr_media';

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'custom_properties' => 'array',
            'manipulations' => 'array',
            'generated_conversions' => 'array',
            'responsive_images' => 'array',
            'size' => 'integer',
        ];
    }

    /**
     * Files the old app kept on its "local" disk sit under private/ on the
     * "hr" disk; every other disk is used as named.
     */
    public function storage(): Filesystem
    {
        return Storage::disk($this->disk === 'local' ? 'hr' : $this->disk);
    }

    public function getPathRelativeToRoot(): string
    {
        return ($this->disk === 'local' ? 'private/' : '').$this->getKey().'/'.$this->file_name;
    }

    public function getPath(): string
    {
        return $this->storage()->path($this->getPathRelativeToRoot());
    }

    public function getCustomProperty(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->custom_properties ?? [], $key, $default);
    }

    public function setCustomProperty(string $key, mixed $value): static
    {
        $properties = $this->custom_properties ?? [];
        Arr::set($properties, $key, $value instanceof BackedEnum ? $value->value : $value);
        $this->custom_properties = $properties;

        return $this;
    }

    public function deleteWithFile(): void
    {
        $this->storage()->delete($this->getPathRelativeToRoot());
        $this->delete();
    }
}
