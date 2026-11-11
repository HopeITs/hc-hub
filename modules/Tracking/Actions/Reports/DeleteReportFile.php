<?php

declare(strict_types=1);

namespace Modules\Tracking\Actions\Reports;

use App\Support\StoredFiles;
use Illuminate\Support\Facades\Gate;
use Modules\Tracking\Models\Report;
use Modules\Tracking\Models\ReportFile;

/**
 * Delete a report file's row and, for a real upload, its file on disk.
 */
class DeleteReportFile
{
    public function handle(Report $report, ReportFile $file): void
    {
        Gate::authorize('update', $report);

        if ($file->original_name !== null) {
            StoredFiles::forget($file->disk, $file->path, 'report file', ['report_file_id' => $file->id]);
        }

        $file->delete();
    }
}
