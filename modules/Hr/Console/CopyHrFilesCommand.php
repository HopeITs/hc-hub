<?php

declare(strict_types=1);

namespace Modules\Hr\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Copy the files of the hr_media rows from the old HR app's storage folder
 * onto the hub's `hr` disk, then check every copy byte for byte.
 *
 * Each row's folder (<public|private>/<id>/, the file and any conversions) is
 * copied as is. Nothing is ever deleted or overwritten: a file already on the
 * hub disk with different bytes is reported, not replaced. Running it again
 * copies only what is still missing.
 */
class CopyHrFilesCommand extends Command
{
    /**
     * The folder each old media-library disk used on the HR app's storage/app.
     *
     * @var array<string, string>
     */
    private const array DiskFolders = ['public' => 'public', 'local' => 'private'];

    protected $signature = 'hub:copy-hr-files
        {--from= : The old HR app\'s storage/app folder (default: ../hc-hr/storage/app)}
        {--dry-run : Report what would be copied without writing anything}';

    protected $description = 'Copy the HR app\'s stored files onto the hub\'s hr disk and verify them (never deletes)';

    public function handle(): int
    {
        $from = (string) ($this->option('from') ?: base_path('../hc-hr/storage/app'));

        if (! is_dir($from)) {
            $this->components->error("The source folder does not exist: {$from}");

            return self::FAILURE;
        }

        $source = Storage::build(['driver' => 'local', 'root' => $from, 'throw' => false]);
        $target = Storage::disk('hr');
        $dryRun = (bool) $this->option('dry-run');
        $totals = ['rows' => 0, 'copied' => 0, 'identical' => 0, 'missing' => 0, 'different' => 0];
        $problems = [];

        DB::table('hr_media')->orderBy('id')->select(['id', 'disk', 'file_name'])->each(function (object $media) use ($source, $target, $dryRun, &$totals, &$problems): void {
            $totals['rows']++;
            $folder = self::DiskFolders[$media->disk] ?? null;
            $main = $folder === null ? null : $folder.'/'.$media->id.'/'.$media->file_name;

            if ($main === null || ! $source->exists($main)) {
                $totals['missing']++;
                $problems[] = ["#{$media->id}", 'missing at source', $main ?? "unknown disk '{$media->disk}'"];

                return;
            }

            foreach ($source->allFiles($folder.'/'.$media->id) as $path) {
                $outcome = $this->copy($source, $target, $path, $dryRun);
                $totals[$outcome]++;

                if ($outcome === 'different') {
                    $problems[] = ["#{$media->id}", 'differs on the hub disk, left as is', $path];
                }
            }
        });

        $this->table(['Rows', 'Copied', 'Already identical', 'Missing at source', 'Different on hub'], [array_values($totals)]);

        if ($problems !== []) {
            $this->components->warn('Needs a look:');
            $this->table(['Media', 'Problem', 'Path'], $problems);
        }

        $this->components->info($dryRun ? 'Dry run: nothing was written.' : 'Done. The source folder was not changed.');

        return $totals['different'] === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Copy one file unless the hub disk already holds it, and verify the copy.
     *
     * @return 'copied'|'identical'|'different'
     */
    private function copy(Filesystem $source, Filesystem $target, string $path, bool $dryRun): string
    {
        $bytes = (string) $source->get($path);

        if ($target->exists($path)) {
            return hash_equals(sha1($bytes), sha1((string) $target->get($path))) ? 'identical' : 'different';
        }

        if ($dryRun) {
            return 'copied';
        }

        $target->put($path, $bytes);

        return hash_equals(sha1($bytes), sha1((string) $target->get($path))) ? 'copied' : 'different';
    }
}
