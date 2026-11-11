<?php

declare(strict_types=1);

namespace Modules\Hr\Support\News;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * A news item's cover picture. The old HR app kept it in its media library:
 * a row of hr_media (model App\Models\News, collection news-image) and a file
 * on the `hr` disk at <public|private>/<media id>/<file name>. Covers are
 * still read from there, and a new one is stored the same way, so both apps
 * agree until the files move into the hub's own media.
 */
final class NewsCover
{
    public const string ModelType = 'App\Models\News';

    public const string Collection = 'news-image';

    private const string CacheKey = 'hr.news.covers';

    /**
     * Where the old media library put each of its disks under the HR storage root.
     *
     * @var array<string, string>
     */
    private const array DiskFolders = ['public' => 'public', 'local' => 'private'];

    /**
     * Every cover, by news id (remembered five minutes; forgotten on a change).
     *
     * @return array<int, array{path: string, mime: string, version: int}>
     */
    public static function all(): array
    {
        return Cache::memo()->remember(self::CacheKey, 300, function (): array {
            $covers = [];

            foreach (DB::table('hr_media')->select(['id', 'model_id', 'file_name', 'mime_type', 'disk', 'updated_at'])->where('model_type', self::ModelType)->where('collection_name', self::Collection)->orderBy('id')->get() as $row) {
                $folder = self::DiskFolders[$row->disk] ?? null;
                $fileName = (string) $row->file_name;

                if ($folder === null || $fileName === '' || basename($fileName) !== $fileName || ! str_starts_with((string) $row->mime_type, 'image/')) {
                    continue;
                }

                $covers[(int) $row->model_id] = [
                    'path' => $folder.'/'.$row->id.'/'.$fileName,
                    'mime' => (string) $row->mime_type,
                    'version' => $row->updated_at === null ? (int) $row->id : (int) strtotime((string) $row->updated_at),
                ];
            }

            return $covers;
        });
    }

    /**
     * @return array{path: string, mime: string, version: int}|null
     */
    public static function of(int $newsId): ?array
    {
        return self::all()[$newsId] ?? null;
    }

    public static function urlFor(int $newsId): ?string
    {
        $cover = self::of($newsId);

        return $cover === null || ! Route::has('hr.news.cover') ? null : route('hr.news.cover', ['news' => $newsId, 'v' => $cover['version']]);
    }

    /**
     * Replace a news item's cover with an uploaded picture.
     */
    public static function store(int $newsId, UploadedFile $file): void
    {
        self::remove($newsId);

        $name = Str::limit(Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'cover', 80, '').'.'.strtolower($file->extension() ?: 'jpg');
        $now = now();
        $id = DB::table('hr_media')->insertGetId([
            'model_type' => self::ModelType,
            'model_id' => $newsId,
            'uuid' => (string) Str::uuid(),
            'collection_name' => self::Collection,
            'name' => pathinfo($name, PATHINFO_FILENAME),
            'file_name' => $name,
            'mime_type' => (string) $file->getMimeType(),
            'disk' => 'local',
            'conversions_disk' => 'local',
            'size' => (int) $file->getSize(),
            'manipulations' => '[]',
            'custom_properties' => '[]',
            'generated_conversions' => '[]',
            'responsive_images' => '[]',
            'order_column' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        Storage::disk('hr')->putFileAs(self::DiskFolders['local'].'/'.$id, $file, $name);
        Cache::forget(self::CacheKey);
    }

    public static function remove(int $newsId): void
    {
        /* Only the row goes: the `hr` disk is still shared with the old HR app, whose own copy of the row may point at the file. */
        DB::table('hr_media')->where('model_type', self::ModelType)->where('model_id', $newsId)->where('collection_name', self::Collection)->delete();
        Cache::forget(self::CacheKey);
    }
}
