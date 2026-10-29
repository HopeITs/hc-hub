<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\Education;
use App\Models\Employee;
use App\Models\Experience;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MigrateEmployeeMediaToPrivateDisk extends Command
{
    protected $signature = 'security:migrate-employee-media {--keep-source : Keep the old public copy after verification}';

    protected $description = 'Move sensitive employee attachments from public storage to the private local disk';

    public function handle(): int
    {
        $query = Media::query()
            ->where('disk', '!=', 'local')
            ->where(function ($query): void {
                $query->where(function ($query): void {
                    // Avatars are included: employee photographs are
                    // protection-sensitive and were previously world-readable
                    // under a guessable public path.
                    $query->where('model_type', (new Employee)->getMorphClass())
                        ->whereIn('collection_name', ['attachments', 'avatar']);
                })->orWhere(function ($query): void {
                    $query->whereIn('model_type', [
                        (new Education)->getMorphClass(),
                        (new Course)->getMorphClass(),
                        (new Experience)->getMorphClass(),
                    ])
                        ->where('collection_name', 'file');
                });
            });

        $count = (clone $query)->count();
        if ($count === 0) {
            $this->info('No public employee documents require migration.');

            return self::SUCCESS;
        }

        $this->info("Migrating {$count} employee document(s) to private storage.");

        $query->orderBy('id')->eachById(function (Media $media): void {
            $relativePath = $media->getPathRelativeToRoot();
            $directory = str_replace('\\', '/', dirname($relativePath));
            $source = Storage::disk($media->disk);
            $target = Storage::disk('local');
            $files = $source->allFiles($directory);

            foreach ($files as $file) {
                $stream = $source->readStream($file);
                abort_unless(is_resource($stream), 500, "Unable to read media {$media->id}.");

                try {
                    $target->writeStream($file, $stream);
                } finally {
                    fclose($stream);
                }

                abort_unless(
                    $target->exists($file) && $target->size($file) === $source->size($file),
                    500,
                    "Verification failed for media {$media->id}.",
                );
            }

            $oldDisk = $media->disk;
            $media->forceFill([
                'disk' => 'local',
                'conversions_disk' => 'local',
            ])->save();

            if (! $this->option('keep-source')) {
                Storage::disk($oldDisk)->deleteDirectory($directory);
            }

            $this->line("Migrated media {$media->id}.");
        });

        $this->info('Employee document migration completed and verified.');

        return self::SUCCESS;
    }
}
