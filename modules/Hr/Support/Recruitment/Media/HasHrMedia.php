<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Recruitment\Media;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;

/**
 * Files of a recruitment record, kept as hr_media rows.
 */
trait HasHrMedia
{
    /**
     * @return Collection<int, HrMedia>
     */
    public function getMedia(string $collection = 'default'): Collection
    {
        return HrMedia::query()
            ->where('model_type', static::class)
            ->where('model_id', $this->getKey())
            ->where('collection_name', $collection)
            ->orderBy('order_column')
            ->orderBy('id')
            ->get();
    }

    public function getFirstMedia(string $collection = 'default'): ?HrMedia
    {
        return HrMedia::query()
            ->where('model_type', static::class)
            ->where('model_id', $this->getKey())
            ->where('collection_name', $collection)
            ->orderBy('order_column')
            ->orderBy('id')
            ->first();
    }

    public function addMedia(string|UploadedFile $file): HrMediaAdder
    {
        return new HrMediaAdder($this, $file);
    }

    public function clearMediaCollection(string $collection = 'default'): static
    {
        $this->getMedia($collection)->each(fn (HrMedia $media) => $media->deleteWithFile());

        return $this;
    }
}
