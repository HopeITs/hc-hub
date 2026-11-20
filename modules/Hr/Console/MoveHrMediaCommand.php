<?php

declare(strict_types=1);

namespace Modules\Hr\Console;

use App\Enums\FileType;
use App\Enums\MediaCollection;
use App\Exceptions\FileStorageNotConfigured;
use App\Exceptions\UnreadableStoredFile;
use App\Models\Media;
use App\Support\CsvExport;
use App\Support\FileKeyring;
use App\Support\StoredFiles;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Core\Models\User;
use Modules\Hr\Models\Course;
use Modules\Hr\Models\Education;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\Experience;
use Modules\Hr\Models\Folder;
use Modules\Hr\Models\News;
use Modules\Hr\Support\Employees\HrMedia;
use RuntimeException;
use stdClass;
use Throwable;

/**
 * Move the HR app's files (hr_media rows, plain bytes on the `hr` disk) into
 * the hub's encrypted file store (media rows, AES-256-GCM on the local disk):
 * roadmap 4.10, decision D8.
 *
 * Three steps, each its own run, and none of them ever deletes, moves or
 * changes an original file or an hr_media row:
 *
 * - no option: a dry run. It reads only, and says for every file whether it
 *   is there, whether its owner type and collection are known and what they
 *   become in `media`;
 * - --copy: encrypts each file the way an upload is stored and writes its
 *   `media` row, which points back at its hr_media row (media.hr_media_id),
 *   so a second run copies nothing twice;
 * - --verify: decrypts every copy and compares its sha256 with the original's.
 *
 * Nothing is guessed. An owner type, a collection or a file extension missing
 * from the tables below is reported and its files are left where they are.
 * No page reads the copies yet: switching the reading is a later, separate
 * step.
 */
class MoveHrMediaCommand extends Command
{
    /**
     * The old app's owner types (hr_media.model_type) and what they are in the
     * hub: the model, whose morph map key becomes media.mediable_type, and
     * what each of its collections becomes.
     *
     * @var array<string, array{model: class-string<Model>, collections: array<string, MediaCollection>}>
     */
    private const array Owners = [
        HrMedia::EMPLOYEE => ['model' => Employee::class, 'collections' => ['avatar' => MediaCollection::Avatar, 'attachments' => MediaCollection::EmployeeAttachment]],
        HrMedia::EDUCATION => ['model' => Education::class, 'collections' => ['file' => MediaCollection::Credential]],
        HrMedia::EXPERIENCE => ['model' => Experience::class, 'collections' => ['file' => MediaCollection::Credential]],
        HrMedia::COURSE => ['model' => Course::class, 'collections' => ['file' => MediaCollection::Credential]],
        HrMedia::FOLDER => ['model' => Folder::class, 'collections' => ['archived_files' => MediaCollection::ArchiveFile]],
        'App\Models\News' => ['model' => News::class, 'collections' => ['news-image' => MediaCollection::NewsImage]],
    ];

    /**
     * The file extensions `media` can hold (media.type is a FileType).
     *
     * @var array<string, FileType>
     */
    private const array FileTypes = [
        'pdf' => FileType::Pdf,
        'docx' => FileType::Docx,
        'xlsx' => FileType::Xlsx,
        'jpg' => FileType::Jpg,
        'jpeg' => FileType::Jpg,
        'png' => FileType::Png,
    ];

    private const string TargetDisk = 'local';

    private const array CsvHeadings = ['hr_media_id', 'old_type', 'old_owner_id', 'old_collection', 'old_disk', 'source_path', 'exists', 'readable', 'owner', 'collection', 'file_type', 'status', 'note', 'media_id'];

