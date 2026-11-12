<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Employees;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use stdClass;
use Symfony\Component\HttpFoundation\Response;

/**
 * Files the HR app stored: rows of `hr_media` (the old media-library table)
 * and the bytes on the `hr` disk, at <public|private>/<id>/<file_name>.
 *
 * Reads and writes go straight to the table: the hub enforces a morph map,
 * so the old class names in model_type are plain strings here. New uploads
 * land on the private side of the same disk.
 */
final class HrMedia
{
    public const EMPLOYEE = 'App\Models\Employee';

    public const EDUCATION = 'App\Models\Education';

    public const EXPERIENCE = 'App\Models\Experience';

    public const COURSE = 'App\Models\Course';

    public const FOLDER = 'App\Models\Folder';

    public const CONTRACT_AMENDMENT = 'App\Models\ContractAmendment';

    /** hr_media.disk => folder on the `hr` disk. */
    private const DISK_FOLDERS = ['public' => 'public', 'local' => 'private'];

    private const COLUMNS = ['id', 'model_type', 'model_id', 'collection_name', 'name', 'file_name', 'mime_type', 'disk', 'size', 'custom_properties', 'created_at'];

    /**
     * @param  int|list<int>  $modelIds
     * @return Collection<int, stdClass>
     */
    public static function for(string $modelType, int|array $modelIds, string $collection): Collection
    {
        $ids = (array) $modelIds;

        if ($ids === []) {
            return collect();
        }

        return DB::table('hr_media')
            ->select(self::COLUMNS)
            ->where('model_type', $modelType)
            ->whereIn('model_id', $ids)
            ->where('collection_name', $collection)
            ->orderBy('id')
            ->get();
    }

    public static function find(int $id): ?stdClass
    {
        return DB::table('hr_media')->select(self::COLUMNS)->where('id', $id)->first();
    }

    /**
     * A custom property the old app saved with the file (name, category).
     */
    public static function property(stdClass $media, string $key, string $default = ''): string
    {
        $properties = json_decode((string) ($media->custom_properties ?? ''), true);

        return is_array($properties) && is_scalar($properties[$key] ?? null) ? (string) $properties[$key] : $default;
    }

    public static function path(stdClass $media): ?string
    {
        $folder = self::DISK_FOLDERS[$media->disk] ?? null;
        $fileName = (string) $media->file_name;

        if ($folder === null || $fileName === '' || basename($fileName) !== $fileName) {
            return null;
        }

        return $folder.'/'.$media->id.'/'.$fileName;
    }

    public static function extension(stdClass $media): string
    {
        return strtolower(pathinfo((string) $media->file_name, PATHINFO_EXTENSION));
    }

    /**
     * Store an upload and return the new hr_media id.
     *
     * @param  array<string, scalar|null>  $properties
     */
    public static function store(UploadedFile $file, string $modelType, int $modelId, string $collection, array $properties = [], ?string $displayName = null): int
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: (string) $file->guessExtension());
        $base = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'file';
        $fileName = Str::limit($base, 80, '').'-'.Str::lower(Str::random(6)).($extension !== '' ? '.'.$extension : '');

        $id = (int) DB::table('hr_media')->insertGetId([
            'model_type' => $modelType,
            'model_id' => $modelId,
            'uuid' => (string) Str::uuid(),
            'collection_name' => $collection,
            'name' => $displayName ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'file_name' => $fileName,
            'mime_type' => $file->getMimeType(),
            'disk' => 'local',
            'conversions_disk' => 'local',
            'size' => (int) $file->getSize(),
            'manipulations' => '[]',
            'custom_properties' => json_encode((object) $properties, JSON_UNESCAPED_UNICODE),
            'generated_conversions' => '[]',
            'responsive_images' => '[]',
            'order_column' => ((int) DB::table('hr_media')->max('order_column')) + 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Storage::disk('hr')->putFileAs('private/'.$id, $file, $fileName);

        return $id;
    }

    /**
     * @param  array<string, scalar|null>  $properties
     */
    public static function updateProperties(int $id, array $properties, ?string $name = null): void
    {
        $media = self::find($id);

        if ($media === null) {
            return;
        }

        $current = json_decode((string) ($media->custom_properties ?? ''), true);

        DB::table('hr_media')->where('id', $id)->update(array_filter([
            'custom_properties' => json_encode((object) array_merge(is_array($current) ? $current : [], $properties), JSON_UNESCAPED_UNICODE),
            'name' => $name,
            'updated_at' => now(),
        ], fn (mixed $value): bool => $value !== null));
    }

    public static function delete(int $id): void
    {
        $media = self::find($id);

        if ($media === null) {
            return;
        }

        $folder = self::DISK_FOLDERS[$media->disk] ?? null;

        if ($folder !== null) {
            Storage::disk('hr')->deleteDirectory($folder.'/'.$media->id);
        }

        DB::table('hr_media')->where('id', $id)->delete();
    }

    /**
     * Delete every file of a record's collection (replacing a single file).
     */
    public static function clear(string $modelType, int $modelId, string $collection): void
    {
        self::for($modelType, $modelId, $collection)->each(fn (stdClass $media) => self::delete((int) $media->id));
    }

    /**
     * Stream a stored file. Only images and PDFs are ever shown inline.
     */
    public static function response(stdClass $media, bool $inline = false): Response
    {
        $path = self::path($media);
        $disk = Storage::disk('hr');

        abort_if($path === null || ! $disk->exists($path), 404);

        $extension = self::extension($media);
        $inlineType = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
            default => null,
        };
        $inline = $inline && $inlineType !== null;

        $base = Str::of(pathinfo((string) ($media->name ?: $media->file_name), PATHINFO_FILENAME))
            ->replaceMatches('/[^A-Za-z0-9\-_ .\p{Arabic}]/u', '')
            ->squish()
            ->limit(120, '');
        $downloadName = ($base->isNotEmpty() ? (string) $base : 'file').($extension !== '' ? '.'.$extension : '');

        return $disk->response($path, $downloadName, [
            'Content-Type' => $inline ? $inlineType : 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Frame-Options' => 'SAMEORIGIN',
        ], $inline ? 'inline' : 'attachment');
    }
}
