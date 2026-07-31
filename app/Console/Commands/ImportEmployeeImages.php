<?php

namespace App\Console\Commands;

use App\Models\Employee;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Throwable;

class ImportEmployeeImages extends Command
{
    protected $signature = 'employees:import-images
        {path? : Local folder containing the images (e.g. storage/app/employee-images)}
        {--drive-folder= : Google Drive folder ID to download from (requires GOOGLE_DRIVE_API_KEY in .env)}
        {--dry-run : Show the filename → employee matching without storing anything}
        {--force : Replace the avatar of employees that already have one}';

    protected $description = 'Attach employee avatars from image files named like 001HC_ALP_002-2-Edit.png (002 → employee id 2) using the Spatie media library.';

    public function handle(): int
    {
        if ($driveFolder = $this->option('drive-folder')) {
            $files = $this->downloadDriveFolder($driveFolder);
        } else {
            $path = $this->argument('path');

            if (! $path) {
                $this->error('Provide a local folder path, or --drive-folder=<ID> with GOOGLE_DRIVE_API_KEY set.');

                return self::FAILURE;
            }

            if (! File::isDirectory($path)) {
                $this->error("Folder not found: {$path}");

                return self::FAILURE;
            }

            $files = collect(File::files($path))
                ->filter(fn ($f) => in_array(strtolower($f->getExtension()), ['png', 'jpg', 'jpeg', 'webp']))
                ->map(fn ($f) => $f->getPathname());
        }

        if ($files === null) {
            return self::FAILURE;
        }

        if ($files->isEmpty()) {
            $this->warn('No image files found.');

            return self::SUCCESS;
        }

        $this->info("Found {$files->count()} image(s).");

        $stored = 0;
        $skipped = 0;
        $unmatched = [];

        foreach ($files as $filePath) {
            $filename = basename($filePath);
            $employeeId = $this->employeeIdFromFilename($filename);

            if (! $employeeId) {
                $unmatched[] = [$filename, '—', 'no employee number in filename'];

                continue;
            }

            $employee = Employee::find($employeeId);

            if (! $employee) {
                $unmatched[] = [$filename, $employeeId, 'no employee with this id'];

                continue;
            }

            if ($this->option('dry-run')) {
                $this->line("{$filename} → #{$employee->id} {$employee->name}");
                $stored++;

                continue;
            }

            if (! $this->option('force') && $employee->hasMedia('avatar')) {
                $skipped++;

                continue;
            }

            try {
                // singleFile collection: adding replaces any previous avatar.
                // Explicit private disk, matching the runtime upload path in
                // PersonalInfoForm — a bulk import must not reintroduce
                // publicly fetchable photographs.
                $employee->addMedia($filePath)
                    ->preservingOriginal()
                    ->usingFileName($filename)
                    ->toMediaCollection('avatar', 'local');

                $stored++;
                $this->line("✔ {$filename} → #{$employee->id} {$employee->name}");
            } catch (Throwable $e) {
                $unmatched[] = [$filename, $employeeId, $e->getMessage()];
            }
        }

        $this->newLine();
        $this->info($this->option('dry-run')
            ? "Dry run — {$stored} image(s) matched."
            : "Done. {$stored} avatar(s) stored, {$skipped} skipped (already have one — use --force to replace).");

        if ($unmatched) {
            $this->newLine();
            $this->warn(count($unmatched).' file(s) could not be processed:');
            $this->table(['File', 'Parsed ID', 'Reason'], $unmatched);
        }

        return self::SUCCESS;
    }

    /**
     * Extract the employee id from filenames like "001HC_ALP_002-2-Edit.png":
     * the digits right after the last underscore, leading zeros stripped → 2.
     */
    private function employeeIdFromFilename(string $filename): ?int
    {
        if (! preg_match('/_(\d+)\D*[^_]*$/', $filename, $m)) {
            return null;
        }

        $id = (int) ltrim($m[1], '0');

        return $id > 0 ? $id : null;
    }

    /**
     * List and download every image of a public Google Drive folder into a
     * temp directory, returning the local file paths.
     */
    private function downloadDriveFolder(string $folderId): ?\Illuminate\Support\Collection
    {
        $apiKey = config('services.google.drive_api_key') ?? env('GOOGLE_DRIVE_API_KEY');

        if (! $apiKey) {
            $this->error('GOOGLE_DRIVE_API_KEY is not set. Either add it to .env, or download the Drive folder manually and pass the local path instead.');

            return null;
        }

        $tempDir = storage_path('app/employee-images-drive');
        File::ensureDirectoryExists($tempDir);

        $paths = collect();
        $pageToken = null;

        do {
            $response = Http::get('https://www.googleapis.com/drive/v3/files', array_filter([
                'q' => "'{$folderId}' in parents and mimeType contains 'image/' and trashed = false",
                'fields' => 'nextPageToken, files(id, name)',
                'pageSize' => 100,
                'pageToken' => $pageToken,
                'key' => $apiKey,
            ]));

            if ($response->failed()) {
                $this->error('Google Drive listing failed: '.$response->json('error.message', $response->status()));

                return null;
            }

            foreach ($response->json('files', []) as $file) {
                $local = $tempDir.DIRECTORY_SEPARATOR.$file['name'];

                // Treat an empty/partial leftover as not-yet-downloaded so a
                // resumed run re-fetches it.
                if (File::exists($local) && File::size($local) === 0) {
                    File::delete($local);
                }

                if (! File::exists($local)) {
                    $this->line("Downloading {$file['name']}…");

                    try {
                        $download = Http::retry(3, 500, throw: false)
                            ->timeout(60)
                            ->sink($local)
                            ->get(
                                "https://www.googleapis.com/drive/v3/files/{$file['id']}",
                                ['alt' => 'media', 'key' => $apiKey],
                            );
                    } catch (Throwable $e) {
                        $this->warn("Failed to download {$file['name']} ({$e->getMessage()}) — skipping.");
                        File::delete($local);

                        continue;
                    }

                    if ($download->failed() || File::size($local) === 0) {
                        $this->warn("Failed to download {$file['name']} (HTTP {$download->status()}) — skipping.");
                        File::delete($local);

                        continue;
                    }
                }

                $paths->push($local);
            }

            $pageToken = $response->json('nextPageToken');
        } while ($pageToken);

        return $paths;
    }
}