    protected $signature = 'hub:move-hr-media
        {--copy : Encrypt each file into the hub\'s store and write its media row (originals are kept)}
        {--verify : Decrypt every copy and compare its sha256 with the original\'s}
        {--uploaded-by= : The users.id recorded as the uploader of the copies (required with --copy)}
        {--batch=50 : Rows read per batch}
        {--csv= : Also write one line per file to this path}';

    protected $description = 'Report, copy or verify the move of the HR app\'s files into the hub\'s encrypted media store (never deletes)';

    /**
     * @var list<array<int, scalar|null>>
     */
    private array $lines = [];

    public function handle(): int
    {
        $copy = (bool) $this->option('copy');
        $verify = (bool) $this->option('verify');
        $hasPointer = Schema::hasColumn('media', 'hr_media_id');

        if (($copy || $verify) && ! $hasPointer) {
            $this->components->error('media.hr_media_id does not exist yet: run the migrations first.');

            return self::FAILURE;
        }

        $result = self::SUCCESS;

        if ($copy || ! $verify) {
            $result = $this->report($copy, $hasPointer);
        }

        if ($verify && $result === self::SUCCESS) {
            $result = $this->verify();
        }

        $this->writeCsv();

        return $result;
    }

    /**
     * The dry run, and the copy when asked for: one pass over hr_media.
     */
    private function report(bool $copy, bool $hasPointer): int
    {
        $uploaderId = null;

        if ($copy) {
            $uploaderId = $this->uploaderId();

            if ($uploaderId === null) {
                return self::FAILURE;
            }

            try {
                FileKeyring::current();
            } catch (FileStorageNotConfigured) {
                $this->components->error('The file encryption key is missing or malformed (FILE_ENCRYPTION_KEY): nothing was copied.');

                return self::FAILURE;
            }
        }

        /** @var array<string, int> $statuses */
        $statuses = [];
        /** @var array<string, int> $mappings */
        $mappings = [];
        /** @var array<string, int> $unknown */
        $unknown = [];
        /** @var list<array{string, string, string}> $problems */
        $problems = [];
        $rows = 0;

        DB::table('hr_media')->orderBy('id')->chunkById($this->batchSize(), function (Collection $batch) use ($hasPointer, $uploaderId, &$statuses, &$mappings, &$unknown, &$problems, &$rows): void {
            $copied = $hasPointer
                ? Media::query()->toBase()->whereIn('hr_media_id', $batch->pluck('id')->all())->pluck('id', 'hr_media_id')
                : collect();

            foreach ($batch as $row) {
                $rows++;
                $plan = $this->plan($row);
                $mediaId = $copied->get($row->id);

                if ($mediaId !== null) {
                    $plan['status'] = 'already copied';
                } elseif ($uploaderId !== null && $plan['status'] === 'ready') {
                    [$plan['status'], $mediaId] = $this->copy($row, $plan, $uploaderId);
                }

                $statuses[$plan['status']] = ($statuses[$plan['status']] ?? 0) + 1;

                if ($plan['owner'] !== null && $plan['collection'] !== null) {
                    $mapping = "{$row->model_type} / {$row->collection_name}  ->  {$plan['owner']} / {$plan['collection']->value}";
                    $mappings[$mapping] = ($mappings[$mapping] ?? 0) + 1;
                }

                if (in_array($plan['status'], ['unknown owner type', 'unknown collection'], true)) {
                    $key = $plan['status'] === 'unknown owner type' ? (string) $row->model_type : "{$row->model_type} / {$row->collection_name}";
                    $unknown[$key] = ($unknown[$key] ?? 0) + 1;
                }

                if (! in_array($plan['status'], ['ready', 'copied', 'already copied'], true) || $plan['note'] !== '') {
                    $problems[] = ["#{$row->id}", $plan['status'], trim($plan['note'].' '.($plan['path'] ?? ''))];
                }

                $this->lines[] = [
                    (int) $row->id, (string) $row->model_type, (int) $row->model_id, (string) $row->collection_name, (string) $row->disk,
                    $plan['path'], $plan['exists'], $plan['readable'],
                    $plan['owner'] === null ? null : $plan['owner'].'#'.$row->model_id,
                    $plan['collection']?->value, $plan['type']?->value, $plan['status'], $plan['note'], $mediaId,
                ];
            }
        });

        $this->components->info("hr_media rows: {$rows}");
        ksort($statuses);
        $this->table(['Status', 'Files'], array_map(fn (string $status, int $count): array => [$status, $count], array_keys($statuses), $statuses));

        if ($mappings !== []) {
            ksort($mappings);
            $this->table(['Old owner / collection  ->  media owner / collection', 'Files'], array_map(fn (string $mapping, int $count): array => [$mapping, $count], array_keys($mappings), $mappings));
        }

        if ($unknown !== []) {
            ksort($unknown);
            $this->components->warn('Not in the conversion table (left where they are, never guessed):');
            $this->table(['Old owner type / collection', 'Files'], array_map(fn (string $type, int $count): array => [$type, $count], array_keys($unknown), $unknown));
        }

        if ($problems !== []) {
            $this->components->warn('Needs a look:');
            $this->table(['hr_media', 'Status', 'Detail'], $problems);
        }

        if (! $hasPointer) {
            $this->components->warn('media.hr_media_id does not exist yet (migration not run): no file counts as copied.');
        }

        $this->components->info($copy
            ? 'Done. No original file and no hr_media row was changed. Run --verify next.'
            : 'Dry run: nothing was written.');

        return isset($statuses['copy failed']) ? self::FAILURE : self::SUCCESS;
    }

    /**
     * What one hr_media row is and what it would become, from the tables
     * above and the bytes on the `hr` disk. Reads only.
     *
     * @return array{status: string, note: string, path: string|null, exists: bool, readable: bool, owner: string|null, model: class-string<Model>|null, collection: MediaCollection|null, type: FileType|null}
     */
    private function plan(stdClass $row): array
    {
        $plan = ['status' => 'ready', 'note' => '', 'path' => HrMedia::path($row), 'exists' => false, 'readable' => false, 'owner' => null, 'model' => null, 'collection' => null, 'type' => null];

        $owner = self::Owners[(string) $row->model_type] ?? null;
        $alias = $owner === null ? null : Relation::getMorphAlias($owner['model']);

        if ($owner !== null && is_string($alias) && $alias !== $owner['model']) {
            $plan['owner'] = $alias;
            $plan['model'] = $owner['model'];
            $plan['collection'] = $owner['collections'][(string) $row->collection_name] ?? null;
        }

        $plan['type'] = self::FileTypes[HrMedia::extension($row)] ?? null;

        if ($plan['path'] !== null) {
            $disk = Storage::disk('hr');
            $plan['exists'] = $disk->exists($plan['path']);
            $plan['readable'] = $plan['exists'] && is_resource($stream = $disk->readStream($plan['path'])) && fclose($stream);
        }

        $plan['status'] = match (true) {
            $plan['owner'] === null => 'unknown owner type',
            $plan['collection'] === null => 'unknown collection',
            $plan['type'] === null => 'file type not allowed in media',
            $plan['path'] === null => 'unusable file name or disk',
            ! $plan['exists'] => 'file missing',
            ! $plan['readable'] => 'file unreadable',
            default => 'ready',
        };

        if ($plan['status'] === 'file type not allowed in media') {
            $plan['note'] = '.'.HrMedia::extension($row);
        }

        if ($plan['model'] !== null && ! $plan['model']::query()->withoutGlobalScopes()->whereKey((int) $row->model_id)->exists()) {
            $plan['note'] = trim($plan['note']." (its {$plan['owner']} #{$row->model_id} no longer exists)");
        }

        return $plan;
    }

    /**
     * Encrypt one file into the hub's store and write its media row. The copy
     * is read back and compared before the row is written; a failed row write
     * removes the new file again. The original is only ever read.
     *
     * @param  array{status: string, note: string, path: string|null, exists: bool, readable: bool, owner: string|null, model: class-string<Model>|null, collection: MediaCollection|null, type: FileType|null}  $plan
     * @return array{string, int|null}
     */
    private function copy(stdClass $row, array $plan, int $uploaderId): array
    {
        if ($plan['path'] === null || $plan['owner'] === null || $plan['collection'] === null || $plan['type'] === null) {
            return [$plan['status'], null];
        }

        $bytes = Storage::disk('hr')->get($plan['path']);

        if (! is_string($bytes)) {
            return ['file unreadable', null];
        }

        $target = 'media/hr/'.$plan['owner'].'/'.Str::random(40).'.'.$plan['type']->value;
        $keyId = StoredFiles::putEncrypted(self::TargetDisk, $target, $bytes);

        try {
            if (! hash_equals(hash('sha256', $bytes), hash('sha256', StoredFiles::contents(self::TargetDisk, $target, $keyId)))) {
                throw new RuntimeException('The copy does not read back as the original.');
            }

            $media = Media::query()->create([
                'mediable_type' => $plan['owner'],
                'mediable_id' => (int) $row->model_id,
                'collection' => $plan['collection'],
                'disk' => self::TargetDisk,
                'path' => $target,
                'original_name' => StoredFiles::nameWithExtension((string) ($row->name ?: pathinfo((string) $row->file_name, PATHINFO_FILENAME)), $plan['type']),
                'mime_type' => $plan['type']->mimeType(),
                'size' => strlen($bytes),
                'encryption_key_id' => $keyId,
                'type' => $plan['type'],
                'uploaded_at' => $row->created_at ?? now(),
                'uploaded_by' => $uploaderId,
                'hr_media_id' => (int) $row->id,
            ]);
        } catch (Throwable $exception) {
            StoredFiles::forget(self::TargetDisk, $target, 'copied HR file', ['hr_media_id' => $row->id]);
            report($exception);

            return ['copy failed', null];
        }

        return ['copied', $media->id];
    }

    /**
     * Decrypt every copy and compare it with its original, byte for byte by
     * sha256. Any copy that cannot be proven equal fails the run.
     */
    private function verify(): int
    {
        $copies = 0;
        $matching = 0;
        /** @var list<array{string, string, string}> $differences */
        $differences = [];

        Media::query()->toBase()->whereNotNull('hr_media_id')->select(['id', 'hr_media_id', 'disk', 'path', 'encryption_key_id'])->orderBy('id')
            ->chunkById($this->batchSize(), function (Collection $batch) use (&$copies, &$matching, &$differences): void {
                $originals = DB::table('hr_media')->whereIn('id', $batch->pluck('hr_media_id')->all())->get(['id', 'model_type', 'model_id', 'collection_name', 'disk', 'file_name'])->keyBy('id');

                foreach ($batch as $media) {
                    $copies++;
                    $original = $originals->get($media->hr_media_id);
                    $outcome = $this->compare($media, $original);

                    if ($outcome === 'match') {
                        $matching++;
                    } else {
                        $differences[] = ["#{$media->hr_media_id}", "#{$media->id}", $outcome];
                    }

                    $this->lines[] = [
                        (int) $media->hr_media_id, $original?->model_type, $original?->model_id, $original?->collection_name, $original?->disk,
                        $original === null ? null : HrMedia::path($original), null, null, null, null, null,
                        $outcome === 'match' ? 'verified' : 'verify failed', $outcome === 'match' ? 'sha256 match' : $outcome, (int) $media->id,
                    ];
                }
            });

        $total = DB::table('hr_media')->count();
        $this->components->info("Verified {$matching}/{$copies} copies (sha256 of the decrypted copy against the original).");

        if ($copies < $total) {
            $this->components->warn(($total - $copies)." of {$total} hr_media rows have no copy yet.");
        }

        if ($differences !== []) {
            $this->components->error(count($differences).' copies could not be proven equal to their original:');
            $this->table(['hr_media', 'media', 'Problem'], $differences);
        }

        return $differences === [] ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @return 'match'|'hr_media row missing'|'original file missing'|'copy missing or unreadable'|'sha256 differs'
     */
    private function compare(stdClass $media, ?stdClass $original): string
    {
        if ($original === null) {
            return 'hr_media row missing';
        }

        $path = HrMedia::path($original);
        $bytes = $path === null ? null : Storage::disk('hr')->get($path);

        if (! is_string($bytes)) {
            return 'original file missing';
        }

        try {
            $copy = StoredFiles::contents((string) $media->disk, (string) $media->path, $media->encryption_key_id);
        } catch (UnreadableStoredFile) {
            return 'copy missing or unreadable';
        }

        return hash_equals(hash('sha256', $bytes), hash('sha256', $copy)) ? 'match' : 'sha256 differs';
    }

    /**
     * The account recorded as the uploader of the copies: media.uploaded_by
     * is required and hr_media never knew who uploaded a file, so the person
     * running the copy names one.
     */
    private function uploaderId(): ?int
    {
        $given = $this->option('uploaded-by');

        if (! is_string($given) || ! ctype_digit($given) || ! User::query()->withoutGlobalScopes()->whereKey((int) $given)->exists()) {
            $this->components->error('--copy needs --uploaded-by=<users.id> of an existing account: nothing was copied.');

            return null;
        }

        return (int) $given;
    }

    private function batchSize(): int
    {
        return max(1, (int) $this->option('batch'));
    }

    private function writeCsv(): void
    {
        $path = $this->option('csv');

        if (! is_string($path) || $path === '') {
            return;
        }

        File::ensureDirectoryExists(dirname($path));
        $file = fopen($path, 'wb');

        if ($file === false) {
            $this->components->error("The report could not be written to {$path}.");

            return;
        }

        fwrite($file, "\xEF\xBB\xBF");
        fputcsv($file, self::CsvHeadings, ',', '"', '');

        foreach ($this->lines as $line) {
            fputcsv($file, array_map(CsvExport::cell(...), $line), ',', '"', '');
        }

        fclose($file);
        $this->components->info(count($this->lines)." lines written to {$path}.");
    }
}
