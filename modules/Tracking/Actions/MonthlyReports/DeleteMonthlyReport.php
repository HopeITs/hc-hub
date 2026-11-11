<?php

declare(strict_types=1);

namespace Modules\Tracking\Actions\MonthlyReports;

use App\Support\StoredFiles;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Tracking\Enums\MonthlyReportStatus;
use Modules\Tracking\Models\MonthlyReport;
use Modules\Tracking\Models\MonthlyReportFile;
use Modules\Tracking\Support\MonthlyReportGuard;

/**
 * Delete a draft that was never submitted and has no reopen request
 * (contracts §13). Its lines and mirrors go with it; its evidence files
 * leave the disk after commit. Reopen requests are never deleted.
 */
final class DeleteMonthlyReport
{
    public function handle(MonthlyReport $report, MonthlyReportStatus $expectedStatus): void
    {
        DB::transaction(function () use ($report, $expectedStatus): void {
            $locked = MonthlyReportGuard::lock($report, $expectedStatus, 'delete');

            if ($locked->reopenRequests()->exists()) {
                throw ValidationException::withMessages(['status' => __('monthly_reports.cannot_delete')]);
            }

            $stored = $locked->files()->get(['disk', 'path'])->map(fn (MonthlyReportFile $file): array => ['disk' => $file->disk, 'path' => $file->path])->all();
            $locked->delete();

            DB::afterCommit(function () use ($stored, $locked): void {
                foreach ($stored as $file) {
                    StoredFiles::forget($file['disk'], $file['path'], 'monthly report file', ['monthly_report_id' => $locked->id]);
                }
            });
        });
    }
}
